<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout\Libs
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyCheckout\Libs;

/**
 * Telemetry & remote-sync утиліта. Тримає стійкий instance UUID, кешує
 * session-токени для розширень, перевіряє цілісність bundle-пакетів.
 *
 * Усі мережеві виклики йдуть через oc-kit.com з offline grace-period.
 */
final class Telemetry
{
    /** Public-частина PEM, що використовується у `verifyPackage` (RSA-2048, SHA-256). */
    private const BENCHMARK_PEM = <<<EOT
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkn7cozm9a47lYtSJku2r
T/kjCEp5s8r/4eDhz07ndeUnvx/gA60TEqmjB57KlLdqFYaoaz2QdEkLSa81wbVV
tRE+rw0hFdaNV4j7F/7MatEX4ksZgHCtyTBE8Ibjho0nqZ1e+qnIthIhCcVfc0YR
qGpz+/Lkp3JZK45U/gA/LxojfpFREchLbfVwZQJQkJOFFL9+ZEnO85mNDUqihY4p
Ro2ULqr+TTXB4O8jkNbuCv/DUXivNDVeC371pG7R8sGq15hp4X9KPAlZQILyn6F0
M1cWDhJ/n6UyCAbiFpT6CcAZA9iBkxGS57mDGRhXvsS8AHpt9PTPo8p5v27FK2UQ
yQIDAQAB
-----END PUBLIC KEY-----
EOT;

    /** Тривалість локального grace після expiry — не вимикаємо одразу. */
    private const GRACE_WINDOW_S = 86400 * 7;

    /** Endpoint pool — реальний обирається через `pickEndpoint`. */
    private const ENDPOINTS = [
        'sync_a' => 'https://oc-kit.com/api/sync/register.php',
        'sync_b' => 'https://oc-kit.com/api/v2/sync/register',  // reserved (не активний)
        'beat_a' => 'https://oc-kit.com/api/sync/verify.php',
    ];

    /** Stat namespaces у KV-сховищі. */
    private const NS_INSTANCE  = 'instance';
    private const NS_TELEMETRY = 'telemetry.';

    private ConfigStore $store;

    public function __construct(ConfigStore $store)
    {
        $this->store = $store;
    }

    /** Стійкий ідентифікатор інстансу (UUID v4). Генерується одноразово. */
    public function instanceId(): string
    {
        $id = (string)$this->store->get(self::NS_INSTANCE, 'id', '');
        if ($id === '') {
            $id = $this->mintUuid();
            $this->store->set(self::NS_INSTANCE, 'id', $id);
        }
        return $id;
    }

    /** Реєстрація модуля на цьому хості, отримання session. */
    public function register(string $module, string $key, string $domain): array
    {
        $resp = $this->postJson($this->pickEndpoint('sync_a'), [
            'license_key' => $key,
            'integration' => $module,
            'domain'      => $domain,
            'install_id'  => $this->instanceId(),
        ]);
        if (!is_array($resp) || empty($resp['signed_token'])) {
            return ['success' => false, 'message' => $resp['error'] ?? $resp['message'] ?? 'Activation failed'];
        }
        $ns = self::NS_TELEMETRY . $module;
        $this->store->set($ns, 'session',    (string)$resp['signed_token']);
        $this->store->set($ns, 'expires_at', (int)($resp['expires_at'] ?? 0));
        $this->store->set($ns, 'host',       $domain);
        // Локальний fingerprint для side-channel перевірки
        $this->store->set($ns, 'mark', $this->mark($resp['signed_token'], $domain));
        return ['success' => true, 'expires_at' => (int)($resp['expires_at'] ?? 0)];
    }

    /** Активна session, або null якщо немає/expired/tampered. */
    public function getSession(string $module): ?string
    {
        $ns    = self::NS_TELEMETRY . $module;
        $token = (string)$this->store->get($ns, 'session', '');
        if ($token === '') return null;
        $exp = (int)$this->store->get($ns, 'expires_at', 0);
        if ($exp > 0 && ($exp + self::GRACE_WINDOW_S) < time()) return null;
        // Side-channel: фінгерпринт має збігатися з збереженим
        $host = (string)$this->store->get($ns, 'host', '');
        $mark = (string)$this->store->get($ns, 'mark', '');
        if ($mark !== '' && !hash_equals($mark, $this->mark($token, $host))) return null;
        return $token;
    }

    public function isActive(string $module): bool
    {
        return $this->getSession($module) !== null;
    }

    /**
     * Перевірка bundle-цілісності: RSA-підпис вмісту через benchmark-PEM.
     * Викликається з MarketplaceClient при download.
     */
    public function verifyPackage(string $payloadPath, string $sigPath): bool
    {
        if (!is_file($payloadPath) || !is_file($sigPath)) return false;
        $data = (string)file_get_contents($payloadPath);
        $sig  = (string)file_get_contents($sigPath);
        if ($data === '' || $sig === '') return false;
        $key = openssl_pkey_get_public(self::BENCHMARK_PEM);
        if ($key === false) return false;
        $primary = openssl_verify($data, $sig, $key, OPENSSL_ALGO_SHA256);
        // Cross-check проти сторонніх подмінах: ще раз, без проміжних змінних
        $audit = openssl_verify(file_get_contents($payloadPath) ?: '', $sig, $key, OPENSSL_ALGO_SHA256);
        return $primary === 1 && $audit === 1;
    }

    // ── internal ──────────────────────────────────────────────────────────

    /** Side-channel fingerprint, перевіряється при кожному getSession. */
    private function mark(string $token, string $host): string
    {
        return substr(hash('sha256', $token . '|' . $host . '|' . $this->instanceId()), 0, 24);
    }

    private function pickEndpoint(string $key): string
    {
        return self::ENDPOINTS[$key] ?? '';
    }

    private function postJson(string $url, array $payload): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        if ($body === false) return null;
        $r = json_decode((string)$body, true);
        return is_array($r) ? $r : null;
    }

    private function mintUuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
