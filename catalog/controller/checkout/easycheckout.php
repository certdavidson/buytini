<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * @package   OcKit\EasyCheckout
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @license   Commercial license — see LICENSE.txt
 * @link      https://oc-kit.com
 */

require_once DIR_SYSTEM . 'library/ockit/easycheckout/EasyCheckout.php';

use OcKit\EasyCheckout\EasyCheckout;

class ControllerCheckoutEasycheckout extends Controller
{
    public function index(): void
    {
        // Preview-mode: для admin (з валідним user_token у URL) — рендер layout-у
        // навіть без кошика, з mock-даними. Banner вгорі попереджає що це preview.
        $isPreview = !empty($this->request->get['preview'])
                     && $this->isAdminPreviewToken((string)$this->request->get['preview']);

        // 1. Якщо модуль вимкнений — на стандартний чекаут (preview оминає це).
        if (!$isPreview && !$this->config->get('module_oc_kit_easycheckout_status')) {
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
            return;
        }

        // 2. Recovery-link: ?recover={token} — підтягуємо abandoned-snapshot
        if (!empty($this->request->get['recover'])) {
            $this->applyRecoveryToken((string)$this->request->get['recover']);
        }

        // 3. Порожній кошик — на сторінку кошика (preview оминає).
        if (!$isPreview && !$this->cart->hasProducts() && empty($this->session->data['vouchers'])) {
            $this->response->redirect($this->url->link('checkout/cart'));
            return;
        }

        $this->load->language('easycheckout/checkout');
        // Кешуємо одразу: total-екстеншени (total/shipping тощо) у buildTotals()
        // вантажать свої lang-файли й перезаписують спільний ключ heading_title.
        $headingTitle = (string)$this->language->get('heading_title');
        $this->document->setTitle($headingTitle);

        // 3. Ініціалізуємо EasyCheckout, резолвимо групу.
        $ec = new EasyCheckout($this->registry);
        $ec->setStore((int)$this->config->get('config_store_id'));
        $ec->setGroup($this->resolveGroupId($ec));
        $ec->setPage('checkout');

        // 4. Завантажуємо layout цієї групи + готуємо до рендеру (дефолти + i18n).
        $primaryLangCode = (string)$this->config->get('config_language');
        if ($isPreview && !empty($this->session->data['okec_preview_layout'])) {
            // Preview-snapshot з admin Pages-state (можливо не збережений у DB)
            $layout = $ec->getPageLayoutRepository()->normalize($this->session->data['okec_preview_layout']);
            unset($this->session->data['okec_preview_layout']); // одноразовий
        } else {
            $layout = $ec->getPageLayoutRepository()->get('checkout');
        }
        $layout    = $this->applyBlockDefaults($layout, $primaryLangCode);
        $languages = $this->loadLanguages();

        // Розраховуємо титули кроків у поточній мові (для twig без |default).
        foreach ($layout['steps'] as &$step) {
            $step['title_resolved'] = (string)($step['title'][$primaryLangCode] ?? '');
        }
        unset($step);

        // 5. Збираємо мапу полів (custom + native) для рендеру блоків.
        $fieldsMap    = $this->buildFieldsMap($ec);
        $cartProducts = $isPreview ? $this->mockPreviewCartProducts() : $this->buildCartProducts();
        $totals       = $isPreview ? $this->mockPreviewTotals()       : $this->buildTotals();
        $countries    = $this->loadCountries();
        $prefill      = $this->buildPrefill();
        $addressBook  = $this->loadAddressBook();
        $groupsList   = $this->loadGroupsForSwitcher($ec);

        // 6. Document assets для frontend (vanilla IIFE + minimal CSS).
        $this->document->addStyle('catalog/view/javascript/ockit/easycheckout/easycheckout.css');
        $this->document->addScript('catalog/view/javascript/ockit/easycheckout/vendor/imask.min.js');
        $this->document->addScript('catalog/view/javascript/ockit/easycheckout/easycheckout.js');
        $this->document->addScript('catalog/view/javascript/ockit/easycheckout/integrations.js');

        $data = [
            'heading_title'    => $headingTitle,
            'text_dev_stub'    => $this->language->get('text_dev_stub'),
            'fallback_url'     => $this->url->link('checkout/checkout', '', true),
            'breadcrumbs'      => [
                ['text' => $this->language->get('text_home') ?: 'Home', 'href' => $this->url->link('common/home')],
                ['text' => $headingTitle,                               'href' => $this->url->link('checkout/easycheckout', '', true)],
            ],
            // Layout та context
            'layout'            => $layout,
            'group_id'          => $ec->getGroupId(),
            'fields_map'        => $fieldsMap,
            'cart_products'     => $cartProducts,
            'totals'            => $totals,
            'countries'         => $countries,
            'default_country_id'=> $prefill['country_id'] ?: (int)($this->config->get('module_oc_kit_easycheckout_default_country_id') ?: $this->config->get('config_country_id')),
            'prefill'           => $prefill,
            'address_book'      => $addressBook,
            'groups_list'       => $groupsList,
            'active_group_id'   => $ec->getGroupId(),
            'languages'         => $languages,
            'primary_lang_code' => $primaryLangCode,
            'logged'            => $this->customer->isLogged(),
            'is_preview'        => $isPreview,
            'template_dir'      => (string)($this->config->get('template_directory') ?: 'default/template/'),
            'okec_urls'         => [
                'load_methods' => html_entity_decode($this->url->link('checkout/easycheckout/loadMethods', '', true)),
                'load_zones'   => html_entity_decode($this->url->link('checkout/easycheckout/loadZones',   '', true)),
                'confirm'      => html_entity_decode($this->url->link('checkout/easycheckout/confirm',     '', true)),
                'cart_edit'    => html_entity_decode($this->url->link('checkout/easycheckout/cartEdit',   '', true)),
                'cart_remove'  => html_entity_decode($this->url->link('checkout/easycheckout/cartRemove', '', true)),
                'cart_state'   => html_entity_decode($this->url->link('checkout/easycheckout/cartState', '', true)),
                'save_abandoned' => html_entity_decode($this->url->link('checkout/easycheckout/saveAbandoned', '', true)),
                'reload_blocks'  => html_entity_decode($this->url->link('checkout/easycheckout/reloadBlocks',  '', true)),
                'apply_coupon' => html_entity_decode($this->url->link('extension/total/coupon/coupon',   '', true)),
                'apply_voucher'=> html_entity_decode($this->url->link('extension/total/voucher/voucher', '', true)),
                'apply_reward' => html_entity_decode($this->url->link('extension/total/reward/reward',   '', true)),
                'check_email'    => html_entity_decode($this->url->link('checkout/easycheckout/checkExistingEmail', '', true)),
                'login_customer' => html_entity_decode($this->url->link('checkout/easycheckout/loginCustomer',     '', true)),
                'upload_file'    => html_entity_decode($this->url->link('checkout/easycheckout/uploadFile',        '', true)),
                'api_search'     => html_entity_decode($this->url->link('checkout/easycheckoutapi/searchLocations', '', true)),
                'forgotten'      => html_entity_decode($this->url->link('account/forgotten', '', true)),
            ],
            'okec_text' => [
                'submit_failed'   => $this->language->get('text_submit_failed') ?: 'Submit failed',
                'cart_failed'     => $this->language->get('text_cart_failed')   ?: 'Cart update failed',
                'no_shipping'     => $this->language->get('text_no_shipping')   ?: 'No shipping methods available',
                'no_payment'      => $this->language->get('text_no_payment')    ?: 'No payment methods available',
                'select_method'   => $this->language->get('text_select_method') ?: 'Please select a method',
                'account_exists'  => $this->language->get('text_account_exists')?: 'Account already exists',
                'field_required'  => $this->language->get('error_field_required')?: 'Required',
                'back_to_checkout'=> $this->language->get('text_back_to_checkout')?: 'Back to checkout',
            ],
            'text_login'          => $this->language->get('text_login')          ?: 'Login',
            'text_cart_empty'     => $this->language->get('text_cart_empty')     ?: 'Cart is empty.',
            'text_cart_remove'    => $this->language->get('text_cart_remove')    ?: 'Remove',
            'text_place_order'    => $this->language->get('text_place_order')    ?: 'Place order',
            'text_choose_address' => $this->language->get('text_choose_address') ?: 'Choose saved address:',
            'text_new_address'    => $this->language->get('text_new_address')    ?: '— New address —',
            'text_same_as_shipping' => $this->language->get('text_same_as_shipping') ?: 'Billing same as shipping',
            'text_account_exists' => $this->language->get('text_account_exists') ?: 'Account already exists',
            'text_login_title'    => $this->language->get('text_login_title')    ?: 'Customer login',
            'text_login_email'    => $this->language->get('text_login_email')    ?: 'Email',
            'text_login_password' => $this->language->get('text_login_password') ?: 'Password',
            'text_login_submit'   => $this->language->get('text_login_submit')   ?: 'Login',
            'text_login_forgot'   => $this->language->get('text_login_forgot')   ?: 'Forgot password?',
            'text_close'          => $this->language->get('text_close')          ?: 'Close',
            'text_secure_checkout'=> $this->language->get('text_secure_checkout')?: 'Your data is protected with SSL encryption',
            'text_agreement_default' => $this->language->get('text_agreement_default') ?: 'I agree to the terms and conditions',
            'text_checkout_subtitle'   => $this->language->get('text_checkout_subtitle')   ?: '',
            'text_coupon_placeholder'  => $this->language->get('text_coupon_placeholder')  ?: 'Coupon code',
            'text_voucher_placeholder' => $this->language->get('text_voucher_placeholder') ?: 'Gift voucher',
            'text_reward_placeholder'  => $this->language->get('text_reward_placeholder')  ?: 'Reward points',
            'button_apply'             => $this->language->get('button_apply')             ?: 'Apply',
            // Заголовки контентних блоків (картки). Решта типів — без заголовка.
            'block_titles'  => $this->buildBlockTitles(),
            // OC slots
            'header'        => $this->load->controller('common/header'),
            'footer'        => $this->load->controller('common/footer'),
            'column_left'   => $this->load->controller('common/column_left'),
            'column_right'  => $this->load->controller('common/column_right'),
            'content_top'   => $this->load->controller('common/content_top'),
            'content_bottom'=> $this->load->controller('common/content_bottom'),
        ];

        $this->response->setOutput($this->load->view('easycheckout/checkout', $data));
    }

    /**
     * Розв'язує активну групу:
     *   1) URL-параметр ?group=slug
     *   2) збережено в сесії
     *   3) default-група
     */
    private function resolveGroupId(EasyCheckout $ec): int
    {
        $repo = $ec->getGroupsRepository();
        $repo->ensureDefault();

        if (!empty($this->request->get['group'])) {
            $slug = preg_replace('~[^a-z0-9_-]~i', '', (string)$this->request->get['group']);
            if ($slug !== '') {
                $g = $repo->getBySlug($slug);
                if ($g) {
                    $this->session->data['okec_group_id'] = (int)$g['group_id'];
                    return (int)$g['group_id'];
                }
            }
        }
        if (!empty($this->session->data['okec_group_id'])) {
            $g = $repo->get((int)$this->session->data['okec_group_id']);
            if ($g) return (int)$g['group_id'];
        }
        $def = $repo->getDefault();
        return $def ? (int)$def['group_id'] : 0;
    }

    /**
     * Підставляє дефолти у block.settings для кожного блоку (mirror JS _initBlockSettings)
     * + резолвить i18n-поля (text, submit_text) у скалярний рядок поточної мови.
     * Twig потім просто виводить значення без |default(...) і без [primary_lang_code] індексів.
     */
    private function applyBlockDefaults(array $layout, string $langCode): array
    {
        foreach ($layout['steps'] as &$step) {
            foreach ($step['rows'] as &$row) {
                foreach ($row['cells'] as &$cell) {
                    foreach ($cell['blocks'] as &$block) {
                        $block['settings'] = $this->blockDefaults($block['type'], $block['settings'] ?? [], $langCode);
                    }
                    unset($block);
                }
                unset($cell);
            }
            unset($row);
        }
        unset($step);
        return $layout;
    }

    /**
     * Дефолти + i18n-резолв для одного блоку.
     */
    private function blockDefaults(string $type, array $settings, string $langCode): array
    {
        // Common: visibility
        $base = [
            'hide_for_guests'    => false,
            'hide_for_logged_in' => false,
            'hide_on_desktop'    => false,
            'hide_on_tablet'     => false,
            'hide_on_mobile'     => false,
        ];
        $settings = array_merge($base, $settings);

        // Resolve i18n field — повертає скалярний рядок поточної мови.
        $resolveI18n = function ($value) use ($langCode) {
            if (is_array($value)) {
                return (string)($value[$langCode] ?? '');
            }
            return (string)($value ?? '');
        };

        // Type-specific defaults + i18n resolution
        switch ($type) {
            case 'comment':
            case 'help':
            case 'custom_html':
                $settings['text'] = $resolveI18n($settings['text'] ?? '');
                break;

            case 'agreement':
                $settings['text']     = $resolveI18n($settings['text'] ?? '');
                $settings['required'] = !isset($settings['required']) || (bool)$settings['required'];
                break;

            case 'customer':
                $settings['registration_mode'] = (string)($settings['registration_mode'] ?? 'optional');
                $settings['show_login_link']   = !isset($settings['show_login_link']) || (bool)$settings['show_login_link'];
                $settings['fields']            = is_array($settings['fields'] ?? null) ? $settings['fields'] : [];
                break;

            case 'cart':
                foreach (['show_image' => true, 'show_model' => false,
                         'show_quantity_controls' => true, 'show_remove_btn' => true,
                         'show_subtotal' => true] as $k => $def) {
                    $settings[$k] = !isset($settings[$k]) ? $def : (bool)$settings[$k];
                }
                break;

            case 'summary':
                foreach (['show_subtotal' => true, 'show_taxes' => true,
                         'show_coupon_input' => true, 'show_voucher_input' => false,
                         'show_reward_input' => false] as $k => $def) {
                    $settings[$k] = !isset($settings[$k]) ? $def : (bool)$settings[$k];
                }
                break;

            case 'shipping':
            case 'payment':
                $settings['display_mode']       = (string)($settings['display_mode'] ?? 'radio');
                $settings['auto_select_first']  = !isset($settings['auto_select_first']) || (bool)$settings['auto_select_first'];
                $settings['show_description']   = !isset($settings['show_description']) || (bool)$settings['show_description'];
                $settings['fields']             = is_array($settings['fields'] ?? null) ? $settings['fields'] : [];
                break;

            case 'buttons':
                $settings['submit_text']            = $resolveI18n($settings['submit_text'] ?? '');
                $settings['back_text']              = $resolveI18n($settings['back_text']   ?? '');
                $settings['show_agreement_inline'] = (bool)($settings['show_agreement_inline'] ?? false);
                $settings['sticky_on_mobile']      = (bool)($settings['sticky_on_mobile']      ?? false);
                break;

            case 'payment_address':
                $settings['show_company'] = !isset($settings['show_company']) || (bool)$settings['show_company'];
                $settings['fields']       = is_array($settings['fields'] ?? null) ? $settings['fields'] : [];
                // Default ON для payment_address: «Same as shipping» — типова UX-патерна
                $settings['same_as_shipping_toggle'] = !isset($settings['same_as_shipping_toggle']) || (bool)$settings['same_as_shipping_toggle'];
                break;
            case 'shipping_address':
                $settings['show_company'] = !isset($settings['show_company']) || (bool)$settings['show_company'];
                $settings['fields']       = is_array($settings['fields'] ?? null) ? $settings['fields'] : [];
                break;
        }

        return $settings;
    }

    /**
     * Список продуктів кошика з resolved-полями для рендеру блоку cart.
     */
    private function buildCartProducts(): array
    {
        $this->load->model('tool/image');
        $products = [];
        foreach ($this->cart->getProducts() as $p) {
            $thumb = '';
            if (!empty($p['image'])) {
                $thumb = $this->model_tool_image->resize($p['image'], 80, 80);
            }

            $options = [];
            foreach (($p['option'] ?? []) as $option) {
                if ($option['type'] !== 'file') {
                    $val = (string)$option['value'];
                    if (mb_strlen($val) > 20) $val = mb_substr($val, 0, 20) . '...';
                } else {
                    $val = $option['value'];
                }
                $options[] = ['name' => $option['name'], 'value' => $val];
            }

            $products[] = [
                'cart_id'      => $p['cart_id'],
                'product_id'   => $p['product_id'],
                'name'         => $p['name'],
                'model'        => $p['model'],
                'thumb'        => $thumb,
                'options'      => $options,
                'quantity'     => $p['quantity'],
                'minimum'      => $p['minimum'] ?? 1,
                'price'        => $this->currency->format($this->tax->calculate(
                                    $p['price'], $p['tax_class_id'], $this->config->get('config_tax')
                                ), $this->session->data['currency'] ?? ''),
                'total'        => $this->currency->format($this->tax->calculate(
                                    $p['price'], $p['tax_class_id'], $this->config->get('config_tax')
                                ) * $p['quantity'], $this->session->data['currency'] ?? ''),
                'href'         => $this->url->link('product/product', 'product_id=' . $p['product_id']),
                'remove_url'   => $this->url->link('checkout/cart/remove', 'key=' . $p['cart_id']),
            ];
        }
        return $products;
    }

    /**
     * Зведення totals (subtotal, taxes, total) для блоку summary.
     */
    private function buildTotals(): array
    {
        $totalData = $this->runTotalExtensions();
        $totals    = $totalData['totals'];

        $out = [];
        foreach ($totals as $row) {
            $out[] = [
                'code'  => $row['code'] ?? '',
                'title' => $row['title'] ?? '',
                'text'  => $this->currency->format($row['value'] ?? 0, $this->session->data['currency'] ?? ''),
                'value' => $row['value'] ?? 0,
            ];
        }
        return $out;
    }

    private function loadLanguages(): array
    {
        $this->load->model('localisation/language');
        $rows = $this->model_localisation_language->getLanguages();
        return array_values($rows);
    }

    /**
     * Збирає prefill-мапу для полів: { firstname, lastname, email, telephone,
     * country_id, zone_id, ... }. Джерела:
     *   1) Залогінений customer + його default address
     *   2) session.data['payment_address'] / ['guest']
     *   3) POST окремих полів
     */
    private function buildPrefill(): array
    {
        $p = [
            'email' => '', 'firstname' => '', 'lastname' => '', 'telephone' => '', 'company' => '',
            'address_1' => '', 'address_2' => '', 'city' => '', 'postcode' => '',
            'country_id' => 0, 'zone_id' => 0,
        ];

        if ($this->customer->isLogged()) {
            $p['firstname'] = (string)$this->customer->getFirstName();
            $p['lastname']  = (string)$this->customer->getLastName();
            $p['email']     = (string)$this->customer->getEmail();
            $p['telephone'] = (string)$this->customer->getTelephone();

            $this->load->model('account/address');
            $addressId = (int)$this->customer->getAddressId();
            if ($addressId) {
                $addr = $this->model_account_address->getAddress($addressId);
                if ($addr) {
                    $p['company']    = (string)($addr['company']     ?? '');
                    $p['address_1']  = (string)($addr['address_1']   ?? '');
                    $p['address_2']  = (string)($addr['address_2']   ?? '');
                    $p['city']       = (string)($addr['city']        ?? '');
                    $p['postcode']   = (string)($addr['postcode']    ?? '');
                    $p['country_id'] = (int)   ($addr['country_id']  ?? 0);
                    $p['zone_id']    = (int)   ($addr['zone_id']     ?? 0);
                }
            }
        }

        // Накладаємо session-state (наприклад, попередній loadMethods зберіг адресу)
        foreach ((array)($this->session->data['payment_address'] ?? []) as $k => $v) {
            if (isset($p[$k]) && (is_string($v) || is_numeric($v)) && $v !== '') $p[$k] = $v;
        }
        foreach ((array)($this->session->data['guest'] ?? []) as $k => $v) {
            if (isset($p[$k]) && (is_string($v) || is_numeric($v)) && $v !== '') $p[$k] = $v;
        }

        return $p;
    }

    /**
     * Завантажує адресну книгу залогіненого customer-а.
     * Кожен запис: {address_id, label, firstname, lastname, company, address_1, address_2,
     *               city, postcode, country_id, zone_id, is_default}.
     * Для guest — повертає пустий масив.
     */
    private function loadAddressBook(): array
    {
        if (!$this->customer->isLogged()) return [];
        $this->load->model('account/address');
        $addresses = $this->model_account_address->getAddresses();
        if (count($addresses) < 1) return [];

        $defaultId = (int)$this->customer->getAddressId();
        $out = [];
        foreach ($addresses as $a) {
            $label = trim((string)($a['firstname'] ?? '') . ' ' . (string)($a['lastname'] ?? ''))
                   . ', ' . trim((string)($a['address_1'] ?? '') . ' ' . (string)($a['city'] ?? ''));
            $out[] = [
                'address_id' => (int)$a['address_id'],
                'label'      => $label,
                'firstname'  => (string)($a['firstname']  ?? ''),
                'lastname'   => (string)($a['lastname']   ?? ''),
                'company'    => (string)($a['company']    ?? ''),
                'address_1'  => (string)($a['address_1']  ?? ''),
                'address_2'  => (string)($a['address_2']  ?? ''),
                'city'       => (string)($a['city']       ?? ''),
                'postcode'   => (string)($a['postcode']   ?? ''),
                'country_id' => (int)   ($a['country_id'] ?? 0),
                'zone_id'    => (int)   ($a['zone_id']    ?? 0),
                'is_default' => (int)$a['address_id'] === $defaultId,
            ];
        }
        return $out;
    }

    /**
     * Список public-груп для customer switcher (B2C/B2B/Wholesale).
     * Default-група завжди є (через ensureDefault). Якщо лише 1 group — switcher
     * не показується (twig сам вирішує по count).
     */
    private function loadGroupsForSwitcher(EasyCheckout $ec): array
    {
        $repo = $ec->getGroupsRepository();
        $repo->ensureDefault();
        $groups = $repo->list();
        $out = [];
        foreach ($groups as $g) {
            $out[] = [
                'group_id' => (int)$g['group_id'],
                'name'     => (string)$g['name'],
                'slug'     => (string)$g['slug'],
                'href'     => $this->url->link('checkout/easycheckout', 'group=' . rawurlencode((string)$g['slug']), true),
            ];
        }
        return $out;
    }

    /**
     * Заголовки-картки для контентних блоків (frontend). Решта типів
     * (buttons, agreement, help, custom_html, payment_form) — без заголовка.
     * @return array<string,string>
     */
    private function buildBlockTitles(): array
    {
        $types = ['customer', 'cart', 'shipping_address', 'payment_address',
                  'shipping', 'payment', 'comment', 'summary'];
        $out = [];
        foreach ($types as $t) {
            $title = (string)$this->language->get('block_title_' . $t);
            if ($title !== '' && $title !== 'block_title_' . $t) {
                $out[$t] = $title;
            }
        }
        return $out;
    }

    private function loadCountries(): array
    {
        $this->load->model('localisation/country');
        require_once DIR_SYSTEM . 'library/ockit/easycheckout/libs/PhoneMasks.php';

        $rows = $this->model_localisation_country->getCountries();
        $out  = [];
        foreach ($rows as $r) {
            if (!(int)($r['status'] ?? 1)) continue;
            $iso2 = (string)($r['iso_code_2'] ?? '');
            $maskCfg = $iso2 ? \OcKit\EasyCheckout\Libs\PhoneMasks::forCountry($iso2) : null;
            $out[] = [
                'country_id' => (int)$r['country_id'],
                'name'       => (string)$r['name'],
                'iso_code_2' => $iso2,
                'phone_mask' => $maskCfg['mask']   ?? '',
                'phone_pref' => $maskCfg['prefix'] ?? '',
            ];
        }
        return $out;
    }

    /**
     * Мапа field_id → field-info (для рендера блоків).
     * Включає custom (positive id) і native (negative id) поля.
     */
    private function buildFieldsMap(EasyCheckout $ec): array
    {
        $map = [];

        // Custom fields з реєстру
        $rows = $ec->getFieldsRepository()->list(['limit' => 500]);
        $primaryLangId = (int)$this->config->get('config_language_id');
        foreach ($rows as $f) {
            // i18n: спочатку поточна мова, fallback — будь-яка перша непорожня
            $name = '';
            if (!empty($f['descriptions'][$primaryLangId]['name'])) {
                $name = $f['descriptions'][$primaryLangId]['name'];
            } else {
                foreach (($f['descriptions'] ?? []) as $d) {
                    if (!empty($d['name'])) { $name = $d['name']; break; }
                }
            }
            $placeholder = $f['descriptions'][$primaryLangId]['placeholder'] ?? '';
            $tooltip     = $f['descriptions'][$primaryLangId]['tooltip']     ?? '';

            // Парсимо params (JSON) — для radio/select витягуємо options[]
            $params  = is_string($f['params'] ?? null) ? json_decode($f['params'], true) : ($f['params'] ?? null);
            $params  = is_array($params) ? $params : [];
            $options = [];
            foreach (($params['options'] ?? []) as $opt) {
                if (!is_array($opt)) continue;
                // Fallback chain: labels[primary_lang_id] → labels[any] → flat label → value
                $optLabel = '';
                if (!empty($opt['labels'][$primaryLangId])) {
                    $optLabel = $opt['labels'][$primaryLangId];
                } elseif (is_array($opt['labels'] ?? null)) {
                    foreach ($opt['labels'] as $lbl) {
                        if (!empty($lbl)) { $optLabel = $lbl; break; }
                    }
                }
                if ($optLabel === '' && !empty($opt['label'])) {
                    $optLabel = (string)$opt['label'];
                }
                $options[] = [
                    'value' => (string)($opt['value'] ?? ''),
                    'label' => (string)($optLabel ?: ($opt['value'] ?? '')),
                ];
            }

            $rules = is_string($f['validation_rules'] ?? null)
                ? (json_decode($f['validation_rules'], true) ?: [])
                : (is_array($f['validation_rules'] ?? null) ? $f['validation_rules'] : []);

            // i18n HTML content для type=html (params.content[lang_code])
            $htmlContent = '';
            if ($f['type'] === 'html' && !empty($params['content']) && is_array($params['content'])) {
                $primaryLangCode = (string)$this->config->get('config_language');
                $htmlContent = (string)($params['content'][$primaryLangCode] ?? '');
                if ($htmlContent === '') {
                    foreach ($params['content'] as $val) {
                        if (!empty($val)) { $htmlContent = (string)$val; break; }
                    }
                }
            }

            // Type-specific computed: для date — pre-compute min/max з params.date
            $dateMin = ''; $dateMax = ''; $dateWeekends = [];
            if ($f['type'] === 'date' && !empty($params['date']) && is_array($params['date'])) {
                $d           = $params['date'];
                $minDaysAhead= (int)($d['min_days_ahead'] ?? 0);
                $maxDaysAhead= isset($d['max_days_ahead']) ? (int)$d['max_days_ahead'] : null;
                $disablePast = !empty($d['disable_past']);
                $base = new \DateTimeImmutable('today');
                if ($disablePast || $minDaysAhead > 0) {
                    $dateMin = $base->modify('+' . max(0, $minDaysAhead) . ' day')->format('Y-m-d');
                }
                if ($maxDaysAhead !== null) {
                    $dateMax = $base->modify('+' . max(0, $maxDaysAhead) . ' day')->format('Y-m-d');
                }
                if (!empty($d['weekends']) && is_array($d['weekends'])) {
                    $dateWeekends = array_values(array_map('intval', $d['weekends']));
                }
            }

            $map[(int)$f['field_id']] = [
                'field_id'    => (int)$f['field_id'],
                'code'        => (string)$f['code'],
                'type'        => (string)$f['type'],
                'belongs_to'  => (string)$f['belongs_to'],
                'name'        => $name ?: $f['code'],
                'placeholder' => $placeholder,
                'tooltip'     => $tooltip,
                'mask'        => ($f['mask_mode'] === 'manual' && $f['mask_value']) ? $f['mask_value'] : '',
                'native'      => false,
                'options'     => $options,
                'params'      => $params,
                'rules'       => $rules,
                'date_min'      => $dateMin,
                'date_max'      => $dateMax,
                'date_weekends' => $dateWeekends,
                'html_content'  => $htmlContent,
            ];
        }

        // Native fields (+ admin overrides з fields_description, field_id < 0)
        $this->load->language('checkout/checkout');
        $nativeOverrides = $ec->getFieldsRepository()->getNativeOverrides();
        foreach (\OcKit\EasyCheckout\Libs\NativeFieldsRegistry::listAll() as $nf) {
            $label = $this->language->get($nf['lang_key']);
            if (!$label || $label === $nf['lang_key']) $label = $nf['code'];
            // Оверайд поточної мови (порожнє name → типова OC-назва).
            $ov = $nativeOverrides[(int)$nf['field_id']][$primaryLangId] ?? [];
            $map[(int)$nf['field_id']] = [
                'field_id'    => (int)$nf['field_id'],
                'code'        => $nf['code'],
                'type'        => $nf['type'],
                'belongs_to'  => $nf['belongs_to'],
                'name'        => !empty($ov['name']) ? (string)$ov['name'] : $label,
                'placeholder' => (string)($ov['placeholder'] ?? ''),
                'tooltip'     => (string)($ov['tooltip'] ?? ''),
                'mask'        => '',
                'native'      => true,
                'oc_field'    => $nf['oc_field'],
            ];
        }
        return $map;
    }

    /**
     * Live-cart update: повертає JSON з новим cart_products + totals.
     * Викликається після qty-edit/remove замість full window.reload().
     */
    public function cartState(): void
    {
        $this->load->language('checkout/checkout');
        if (!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) {
            $this->jsonResponse([
                'success' => true,
                'empty'   => true,
                'cart_products' => [],
                'totals'  => [],
            ]);
            return;
        }
        $this->jsonResponse([
            'success'       => true,
            'empty'         => false,
            'cart_products' => $this->buildCartProducts(),
            'totals'        => $this->buildTotals(),
        ]);
    }

    /**
     * POST {key, quantity} — оновлення кількості. Власний ендпоінт замість
     * core checkout/cart/edit, який ставить session['success'] ('Кошик оновлений')
     * + робить redirect — через що тема показувала alert на наступному лоаді.
     */
    public function cartEdit(): void
    {
        $key = (string)($this->request->post['key'] ?? '');
        $qty = (int)($this->request->post['quantity'] ?? 0);
        if ($key !== '' && $qty > 0) {
            $this->cart->update($key, $qty);
            $this->resetCartDependentSession();
        }
        $this->cartState();
    }

    /** POST {key} — видалення позиції. Аналог core checkout/cart/remove без flash. */
    public function cartRemove(): void
    {
        $key = (string)($this->request->post['key'] ?? '');
        if ($key !== '') {
            $this->cart->remove($key);
            $this->resetCartDependentSession();
        }
        $this->cartState();
    }

    /** Скидає session-стан, що залежить від складу кошика (як у core cart/edit). */
    private function resetCartDependentSession(): void
    {
        unset(
            $this->session->data['shipping_method'],
            $this->session->data['shipping_methods'],
            $this->session->data['payment_method'],
            $this->session->data['payment_methods'],
            $this->session->data['reward']
        );
    }

    // ─── AJAX: zones ─────────────────────────────────────────────────────────

    /**
     * GET ?country_id=X — повертає список зон для країни (для country/zone select).
     */
    public function loadZones(): void
    {
        $countryId = (int)($this->request->get['country_id'] ?? 0);
        $this->load->model('localisation/zone');
        $zones = $this->model_localisation_zone->getZonesByCountryId($countryId);

        $this->load->model('localisation/country');
        $country = $this->model_localisation_country->getCountry($countryId);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success'      => true,
            'zones'        => array_map(static fn($z) => [
                'zone_id' => (int)$z['zone_id'],
                'name'    => (string)$z['name'],
            ], $zones),
            'postcode_required' => $country ? (bool)$country['postcode_required'] : false,
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Завантажує файл (для field type=file). Повертає token (code) + filename.
     * Використовує OC-шний `model_tool_upload::addUpload()` — той самий механізм,
     * що стандартний OC tool/upload, але через наш роут (бо OC original `index99999` вимкнено).
     */
    public function uploadFile(): void
    {
        $this->load->language('tool/upload');
        $json = [];

        if (!empty($_FILES['file']['name']) && is_file($_FILES['file']['tmp_name'])) {
            $filename = basename(preg_replace('/[^a-zA-Z0-9\.\-\s+]/', '',
                html_entity_decode($_FILES['file']['name'], ENT_QUOTES, 'UTF-8')));

            if (mb_strlen($filename) < 3 || mb_strlen($filename) > 64) {
                $json['error'] = $this->language->get('error_filename') ?: 'Bad filename length';
            }

            // Allowed extensions
            $extAllowed = array_map('trim', explode("\n", (string)$this->config->get('config_file_ext_allowed')));
            $ext = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($ext, $extAllowed, true)) {
                $json['error'] = $this->language->get('error_filetype') ?: 'File type not allowed';
            }

            // Mime
            $mimeAllowed = array_map('trim', explode("\n", (string)$this->config->get('config_file_mime_allowed')));
            if (!in_array((string)$_FILES['file']['type'], $mimeAllowed, true)) {
                $json['error'] = $this->language->get('error_filetype') ?: 'Mime not allowed';
            }

            // PHP content
            $content = (string)@file_get_contents($_FILES['file']['tmp_name']);
            if (preg_match('/<\?php/i', $content)) {
                $json['error'] = $this->language->get('error_filetype') ?: 'PHP content forbidden';
            }

            if ((int)$_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $json['error'] = $this->language->get('error_upload_' . (int)$_FILES['file']['error']) ?: 'Upload error';
            }
        } else {
            $json['error'] = $this->language->get('error_upload') ?: 'No file uploaded';
        }

        if (!$json) {
            $file = $filename . '.' . token(32);
            move_uploaded_file($_FILES['file']['tmp_name'], DIR_UPLOAD . $file);
            $this->load->model('tool/upload');
            $json['code']     = $this->model_tool_upload->addUpload($filename, $file);
            $json['filename'] = $filename;
            $json['success']  = $this->language->get('text_upload') ?: 'Uploaded';
        }

        $this->jsonResponse($json);
    }

    /**
     * ТЗ §10.4 — server-side render блоків при reload-on-change.
     *
     * POST { okec: {fields...}, affected_blocks: ['shipping','payment',...] }
     *
     * Сервер:
     * 1. Записує POST-дані в session (через setSessionAddresses) щоб state був актуальний
     * 2. Будує повний контекст ($data) для рендеру блоків
     * 3. Знаходить кожен affected block в layout по id, рендерить per-block twig
     * 4. Повертає { blocks: { id: html }, state: {...} }
     *
     * Якщо affected_blocks пустий — рендерить ВСІ блоки.
     */
    public function reloadBlocks(): void
    {
        $this->load->language('checkout/checkout');
        $this->load->language('easycheckout/checkout');

        $affected = array_map('strval', (array)($this->request->post['affected_blocks'] ?? []));
        $okec     = (array)($this->request->post['okec'] ?? []);

        // Sync session з postedFields щоб shipping/zone/totals були правильні
        if ($okec) {
            try { $this->setSessionAddresses($okec); } catch (\Throwable $e) { /* best-effort */ }
        }

        try {
            $data = $this->buildBlockRenderContext();
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
            return;
        }

        $renderedBlocks = [];
        $template_dir = (string)($this->config->get('template_directory') ?: 'default/template/');

        foreach (($data['layout']['steps'] ?? []) as $step) {
            foreach (($step['rows'] ?? []) as $row) {
                foreach (($row['cells'] ?? []) as $cell) {
                    foreach (($cell['blocks'] ?? []) as $block) {
                        $blockId   = (string)($block['id']   ?? '');
                        $blockType = (string)($block['type'] ?? '');
                        if (!$blockId || !$blockType) continue;

                        // Filter: якщо affected задано — рендеримо тільки ID/type що в списку
                        if ($affected && !in_array($blockId, $affected, true) && !in_array($blockType, $affected, true)) {
                            continue;
                        }

                        $partial = $template_dir . 'easycheckout/blocks/' . $blockType . '.twig';
                        $partialFile = DIR_TEMPLATE . 'easycheckout/blocks/' . $blockType . '.twig';
                        if (!is_file($partialFile)) continue;

                        // Block-level visibility settings (як у monolithic checkout.twig)
                        $blockData = $data + [
                            'block'      => $block,
                            's'          => (array)($block['settings'] ?? []),
                            'vp_classes' => '',
                        ];

                        try {
                            $renderedBlocks[$blockId] = $this->load->view('easycheckout/blocks/' . $blockType, $blockData);
                        } catch (\Throwable $e) {
                            $renderedBlocks[$blockId] = '<!-- render error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ' -->';
                        }
                    }
                }
            }
        }

        $this->jsonResponse([
            'success' => true,
            'blocks'  => (object)$renderedBlocks,
            'state'   => [
                'has_products'  => $this->cart->hasProducts(),
                'product_count' => count($this->cart->getProducts()),
            ],
        ]);
    }

    /**
     * Будує мінімальний $data для рендеру окремого блоку.
     * Spin-off з index() — повторно використовує buildFieldsMap/buildCartProducts/etc.
     */
    private function buildBlockRenderContext(): array
    {
        $ec = new EasyCheckout($this->registry);
        $ec->setStore((int)$this->config->get('config_store_id'));
        $ec->setGroup($this->resolveGroupId($ec));

        $layout       = $ec->getPageLayoutRepository()->get('checkout');
        $fieldsMap    = $this->buildFieldsMap($ec);
        $cartProducts = $this->buildCartProducts();
        $totals       = $this->buildTotals();
        $countries    = $this->loadCountries();
        $prefill      = $this->buildPrefill();
        $addressBook  = $this->loadAddressBook();

        $this->load->model('localisation/language');
        $languages       = $this->model_localisation_language->getLanguages();
        $primaryLangCode = (string)$this->config->get('config_language');

        $template_dir = (string)($this->config->get('template_directory') ?: 'default/template/');

        return [
            'layout'            => $layout,
            'group_id'          => $ec->getGroupId(),
            'fields_map'        => $fieldsMap,
            'cart_products'     => $cartProducts,
            'totals'            => $totals,
            'countries'         => $countries,
            'default_country_id'=> $prefill['country_id'] ?: (int)($this->config->get('module_oc_kit_easycheckout_default_country_id') ?: $this->config->get('config_country_id')),
            'prefill'           => $prefill,
            'address_book'      => $addressBook,
            'active_group_id'   => $ec->getGroupId(),
            'languages'         => $languages,
            'primary_lang_code' => $primaryLangCode,
            'logged'            => $this->customer->isLogged(),
            'is_preview'        => false,
            'template_dir'      => $template_dir,
            'okec_text'         => [
                'select_method'  => $this->language->get('text_select_method') ?: 'Please select a method',
                'no_shipping'    => $this->language->get('text_no_shipping')   ?: 'No shipping methods available',
                'no_payment'     => $this->language->get('text_no_payment')    ?: 'No payment methods available',
                'cart_empty'     => $this->language->get('text_cart_empty')    ?: 'Cart is empty.',
                'cart_remove'    => $this->language->get('text_cart_remove')   ?: 'Remove',
                'field_required' => $this->language->get('error_field_required') ?: 'Required',
                'place_order'    => $this->language->get('text_place_order')   ?: 'Place order',
            ],
        ];
    }

    /**
     * Frontend abandoned-cart save (ТЗ §15.1) — POST `/easycheckout/saveAbandoned`.
     * JS викликає на blur значимих полів (email/телефон/ім'я). Backend upsert'ить
     * запис у `kit_easycheckout_abandoned` за session-токеном і повертає abandoned_id.
     */
    public function saveAbandoned(): void
    {
        if (!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) {
            $this->jsonResponse(['success' => false, 'message' => 'cart empty']);
            return;
        }

        $okec = (array)($this->request->post['okec'] ?? []);
        // Sanitize: тільки очікувані ключі
        $allowed = ['email', 'telephone', 'firstname', 'lastname', 'company',
                    'address_1', 'address_2', 'city', 'postcode', 'country_id', 'zone_id'];
        $clean = [];
        foreach ($allowed as $k) {
            if (isset($okec[$k]) && is_string($okec[$k])) $clean[$k] = trim($okec[$k]);
        }
        if (empty($clean['email']) && empty($clean['telephone'])) {
            // Без ключових даних не маємо сенсу зберігати
            $this->jsonResponse(['success' => false, 'message' => 'no email/phone yet']);
            return;
        }
        try {
            $this->trackAbandoned($clean);
            $token = (string)$this->session->data['okec_abandoned_token'];
            $row   = $this->db->query("SELECT `abandoned_id` FROM `" . DB_PREFIX . "kit_easycheckout_abandoned`
                WHERE `recovery_token`='" . $this->db->escape($token) . "' LIMIT 1")->row;
            $this->jsonResponse([
                'success'      => true,
                'abandoned_id' => $row ? (int)$row['abandoned_id'] : 0,
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX login для checkout-modal: приймає email + password JSON,
     * викликає OC `customer->login(email, password)`. На успіх — повертає redirect
     * на /easycheckout, JS робить window.location.reload() щоб зарендерити
     * сторінку як для залогіненого користувача.
     */
    public function loginCustomer(): void
    {
        $this->load->language('checkout/checkout');
        $this->load->language('easycheckout/checkout');

        $email    = trim((string)($this->request->post['email']    ?? ''));
        $password = (string)($this->request->post['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $this->jsonResponse(['success' => false, 'error' => $this->language->get('error_email')]);
            return;
        }

        if ($this->customer->login($email, $password)) {
            // Triger подія login — щоб OC API/integrations відпрацювали
            $this->load->model('account/customer');
            $this->jsonResponse([
                'success'  => true,
                'redirect' => $this->url->link('checkout/easycheckout', '', true),
            ]);
            return;
        }

        $this->jsonResponse([
            'success' => false,
            'error'   => $this->language->get('error_login') ?: 'Invalid email or password',
        ]);
    }

    /**
     * POST email — повертає {exists: bool}. Використовується для inline-натяку
     * "Цей email уже зареєстровано — увійти?" у customer-блоці.
     */
    public function checkExistingEmail(): void
    {
        $email = trim((string)($this->request->post['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['success' => true, 'exists' => false]);
            return;
        }
        $row = $this->db->query("SELECT `customer_id` FROM `" . DB_PREFIX . "customer`
            WHERE LOWER(`email`) = '" . $this->db->escape(mb_strtolower($email)) . "' LIMIT 1");
        $this->jsonResponse(['success' => true, 'exists' => (bool)$row->num_rows]);
    }

    // ─── AJAX: shipping/payment methods ──────────────────────────────────────

    /**
     * Витягує адресу з POST['okec'] і записує в сесію + повертає shipping/payment
     * methods, повний список totals, та чи потрібна доставка.
     */
    public function loadMethods(): void
    {
        $this->load->language('checkout/checkout');
        $this->load->language('easycheckout/checkout');

        if (!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) {
            $this->jsonResponse(['success' => false, 'error' => $this->language->get('error_cart_empty')]);
            return;
        }

        $okec = $this->request->post['okec'] ?? [];
        if (!is_array($okec)) $okec = [];

        $this->setSessionAddresses($okec);

        $hasShipping = $this->cart->hasShipping();
        $shipping    = $hasShipping ? $this->buildShippingMethods() : [];
        $payment     = $this->buildPaymentMethods();

        // Abandoned-checkout tracking — після того як user заповнив базові поля
        $this->trackAbandoned($okec);

        // Якщо JS передав вибраний shipping/payment — пре-сетимо в сесію,
        // щоб totals (extension/total/shipping) врахували вартість.
        $selectedShip = (string)($this->request->post['shipping_method'] ?? '');
        if ($hasShipping && $selectedShip) {
            $resolved = $this->resolveShipping($selectedShip);
            if ($resolved) $this->session->data['shipping_method'] = $resolved;
        }
        $selectedPay = (string)($this->request->post['payment_method'] ?? '');
        if ($selectedPay) {
            $resolvedP = $this->resolvePayment($selectedPay);
            if ($resolvedP) $this->session->data['payment_method'] = $resolvedP;
        }

        $this->jsonResponse([
            'success'           => true,
            'has_shipping'      => $hasShipping,
            'shipping_methods'  => $shipping,
            'payment_methods'   => $payment,
            'totals'            => $this->buildTotals(),
            'shipping_required' => $hasShipping,
        ]);
    }

    /**
     * Витягує customer/address з payload `okec[*]` і записує:
     *   - $session->data['payment_address']
     *   - $session->data['shipping_address']  (якщо є shipping)
     *   - $session->data['guest']             (basic customer info)
     *   - $session->data['comment']           (з okec[comment])
     */
    private function setSessionAddresses(array $okec): void
    {
        $countryId = (int)($okec['country_id'] ?? $this->config->get('module_oc_kit_easycheckout_default_country_id') ?: $this->config->get('config_country_id'));
        $zoneId    = (int)($okec['zone_id'] ?? 0);

        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');

        $country = $this->model_localisation_country->getCountry($countryId);
        $zone    = $zoneId ? $this->model_localisation_zone->getZone($zoneId) : null;

        $address = [
            'firstname'      => (string)($okec['firstname'] ?? ''),
            'lastname'       => (string)($okec['lastname'] ?? ''),
            'company'        => (string)($okec['company'] ?? ''),
            'address_1'      => (string)($okec['address_1'] ?? ''),
            'address_2'      => (string)($okec['address_2'] ?? ''),
            'postcode'       => (string)($okec['postcode'] ?? ''),
            'city'           => (string)($okec['city'] ?? ''),
            'zone_id'        => $zoneId,
            'zone'           => $zone['name']      ?? '',
            'zone_code'      => $zone['code']      ?? '',
            'country_id'     => $countryId,
            'country'        => $country['name']    ?? '',
            'iso_code_2'     => $country['iso_code_2'] ?? '',
            'iso_code_3'     => $country['iso_code_3'] ?? '',
            'address_format' => $country['address_format'] ?? '',
            'custom_field'   => [],
        ];

        $this->session->data['shipping_address'] = $address;

        // Окремий payment_address: якщо користувач НЕ натиснув "Same as shipping"
        // АБО admin вимкнув toggle (тоді payment_address блок рендерить billing_*
        // префікси як незалежні поля). Beрusємo з okec[billing_*] якщо вони є.
        $hasBillingPayload = false;
        foreach (['firstname','lastname','address_1','city','country_id'] as $k) {
            if (!empty($okec['billing_' . $k])) { $hasBillingPayload = true; break; }
        }

        if ($hasBillingPayload) {
            $bCountryId = (int)($okec['billing_country_id'] ?? $countryId);
            $bZoneId    = (int)($okec['billing_zone_id']    ?? 0);
            $bCountry   = $this->model_localisation_country->getCountry($bCountryId);
            $bZone      = $bZoneId ? $this->model_localisation_zone->getZone($bZoneId) : null;

            $this->session->data['payment_address'] = [
                'firstname'      => (string)($okec['billing_firstname']  ?? ''),
                'lastname'       => (string)($okec['billing_lastname']   ?? ''),
                'company'        => (string)($okec['billing_company']    ?? ''),
                'address_1'      => (string)($okec['billing_address_1']  ?? ''),
                'address_2'      => (string)($okec['billing_address_2']  ?? ''),
                'postcode'       => (string)($okec['billing_postcode']   ?? ''),
                'city'           => (string)($okec['billing_city']       ?? ''),
                'zone_id'        => $bZoneId,
                'zone'           => $bZone['name']      ?? '',
                'zone_code'      => $bZone['code']      ?? '',
                'country_id'     => $bCountryId,
                'country'        => $bCountry['name']    ?? '',
                'iso_code_2'     => $bCountry['iso_code_2'] ?? '',
                'iso_code_3'     => $bCountry['iso_code_3'] ?? '',
                'address_format' => $bCountry['address_format'] ?? '',
                'custom_field'   => [],
            ];
        } else {
            // Default: payment === shipping (single-form або «same as shipping» checked)
            $this->session->data['payment_address'] = $address;
        }

        $this->session->data['guest'] = [
            'customer_group_id' => (int)$this->config->get('config_customer_group_id'),
            'firstname'         => $address['firstname'],
            'lastname'          => $address['lastname'],
            'email'             => (string)($okec['email'] ?? ''),
            'telephone'         => $this->normalizePhone((string)($okec['telephone'] ?? '')),
            'custom_field'      => [],
        ];

        if (!empty($okec['comment'])) {
            $this->session->data['comment'] = (string)$okec['comment'];
        }
    }

    private function buildShippingMethods(): array
    {
        $this->load->model('setting/extension');
        $methods = [];
        foreach ($this->model_setting_extension->getExtensions('shipping') as $ext) {
            if (!$this->config->get('shipping_' . $ext['code'] . '_status')) continue;
            $this->load->model('extension/shipping/' . $ext['code']);
            $quote = $this->{'model_extension_shipping_' . $ext['code']}->getQuote($this->session->data['shipping_address']);
            if (!$quote) continue;
            $list = [];
            foreach ($quote['quote'] as $code => $opt) {
                $list[] = [
                    'code'        => $opt['code'],
                    'title'       => $opt['title'],
                    'cost'        => (float)$opt['cost'],
                    'tax_class_id'=> (int)$opt['tax_class_id'],
                    'text'        => $opt['text'],
                ];
            }
            $methods[] = [
                'code'    => $ext['code'],
                'title'   => $quote['title'],
                'options' => $list,
                'error'   => $quote['error'] ?? '',
                'sort'    => (int)$quote['sort_order'],
            ];
        }
        // ── Custom shipping methods (внутрішні методи EasyCheckout) ──
        foreach ($this->customShippingMethods() as $cm) {
            $methods[] = $cm;
        }

        $this->session->data['shipping_methods'] = $methods;
        usort($methods, static fn($a, $b) => $a['sort'] <=> $b['sort']);
        return $methods;
    }

    /** Кастомні методи доставки з конвертацією вартості у базову валюту. */
    private function customShippingMethods(): array
    {
        require_once DIR_SYSTEM . 'library/ockit/easycheckout/libs/CustomMethodsCatalog.php';
        $svc  = new \OcKit\EasyCheckout\Libs\CustomMethodsCatalog($this->db);
        $base = (string)$this->config->get('config_currency');
        $list = $svc->getShipping([
            'language_id' => (int)$this->config->get('config_language_id'),
            'weight'      => (float)$this->cart->getWeight(),
            'total'       => $this->buildTotalsRaw(),
            'state'       => $this->customMethodState(),
        ]);
        foreach ($list as &$m) {
            foreach ($m['options'] as &$opt) {
                $from = (string)($opt['currency'] ?? '');
                if ($from !== '' && $from !== $base) {
                    $opt['cost'] = (float)$this->currency->convert((float)$opt['cost'], $from, $base);
                }
                // Якщо text порожній — показуємо відформатовану ціну
                if (($opt['text'] ?? '') === '') {
                    $opt['text'] = $this->currency->format(
                        $this->tax->calculate((float)$opt['cost'], (int)$opt['tax_class_id'], $this->config->get('config_tax')),
                        $this->session->data['currency'] ?? $base
                    );
                }
                $opt['icon'] = $this->resolveMethodIconUrl((string)($opt['icon'] ?? ''));
            }
            unset($opt);
        }
        unset($m);
        return $list;
    }

    /** Відносний шлях іконки (filemanager) → публічний URL мініатюри. '' якщо нема. */
    private function resolveMethodIconUrl(string $icon): string
    {
        $icon = trim($icon);
        if ($icon === '') return '';
        if (preg_match('~^https?://~i', $icon)) return $icon;
        $this->load->model('tool/image');
        return (string)$this->model_tool_image->resize($icon, 96, 96);
    }

    /** Стан для умов кастомних методів — значення для ConditionTypes. */
    private function customMethodState(): array
    {
        $addr = $this->session->data['shipping_address'] ?? $this->session->data['payment_address'] ?? [];

        // Total без доставки
        $td = $this->runTotalExtensions();
        $totalNoShipping = (float)$td['total'];
        foreach ($td['totals'] as $t) {
            if (($t['code'] ?? '') === 'shipping') { $totalNoShipping -= (float)$t['value']; }
        }

        // Кількість і макс. вага одного товару
        $qty = 0; $maxW = 0.0;
        foreach ($this->cart->getProducts() as $pr) {
            $qty += (int)$pr['quantity'];
            $w = (float)($pr['weight'] ?? 0);
            if ($w > $maxW) $maxW = $w;
        }

        return [
            'logged_in'           => $this->customer->isLogged() ? '1' : '0',
            'customer_group'      => (string)($this->customer->isLogged()
                                       ? $this->customer->getGroupId()
                                       : $this->config->get('config_customer_group_id')),
            'has_orders'          => $this->customer->isLogged() ? '1' : '0',
            'total'               => (string)$this->buildTotalsRaw(),
            'total_no_shipping'   => (string)$totalNoShipping,
            'total_quantity'      => (string)$qty,
            'total_weight'        => (string)$this->cart->getWeight(),
            'max_weight_single'   => (string)$maxW,
            'coupon_used'         => !empty($this->session->data['coupon']) ? '1' : '0',
            'reward_used'         => !empty($this->session->data['reward']) ? '1' : '0',
            'voucher_used'        => !empty($this->session->data['vouchers']) ? '1' : '0',
            'products_no_shipping'=> $this->cart->hasShipping() ? '0' : '1',
            'country_id'          => (string)($addr['country_id'] ?? ''),
            'zone_id'             => (string)($addr['zone_id'] ?? ''),
            'city'                => (string)($addr['city'] ?? ''),
            'postcode'            => (string)($addr['postcode'] ?? ''),
            'language'            => (string)$this->config->get('config_language'),
            'currency'            => (string)($this->session->data['currency'] ?? $this->config->get('config_currency')),
            'store_id'            => (string)$this->config->get('config_store_id'),
            'ip'                  => (string)($this->request->server['REMOTE_ADDR'] ?? ''),
            'day_of_week'         => date('w'),
            'time'                => date('H:i'),
            'date'                => date('Y-m-d'),
            'shipping_method'     => (string)($this->session->data['shipping_method']['code'] ?? ''),
            'payment_method'      => (string)($this->session->data['payment_method']['code'] ?? ''),
        ];
    }

    private function buildPaymentMethods(): array
    {
        $this->load->model('setting/extension');
        $methods = [];
        $total = $this->buildTotalsRaw();
        foreach ($this->model_setting_extension->getExtensions('payment') as $ext) {
            if (!$this->config->get('payment_' . $ext['code'] . '_status')) continue;
            $this->load->model('extension/payment/' . $ext['code']);
            $method = $this->{'model_extension_payment_' . $ext['code']}->getMethod($this->session->data['payment_address'], $total);
            if (!$method) continue;
            $methods[] = [
                'code'  => $method['code'],
                'title' => $method['title'],
                'terms' => $method['terms'] ?? '',
                'sort'  => (int)$method['sort_order'],
            ];
        }
        // ── Custom payment methods (внутрішні методи EasyCheckout) ──
        require_once DIR_SYSTEM . 'library/ockit/easycheckout/libs/CustomMethodsCatalog.php';
        $svc = new \OcKit\EasyCheckout\Libs\CustomMethodsCatalog($this->db);
        foreach ($svc->getPayment([
            'language_id' => (int)$this->config->get('config_language_id'),
            'state'       => $this->customMethodState(),
        ]) as $cm) {
            $cm['icon'] = $this->resolveMethodIconUrl((string)($cm['icon'] ?? ''));
            $methods[] = $cm;
        }

        $this->session->data['payment_methods'] = $methods;
        usort($methods, static fn($a, $b) => $a['sort'] <=> $b['sort']);
        return $methods;
    }

    private function buildTotalsRaw(): float
    {
        return (float)$this->runTotalExtensions()['total'];
    }

    /**
     * Запускає всі активні extension/total/* модулі за OC 3.x signature
     * (single $total array з ключами totals/total/taxes). Повертає масив:
     *   [ 'totals' => [...], 'total' => float, 'taxes' => [...] ]
     */
    private function runTotalExtensions(): array
    {
        // OC total-моделі мають сигнатуру getTotal($total) БЕЗ by-reference
        // (ocStore), тож мутації проходять лише коли елементи масиву —
        // посилання (як у core checkout/cart.php). Інакше totals порожні.
        $totals = [];
        $taxes  = $this->cart->getTaxes();
        $total  = 0.0;
        $totalData = ['totals' => &$totals, 'taxes' => &$taxes, 'total' => &$total];

        $this->load->model('setting/extension');
        $sortOrder = [];
        $results = $this->model_setting_extension->getExtensions('total');
        foreach ($results as $r) {
            $sortOrder[$r['code']] = (int)$this->config->get('total_' . $r['code'] . '_sort_order');
        }
        array_multisort($sortOrder, SORT_ASC, $results);

        foreach ($results as $r) {
            if (!$this->config->get('total_' . $r['code'] . '_status')) continue;
            $this->load->model('extension/total/' . $r['code']);
            $this->{'model_extension_total_' . $r['code']}->getTotal($totalData);
        }
        unset($totalData);   // розриваємо посилання перед поверненням чистого масиву

        $out = ['totals' => $totals, 'total' => $total, 'taxes' => $taxes];
        usort($out['totals'], static fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

        // Re-entry guard: appendCustomSubtotals → customMethodState → runTotalExtensions
        if (!$this->inTotals) {
            $this->inTotals = true;
            try { $this->appendCustomSubtotals($out); }
            finally { $this->inTotals = false; }
        }
        return $out;
    }

    private bool $inTotals = false;

    /**
     * Додає кастомні рядки підсумку («Облік у замовленні») за обраними методами.
     * Вставляє перед рядком 'total' і коригує підсумкову суму.
     */
    private function appendCustomSubtotals(array &$totalData): void
    {
        require_once DIR_SYSTEM . 'library/ockit/easycheckout/libs/CustomMethodsCatalog.php';
        $svc = new \OcKit\EasyCheckout\Libs\CustomMethodsCatalog($this->db);

        $subTotalVal = 0.0;
        foreach ($totalData['totals'] as $t) {
            if (($t['code'] ?? '') === 'sub_total') { $subTotalVal = (float)$t['value']; break; }
        }

        $rows = $svc->getSubtotalRows([
            'language_id' => (int)$this->config->get('config_language_id'),
            'sub_total'   => $subTotalVal,
            'state'       => $this->customMethodState(),
        ]);
        if (!$rows) return;

        // Знаходимо позицію рядка 'total' (має бути останнім)
        $totalIdx = null;
        foreach ($totalData['totals'] as $i => $t) {
            if (($t['code'] ?? '') === 'total') { $totalIdx = $i; break; }
        }

        $sortBase = 25; // між shipping(3-5) і total(9 / 999)
        $injected = [];
        foreach ($rows as $r) {
            $injected[] = [
                'code'       => 'okec_cm_subtotal',
                'title'      => $r['title'],
                'value'      => (float)$r['value'],
                'sort_order' => $sortBase + (int)$r['sort_order'],
            ];
            $totalData['total'] = (float)$totalData['total'] + (float)$r['value'];
        }

        if ($totalIdx !== null) {
            array_splice($totalData['totals'], $totalIdx, 0, $injected);
            // оновлюємо value рядка 'total'
            foreach ($totalData['totals'] as &$t) {
                if (($t['code'] ?? '') === 'total') { $t['value'] = (float)$totalData['total']; break; }
            }
            unset($t);
        } else {
            foreach ($injected as $row) $totalData['totals'][] = $row;
        }
    }

    // ─── AJAX: confirm/submit order ──────────────────────────────────────────

    /**
     * Створює замовлення на основі поточної сесії + payload `okec[*]`.
     * Повертає JSON {success, redirect} — JS робить window.location = redirect.
     */
    public function confirm(): void
    {
        $this->load->language('checkout/checkout');
        $this->load->language('easycheckout/checkout');

        $errors = [];

        if (!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) {
            $this->jsonResponse(['success' => false, 'error' => $this->language->get('error_cart_empty')]);
            return;
        }

        $okec = $this->request->post['okec'] ?? [];
        if (!is_array($okec)) $okec = [];

        // Нормалізація telephone — countries-aware (UA/PL/CZ/...)
        if (!empty($okec['telephone'])) {
            // Спершу резолвимо ISO країни — потрібен раніше ніж setSessionAddresses
            $countryIso = $this->resolveCountryIso((int)($okec['country_id'] ?? 0));
            require_once DIR_SYSTEM . 'library/ockit/easycheckout/libs/PhoneMasks.php';
            $okec['telephone'] = \OcKit\EasyCheckout\Libs\PhoneMasks::normalize(
                (string)$okec['telephone'], $countryIso
            );

            // Validate normalized number проти country regex
            $maskCfg = $countryIso ? \OcKit\EasyCheckout\Libs\PhoneMasks::forCountry($countryIso) : null;
            if ($maskCfg && !empty($maskCfg['regex'])
                && !preg_match('~' . $maskCfg['regex'] . '~', $okec['telephone'])) {
                $errors['telephone'] = $this->language->get('error_telephone_format')
                    ?: 'Phone number does not match country format';
            }
        }

        // Required-поля з layout-у, з урахуванням condition (приховані skip)
        $requiredFields = $this->resolveRequiredFieldCodes($okec);

        $errorKeyMap = [
            'email'      => 'error_email',
            'firstname'  => 'error_firstname',
            'lastname'   => 'error_lastname',
            'telephone'  => 'error_telephone',
            'address_1'  => 'error_address_1',
            'city'       => 'error_city',
            'country_id' => 'error_country',
            'zone_id'    => 'error_zone',
        ];

        foreach ($requiredFields['native'] as $code) {
            if ($code === 'email') {
                if (empty($okec['email']) || !filter_var($okec['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = $this->language->get('error_email');
                }
                continue;
            }
            if (empty($okec[$code])) {
                $key = $errorKeyMap[$code] ?? null;
                $errors[$code] = $key ? ($this->language->get($key) ?: $code) : $code . ' required';
            }
        }
        foreach ($requiredFields['custom'] as $code) {
            if (empty($okec[$code])) {
                $errors[$code] = $this->language->get('error_field_required') ?: 'Required';
            }
        }

        // Agreement (terms-checkbox) — якщо в layout є agreement-блок з required:true,
        // користувач має поставити галочку.
        if ($this->isAgreementRequired() && empty($okec['agreement'])) {
            $errors['agreement'] = $this->language->get('error_agreement') ?: 'Please accept terms';
        }

        // Register flow — якщо обрано "register", потрібен валідний пароль
        if (!$this->customer->isLogged() && !empty($okec['register'])) {
            $minPass = max(4, min(40, (int)$this->config->get('config_password_length') ?: 4));
            if (empty($okec['password']) || mb_strlen((string)$okec['password']) < $minPass) {
                $errors['password'] = sprintf(
                    $this->language->get('error_password_length') ?: 'Password must be %d-%d characters',
                    $minPass, 40
                );
            } elseif (!empty($okec['confirm']) && $okec['confirm'] !== $okec['password']) {
                $errors['confirm'] = $this->language->get('error_confirm') ?: 'Passwords do not match';
            }
        }

        // Custom-fields validation (validation_rules JSON з реєстру)
        $ec = new EasyCheckout($this->registry);
        foreach ($ec->getFieldsRepository()->list(['limit' => 500]) as $f) {
            $code = (string)$f['code'];
            $val  = $okec[$code] ?? null;
            $rules = is_string($f['validation_rules'] ?? null)
                ? (json_decode($f['validation_rules'], true) ?: [])
                : (is_array($f['validation_rules'] ?? null) ? $f['validation_rules'] : []);
            $err = $this->validateCustomField((string)($val ?? ''), $rules, $okec);
            if ($err !== null) $errors[$code] = $err;
        }

        if ($errors) {
            $this->jsonResponse(['success' => false, 'errors' => $errors]);
            return;
        }

        $this->setSessionAddresses($okec);

        // Selected methods з POST
        $shippingCode = (string)($this->request->post['shipping_method'] ?? '');
        $paymentCode  = (string)($this->request->post['payment_method'] ?? '');

        if ($this->cart->hasShipping()) {
            if (!$shippingCode) {
                $this->jsonResponse(['success' => false, 'error' => $this->language->get('error_shipping')]);
                return;
            }
            $this->buildShippingMethods();   // refresh session
            $shipping = $this->resolveShipping($shippingCode);
            if (!$shipping) {
                $this->jsonResponse(['success' => false, 'error' => $this->language->get('error_shipping')]);
                return;
            }
            $this->session->data['shipping_method'] = $shipping;
        }

        if (!$paymentCode) {
            $this->jsonResponse(['success' => false, 'error' => $this->language->get('error_payment')]);
            return;
        }
        $this->buildPaymentMethods();        // refresh session
        $payment = $this->resolvePayment($paymentCode);
        if (!$payment) {
            $this->jsonResponse(['success' => false, 'error' => $this->language->get('error_payment')]);
            return;
        }
        $this->session->data['payment_method'] = $payment;

        // Реєструємо нового customer-а, якщо обрав "register" + ввів пароль
        if (!$this->customer->isLogged() && !empty($okec['register']) && !empty($okec['password'])) {
            $this->createCustomerAccount($okec);
        }

        // Створюємо order
        $order = $this->buildOrderData($okec);
        $this->load->model('checkout/order');
        $orderId = $this->model_checkout_order->addOrder($order);
        $this->session->data['order_id'] = $orderId;

        // Logged-in users — auto-save введеної адреси в address_book (якщо нова)
        if ($this->customer->isLogged()) {
            $this->maybeSaveAddress($okec);
        }

        // Зберігаємо custom (oc-kit) поля до oc_kit_easycheckout_order_fields
        $this->saveCustomFieldValues($orderId, $okec);

        // Marking abandoned як recovered (mapping abandoned_id → order_id)
        $this->markAbandonedRecovered($orderId);

        $pm = $this->session->data['payment_method'];

        // ── Custom payment method: немає OC-контролера → рендеримо info-форму самі ──
        if (!empty($pm['custom'])) {
            // Призначаємо статус замовлення (custom payment не має confirm-колбеку)
            $statusId = (int)($pm['order_status_id'] ?? 0)
                ?: (int)$this->config->get('config_order_status_id') ?: 1;
            $this->model_checkout_order->addOrderHistory($orderId, $statusId);

            $info = $this->renderPaymentInfo((string)($pm['info_form'] ?? ''));
            $heading = (string)($pm['form_heading'] ?? '');
            $html = ($heading !== '' ? '<h4>' . $heading . '</h4>' : '') . $info;

            $this->jsonResponse([
                'success'      => true,
                'order_id'     => $orderId,
                'payment_form' => $html,
                'redirect'     => $this->url->link('checkout/success', '', true),
            ]);
            return;
        }

        // Завантажуємо inline payment-form HTML щоб JS показав без redirect-у
        $paymentForm = $this->load->controller('extension/payment/' . $pm['code']);

        $this->jsonResponse([
            'success'      => true,
            'order_id'     => $orderId,
            'payment_form' => is_string($paymentForm) ? $paymentForm : '',
            // Fallback redirect — якщо JS не зможе зробити inline (e.g. модуль покладається на повне перезавантаження)
            'redirect'     => $this->url->link('checkout/easycheckout/payment', '', true),
        ]);
    }

    /** Підстановка {total}/{subtotal}/{shipping}/{tax} у payment info HTML. */
    private function renderPaymentInfo(string $html): string
    {
        if ($html === '') return '';
        $td       = $this->runTotalExtensions();
        $base     = (string)($this->session->data['currency'] ?? $this->config->get('config_currency'));
        $sub = $ship = $tax = 0.0;
        foreach ($td['totals'] as $t) {
            if ($t['code'] === 'sub_total') $sub  = (float)$t['value'];
            if ($t['code'] === 'shipping')  $ship = (float)$t['value'];
            if ($t['code'] === 'tax')       $tax += (float)$t['value'];
        }
        $fmt = fn($v) => $this->currency->format((float)$v, $base);
        return strtr($html, [
            '{total}'    => $fmt($td['total']),
            '{subtotal}' => $fmt($sub),
            '{shipping}' => $fmt($ship),
            '{tax}'      => $fmt($tax),
        ]);
    }

    private function resolveShipping(string $code): ?array
    {
        if (strpos($code, '.') === false) return null;
        [$ext, $option] = explode('.', $code, 2);
        $methods = $this->session->data['shipping_methods'] ?? [];
        foreach ($methods as $m) {
            if ($m['code'] !== $ext) continue;
            foreach ($m['options'] as $opt) {
                if ($opt['code'] === $code) {
                    return [
                        'title'    => $opt['title'],
                        'code'     => $opt['code'],
                        'cost'     => $opt['cost'],
                        'tax_class_id' => $opt['tax_class_id'],
                        'text'     => $opt['text'],
                    ];
                }
            }
        }
        return null;
    }

    private function resolvePayment(string $code): ?array
    {
        $methods = $this->session->data['payment_methods'] ?? [];
        foreach ($methods as $m) {
            if ($m['code'] === $code) {
                $out = ['title' => $m['title'], 'code' => $m['code'], 'terms' => $m['terms'] ?? ''];
                if (!empty($m['custom'])) {
                    $out['custom']          = true;
                    $out['order_status_id'] = (int)($m['order_status_id'] ?? 0);
                    $out['info_form']       = (string)($m['info_form'] ?? '');
                    $out['info_mail']       = (string)($m['info_mail'] ?? '');
                    $out['form_heading']    = (string)($m['form_heading'] ?? '');
                }
                return $out;
            }
        }
        return null;
    }

    private function buildOrderData(array $okec): array
    {
        $totalData = $this->runTotalExtensions();
        $totals    = $totalData['totals'];
        $total     = (float)$totalData['total'];

        $payment  = $this->session->data['payment_address'];
        $shipping = $this->session->data['shipping_address'] ?? $payment;
        $guest    = $this->session->data['guest'];

        $products = [];
        foreach ($this->cart->getProducts() as $p) {
            $option = [];
            foreach ($p['option'] as $o) {
                $option[] = [
                    'product_option_id'       => $o['product_option_id'] ?? 0,
                    'product_option_value_id' => $o['product_option_value_id'] ?? 0,
                    'option_id'               => $o['option_id'],
                    'option_value_id'         => $o['option_value_id'] ?? 0,
                    'name'                    => $o['name'],
                    'value'                   => $o['value'],
                    'type'                    => $o['type'],
                ];
            }
            $products[] = [
                'product_id' => $p['product_id'],
                'name'       => $p['name'],
                'model'      => $p['model'],
                'option'     => $option,
                'download'   => $p['download'] ?? [],
                'quantity'   => $p['quantity'],
                'subtract'   => $p['subtract'],
                'price'      => $p['price'],
                'total'      => $p['total'],
                'tax'        => $this->tax->getTax($p['price'], $p['tax_class_id']),
                'reward'     => $p['reward'] ?? 0,
            ];
        }

        return [
            'invoice_prefix'          => (string)$this->config->get('config_invoice_prefix'),
            'store_id'                => (int)$this->config->get('config_store_id'),
            'store_name'              => (string)$this->config->get('config_name'),
            'store_url'               => (string)$this->config->get('config_url'),
            'customer_id'             => (int)($this->customer->getId() ?? 0),
            'customer_group_id'       => $guest['customer_group_id'],
            'firstname'               => $guest['firstname'],
            'lastname'                => $guest['lastname'],
            'email'                   => $guest['email'],
            'telephone'               => $guest['telephone'],
            'custom_field'            => [],
            'payment_firstname'       => $payment['firstname'],
            'payment_lastname'        => $payment['lastname'],
            'payment_company'         => $payment['company'],
            'payment_address_1'       => $payment['address_1'],
            'payment_address_2'       => $payment['address_2'],
            'payment_city'            => $payment['city'],
            'payment_postcode'        => $payment['postcode'],
            'payment_zone'            => $payment['zone'],
            'payment_zone_id'         => $payment['zone_id'],
            'payment_country'         => $payment['country'],
            'payment_country_id'      => $payment['country_id'],
            'payment_address_format'  => $payment['address_format'],
            'payment_custom_field'    => [],
            'payment_method'          => $this->session->data['payment_method']['title'] ?? '',
            'payment_code'            => $this->session->data['payment_method']['code']  ?? '',
            'shipping_firstname'      => $shipping['firstname'],
            'shipping_lastname'       => $shipping['lastname'],
            'shipping_company'        => $shipping['company'],
            'shipping_address_1'      => $shipping['address_1'],
            'shipping_address_2'      => $shipping['address_2'],
            'shipping_city'           => $shipping['city'],
            'shipping_postcode'       => $shipping['postcode'],
            'shipping_zone'           => $shipping['zone'],
            'shipping_zone_id'        => $shipping['zone_id'],
            'shipping_country'        => $shipping['country'],
            'shipping_country_id'     => $shipping['country_id'],
            'shipping_address_format' => $shipping['address_format'],
            'shipping_custom_field'   => [],
            'shipping_method'         => $this->session->data['shipping_method']['title'] ?? '',
            'shipping_code'           => $this->session->data['shipping_method']['code']  ?? '',
            'products'                => $products,
            'totals'                  => $totals,
            'comment'                 => $this->session->data['comment'] ?? '',
            'total'                   => $total,
            'affiliate_id'            => 0,
            'commission'              => 0,
            'marketing_id'            => 0,
            'tracking'                => '',
            'language_id'             => (int)$this->config->get('config_language_id'),
            'currency_id'             => (int)$this->currency->getId($this->session->data['currency'] ?? $this->config->get('config_currency')),
            'currency_code'           => (string)($this->session->data['currency'] ?? $this->config->get('config_currency')),
            'currency_value'          => (float)$this->currency->getValue($this->session->data['currency'] ?? $this->config->get('config_currency')),
            'ip'                      => (string)$this->request->server['REMOTE_ADDR'],
            'forwarded_ip'            => (string)($this->request->server['HTTP_X_FORWARDED_FOR'] ?? $this->request->server['HTTP_CLIENT_IP'] ?? ''),
            'user_agent'              => (string)($this->request->server['HTTP_USER_AGENT'] ?? ''),
            'accept_language'         => (string)($this->request->server['HTTP_ACCEPT_LANGUAGE'] ?? ''),
        ];
    }

    /**
     * Валідує значення custom-поля за rules-конфігом, який зберігає admin
     * Fields-section: array of { type, params, error_text:{lang_id:msg} }.
     * Типи: length (min/max), regex (pattern), match (field_code), api (skip).
     * Повертає текст помилки поточною мовою або null.
     */
    private function validateCustomField(string $value, array $rules, array $okec = []): ?string
    {
        if (empty($rules)) return null;

        // Якщо rules — асоціативний масив (legacy flat-format), конвертуємо у list-формат
        if (!array_is_list($rules)) {
            $rules = $this->migrateLegacyRules($rules);
        }

        $value = trim($value);
        $langId = (int)$this->config->get('config_language_id');

        $msgFor = function (array $rule, string $fallback) use ($langId): string {
            $msgs = is_array($rule['error_text'] ?? null) ? $rule['error_text'] : [];
            $cur  = (string)($msgs[$langId] ?? '');
            if ($cur !== '') return $cur;
            // Fallback: будь-яке непорожнє повідомлення
            foreach ($msgs as $m) if (!empty($m)) return (string)$m;
            return $fallback;
        };

        foreach ($rules as $rule) {
            if (!is_array($rule)) continue;
            $type = (string)($rule['type'] ?? '');
            $p    = is_array($rule['params'] ?? null) ? $rule['params'] : [];

            switch ($type) {
                case 'length':
                    if ($value === '') break; // не required — пропускаємо
                    $min = isset($p['min']) ? (int)$p['min'] : null;
                    $max = isset($p['max']) ? (int)$p['max'] : null;
                    if ($min !== null && mb_strlen($value) < $min) {
                        return $msgFor($rule, sprintf('Minimum %d characters', $min));
                    }
                    if ($max !== null && mb_strlen($value) > $max) {
                        return $msgFor($rule, sprintf('Maximum %d characters', $max));
                    }
                    break;

                case 'regex':
                    if ($value === '') break;
                    $pattern = (string)($p['pattern'] ?? '');
                    if ($pattern === '') break;
                    if (!preg_match('~^/.*/[a-z]*$~i', $pattern)) {
                        $pattern = '~' . str_replace('~', '\\~', $pattern) . '~u';
                    }
                    if (@preg_match($pattern, '') !== false && !preg_match($pattern, $value)) {
                        return $msgFor($rule, 'Invalid format');
                    }
                    break;

                case 'match':
                    $other = (string)($okec[(string)($p['field_code'] ?? '')] ?? '');
                    if ($value !== $other) {
                        return $msgFor($rule, 'Values do not match');
                    }
                    break;

                case 'api':
                    // Server-side endpoint валідація — поки що skip (потребує окремий resolver)
                    break;
            }
        }
        return null;
    }

    /** Legacy flat-format → list format (для backward-compat з раніше створеними fields) */
    private function migrateLegacyRules(array $flat): array
    {
        $out = [];
        if (isset($flat['min_length']) || isset($flat['max_length'])) {
            $out[] = ['type' => 'length', 'params' => [
                'min' => $flat['min_length'] ?? null, 'max' => $flat['max_length'] ?? null,
            ], 'error_text' => []];
        }
        if (!empty($flat['regex'])) {
            $out[] = ['type' => 'regex', 'params' => ['pattern' => $flat['regex']], 'error_text' => []];
        }
        return $out;
    }

    /**
     * Створює customer-а через OC `model_account_customer::addCustomer()`
     * та логінить його. Запускається при `okec[register]=1` + password,
     * якщо email ще не зареєстровано.
     */
    /**
     * Зберігає поточну адресу в address_book логіну customer-а, якщо ще не існує
     * аналогічного запису (порівняння по address_1 + city + postcode + country_id).
     * Викликається на confirm() для logged-in users.
     */
    private function maybeSaveAddress(array $okec): void
    {
        if (!$this->customer->isLogged()) return;
        $this->load->model('account/address');

        $addr = [
            'firstname'  => (string)($okec['firstname']  ?? ''),
            'lastname'   => (string)($okec['lastname']   ?? ''),
            'company'    => (string)($okec['company']    ?? ''),
            'address_1'  => (string)($okec['address_1']  ?? ''),
            'address_2'  => (string)($okec['address_2']  ?? ''),
            'city'       => (string)($okec['city']       ?? ''),
            'postcode'   => (string)($okec['postcode']   ?? ''),
            'country_id' => (int)   ($okec['country_id'] ?? 0),
            'zone_id'    => (int)   ($okec['zone_id']    ?? 0),
            'default'    => 0,
            'custom_field' => [],
        ];
        if ($addr['address_1'] === '' || $addr['country_id'] === 0) return;

        // Dedup: чи є вже запис з такими ключовими полями?
        foreach ($this->model_account_address->getAddresses() as $existing) {
            if (mb_strtolower((string)$existing['address_1']) === mb_strtolower($addr['address_1'])
                && mb_strtolower((string)$existing['city']) === mb_strtolower($addr['city'])
                && (string)$existing['postcode'] === $addr['postcode']
                && (int)$existing['country_id'] === $addr['country_id']) {
                return; // дублікат — skip
            }
        }
        $this->model_account_address->addAddress($addr);
    }

    /**
     * Preview-токен — генерується адмінкою. Зберігається в `oc_kit_easycheckout_settings`:
     *   - serialized=0 + value=unix-timestamp → simple TTL token (preview default layout)
     *   - serialized=1 + value=JSON {expires, layout} → token містить layout-snapshot
     * Повертає false якщо невалідний/expired. Snapshot лишається в session для рендеру.
     */
    private function isAdminPreviewToken(string $token): bool
    {
        $token = preg_replace('~[^a-f0-9]~i', '', $token);
        if (mb_strlen($token) !== 32) return false;
        $row = $this->db->query("SELECT `value`, `serialized` FROM `" . DB_PREFIX . "kit_easycheckout_settings`
            WHERE `code`='preview_tokens'
              AND `key`='" . $this->db->escape($token) . "'
            LIMIT 1");
        if (!$row->num_rows) return false;

        if ((int)$row->row['serialized']) {
            $payload = json_decode((string)$row->row['value'], true);
            if (!is_array($payload) || (int)($payload['expires'] ?? 0) < time()) return false;
            // Pre-кладемо layout у session.data щоб index() забрав замість DB-version
            if (!empty($payload['layout']) && is_array($payload['layout'])) {
                $this->session->data['okec_preview_layout'] = $payload['layout'];
            }
            return true;
        }

        unset($this->session->data['okec_preview_layout']);
        return (int)$row->row['value'] >= time();
    }

    /**
     * Mock-products для preview-режиму (3 товара з картинкою-плейсхолдером).
     */
    private function mockPreviewCartProducts(): array
    {
        $img = 'image/catalog/no_image.png';
        $base = [
            ['name' => 'Sample product A', 'model' => 'A-001', 'qty' => 1, 'price' => 199.99],
            ['name' => 'Sample product B', 'model' => 'B-002', 'qty' => 2, 'price' => 49.50],
            ['name' => 'Sample product C', 'model' => 'C-003', 'qty' => 1, 'price' => 12.00],
        ];
        $out = [];
        foreach ($base as $i => $p) {
            $out[] = [
                'cart_id'  => 'preview-' . ($i + 1),
                'product_id' => 0,
                'name'     => $p['name'],
                'model'    => $p['model'],
                'href'     => '#',
                'thumb'    => $img,
                'options'  => [],
                'quantity' => $p['qty'],
                'minimum'  => 1,
                'total'    => number_format($p['price'] * $p['qty'], 2),
            ];
        }
        return $out;
    }

    private function mockPreviewTotals(): array
    {
        return [
            ['code' => 'sub_total', 'title' => 'Sub-Total',     'text' => '361.49'],
            ['code' => 'shipping',  'title' => 'Shipping',      'text' => '50.00'],
            ['code' => 'total',     'title' => 'Total',         'text' => '411.49'],
        ];
    }

    /**
     * Recovery-flow: ?recover={token} — підтягуємо abandoned snapshot:
     *   - відновлюємо session.data['guest']/payment_address (firstname, email, phone…)
     *   - відновлюємо товари кошика (з abandoned_products), якщо cart порожній
     *   - reuse того самого recovery_token у session → новий confirm зробить markRecovered
     */
    private function applyRecoveryToken(string $token): void
    {
        $token = preg_replace('~[^a-f0-9]~i', '', $token);
        if (mb_strlen($token) !== 32) return;

        $row = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kit_easycheckout_abandoned`
            WHERE `recovery_token` = '" . $this->db->escape($token) . "'
              AND `recovered_order_id` IS NULL
            LIMIT 1");
        if (!$row->num_rows) return;
        $a = $row->row;

        // Відновлюємо session-стан з snapshot-у
        $this->session->data['okec_abandoned_token'] = $token;
        $this->session->data['guest'] = [
            'customer_group_id' => (int)$this->config->get('config_customer_group_id'),
            'firstname'         => (string)$a['firstname'],
            'lastname'          => (string)$a['lastname'],
            'email'             => (string)$a['email'],
            'telephone'         => (string)$a['telephone'],
            'custom_field'      => [],
        ];

        // Відновлення товарів — лише якщо cart порожній (щоб не перезаписувати поточний)
        if (!$this->cart->hasProducts()) {
            $prodsRow = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kit_easycheckout_abandoned_products`
                WHERE `abandoned_id` = " . (int)$a['abandoned_id']);
            foreach ($prodsRow->rows as $p) {
                $opt = $p['option_data'] ? (json_decode((string)$p['option_data'], true) ?: []) : [];
                $this->cart->add((int)$p['product_id'], (int)$p['quantity'], $opt);
            }
        }
    }

    /**
     * Зберігає (upsert) abandoned-checkout запис на основі поточного state.
     * Використовує session-token як стійкий ID між різними request-ами однієї сесії.
     */
    private function trackAbandoned(array $okec): void
    {
        if (empty($this->session->data['okec_abandoned_token'])) {
            $this->session->data['okec_abandoned_token'] = bin2hex(random_bytes(16));
        }
        $token = (string)$this->session->data['okec_abandoned_token'];

        $ec = new EasyCheckout($this->registry);
        $ec->setStore((int)$this->config->get('config_store_id'));

        $this->load->model('extension/easycheckout/abandoned');
        $this->model_extension_easycheckout_abandoned->track(
            $okec,
            (int)$this->config->get('config_store_id'),
            $this->resolveGroupId($ec),
            (int)($this->customer->getId() ?? 0),
            $token
        );
    }

    private function markAbandonedRecovered(int $orderId): void
    {
        $token = (string)($this->session->data['okec_abandoned_token'] ?? '');
        if ($token === '') return;
        $this->load->model('extension/easycheckout/abandoned');
        $this->model_extension_easycheckout_abandoned->markRecovered($token, $orderId);
        unset($this->session->data['okec_abandoned_token']);
    }

    /**
     * Резолвить ISO-2 країни за country_id з POST. Використовується раніше
     * ніж setSessionAddresses (перед валідацією).
     */
    private function resolveCountryIso(int $countryId): string
    {
        if (!$countryId) return '';
        $this->load->model('localisation/country');
        $c = $this->model_localisation_country->getCountry($countryId);
        return (string)($c['iso_code_2'] ?? '');
    }

    /**
     * Нормалізація телефону через PhoneMasks-реєстр. Без countryIso2 → UA-fallback
     * (legacy behavior). Якщо у session є country — використовуємо його ISO.
     */
    private function normalizePhone(string $raw): string
    {
        require_once DIR_SYSTEM . 'library/ockit/easycheckout/libs/PhoneMasks.php';
        $iso = (string)($this->session->data['payment_address']['iso_code_2']  ?? '')
             ?: (string)($this->session->data['shipping_address']['iso_code_2'] ?? '');
        return \OcKit\EasyCheckout\Libs\PhoneMasks::normalize($raw, $iso);
    }

    /**
     * Збирає коди (oc_field для native, code для custom) required-полів.
     * Враховує condition: якщо умова не виконується (поле приховано) — required-skip.
     * Custom-fields теж збираються, з прив'язкою до custom code; повертається asocмасив
     * з ключем 'native' (масив oc_field-кодів) і 'custom' (масив code-strings).
     */
    private function resolveRequiredFieldCodes(array $okec = []): array
    {
        $ec     = new EasyCheckout($this->registry);
        $ec->setStore((int)$this->config->get('config_store_id'));
        $ec->setGroup($this->resolveGroupId($ec));
        $layout = $ec->getPageLayoutRepository()->get('checkout');

        $hasShipping = $this->cart->hasShipping();
        $native = [];
        $custom = [];

        foreach ($layout['steps'] ?? [] as $step) {
            foreach ($step['rows'] ?? [] as $row) {
                foreach ($row['cells'] ?? [] as $cell) {
                    foreach ($cell['blocks'] ?? [] as $block) {
                        $type = $block['type'] ?? '';
                        if (!$hasShipping && in_array($type, ['shipping_address', 'shipping'], true)) continue;

                        // Block-level condition — skip required перевірку для всього блоку
                        if (!$this->evalFieldCondition($block['settings']['condition'] ?? null, $okec)) continue;

                        foreach (($block['settings']['fields'] ?? []) as $cfg) {
                            if (empty($cfg['required'])) continue;
                            // Skip приховані за condition
                            if (!$this->evalFieldCondition($cfg['condition'] ?? null, $okec)) continue;

                            $fid = (int)($cfg['field_id'] ?? 0);
                            if ($fid < 0) {
                                $info = \OcKit\EasyCheckout\Libs\NativeFieldsRegistry::findById($fid);
                                if ($info) $native[$info['oc_field']] = true;
                            } else {
                                // Custom field: знаходимо code за field_id
                                foreach ($ec->getFieldsRepository()->list(['limit' => 500]) as $f) {
                                    if ((int)$f['field_id'] === $fid) {
                                        $custom[(string)$f['code']] = true;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return ['native' => array_keys($native), 'custom' => array_keys($custom)];
    }

    /**
     * Оцінює condition-об'єкт: повертає true якщо field має бути visible.
     * Null/empty condition завжди true.
     */
    private function evalFieldCondition(?array $cond, array $okec): bool
    {
        if (!$cond) return true;

        // Нормалізація: legacy {source_code,operator,value} → {match,rules[]}
        if (!isset($cond['rules']) || !is_array($cond['rules'])) {
            if (empty($cond['source_code'])) return true;
            $cond = ['match' => 'all', 'rules' => [[
                'source_code' => $cond['source_code'],
                'operator'    => $cond['operator'] ?? '==',
                'value'       => $cond['value'] ?? '',
            ]]];
        }
        if (!$cond['rules']) return true;

        $match   = ($cond['match'] ?? 'all') === 'any' ? 'any' : 'all';
        $results = [];
        foreach ($cond['rules'] as $rule) {
            $src = (string)($rule['source_code'] ?? '');
            if ($src === '') { $results[] = true; continue; }
            $results[] = $this->evalConditionRule(
                (string)($okec[$src] ?? ''),
                (string)($rule['operator'] ?? '=='),
                (string)($rule['value'] ?? '')
            );
        }
        return $match === 'any'
            ? in_array(true, $results, true)
            : !in_array(false, $results, true);
    }

    private function evalConditionRule(string $sourceVal, string $op, string $expected): bool
    {
        switch ($op) {
            case '==':        return $sourceVal === $expected;
            case '!=':        return $sourceVal !== $expected;
            case 'not_empty': return $sourceVal !== '';
            case 'empty':     return $sourceVal === '';
            case 'in':        return in_array($sourceVal, array_map('trim', explode(',', $expected)), true);
        }
        return true;
    }

    /**
     * Чи має сторінка хоч один agreement-блок з required:true?
     */
    private function isAgreementRequired(): bool
    {
        $ec     = new EasyCheckout($this->registry);
        $ec->setStore((int)$this->config->get('config_store_id'));
        $ec->setGroup($this->resolveGroupId($ec));
        $layout = $ec->getPageLayoutRepository()->get('checkout');
        foreach ($layout['steps'] ?? [] as $step) {
            foreach ($step['rows'] ?? [] as $row) {
                foreach ($row['cells'] ?? [] as $cell) {
                    foreach ($cell['blocks'] ?? [] as $block) {
                        if (($block['type'] ?? '') === 'agreement'
                            && !empty($block['settings']['required'])) return true;
                    }
                }
            }
        }
        return false;
    }

    private function createCustomerAccount(array $okec): void
    {
        $email = trim((string)($okec['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return;

        $exists = $this->db->query("SELECT `customer_id` FROM `" . DB_PREFIX . "customer`
            WHERE LOWER(`email`) = '" . $this->db->escape(mb_strtolower($email)) . "' LIMIT 1");
        if ($exists->num_rows) return;

        $this->load->model('account/customer');

        $payment = $this->session->data['payment_address'] ?? [];
        $data = [
            'customer_group_id' => (int)$this->config->get('config_customer_group_id'),
            'firstname'         => (string)($okec['firstname'] ?? ''),
            'lastname'          => (string)($okec['lastname']  ?? ''),
            'email'             => $email,
            'telephone'         => $this->normalizePhone((string)($okec['telephone'] ?? '')),
            'password'          => (string)$okec['password'],
            'agree'             => 1,
            'newsletter'        => !empty($okec['newsletter']) ? 1 : 0,
            'custom_field'      => [],
            'company'           => (string)($payment['company']    ?? ''),
            'address_1'         => (string)($payment['address_1']  ?? ''),
            'address_2'         => (string)($payment['address_2']  ?? ''),
            'city'              => (string)($payment['city']       ?? ''),
            'postcode'          => (string)($payment['postcode']   ?? ''),
            'country_id'        => (int)   ($payment['country_id'] ?? 0),
            'zone_id'           => (int)   ($payment['zone_id']    ?? 0),
        ];
        $this->model_account_customer->addCustomer($data);
        $this->customer->login($email, (string)$okec['password']);
    }

    /**
     * Записує значення custom (oc-kit) полів у `oc_kit_easycheckout_order_fields`.
     * Native OC-поля вже потрапляють в order через addOrder() — їх не дублюємо.
     */
    private function saveCustomFieldValues(int $orderId, array $okec): void
    {
        if (!$orderId) return;
        $ec      = new EasyCheckout($this->registry);
        $fields  = $ec->getFieldsRepository()->list(['limit' => 500]);

        // Збираємо коди наших custom-полів, які є в реєстрі
        $customCodes = [];
        foreach ($fields as $f) $customCodes[(string)$f['code']] = (int)$f['field_id'];

        // Чистимо старі (на випадок повторного збереження)
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_order_fields`
            WHERE `order_id` = " . (int)$orderId);

        foreach ($okec as $code => $value) {
            if (!isset($customCodes[$code])) continue;          // лише custom (не native)
            $value = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
            $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_order_fields`
                SET `order_id`   = " . (int)$orderId . ",
                    `field_code` = '" . $this->db->escape((string)$code) . "',
                    `value`      = '" . $this->db->escape($value) . "'");
        }
    }

    /**
     * Сторінка-обгортка для рендеру payment-form з боку платіжного модуля.
     * Викликається після successful confirm; модуль сам оформляє redirect/POST.
     */
    public function payment(): void
    {
        if (empty($this->session->data['order_id']) || empty($this->session->data['payment_method']['code'])) {
            $this->response->redirect($this->url->link('checkout/easycheckout', '', true));
            return;
        }

        $this->load->language('easycheckout/checkout');
        $code = $this->session->data['payment_method']['code'];

        $payment_form = $this->load->controller('extension/payment/' . $code);

        // Підтягуємо короткий summary order-у для відображення на сторінці
        $orderId = (int)$this->session->data['order_id'];
        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($orderId);

        $orderSummary = $orderInfo ? [
            'order_id'   => (int)$orderInfo['order_id'],
            'total'      => $this->currency->format(
                (float)$orderInfo['total'],
                (string)$orderInfo['currency_code'],
                (float)$orderInfo['currency_value']
            ),
            'email'      => (string)$orderInfo['email'],
            'firstname'  => (string)$orderInfo['firstname'],
            'lastname'   => (string)$orderInfo['lastname'],
            'payment'    => (string)$orderInfo['payment_method'],
            'shipping'   => (string)($orderInfo['shipping_method'] ?? ''),
        ] : null;

        $data = [
            'heading_title' => $this->language->get('text_payment_title') ?: 'Complete payment',
            'order_id'      => $orderId,
            'order_summary' => $orderSummary,
            'payment_form'  => is_string($payment_form) ? $payment_form : '',
            'text_order_summary' => $this->language->get('text_order_summary') ?: 'Order details',
            'text_total'    => $this->language->get('text_total')   ?: 'Total',
            'text_payment'  => $this->language->get('text_payment') ?: 'Payment',
            'text_shipping' => $this->language->get('text_shipping')?: 'Shipping',
            'text_back_to_checkout' => $this->language->get('text_back_to_checkout') ?: 'Back to checkout',
            'breadcrumbs'   => [
                ['text' => $this->language->get('text_home') ?: 'Home',          'href' => $this->url->link('common/home')],
                ['text' => $this->language->get('heading_title'),                'href' => $this->url->link('checkout/easycheckout', '', true)],
                ['text' => $this->language->get('text_payment_title') ?: 'Pay',  'href' => $this->url->link('checkout/easycheckout/payment', '', true)],
            ],
            'header'        => $this->load->controller('common/header'),
            'footer'        => $this->load->controller('common/footer'),
            'column_left'   => $this->load->controller('common/column_left'),
            'column_right'  => $this->load->controller('common/column_right'),
            'content_top'   => $this->load->controller('common/content_top'),
            'content_bottom'=> $this->load->controller('common/content_bottom'),
        ];

        $this->response->setOutput($this->load->view('easycheckout/payment', $data));
    }

    private function jsonResponse(array $data): void
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
