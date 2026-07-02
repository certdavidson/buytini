<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 *
 * Catalog event handler — реєструється OC event-системою через
 * ModelSettingEvent::addEvent('oc_kit_easycheckout_redirect',
 *   'catalog/controller/checkout/checkout/before',
 *   'extension/easycheckout/redirect/fromStandardCheckout').
 */

class ControllerExtensionEasycheckoutRedirect extends Controller
{
    /**
     * Викликається ДО запуску стандартного OC checkout-контролера.
     * Якщо модуль активний і опція "replace_checkout_links" увімкнена —
     * редіректить на наш /easycheckout зі збереженням query string.
     */
    public function fromStandardCheckout(&$route, &$args): void
    {
        if (!$this->config->get('module_oc_kit_easycheckout_status'))         return;
        if (!$this->config->get('module_oc_kit_easycheckout_replace_checkout_links')) return;

        // Уникаємо рекурсії, якщо щось wired wrong.
        if (strpos((string)$route, 'easycheckout/') === 0) return;

        // Зберігаємо query string (наприклад ?group=b2b).
        // ВАЖЛИВО: прибираємо і `route`, і SEO-параметр `_route_` — інакше при
        // SEO-URL (/checkout → ?_route_=checkout) він просочився б у редірект,
        // OC резолвив би `_route_` назад у checkout/checkout → нескінченний цикл.
        $query = '';
        if (!empty($this->request->server['QUERY_STRING'])) {
            $qs = preg_replace('~(^|&)(route|_route_)=[^&]*~', '', (string)$this->request->server['QUERY_STRING']);
            $qs = ltrim($qs, '&');
            if ($qs !== '') $query = $qs;
        }

        $this->response->redirect($this->url->link('checkout/easycheckout', $query, true));
    }
}
