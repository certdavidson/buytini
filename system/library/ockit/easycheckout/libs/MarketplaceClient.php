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
 * Клієнт mini-marketplace oc-kit.com. Поки що — stub: повертає hardcoded
 * каталог + завантажує ZIP за прямим URL і розпаковує в integrations/<code>/.
 *
 * Майбутнє: запит до https://oc-kit.com/api/marketplace/easycheckout/list,
 * signed download URL після покупки, перевірка license-key.
 */
final class MarketplaceClient
{
    private string $integrationsDir;
    private ?Telemetry $telemetry;
    private bool $requireSignature;

    public function __construct(string $integrationsDir, ?Telemetry $telemetry = null, bool $requireSignature = false)
    {
        $this->integrationsDir  = rtrim($integrationsDir, '/');
        $this->telemetry        = $telemetry;
        $this->requireSignature = $requireSignature;
    }

    private const CATALOG_URL = 'https://oc-kit.com/api/marketplace/list.php?for=easycheckout';

    /**
     * Каталог доступних для встановлення розширень. Підтягується з oc-kit.com,
     * fallback на офлайн-список якщо мережа недоступна.
     * @return array<int,array>
     */
    public function listAvailable(): array
    {
        $ch = curl_init(self::CATALOG_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        $resp = is_string($body) ? json_decode($body, true) : null;
        if (is_array($resp) && !empty($resp['items'])) {
            return $resp['items'];
        }
        // Offline fallback (порожній каталог)
        return [];
    }

    /**
     * Завантажує ZIP з $url, розпаковує в integrations/<code>/.
     * Повертає {success, message, code?}.
     */
    public function download(string $code, string $url): array
    {
        if (!preg_match('/^[a-z0-9_]+$/', $code)) {
            return ['success' => false, 'message' => 'Invalid code'];
        }
        if (!preg_match('#^https://#i', $url)) {
            return ['success' => false, 'message' => 'Only HTTPS URLs allowed'];
        }
        $tmp = tempnam(sys_get_temp_dir(), 'okec_int_');
        $ch  = curl_init($url);
        $fp  = fopen($tmp, 'wb');
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60, CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $ok  = curl_exec($ch);
        $err = curl_error($ch);
        $code_http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        if (!$ok || $code_http >= 400) {
            @unlink($tmp);
            return ['success' => false, 'message' => 'Download failed: ' . ($err ?: 'HTTP ' . $code_http)];
        }

        // Перевірка цілісності пакета через Telemetry::verifyPackage (RSA).
        if ($this->telemetry && $this->requireSignature) {
            $sigUrl = (strpos($url, '?') === false ? $url . '?sig=1' : $url . '&sig=1');
            $sigTmp = $tmp . '.sig';
            $sf  = fopen($sigTmp, 'wb');
            $sch = curl_init($sigUrl);
            curl_setopt_array($sch, [CURLOPT_FILE => $sf, CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => true]);
            curl_exec($sch);
            curl_close($sch);
            fclose($sf);
            if (!$this->telemetry->verifyPackage($tmp, $sigTmp)) {
                @unlink($tmp); @unlink($sigTmp);
                return ['success' => false, 'message' => 'Package integrity verification failed'];
            }
            @unlink($sigTmp);
        }

        $target = $this->integrationsDir . '/' . $code;
        if (is_dir($target)) {
            @unlink($tmp);
            return ['success' => false, 'message' => 'Already installed: ' . $code];
        }
        if (!class_exists('ZipArchive')) {
            @unlink($tmp);
            return ['success' => false, 'message' => 'PHP zip extension not available'];
        }

        $zip = new \ZipArchive();
        if ($zip->open($tmp) !== true) {
            @unlink($tmp);
            return ['success' => false, 'message' => 'Invalid ZIP archive'];
        }
        if (!is_dir($target) && !mkdir($target, 0755, true)) {
            $zip->close(); @unlink($tmp);
            return ['success' => false, 'message' => 'Cannot create target dir'];
        }
        $zip->extractTo($target);
        $zip->close();
        @unlink($tmp);

        if (!is_file($target . '/manifest.json') || !is_file($target . '/Integration.php')) {
            return ['success' => false, 'message' => 'ZIP missing manifest.json or Integration.php'];
        }
        return ['success' => true, 'message' => 'Installed: ' . $code, 'code' => $code];
    }

    /**
     * Оновлення інтеграції — видаляє файли пакета, але залишає БД-таблиці
     * (вже наявні дані). Потім завантажує нову версію.
     */
    public function update(string $code, string $url): array
    {
        if (!preg_match('/^[a-z0-9_]+$/', $code)) return ['success' => false, 'message' => 'Invalid code'];
        $dir = $this->integrationsDir . '/' . $code;
        if (is_dir($dir)) $this->rrmdir($dir);
        return $this->download($code, $url);
    }

    public function uninstall(string $code): array
    {
        if (!preg_match('/^[a-z0-9_]+$/', $code)) return ['success' => false, 'message' => 'Invalid code'];
        $dir = $this->integrationsDir . '/' . $code;
        if (!is_dir($dir)) return ['success' => false, 'message' => 'Not installed: ' . $code];
        $this->rrmdir($dir);
        return ['success' => true, 'message' => 'Removed: ' . $code];
    }

    private function rrmdir(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $f) {
            is_dir($f) ? $this->rrmdir($f) : @unlink($f);
        }
        @rmdir($dir);
    }
}
