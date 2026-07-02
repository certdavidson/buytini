<?php
/**
 * SEO Core — Language Switcher widget.
 *
 * Replaces the OC `common/language` controller call from `common/header.php`
 * (via OCMOD) so that each language link is rendered with the canonical
 * target-language SEO URL pre-computed from oc_seo_url. No POST form, no JS,
 * no slug parsing — clicks are plain GET navigations.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */
class ControllerExtensionModuleOcKitSeoCoreLangSwitch extends Controller
{
    public function index(): string
    {
        $this->load->language('common/language');
        $this->load->model('localisation/language');

        $route  = (string)($this->request->get['route'] ?? 'common/home');
        $params = $this->request->get;
        unset($params['_route_'], $params['route'], $params['language_id']);

        $scf       = $this->registry->has('seo_core') ? $this->registry->get('seo_core') : null;
        $languages = [];

        foreach ($this->model_localisation_language->getLanguages() as $result) {
            if (!$result['status']) continue;
            $languages[] = [
                'name' => $result['name'],
                'code' => $result['code'],
                'url'  => $scf ? $scf->urlFor($route, $params, (int)$result['language_id']) : '',
            ];
        }

        return $this->load->view('extension/module/ockit/seo_core/lang_switch', [
            'languages' => $languages,
            'code'      => $this->session->data['language'] ?? $this->config->get('config_language'),
        ]);
    }
}
