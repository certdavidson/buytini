<?php
/**
 * Easy Login — OpenCart 3.x Module
 *
 * @package   OcKit\EasyLogin
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

namespace OcKit\EasyLogin;

require_once __DIR__ . '/exceptions/AuthException.php';
require_once __DIR__ . '/exceptions/ProviderException.php';
require_once __DIR__ . '/dto/ProviderProfile.php';
require_once __DIR__ . '/dto/AuthResult.php';
require_once __DIR__ . '/libs/SchemaInstaller.php';
require_once __DIR__ . '/libs/LoginLogger.php';
require_once __DIR__ . '/libs/HttpClient.php';
require_once __DIR__ . '/libs/JwtUtil.php';
require_once __DIR__ . '/libs/IdentityRepository.php';
require_once __DIR__ . '/libs/OtpRepository.php';
require_once __DIR__ . '/libs/RateLimiter.php';
require_once __DIR__ . '/libs/CustomerLinker.php';
require_once __DIR__ . '/libs/AuthService.php';
require_once __DIR__ . '/libs/StoreContext.php';
require_once __DIR__ . '/libs/SignedStateCookie.php';
require_once __DIR__ . '/libs/providers/AbstractAuthProvider.php';
require_once __DIR__ . '/libs/providers/GoogleProvider.php';
require_once __DIR__ . '/libs/providers/TelegramProvider.php';
require_once __DIR__ . '/libs/providers/AppleProvider.php';
require_once __DIR__ . '/libs/providers/FacebookProvider.php';
require_once __DIR__ . '/libs/providers/EmailMagicProvider.php';
require_once __DIR__ . '/libs/providers/SmsOtpProvider.php';

use OcKit\EasyLogin\Libs\SchemaInstaller;
use OcKit\EasyLogin\Libs\LoginLogger;
use OcKit\EasyLogin\Libs\HttpClient;
use OcKit\EasyLogin\Libs\IdentityRepository;
use OcKit\EasyLogin\Libs\OtpRepository;
use OcKit\EasyLogin\Libs\RateLimiter;
use OcKit\EasyLogin\Libs\CustomerLinker;
use OcKit\EasyLogin\Libs\AuthService;
use OcKit\EasyLogin\Libs\StoreContext;
use OcKit\EasyLogin\Libs\Providers\GoogleProvider;
use OcKit\EasyLogin\Libs\Providers\TelegramProvider;
use OcKit\EasyLogin\Libs\Providers\AppleProvider;
use OcKit\EasyLogin\Libs\Providers\FacebookProvider;
use OcKit\EasyLogin\Libs\Providers\EmailMagicProvider;
use OcKit\EasyLogin\Libs\Providers\SmsOtpProvider;
use OcKit\EasyLogin\Libs\Providers\AbstractAuthProvider;

class EasyLogin
{
    private $registry;
    private $db;
    private $config;
    private ?SchemaInstaller $schema = null;
    private ?LoginLogger $logger = null;
    private ?HttpClient $http = null;
    private ?IdentityRepository $identities = null;
    private ?OtpRepository $otp = null;
    private ?RateLimiter $rateLimiter = null;
    private ?CustomerLinker $linker = null;
    private ?AuthService $authService = null;
    private ?StoreContext $storeContext = null;
    private bool $licensed = false;
    private array $providers = [];

    public function __construct($registry)
    {
        $this->registry = $registry;
        $this->db       = $registry->get('db');
        $this->config   = $registry->get('config');

        $this->storeContext = new StoreContext($this->db, $this->config);
        $this->licensed     = $this->storeContext->isActive();

        // Admin context: redirect to license page from inside the library
        if (!$this->licensed && php_sapi_name() !== 'cli') {
            $token = $registry->get('session')->data['user_token'] ?? '';
            if ($token !== '') {
                $registry->get('response')->redirect(
                    $registry->get('url')->link(
                        'extension/module/oc_kit_easy_login/license',
                        'user_token=' . $token,
                        true
                    )
                );
                exit;
            }
        }
    }

    public function isLicensed(): bool { return $this->licensed; }

    public function install(): void
    {
        $this->getSchema()->createTables();
    }

    public function uninstall(): void
    {
        $this->getSchema()->dropTables();
    }

    // ─── Lazy getters ─────────────────────────────────────────────────────────

    public function getSchema(): SchemaInstaller
    {
        if ($this->schema === null) {
            $this->schema = new SchemaInstaller($this->db);
        }
        return $this->schema;
    }

    public function getLogger(): LoginLogger
    {
        if ($this->logger === null) {
            $this->logger = new LoginLogger($this->db, $this->config);
        }
        return $this->logger;
    }

    public function getHttp(): HttpClient
    {
        if ($this->http === null) {
            $this->http = new HttpClient();
        }
        return $this->http;
    }

    public function getIdentities(): IdentityRepository
    {
        if ($this->identities === null) {
            $this->identities = new IdentityRepository($this->db);
        }
        return $this->identities;
    }

    public function getOtp(): OtpRepository
    {
        if ($this->otp === null) {
            $this->otp = new OtpRepository($this->db, $this->config);
        }
        return $this->otp;
    }

    public function getRateLimiter(): RateLimiter
    {
        if ($this->rateLimiter === null) {
            $this->rateLimiter = new RateLimiter($this->db, $this->config);
        }
        return $this->rateLimiter;
    }

    public function getCustomerLinker(): CustomerLinker
    {
        if ($this->linker === null) {
            $this->linker = new CustomerLinker($this->db, $this->config);
        }
        return $this->linker;
    }

    public function getAuthService(): AuthService
    {
        if ($this->authService === null) {
            $this->authService = new AuthService(
                $this->registry,
                $this->getIdentities(),
                $this->getCustomerLinker(),
                $this->getLogger()
            );
        }
        return $this->authService;
    }

    public function getProvider(string $name): AbstractAuthProvider
    {
        if (!isset($this->providers[$name])) {
            switch ($name) {
                case 'google':
                    $this->providers[$name] = new GoogleProvider($this->config, $this->getHttp());
                    break;
                case 'telegram':
                    $this->providers[$name] = new TelegramProvider($this->config);
                    break;
                case 'apple':
                    $this->providers[$name] = new AppleProvider($this->config, $this->getHttp());
                    break;
                case 'facebook':
                    $this->providers[$name] = new FacebookProvider($this->config, $this->getHttp());
                    break;
                case 'email_magic':
                    $this->providers[$name] = new EmailMagicProvider($this->config, $this->getOtp(), $this->registry);
                    break;
                case 'sms_otp':
                    $this->providers[$name] = new SmsOtpProvider($this->config, $this->getOtp());
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown provider: ' . $name);
            }
        }
        return $this->providers[$name];
    }

    /**
     * Build the catalog callback URL for a provider action.
     */
    public function buildCallbackUrl(string $provider, string $action): string
    {
        $base = defined('HTTP_CATALOG') ? HTTP_CATALOG : (defined('HTTPS_SERVER') ? HTTPS_SERVER : HTTP_SERVER);
        $base = rtrim(preg_replace('#/admin/?$#', '/', $base), '/');
        return $base . '/index.php?route=extension/module/oc_kit_easy_login/' . $action;
    }

    // ─── Static license helpers (lightweight — no full init) ─────────────────

    public static function guardAdmin($registry): void
    {
        $ctx = new StoreContext($registry->get('db'), $registry->get('config'));
        if ($ctx->isActive()) return;
        $token = $registry->get('session')->data['user_token'] ?? '';
        $registry->get('response')->redirect(
            $registry->get('url')->link(
                'extension/module/oc_kit_easy_login/license',
                'user_token=' . $token,
                true
            )
        );
        exit;
    }

    public static function getLicenseStatus($registry): array
    {
        $ctx = new StoreContext($registry->get('db'), $registry->get('config'));
        return $ctx->getInfo();
    }

    public static function activateLicenseKey($registry, string $key): array
    {
        $ctx = new StoreContext($registry->get('db'), $registry->get('config'));
        return $ctx->activate($key);
    }
}
