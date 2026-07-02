<?php
/**
 * Easy Login — OpenCart 3.x Module
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyLogin\Libs;

use OcKit\EasyLogin\Dto\ProviderProfile;
use OcKit\EasyLogin\Dto\AuthResult;

/**
 * Resolves a ProviderProfile to a customer (find / link / create) and
 * logs the customer into the OpenCart session.
 */
class AuthService
{
    private $registry;
    private IdentityRepository $identities;
    private CustomerLinker $linker;
    private LoginLogger $logger;

    public function __construct(
        $registry,
        IdentityRepository $identities,
        CustomerLinker $linker,
        LoginLogger $logger
    ) {
        $this->registry   = $registry;
        $this->identities = $identities;
        $this->linker     = $linker;
        $this->logger     = $logger;
    }

    public function authenticate(ProviderProfile $profile): AuthResult
    {
        $existing = $this->identities->findByProviderUser($profile->provider, $profile->providerUserId);

        if ($existing) {
            $this->identities->touchLastLogin((int)$existing['identity_id']);
            $customer = $this->loadCustomer((int)$existing['customer_id']);
            if (!$customer) {
                $this->logger->log($profile->provider, 'failed', [
                    'email' => $profile->email,
                    'error' => 'Linked customer missing',
                ]);
                return new AuthResult(['success' => false, 'message' => 'Linked customer missing']);
            }
            return $this->loginAndReturn($customer, $profile, 'login');
        }

        // No existing identity — try to find customer by email
        if ($profile->email && $profile->emailVerified) {
            $customer = $this->linker->findByEmail($profile->email);
            if ($customer) {
                $customerId = (int)$customer['customer_id'];
                $this->identities->create($customerId, $profile);
                $this->logger->log($profile->provider, 'linked', [
                    'customer_id' => $customerId,
                    'email'       => $profile->email,
                ]);
                return $this->loginAndReturn($customer, $profile, 'linked');
            }
        }

        if ($profile->email && !$profile->emailVerified) {
            $customer = $this->linker->findByEmail($profile->email);
            if ($customer) {
                // Confirm flow: identity not created; UI will handle confirmation
                return new AuthResult([
                    'success'    => false,
                    'customer_id'=> (int)$customer['customer_id'],
                    'email'      => $profile->email,
                    'action'     => 'needs_confirmation',
                    'message'    => 'Confirm linking to existing account',
                ]);
            }
        }

        // No customer found — create new. Wrapped in a per-email named lock so
        // two concurrent OAuth callbacks for the same email cannot both
        // INSERT a customer row (OC does not enforce UNIQUE on customer.email).
        $lockKey = $this->emailLockKey($profile->email ?: $profile->providerUserId);
        $db = $this->registry->get('db');
        $db->query("SELECT GET_LOCK('" . $db->escape($lockKey) . "', 5) AS got");

        try {
            // Re-check inside the lock — another request may have already created the row.
            if ($profile->email) {
                $existingByEmail = $this->linker->findByEmail($profile->email);
                if ($existingByEmail) {
                    $customerId = (int)$existingByEmail['customer_id'];
                    $this->identities->create($customerId, $profile);
                    $this->logger->log($profile->provider, 'linked', [
                        'customer_id' => $customerId,
                        'email'       => $profile->email,
                    ]);
                    $db->query("SELECT RELEASE_LOCK('" . $db->escape($lockKey) . "')");
                    return $this->loginAndReturn($existingByEmail, $profile, 'linked');
                }
            }

            $customerId = $this->linker->createFromProfile($profile);
            $this->identities->create($customerId, $profile);
        } finally {
            $db->query("SELECT RELEASE_LOCK('" . $db->escape($lockKey) . "')");
        }

        $customer = $this->loadCustomer($customerId);
        $this->logger->log($profile->provider, 'registered', [
            'customer_id' => $customerId,
            'email'       => $profile->email,
        ]);
        return $this->loginAndReturn($customer, $profile, 'registered');
    }

    /**
     * 64-char-safe MySQL named-lock key. Prefixed so it cannot collide with
     * locks taken by other oc-kit modules.
     */
    private function emailLockKey(string $key): string
    {
        return 'el_reg_' . substr(hash('sha256', strtolower(trim($key))), 0, 40);
    }

    private function loginAndReturn(array $customer, ProviderProfile $profile, string $action): AuthResult
    {
        $email   = (string)$customer['email'];
        $loginOk = $this->registry->get('customer')->login($email, '', true);
        if (!$loginOk) {
            $this->logger->log($profile->provider, 'failed', [
                'customer_id' => (int)$customer['customer_id'],
                'email'       => $email,
                'error'       => 'OC customer login override failed (status=0?)',
            ]);
            return new AuthResult([
                'success' => false,
                'email'   => $email,
                'message' => 'Account is inactive',
            ]);
        }

        // OC 3 Customer::login() does not rotate the session id, so an attacker who
        // pre-fixates a victim's session (e.g. via XSS or shared browser) keeps the
        // same id post-login. Rotate the PHP session id while preserving session
        // data so the freshly-authenticated session is unguessable.
        $sess = $this->registry->get('session');
        if ($sess && session_status() === PHP_SESSION_ACTIVE) {
            $data = $sess->data;
            session_regenerate_id(true);
            $sess->data = $data;
        }

        $this->logger->log($profile->provider, 'success', [
            'customer_id' => (int)$customer['customer_id'],
            'email'       => $email,
        ]);

        return new AuthResult([
            'success'    => true,
            'customer_id'=> (int)$customer['customer_id'],
            'email'      => $email,
            'action'     => $action,
        ]);
    }

    private function loadCustomer(int $customerId): ?array
    {
        $db  = $this->registry->get('db');
        $row = $db->query(
            "SELECT * FROM `" . DB_PREFIX . "customer`
              WHERE `customer_id` = '" . (int)$customerId . "' AND `status` = '1' LIMIT 1"
        )->row;
        return $row ?: null;
    }
}
