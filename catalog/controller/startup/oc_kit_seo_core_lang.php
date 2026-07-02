<?php
/**
 * SEO Core — language prefix detector & switch.
 *
 * Runs AFTER startup/startup (so settings + initial Language are loaded) and
 * BEFORE startup/seo_url (so the URL gets normalised before route decoding).
 *
 *   1. Reads URL → detects language prefix via LanguagePrefixConfig
 *   2. Strips the prefix from $_GET['_route_'] so seo_url decodes a clean path
 *   3. Switches language: session, cookie, config_language(_id) and rebuilds
 *      the Language object in the registry so all downstream controllers and
 *      templates see the URL-derived language.
 *
 * Mirrors the proven approach from the SeoLang module but keeps the
 * implementation inside the OcKit\SeoCore library boundary.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */
class ControllerStartupOcKitSeoCoreLang extends Controller
{
    public function index(): void
    {
        if (!$this->config->get('module_oc_kit_seo_core_status')) return;

        $base = DIR_SYSTEM . 'library/ockit/seo_core/';
        require_once $base . 'Autoloader.php';
        \OcKit\SeoCore\Autoloader::register($base);

        $route = (string)($this->request->get['_route_'] ?? '');
        $parts = $route !== '' ? explode('/', trim($route, '/')) : [];

        $langConfig = new \OcKit\SeoCore\Libs\LanguagePrefixConfig($this->config);
        $uri        = $_SERVER['REQUEST_URI'] ?? '/';

        [$stripped, $languageId] = $langConfig->stripPrefix($parts, $uri);

        // URL is authoritative for language. If a prefix was consumed, strip
        // it from `_route_` so seo_url decodes a clean path. If no prefix
        // matched, the URL implies the default language — switch to it.
        if (count($stripped) !== count($parts)) {
            $newRoute = implode('/', $stripped);
            $this->request->get['_route_'] = $newRoute;
            $_GET['_route_']               = $newRoute;
        }
        if (!$languageId) return;

        // Skip if config already matches the URL-implied language.
        if ((int)$this->config->get('config_language_id') === (int)$languageId) return;

        $this->load->model('localisation/language');
        $code = '';
        foreach ($this->model_localisation_language->getLanguages() as $lang) {
            if ((int)$lang['language_id'] === (int)$languageId) {
                $code = (string)$lang['code'];
                break;
            }
        }
        if ($code === '') return;
        $this->switchLanguage((int)$languageId, $code);
    }

    /**
     * Atomic language switch: session + cookie + config + rebuilt Language object.
     */
    private function switchLanguage(int $languageId, string $code): void
    {
        $this->session->data['language'] = $code;

        if (($this->request->cookie['language'] ?? null) !== $code) {
            setcookie(
                'language',
                $code,
                time() + 60 * 60 * 24 * 30,
                '/',
                $this->request->server['HTTP_HOST'] ?? ''
            );
        }

        $this->config->set('config_language',    $code);
        $this->config->set('config_language_id', $languageId);

        $language = new \Language($code);
        $language->load($code);
        $this->registry->set('language', $language);
    }
}
