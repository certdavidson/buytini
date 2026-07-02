<?php
/**
 * EasyCheckout — Catalog public API
 *
 * @package   OcKit\EasyCheckout
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

require_once DIR_SYSTEM . 'library/ockit/easycheckout/EasyCheckout.php';

use OcKit\EasyCheckout\EasyCheckout;
use OcKit\EasyCheckout\Libs\IntegrationsRegistry;

class ControllerCheckoutEasycheckoutapi extends Controller
{
    /**
     * GET checkout/easycheckoutapi/searchLocations
     *   ?integration=nova_poshta&type=city&q=Київ
     *   ?integration=nova_poshta&type=warehouse&q=&parent=<city_ref>
     *   ?integration=ukrposhta&type=district&parent=<region_id>
     */
    public function searchLocations(): void
    {
        $code   = (string)($this->request->get['integration'] ?? '');
        $type   = (string)($this->request->get['type'] ?? '');
        $q      = (string)($this->request->get['q'] ?? '');
        $page   = max(1, (int)($this->request->get['page']  ?? 1));
        $limit  = min(100, max(10, (int)($this->request->get['limit'] ?? 50)));
        $ctx    = ['page' => $page, 'limit' => $limit];
        foreach (['parent', 'city_ref', 'region_id', 'district_id', 'city_id'] as $k) {
            if (isset($this->request->get[$k])) $ctx[$k] = (string)$this->request->get[$k];
        }

        $ec = new EasyCheckout($this->registry);
        $ec->setStore((int)$this->config->get('config_store_id'));
        $reg = new IntegrationsRegistry($ec->getConfigStore());
        $i = $reg->get($code);

        $items = ($i && $i->isEnabled())
            ? $i->searchLocations($type, $q, $ctx, $this->db)
            : [];

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success' => $i !== null,
            'page'    => $page,
            'limit'   => $limit,
            'has_more'=> count($items) >= $limit,
            'items'   => $items,
        ], JSON_UNESCAPED_UNICODE));
    }
}
