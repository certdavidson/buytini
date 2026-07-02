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

class ModelExtensionModuleOcKitEasycheckout extends Model
{
    private ?EasyCheckout $lib = null;

    private function getLib(): EasyCheckout
    {
        if ($this->lib === null) {
            $this->lib = new EasyCheckout($this->registry);
        }
        return $this->lib;
    }

    public function install(): void
    {
        $this->getLib()->install();
        // Інтеграція (seo_url + redirect event) лишається ОПЦІЙНОЮ —
        // користувач явно активує кнопкою з admin UI.
    }

    public function uninstall(): void
    {
        $this->getLib()->uninstall();
        // Прибираємо інтеграцію якщо була активна
        $this->removeIntegration();
    }

    // ─── Frontend integration (seo_url + redirect event) ─────────────────

    public const EVENT_CODE = 'oc_kit_easycheckout_redirect';
    public const SEO_QUERY  = 'checkout/easycheckout';
    public const SEO_KEYWORD = 'easycheckout';

    /**
     * Активує інтеграцію з фронтом:
     *  1. Реєструє SEO URL «/easycheckout» (для кожної мови, якщо ще нема).
     *  2. Реєструє OC event «catalog/controller/checkout/checkout/before»
     *     → catalog/easycheckout/redirect/fromStandardCheckout, який редіректить
     *     стандартний checkout на наш.
     * Ідемпотентно — повторний виклик не дублює.
     */
    public function setupIntegration(): array
    {
        $report = ['seo_url' => 0, 'event' => false];

        // 1. SEO URL — по одному запису per language
        $this->load->model('localisation/language');
        foreach ($this->model_localisation_language->getLanguages() as $lang) {
            $exists = $this->db->query("SELECT `seo_url_id` FROM `" . DB_PREFIX . "seo_url`
                WHERE `query` = '" . $this->db->escape(self::SEO_QUERY) . "'
                  AND `language_id` = " . (int)$lang['language_id'] . "
                  AND `store_id` = 0
                LIMIT 1");
            if ($exists->num_rows) continue;
            $this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET
                `store_id`    = 0,
                `language_id` = " . (int)$lang['language_id'] . ",
                `query`       = '" . $this->db->escape(self::SEO_QUERY) . "',
                `keyword`     = '" . $this->db->escape(self::SEO_KEYWORD) . "'");
            $report['seo_url']++;
        }

        // 2. OC event — register if missing
        $eventRow = $this->db->query("SELECT `event_id` FROM `" . DB_PREFIX . "event`
            WHERE `code` = '" . $this->db->escape(self::EVENT_CODE) . "' LIMIT 1");
        if (!$eventRow->num_rows) {
            $this->load->model('setting/event');
            $this->model_setting_event->addEvent(
                self::EVENT_CODE,
                'catalog/controller/checkout/checkout/before',
                'extension/easycheckout/redirect/fromStandardCheckout'
            );
            $report['event'] = true;
        }

        // 3. OCMOD refresh — щоб admin order tab та інші патчі підхопились
        $this->refreshModifications();

        return $report;
    }

    /**
     * Знімає інтеграцію — видаляє seo_url-записи й OC event.
     */
    public function removeIntegration(): array
    {
        $report = ['seo_url' => 0, 'event' => false];

        // 1. SEO URLs
        $this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url`
            WHERE `query` = '" . $this->db->escape(self::SEO_QUERY) . "'");
        $report['seo_url'] = (int)$this->db->countAffected();

        // 2. Event
        $eventRow = $this->db->query("SELECT `event_id` FROM `" . DB_PREFIX . "event`
            WHERE `code` = '" . $this->db->escape(self::EVENT_CODE) . "' LIMIT 1");
        if ($eventRow->num_rows) {
            $this->load->model('setting/event');
            $this->model_setting_event->deleteEventByCode(self::EVENT_CODE);
            $report['event'] = true;
        }
        return $report;
    }

    /**
     * Повертає статус інтеграції — для UI індикатора в admin General секції.
     */
    public function integrationStatus(): array
    {
        $this->load->model('localisation/language');
        $totalLangs = count($this->model_localisation_language->getLanguages());

        $seoCount = (int)$this->db->query("SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "seo_url`
            WHERE `query` = '" . $this->db->escape(self::SEO_QUERY) . "'
              AND `store_id` = 0")->row['cnt'];

        $eventActive = (bool)$this->db->query("SELECT `event_id` FROM `" . DB_PREFIX . "event`
            WHERE `code` = '" . $this->db->escape(self::EVENT_CODE) . "'
              AND `status` = 1 LIMIT 1")->num_rows;

        return [
            'seo_url'       => $seoCount,
            'seo_url_total' => $totalLangs,
            'event'         => $eventActive,
            'is_active'     => $seoCount === $totalLangs && $eventActive,
        ];
    }

    /**
     * Викликає OC-шний refresh модифікацій (rebuild DIR_MODIFICATION).
     * Без цього щойно додані .ocmod.xml не активуються до ручного refresh-у.
     * Обережно — operation тривалий (читає всі OCMOD).
     */
    private function refreshModifications(): void
    {
        try {
            $this->load->controller('marketplace/modification/refresh');
        } catch (\Throwable $e) {
            // silent — не блокуємо setup при помилці
        }
    }

    /**
     * Повертає кастомні (oc-kit) поля, збережені для конкретного замовлення,
     * збагачені людською назвою (поточна мова адмінки), типом і belongs_to.
     * Native OC-поля не дублюються — їх показ робить стандартний admin order.
     *
     * @return array<int, array{field_code: string, value: string, name: string, type: string, belongs_to: string}>
     */
    public function getOrderCustomFields(int $orderId): array
    {
        if (!$orderId) return [];

        $rows = $this->db->query("SELECT v.`field_code`, v.`value`,
                                         f.`type`, f.`belongs_to`,
                                         d.`name`
            FROM `" . DB_PREFIX . "kit_easycheckout_order_fields` v
            LEFT JOIN `" . DB_PREFIX . "kit_easycheckout_fields`             f ON f.`code`     = v.`field_code`
            LEFT JOIN `" . DB_PREFIX . "kit_easycheckout_fields_description` d ON d.`field_id` = f.`field_id`
                                                                              AND d.`language_id` = " . (int)$this->config->get('config_language_id') . "
            WHERE v.`order_id` = " . (int)$orderId . "
            ORDER BY v.`id`");

        $out = [];
        foreach ($rows->rows as $r) {
            $value     = (string)$r['value'];
            $fileLink  = '';
            $fileName  = '';
            // Якщо тип file — резолвимо token у назву файлу/посилання
            if (($r['type'] ?? '') === 'file' && $value !== '') {
                $up = $this->db->query("SELECT `name`, `filename` FROM `" . DB_PREFIX . "upload`
                    WHERE `code` = '" . $this->db->escape($value) . "' LIMIT 1");
                if ($up->num_rows) {
                    $fileName = (string)$up->row['name'];
                    $fileLink = $this->url->link('tool/upload/download', 'user_token=' . ($this->session->data['user_token'] ?? '') . '&code=' . urlencode($value), true);
                }
            }
            $out[] = [
                'field_code' => (string)$r['field_code'],
                'value'      => $value,
                'name'       => (string)($r['name'] ?: $r['field_code']),
                'type'       => (string)($r['type'] ?: ''),
                'belongs_to' => (string)($r['belongs_to'] ?: ''),
                'file_link'  => $fileLink,
                'file_name'  => $fileName,
            ];
        }
        return $out;
    }

    /**
     * Рендерить HTML-фрагмент для admin order info tab — таблиця з custom-полями
     * та посиланнями на завантаження для file-полів. Повертає '' якщо полів нема.
     */
    public function renderOrderCustomFieldsHtml(int $orderId): string
    {
        $rows = $this->getOrderCustomFields($orderId);
        if (!$rows) return '';

        $this->load->language('extension/module/oc_kit_easycheckout');
        $lblField = $this->language->get('text_order_tab_col_field') ?: 'Field';
        $lblValue = $this->language->get('text_order_tab_col_value') ?: 'Value';
        $lblType  = $this->language->get('text_order_tab_col_type')  ?: 'Type';
        $btnSave  = $this->language->get('button_save')              ?: 'Save';
        $btnPrint = $this->language->get('button_print')             ?: 'Print';

        $token   = (string)($this->session->data['user_token'] ?? '');
        $saveUrl = html_entity_decode($this->url->link(
            'extension/module/oc_kit_easycheckout/orderFieldsSave',
            'user_token=' . $token, true
        ));
        $printUrl = html_entity_decode($this->url->link(
            'extension/module/oc_kit_easycheckout/orderPrint',
            'user_token=' . $token . '&order_id=' . (int)$orderId, true
        ));

        $html  = '<div class="okec-order-tab" data-okec-order-id="' . (int)$orderId . '">';
        $html .= '<table class="table table-bordered table-hover" style="margin-top:8px;">';
        $html .= '<thead><tr>'
              . '<th style="width:30%;">' . htmlspecialchars($lblField, ENT_QUOTES, 'UTF-8') . '</th>'
              . '<th style="width:55%;">' . htmlspecialchars($lblValue, ENT_QUOTES, 'UTF-8') . '</th>'
              . '<th style="width:15%;">' . htmlspecialchars($lblType,  ENT_QUOTES, 'UTF-8') . '</th>'
              . '</tr></thead><tbody>';

        foreach ($rows as $r) {
            $code  = (string)$r['field_code'];
            $value = (string)$r['value'];
            $type  = (string)$r['type'];

            // Файлові поля редагувати не дозволяємо (потрібен upload-flow) — показуємо link readonly
            if ($type === 'file' && !empty($r['file_link'])) {
                $editor = '<a href="' . htmlspecialchars((string)$r['file_link'], ENT_QUOTES, 'UTF-8') . '">'
                        . '<i class="fa fa-download"></i> '
                        . htmlspecialchars((string)($r['file_name'] ?: $value), ENT_QUOTES, 'UTF-8')
                        . '</a> <small class="text-muted">(read-only)</small>';
            } elseif ($type === 'textarea' || $type === 'html') {
                $editor = '<textarea class="form-control" rows="3" data-okec-order-field="'
                        . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '">'
                        . htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
                        . '</textarea>';
            } else {
                // text / select / radio / etc — редагується як простий input
                $editor = '<input type="text" class="form-control" data-okec-order-field="'
                        . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '" '
                        . 'value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '">';
            }

            $html .= '<tr>'
                  . '<td><strong>' . htmlspecialchars((string)$r['name'], ENT_QUOTES, 'UTF-8') . '</strong>'
                  .   '<br><small class="text-muted"><code>' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</code></small></td>'
                  . '<td>' . $editor . '</td>'
                  . '<td><span class="label label-default">' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '</span>'
                  .   ($r['belongs_to'] ? ' <small class="text-muted">/ ' . htmlspecialchars((string)$r['belongs_to'], ENT_QUOTES, 'UTF-8') . '</small>' : '')
                  . '</td>'
                  . '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<div style="text-align:right;margin-top:10px;">';
        $html .= '<a href="' . htmlspecialchars($printUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" '
              . 'class="btn btn-default" style="margin-right:8px;">'
              . '<i class="fa fa-print"></i> ' . htmlspecialchars($btnPrint, ENT_QUOTES, 'UTF-8') . '</a>';
        $html .= '<button type="button" class="btn btn-primary" data-okec-save-order-fields="'
              . htmlspecialchars($saveUrl, ENT_QUOTES, 'UTF-8') . '">'
              . '<i class="fa fa-save"></i> ' . htmlspecialchars($btnSave, ENT_QUOTES, 'UTF-8') . '</button>';
        $html .= '<span class="okec-order-tab__status" style="margin-left:10px;color:#888;"></span>';
        $html .= '</div></div>';

        // Inline JS — без зовнішніх deps, vanilla fetch
        $html .= '<script>(function(){
            var btn = document.querySelector(\'[data-okec-save-order-fields]\');
            if (!btn || btn.dataset._bound) return;
            btn.dataset._bound = "1";
            btn.addEventListener("click", function () {
                var wrap = btn.closest(".okec-order-tab");
                var orderId = wrap.getAttribute("data-okec-order-id");
                var status  = wrap.querySelector(".okec-order-tab__status");
                var fields  = {};
                wrap.querySelectorAll("[data-okec-order-field]").forEach(function (el) {
                    fields[el.getAttribute("data-okec-order-field")] = el.value;
                });
                btn.disabled = true; status.textContent = "...";
                var fd = new URLSearchParams();
                fd.append("order_id", orderId);
                Object.keys(fields).forEach(function (k) { fd.append("fields[" + k + "]", fields[k]); });
                fetch(btn.getAttribute("data-okec-save-order-fields"), {
                    method: "POST", credentials: "same-origin",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: fd
                })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    btn.disabled = false;
                    status.textContent = (d && d.success) ? "✓" : ((d && d.message) || "Error");
                    status.style.color = (d && d.success) ? "#1aa05a" : "#c62828";
                    setTimeout(function () { status.textContent = ""; }, 3000);
                })
                .catch(function () {
                    btn.disabled = false;
                    status.textContent = "Network error"; status.style.color = "#c62828";
                });
            });
        }());</script>';

        return $html;
    }

    /** Зберігає edits з admin order tab. Очікує { order_id, fields: {code: value} }. */
    public function saveOrderCustomFields(int $orderId, array $fields): void
    {
        if (!$orderId || !$fields) return;
        foreach ($fields as $code => $value) {
            $code = preg_replace('~[^a-z0-9_-]~i', '', (string)$code);
            if ($code === '') continue;
            $value = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;

            // Upsert: спочатку UPDATE, якщо нема — INSERT
            $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_order_fields`
                WHERE `order_id` = " . (int)$orderId . "
                  AND `field_code` = '" . $this->db->escape($code) . "'");
            if ($value !== '') {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_order_fields`
                    SET `order_id`   = " . (int)$orderId . ",
                        `field_code` = '" . $this->db->escape($code) . "',
                        `value`      = '" . $this->db->escape($value) . "'");
            }
        }
    }

    /**
     * Список abandoned-checkouts (без `recovered_order_id`) для admin-секції.
     * @return array<int, array{abandoned_id:int,email:string,firstname:string,lastname:string,
     *                         telephone:string,total:float,currency_code:string,
     *                         date_added:string,date_modified:string,products_count:int}>
     */
    /**
     * @param array $filter — { search: string, status: 'all'|'notified'|'recovered'|'pending', limit, offset }
     */
    public function getAbandoned(int $limit = 100, int $offset = 0, array $filter = []): array
    {
        $where = ['1=1'];
        $status = (string)($filter['status'] ?? 'pending');
        switch ($status) {
            case 'recovered': $where[] = "a.`recovered_order_id` IS NOT NULL"; break;
            case 'notified':  $where[] = "a.`notified_at` IS NOT NULL AND a.`recovered_order_id` IS NULL"; break;
            case 'pending':   $where[] = "a.`recovered_order_id` IS NULL"; break;
            // 'all' — без фільтра
        }
        $search = trim((string)($filter['search'] ?? ''));
        if ($search !== '') {
            $esc = $this->db->escape($search);
            $where[] = "(a.`email` LIKE '%" . $esc . "%' "
                  . "OR a.`telephone` LIKE '%" . $esc . "%' "
                  . "OR CONCAT_WS(' ', a.`firstname`, a.`lastname`) LIKE '%" . $esc . "%')";
        }
        // Amount range
        if (isset($filter['min_total']) && (float)$filter['min_total'] > 0) {
            $where[] = "a.`total` >= " . (float)$filter['min_total'];
        }
        if (isset($filter['max_total']) && (float)$filter['max_total'] > 0) {
            $where[] = "a.`total` <= " . (float)$filter['max_total'];
        }
        $whereSql = implode(' AND ', $where);

        $rows = $this->db->query("SELECT a.*,
                                         (SELECT COUNT(*) FROM `" . DB_PREFIX . "kit_easycheckout_abandoned_products` p
                                          WHERE p.`abandoned_id` = a.`abandoned_id`) AS products_count
            FROM `" . DB_PREFIX . "kit_easycheckout_abandoned` a
            WHERE " . $whereSql . "
            ORDER BY a.`date_modified` DESC
            LIMIT " . (int)$offset . ", " . (int)$limit)->rows;

        // Catalog URL для recovery-link (видимий у admin списку)
        $base = (string)(defined('HTTPS_CATALOG') ? HTTPS_CATALOG : HTTP_CATALOG);
        $base = rtrim($base, '/');

        // Admin URL для recovered → edit order
        $adminToken = (string)($this->session->data['user_token'] ?? '');
        $orderUrlBase = $this->url->link('sale/order/info', 'user_token=' . $adminToken . '&order_id=', true);
        // url->link додає '&' якщо параметр останній — потрібен повний шлях с order_id=N

        return array_map(static function ($r) use ($base, $orderUrlBase) {
            return [
                'abandoned_id'   => (int)$r['abandoned_id'],
                'email'          => (string)$r['email'],
                'firstname'      => (string)$r['firstname'],
                'lastname'       => (string)$r['lastname'],
                'telephone'      => (string)$r['telephone'],
                'total'          => (float)$r['total'],
                'currency_code'  => (string)$r['currency_code'],
                'date_added'     => (string)$r['date_added'],
                'date_modified'  => (string)$r['date_modified'],
                'notified_at'    => $r['notified_at'] ? (string)$r['notified_at'] : '',
                'reminder_count' => (int)($r['reminder_count'] ?? 0),
                'products_count' => (int)$r['products_count'],
                'admin_notes'    => (string)($r['admin_notes'] ?? ''),
                'recovery_url'   => $r['recovery_token']
                    ? $base . '/index.php?route=checkout/easycheckout&recover=' . rawurlencode((string)$r['recovery_token'])
                    : '',
                'recovered_order_id' => $r['recovered_order_id'] ? (int)$r['recovered_order_id'] : null,
                'order_admin_url'    => $r['recovered_order_id']
                    ? html_entity_decode($orderUrlBase . (int)$r['recovered_order_id'], ENT_QUOTES, 'UTF-8')
                    : '',
            ];
        }, $rows);
    }

    /**
     * Повертає товари абандон-кошика конкретного запису.
     * @return array<int, array{product_id:int, name:string, model:string, quantity:int, price:float, options:string}>
     */
    public function getAbandonedProducts(int $abandonedId): array
    {
        if (!$abandonedId) return [];
        $rows = $this->db->query("SELECT ap.*, p.`image` AS product_image
            FROM `" . DB_PREFIX . "kit_easycheckout_abandoned_products` ap
            LEFT JOIN `" . DB_PREFIX . "product` p ON p.`product_id` = ap.`product_id`
            WHERE ap.`abandoned_id` = " . (int)$abandonedId . "
            ORDER BY ap.`id`")->rows;

        $this->load->model('tool/image');

        $out = [];
        foreach ($rows as $r) {
            $opt = '';
            if (!empty($r['option_data'])) {
                $dec = json_decode((string)$r['option_data'], true);
                if (is_array($dec) && $dec) {
                    $parts = [];
                    foreach ($dec as $o) {
                        if (is_array($o)) {
                            $parts[] = (string)($o['name'] ?? '') . ': ' . (string)($o['value'] ?? '');
                        }
                    }
                    $opt = implode('; ', $parts);
                }
            }
            $thumb = '';
            if (!empty($r['product_image']) && is_file(DIR_IMAGE . $r['product_image'])) {
                $thumb = $this->model_tool_image->resize((string)$r['product_image'], 60, 60);
            }
            $out[] = [
                'product_id' => (int)$r['product_id'],
                'name'       => (string)$r['name'],
                'model'      => (string)$r['model'],
                'quantity'   => (int)$r['quantity'],
                'price'      => (float)$r['price'],
                'options'    => $opt,
                'thumb'      => $thumb,
            ];
        }
        return $out;
    }

    /**
     * Стат для Fields-секції: кількість order-фіксацій на кожний field_code.
     * @return array<string,int> {code => count}
     */
    /**
     * Дублює поле зі всіма descriptions, params, rules.
     * Code суфіксується `_copy`, при колізії — `_copy2`, `_copy3`...
     * Повертає новий field_id або 0 при невдачі.
     */
    /**
     * JSON dump усіх fields registry. Включає descriptions/params/validation_rules.
     * Формат портативний — можна импорт-нути в інший магазин.
     */
    /**
     * Конфіг-таблиці, що входять у повний бекап налаштувань.
     * Порядок важливий для import (fields/headings перед settings, бо layout
     * у settings посилається на їхні id). Runtime/order/customer таблиці
     * (abandoned*, order_fields, address_fields, customer_fields) НЕ входять.
     * field_id/heading_id/group_id зберігаються as-is — на них посилається layout.
     */
    private const SETTINGS_BACKUP_TABLES = [
        'kit_easycheckout_groups',
        'kit_easycheckout_fields',
        'kit_easycheckout_fields_description',
        'kit_easycheckout_headings',
        'kit_easycheckout_headings_description',
        'kit_easycheckout_address_formats',
        'kit_easycheckout_order_restrictions',
        'kit_easycheckout_cm_group',
        'kit_easycheckout_cm_method',
        'kit_easycheckout_cm_method_description',
        'kit_easycheckout_cm_subtotal',
        'kit_easycheckout_cm_subtotal_description',
    ];

    /** oc_setting ключі, які НЕ експортуються (домен-залежні / runtime). */
    private const SETTINGS_BACKUP_EXCLUDE_KEYS = [
        'module_oc_kit_easycheckout_license_key',
        'module_oc_kit_easycheckout_license_cache',
        'module_oc_kit_easycheckout_trial_start',
        'module_oc_kit_easycheckout_cron_last_run',
    ];

    /** Повний дамп усіх налаштувань модуля (для export). */
    public function exportAllSettings(): array
    {
        $p   = DB_PREFIX;
        $out = [
            'version'       => 1,
            'module'        => 'oc_kit_easycheckout',
            'exported'      => date('c'),
            'tables'        => [],
            'settings_rows' => [],
            'oc_setting'    => [],
        ];

        foreach (self::SETTINGS_BACKUP_TABLES as $t) {
            $out['tables'][$t] = $this->db->query("SELECT * FROM `{$p}{$t}`")->rows;
        }

        // ConfigStore (page layouts, integration settings) — без домен-залежних рядків
        $out['settings_rows'] = $this->db->query("SELECT * FROM `{$p}kit_easycheckout_settings`
            WHERE `code` NOT LIKE 'license.%'
              AND `code` NOT LIKE 'telemetry.%'
              AND `code` <> 'install'")->rows;

        // oc_setting — module-рядки без license/runtime
        $rows = $this->db->query("SELECT * FROM `{$p}setting`
            WHERE `code` = 'module_oc_kit_easycheckout'")->rows;
        foreach ($rows as $r) {
            if (!in_array((string)$r['key'], self::SETTINGS_BACKUP_EXCLUDE_KEYS, true)) {
                $out['oc_setting'][] = $r;
            }
        }

        return $out;
    }

    /**
     * Відновлює налаштування з дампа (від exportAllSettings).
     * Транзакційно: спершу чистить конфіг-таблиці, потім вставляє.
     * Домен-залежні (license/telemetry/install) та runtime-дані НЕ чіпаються.
     *
     * @return array{success: bool, message: string}
     */
    public function importAllSettings(array $data): array
    {
        if (($data['module'] ?? '') !== 'oc_kit_easycheckout' || !is_array($data['tables'] ?? null)) {
            return ['success' => false, 'message' => 'Invalid backup format'];
        }
        $p = DB_PREFIX;
        $this->db->query("START TRANSACTION");
        try {
            // 1. Конфіг-таблиці — повна заміна (id зберігаються для layout-посилань)
            foreach (self::SETTINGS_BACKUP_TABLES as $t) {
                if (!isset($data['tables'][$t]) || !is_array($data['tables'][$t])) continue;
                $this->db->query("DELETE FROM `{$p}{$t}`");
                foreach ($data['tables'][$t] as $row) {
                    $this->backupInsertRow($t, (array)$row);
                }
            }

            // 2. ConfigStore — замінюємо лише не-домен-залежні рядки
            $this->db->query("DELETE FROM `{$p}kit_easycheckout_settings`
                WHERE `code` NOT LIKE 'license.%'
                  AND `code` NOT LIKE 'telemetry.%'
                  AND `code` <> 'install'");
            foreach (($data['settings_rows'] ?? []) as $row) {
                $this->backupInsertRow('kit_easycheckout_settings', (array)$row, ['setting_id']);
            }

            // 3. oc_setting — замінюємо module-рядки крім виключених
            $exclSql = "'" . implode("','", array_map([$this->db, 'escape'], self::SETTINGS_BACKUP_EXCLUDE_KEYS)) . "'";
            $this->db->query("DELETE FROM `{$p}setting`
                WHERE `code` = 'module_oc_kit_easycheckout' AND `key` NOT IN ({$exclSql})");
            foreach (($data['oc_setting'] ?? []) as $row) {
                $row = (array)$row;
                if (in_array((string)($row['key'] ?? ''), self::SETTINGS_BACKUP_EXCLUDE_KEYS, true)) continue;
                $this->backupInsertRow('setting', $row, ['setting_id']);
            }

            $this->db->query("COMMIT");
            return ['success' => true, 'message' => 'OK'];
        } catch (\Throwable $e) {
            $this->db->query("ROLLBACK");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Generic INSERT з assoc-рядка; $omit — колонки що пропустити (AUTO_INCREMENT PK). */
    private function backupInsertRow(string $table, array $row, array $omit = []): void
    {
        $cols = [];
        $vals = [];
        foreach ($row as $col => $val) {
            if (in_array($col, $omit, true)) continue;
            $cols[] = '`' . str_replace('`', '', (string)$col) . '`';
            $vals[] = $val === null ? 'NULL' : "'" . $this->db->escape((string)$val) . "'";
        }
        if (!$cols) return;
        $this->db->query("INSERT INTO `" . DB_PREFIX . $table . "`
            (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")");
    }

    public function exportFieldsJson(): string
    {
        $repo = $this->getLib()->getFieldsRepository();
        $fields = $repo->list(['limit' => 5000]);
        $out = [];
        foreach ($fields as $f) {
            $out[] = [
                'code'             => (string)$f['code'],
                'type'             => (string)$f['type'],
                'belongs_to'       => (string)$f['belongs_to'],
                'mask_mode'        => (string)$f['mask_mode'],
                'mask_value'       => $f['mask_value'],
                'default_mode'     => (string)$f['default_mode'],
                'default_value'    => $f['default_value'],
                'save_to_comment'  => (int)$f['save_to_comment'],
                'validation_rules' => is_array($f['validation_rules']) ? $f['validation_rules'] : [],
                'params'           => is_array($f['params']) ? $f['params'] : [],
                // descriptions переводимо з language_id у language_code для портативності
                'descriptions'     => $this->descriptionsToCodeKeyed($f['descriptions'] ?? []),
            ];
        }
        return json_encode([
            'version'  => 1,
            'exported' => date('c'),
            'count'    => count($out),
            'fields'   => $out,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Imports JSON (від `exportFieldsJson`). Існуючі fields skip за code.
     * @return array{created: int, skipped: int, errors: array}
     */
    public function importFieldsJson(string $jsonRaw): array
    {
        $payload = json_decode($jsonRaw, true);
        if (!is_array($payload) || !is_array($payload['fields'] ?? null)) {
            return ['created' => 0, 'skipped' => 0, 'errors' => ['Invalid JSON format']];
        }

        // Lang code → id mapping для descriptions
        $this->load->model('localisation/language');
        $langByCode = [];
        foreach ($this->model_localisation_language->getLanguages() as $l) {
            $langByCode[(string)$l['code']] = (int)$l['language_id'];
        }

        $repo = $this->getLib()->getFieldsRepository();
        $created = 0; $skipped = 0; $errors = [];

        foreach ($payload['fields'] as $f) {
            if (!is_array($f) || empty($f['code'])) { $skipped++; continue; }
            $existing = $repo->list(['search' => $f['code'], 'limit' => 50]);
            $hit = false;
            foreach ($existing as $e) if ((string)$e['code'] === $f['code']) { $hit = true; break; }
            if ($hit) { $skipped++; continue; }

            $descs = [];
            foreach (($f['descriptions'] ?? []) as $code => $d) {
                if (!isset($langByCode[$code])) continue;
                $descs[$langByCode[$code]] = [
                    'name'        => (string)($d['name']        ?? ''),
                    'placeholder' => (string)($d['placeholder'] ?? ''),
                    'tooltip'     => (string)($d['tooltip']     ?? ''),
                ];
            }

            try {
                $repo->create([
                    'code'             => (string)$f['code'],
                    'type'             => (string)($f['type'] ?? 'text'),
                    'belongs_to'       => (string)($f['belongs_to'] ?? 'order'),
                    'mask_mode'        => (string)($f['mask_mode'] ?? 'manual'),
                    'mask_value'       => $f['mask_value'] ?? null,
                    'default_mode'     => (string)($f['default_mode'] ?? 'manual'),
                    'default_value'    => $f['default_value'] ?? null,
                    'save_to_comment'  => (int)($f['save_to_comment'] ?? 0),
                    'validation_rules' => is_array($f['validation_rules'] ?? null) ? $f['validation_rules'] : [],
                    'params'           => is_array($f['params'] ?? null) ? $f['params'] : [],
                    'descriptions'     => $descs,
                ]);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = (string)$f['code'] . ': ' . $e->getMessage();
            }
        }
        return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors];
    }

    /** language_id-keyed descriptions → language_code-keyed (для portability). */
    private function descriptionsToCodeKeyed(array $descsById): array
    {
        $this->load->model('localisation/language');
        $codeByLid = [];
        foreach ($this->model_localisation_language->getLanguages() as $l) {
            $codeByLid[(int)$l['language_id']] = (string)$l['code'];
        }
        $out = [];
        foreach ($descsById as $lid => $d) {
            $code = $codeByLid[(int)$lid] ?? '';
            if ($code === '' || !is_array($d)) continue;
            $out[$code] = [
                'name'        => (string)($d['name']        ?? ''),
                'placeholder' => (string)($d['placeholder'] ?? ''),
                'tooltip'     => (string)($d['tooltip']     ?? ''),
            ];
        }
        return $out;
    }

    public function cloneField(int $sourceId): int
    {
        if (!$sourceId) return 0;
        $repo = $this->getLib()->getFieldsRepository();
        $src  = $repo->get($sourceId);
        if (!$src) return 0;

        // Унікальний code
        $base = (string)$src['code'] . '_copy';
        $candidate = $base;
        $i = 2;
        while ($this->codeExists($candidate)) {
            $candidate = $base . $i++;
            if ($i > 100) return 0; // sanity
        }

        // Створюємо через repository — вся schema (rules, params, descriptions) копіюється as-is
        try {
            return $repo->create([
                'code'             => $candidate,
                'type'             => (string)$src['type'],
                'belongs_to'       => (string)$src['belongs_to'],
                'mask_mode'        => (string)$src['mask_mode'],
                'mask_value'       => $src['mask_value'],
                'default_mode'     => (string)$src['default_mode'],
                'default_value'    => $src['default_value'],
                'save_to_comment'  => (int)$src['save_to_comment'],
                'validation_rules' => is_array($src['validation_rules']) ? $src['validation_rules'] : [],
                'params'           => is_array($src['params'])           ? $src['params']           : [],
                'descriptions'     => is_array($src['descriptions'])     ? $src['descriptions']     : [],
            ]);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function codeExists(string $code): bool
    {
        $row = $this->db->query("SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_easycheckout_fields`
            WHERE `code` = '" . $this->db->escape($code) . "'");
        return (int)($row->row['cnt'] ?? 0) > 0;
    }

    /** JSON dump headings registry — портативний, descriptions key-ed by lang_code. */
    public function exportHeadingsJson(): string
    {
        $repo = $this->getLib()->getHeadingsRepository();
        $items = $repo->list(['limit' => 5000]);
        $out = [];
        foreach ($items as $h) {
            $out[] = [
                'code'         => (string)$h['code'],
                'tag'          => (string)$h['tag'],
                'descriptions' => $this->descriptionsToCodeKeyed($h['descriptions'] ?? []),
            ];
        }
        return json_encode([
            'version'  => 1,
            'exported' => date('c'),
            'count'    => count($out),
            'headings' => $out,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function importHeadingsJson(string $jsonRaw): array
    {
        $payload = json_decode($jsonRaw, true);
        if (!is_array($payload) || !is_array($payload['headings'] ?? null)) {
            return ['created' => 0, 'skipped' => 0, 'errors' => ['Invalid JSON']];
        }
        $this->load->model('localisation/language');
        $langByCode = [];
        foreach ($this->model_localisation_language->getLanguages() as $l) {
            $langByCode[(string)$l['code']] = (int)$l['language_id'];
        }
        $repo = $this->getLib()->getHeadingsRepository();
        $created = 0; $skipped = 0; $errors = [];

        foreach ($payload['headings'] as $h) {
            if (!is_array($h) || empty($h['code'])) { $skipped++; continue; }
            if ($repo->getByCode((string)$h['code'])) { $skipped++; continue; }

            $descs = [];
            foreach (($h['descriptions'] ?? []) as $code => $d) {
                if (!isset($langByCode[$code])) continue;
                $descs[$langByCode[$code]] = ['text' => (string)($d['text'] ?? $d['name'] ?? '')];
            }
            try {
                $repo->add([
                    'code'         => (string)$h['code'],
                    'tag'          => (string)($h['tag'] ?? 'h3'),
                    'descriptions' => $descs,
                ]);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = (string)$h['code'] . ': ' . $e->getMessage();
            }
        }
        return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Counts: heading_id → layouts_count. Скільки блоків посилаються на heading.
     * @return array<int,int>
     */
    public function getHeadingUsageInLayouts(): array
    {
        $rows = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "kit_easycheckout_settings`
            WHERE `code` = 'page_layout' AND `serialized` = 1")->rows;

        $counts = [];
        foreach ($rows as $r) {
            $layout = json_decode((string)$r['value'], true);
            if (!is_array($layout)) continue;
            foreach (($layout['steps'] ?? []) as $step) {
                foreach (($step['rows'] ?? []) as $row) {
                    foreach (($row['cells'] ?? []) as $cell) {
                        foreach (($cell['blocks'] ?? []) as $block) {
                            $hid = (int)($block['settings']['heading_id'] ?? 0);
                            if ($hid > 0) {
                                $counts[$hid] = ($counts[$hid] ?? 0) + 1;
                            }
                        }
                    }
                }
            }
        }
        return $counts;
    }

    /**
     * Сканує всі saved-layouts на посилання `block.settings.heading_code` та
     * `block.settings.heading_id`. Headings можуть бути attach-нутi до custom_html
     * або інших info-блоків через ці налаштування. Повертає масив usages.
     */
    public function findHeadingUsages(int $headingId, string $headingCode = ''): array
    {
        $rows = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "kit_easycheckout_settings`
            WHERE `code` = 'page_layout' AND `serialized` = 1")->rows;

        $usages = [];
        foreach ($rows as $r) {
            $layout = json_decode((string)$r['value'], true);
            if (!is_array($layout)) continue;
            foreach (($layout['steps'] ?? []) as $step) {
                foreach (($step['rows'] ?? []) as $row) {
                    foreach (($row['cells'] ?? []) as $cell) {
                        foreach (($cell['blocks'] ?? []) as $block) {
                            $s = $block['settings'] ?? [];
                            $byId   = $headingId   && (int)($s['heading_id']   ?? 0) === $headingId;
                            $byCode = $headingCode !== '' && (string)($s['heading_code'] ?? '') === $headingCode;
                            if ($byId || $byCode) {
                                $usages[] = [
                                    'block_type' => (string)($block['type'] ?? ''),
                                    'block_id'   => (string)($block['id']   ?? ''),
                                ];
                            }
                        }
                    }
                }
            }
        }
        return $usages;
    }

    /**
     * Дублює heading зі всіма descriptions. Code → `{code}_copy[N]` (unique).
     */
    public function cloneHeading(int $sourceId): int
    {
        if (!$sourceId) return 0;
        $repo = $this->getLib()->getHeadingsRepository();
        $src  = $repo->get($sourceId);
        if (!$src) return 0;

        $base = (string)$src['code'] . '_copy';
        $candidate = $base;
        $i = 2;
        while ($repo->getByCode($candidate)) {
            $candidate = $base . $i++;
            if ($i > 100) return 0;
        }

        try {
            return $repo->add([
                'code'         => $candidate,
                'tag'          => (string)$src['tag'],
                'descriptions' => is_array($src['descriptions']) ? $src['descriptions'] : [],
            ]);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Bulk-update sort_order для fields/headings після drag-drop. */
    public function updateFieldsSortOrder(array $orderMap): void
    {
        // $orderMap: [field_id => sort_order]
        foreach ($orderMap as $fid => $so) {
            $this->db->query("UPDATE `" . DB_PREFIX . "kit_easycheckout_fields`
                SET `sort_order` = " . (int)$so . "
                WHERE `field_id` = " . (int)$fid);
        }
    }

    public function updateHeadingsSortOrder(array $orderMap): void
    {
        foreach ($orderMap as $hid => $so) {
            $this->db->query("UPDATE `" . DB_PREFIX . "kit_easycheckout_headings`
                SET `sort_order` = " . (int)$so . "
                WHERE `heading_id` = " . (int)$hid);
        }
    }

    public function getFieldUsageInOrders(): array
    {
        $rows = $this->db->query("SELECT `field_code`, COUNT(*) AS cnt
            FROM `" . DB_PREFIX . "kit_easycheckout_order_fields`
            GROUP BY `field_code`")->rows;
        $out = [];
        foreach ($rows as $r) $out[(string)$r['field_code']] = (int)$r['cnt'];
        return $out;
    }

    /**
     * Стат для Fields-секції: скільки блоків layout-у посилаються на field_id.
     * Single-pass через всі layouts.
     * @return array<int,int> {field_id => count}
     */
    public function getFieldUsageInLayouts(): array
    {
        $rows = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "kit_easycheckout_settings`
            WHERE `code` = 'page_layout' AND `key` = 'checkout' AND `serialized` = 1")->rows;

        $counts = [];
        foreach ($rows as $r) {
            $layout = json_decode((string)$r['value'], true);
            if (!is_array($layout)) continue;
            foreach (($layout['steps'] ?? []) as $step) {
                foreach (($step['rows'] ?? []) as $row) {
                    foreach (($row['cells'] ?? []) as $cell) {
                        foreach (($cell['blocks'] ?? []) as $block) {
                            foreach (($block['settings']['fields'] ?? []) as $f) {
                                $fid = (int)($f['field_id'] ?? 0);
                                if ($fid > 0) {
                                    $counts[$fid] = ($counts[$fid] ?? 0) + 1;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $counts;
    }

    public function getAbandonedCount(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_easycheckout_abandoned`
            WHERE `recovered_order_id` IS NULL")->row['cnt'];
    }

    /**
     * KPI-стат для Abandoned-секції за останні $days днів.
     * Returns:
     *   - abandoned_count: загальна кількість записів за період
     *   - recovered_count: ті, що мають recovered_order_id
     *   - conversion_rate: recovered / abandoned (0..100)
     *   - notified_count:  ті, що отримали email-нагадування
     *   - notified_recovered: notified-and-recovered (для оцінки ефективності reminder-у)
     *   - lost_amount:     сума total серед НЕ-recovered
     *   - recovered_amount: сума total серед recovered
     *   - currency_breakdown: [code => sum]
     */
    public function getAbandonedStats(int $days = 30): array
    {
        $days = max(1, min(365, $days));
        $since = "DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)";

        $row = $this->db->query("SELECT
            COUNT(*) AS abandoned_count,
            SUM(CASE WHEN `recovered_order_id` IS NOT NULL THEN 1 ELSE 0 END) AS recovered_count,
            SUM(CASE WHEN `notified_at` IS NOT NULL THEN 1 ELSE 0 END) AS notified_count,
            SUM(CASE WHEN `notified_at` IS NOT NULL AND `recovered_order_id` IS NOT NULL THEN 1 ELSE 0 END) AS notified_recovered,
            COALESCE(SUM(CASE WHEN `recovered_order_id` IS NULL     THEN `total` ELSE 0 END), 0) AS lost_amount,
            COALESCE(SUM(CASE WHEN `recovered_order_id` IS NOT NULL THEN `total` ELSE 0 END), 0) AS recovered_amount
            FROM `" . DB_PREFIX . "kit_easycheckout_abandoned`
            WHERE `date_added` >= " . $since)->row;

        $abandoned = (int)($row['abandoned_count'] ?? 0);
        $recovered = (int)($row['recovered_count'] ?? 0);
        $notified  = (int)($row['notified_count'] ?? 0);

        $currencyRows = $this->db->query("SELECT `currency_code`, COALESCE(SUM(`total`), 0) AS sum
            FROM `" . DB_PREFIX . "kit_easycheckout_abandoned`
            WHERE `date_added` >= " . $since . "
              AND `recovered_order_id` IS NULL
            GROUP BY `currency_code`")->rows;
        $byCurrency = [];
        foreach ($currencyRows as $cr) {
            $byCurrency[(string)$cr['currency_code']] = (float)$cr['sum'];
        }

        return [
            'days'                => $days,
            'abandoned_count'     => $abandoned,
            'recovered_count'     => $recovered,
            'notified_count'      => $notified,
            'notified_recovered'  => (int)($row['notified_recovered'] ?? 0),
            'conversion_rate'     => $abandoned > 0 ? round($recovered / $abandoned * 100, 1) : 0.0,
            'reminder_efficiency' => $notified > 0 ? round((int)$row['notified_recovered'] / $notified * 100, 1) : 0.0,
            'lost_amount'         => (float)$row['lost_amount'],
            'recovered_amount'    => (float)$row['recovered_amount'],
            'currency_breakdown'  => $byCurrency,
        ];
    }

    public function deleteAbandoned(int $abandonedId): void
    {
        if (!$abandonedId) return;
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_abandoned_products`
            WHERE `abandoned_id` = " . (int)$abandonedId);
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_abandoned`
            WHERE `abandoned_id` = " . (int)$abandonedId);
    }

    /**
     * Сканує всі saved-layouts і повертає де field_id використовується.
     * @return array<int, array{group_id:int, store_id:int, block_type:string, block_id:string}>
     */
    public function findFieldUsages(int $fieldId): array
    {
        if (!$fieldId) return [];
        $rows = $this->db->query("SELECT `store_id`, `group_id`, `value`
            FROM `" . DB_PREFIX . "kit_easycheckout_settings`
            WHERE `code` = 'page_layout' AND `key` = 'checkout' AND `serialized` = 1")->rows;

        $usages = [];
        foreach ($rows as $r) {
            $layout = json_decode((string)$r['value'], true);
            if (!is_array($layout)) continue;
            foreach (($layout['steps'] ?? []) as $step) {
                foreach (($step['rows'] ?? []) as $row) {
                    foreach (($row['cells'] ?? []) as $cell) {
                        foreach (($cell['blocks'] ?? []) as $block) {
                            foreach (($block['settings']['fields'] ?? []) as $f) {
                                if ((int)($f['field_id'] ?? 0) === $fieldId) {
                                    $usages[] = [
                                        'store_id'   => (int)$r['store_id'],
                                        'group_id'   => (int)$r['group_id'],
                                        'block_type' => (string)($block['type'] ?? ''),
                                        'block_id'   => (string)($block['id']   ?? ''),
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $usages;
    }

    public function saveAbandonedNote(int $id, string $note): void
    {
        if (!$id) return;
        $this->db->query("UPDATE `" . DB_PREFIX . "kit_easycheckout_abandoned`
            SET `admin_notes` = '" . $this->db->escape($note) . "',
                `date_modified` = NOW()
            WHERE `abandoned_id` = " . (int)$id);
    }

    public function deleteAbandonedMany(array $ids): int
    {
        $ids = array_filter(array_map('intval', $ids));
        if (!$ids) return 0;
        $list = implode(',', $ids);
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_abandoned_products`
            WHERE `abandoned_id` IN (" . $list . ")");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_abandoned`
            WHERE `abandoned_id` IN (" . $list . ")");
        return count($ids);
    }

    /**
     * Read multilang reminder email subject + body. Storage:
     *   code='reminder', key='subject', value=JSON {lang_code: string}
     *   code='reminder', key='body',    value=JSON {lang_code: html-string}
     */
    public function getReminderTexts(): array
    {
        $rows = $this->db->query("SELECT `key`, `value` FROM `" . DB_PREFIX . "kit_easycheckout_settings`
            WHERE `code`='reminder' AND `serialized`=1");
        $out = ['subject' => [], 'body' => []];
        foreach ($rows->rows as $r) {
            $decoded = json_decode((string)$r['value'], true);
            if ($r['key'] === 'subject' && is_array($decoded)) $out['subject'] = $decoded;
            elseif ($r['key'] === 'body' && is_array($decoded))  $out['body']    = $decoded;
        }
        return $out;
    }

    public function saveReminderTexts(array $subject, array $body): void
    {
        // Upsert: видаляємо існуючі, вставляємо нові
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_settings`
            WHERE `code`='reminder' AND `key` IN ('subject','body')");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_settings`
            SET `store_id`=0, `group_id`=0, `code`='reminder', `key`='subject',
                `value`='" . $this->db->escape(json_encode($subject, JSON_UNESCAPED_UNICODE)) . "',
                `serialized`=1");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_settings`
            SET `store_id`=0, `group_id`=0, `code`='reminder', `key`='body',
                `value`='" . $this->db->escape(json_encode($body, JSON_UNESCAPED_UNICODE)) . "',
                `serialized`=1");
    }

    /**
     * Streams CSV з orders + oc-kit custom-полями (flat columns).
     * Date range у форматі Y-m-d. Якщо не задано — останні 30 днів.
     * order_status_id=0 → усі статуси.
     */
    public function streamOrdersCsv(string $dateFrom, string $dateTo, int $orderStatusId = 0): void
    {
        $dateFrom = $dateFrom ?: date('Y-m-d', strtotime('-30 days'));
        $dateTo   = $dateTo   ?: date('Y-m-d');

        // Збираємо список усіх custom-кодів які реально зустрічаються в обраних замовленнях
        $codesRows = $this->db->query("SELECT DISTINCT v.`field_code`
            FROM `" . DB_PREFIX . "kit_easycheckout_order_fields` v
            INNER JOIN `" . DB_PREFIX . "order` o ON o.`order_id` = v.`order_id`
            WHERE o.`date_added` BETWEEN '" . $this->db->escape($dateFrom) . " 00:00:00'
                                     AND '" . $this->db->escape($dateTo)   . " 23:59:59'
              " . ($orderStatusId ? "AND o.`order_status_id` = " . (int)$orderStatusId : "") . "
            ORDER BY v.`field_code`")->rows;
        $customCodes = array_map(static fn($r) => (string)$r['field_code'], $codesRows);

        // Стандартні колонки + custom-codes
        $headers = [
            'order_id', 'date_added', 'order_status_id',
            'firstname', 'lastname', 'email', 'telephone',
            'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'zone',
            'payment_method', 'shipping_method', 'total', 'currency_code',
        ];
        foreach ($customCodes as $c) $headers[] = 'okec_' . $c;

        // Streaming output
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="okec-orders-' . $dateFrom . '_' . $dateTo . '.csv"');
        $fh = fopen('php://output', 'w');
        // BOM для Excel — щоб Cyrillic не плив
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, $headers);

        $orders = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order`
            WHERE `date_added` BETWEEN '" . $this->db->escape($dateFrom) . " 00:00:00'
                                   AND '" . $this->db->escape($dateTo)   . " 23:59:59'
              " . ($orderStatusId ? "AND `order_status_id` = " . (int)$orderStatusId : "") . "
            ORDER BY `order_id` DESC")->rows;

        foreach ($orders as $o) {
            // Custom-fields для цього order — single query
            $customRows = $this->db->query("SELECT `field_code`, `value`
                FROM `" . DB_PREFIX . "kit_easycheckout_order_fields`
                WHERE `order_id` = " . (int)$o['order_id'])->rows;
            $byCode = [];
            foreach ($customRows as $cr) $byCode[(string)$cr['field_code']] = (string)$cr['value'];

            $row = [
                $o['order_id'],
                $o['date_added'],
                $o['order_status_id'],
                $o['firstname'],
                $o['lastname'],
                $o['email'],
                $o['telephone'],
                $o['payment_company']     ?: $o['shipping_company'],
                $o['shipping_address_1']  ?: $o['payment_address_1'],
                $o['shipping_address_2']  ?: $o['payment_address_2'],
                $o['shipping_city']       ?: $o['payment_city'],
                $o['shipping_postcode']   ?: $o['payment_postcode'],
                $o['shipping_country']    ?: $o['payment_country'],
                $o['shipping_zone']       ?: $o['payment_zone'],
                $o['payment_method'],
                $o['shipping_method'],
                $o['total'],
                $o['currency_code'],
            ];
            foreach ($customCodes as $c) {
                $val = $byCode[$c] ?? '';
                // JSON-array → CSV-friendly
                if ($val !== '' && ($val[0] === '[' || $val[0] === '{')) {
                    $dec = json_decode($val, true);
                    if (is_array($dec)) $val = implode('; ', array_map('strval', $dec));
                }
                $row[] = $val;
            }
            fputcsv($fh, $row);
        }
        fclose($fh);
    }

    public function uninstallDeep(): void
    {
        $this->getLib()->uninstallDeep();
    }

    public function getGroups(): array
    {
        $rows = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kit_easycheckout_groups` ORDER BY `is_default` DESC, `sort_order`, `name`");
        return $rows->rows;
    }

    public function getStats(): array
    {
        $abandonedRow = $this->db->query("SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_easycheckout_abandoned` WHERE `recovered_order_id` IS NULL");
        $fieldsRow    = $this->db->query("SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_easycheckout_fields`");
        $groupsRow    = $this->db->query("SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "kit_easycheckout_groups`");

        return [
            'abandoned' => (int)($abandonedRow->row['cnt'] ?? 0),
            'fields'    => (int)($fieldsRow->row['cnt'] ?? 0),
            'groups'    => (int)($groupsRow->row['cnt'] ?? 0),
        ];
    }

    // ─── Fields CRUD (proxy to library) ───────────────────────────────────────

    public function listFields(array $filter = []): array
    {
        return $this->getLib()->getFieldsRepository()->list($filter);
    }

    public function countFields(array $filter = []): int
    {
        return $this->getLib()->getFieldsRepository()->count($filter);
    }

    public function getField(int $fieldId): ?array
    {
        return $this->getLib()->getFieldsRepository()->get($fieldId);
    }

    public function addField(array $data): int
    {
        return $this->getLib()->getFieldsRepository()->add($data);
    }

    public function updateField(int $fieldId, array $data): void
    {
        $this->getLib()->getFieldsRepository()->update($fieldId, $data);
    }

    public function deleteField(int $fieldId): void
    {
        $this->getLib()->getFieldsRepository()->delete($fieldId);
    }

    public function deleteFields(array $ids): int
    {
        return $this->getLib()->getFieldsRepository()->deleteMany($ids);
    }

    public function generateFieldCode(): string
    {
        return $this->getLib()->getFieldsRepository()->generateNextCode();
    }

    // ─── Headings CRUD (proxy to library) ─────────────────────────────────────

    public function listHeadings(array $filter = []): array
    {
        return $this->getLib()->getHeadingsRepository()->list($filter);
    }

    public function countHeadings(array $filter = []): int
    {
        return $this->getLib()->getHeadingsRepository()->count($filter);
    }

    public function getHeading(int $headingId): ?array
    {
        return $this->getLib()->getHeadingsRepository()->get($headingId);
    }

    public function addHeading(array $data): int
    {
        return $this->getLib()->getHeadingsRepository()->add($data);
    }

    public function updateHeading(int $headingId, array $data): void
    {
        $this->getLib()->getHeadingsRepository()->update($headingId, $data);
    }

    public function deleteHeading(int $headingId): void
    {
        $this->getLib()->getHeadingsRepository()->delete($headingId);
    }

    public function deleteHeadings(array $ids): int
    {
        return $this->getLib()->getHeadingsRepository()->deleteMany($ids);
    }

    public function generateHeadingCode(): string
    {
        return $this->getLib()->getHeadingsRepository()->generateNextCode();
    }

    // ─── Groups CRUD ──────────────────────────────────────────────────────────

    public function listGroups(): array
    {
        return $this->getLib()->getGroupsRepository()->list();
    }

    public function getGroup(int $id): ?array
    {
        return $this->getLib()->getGroupsRepository()->get($id);
    }

    public function addGroup(array $data): int
    {
        return $this->getLib()->getGroupsRepository()->add($data);
    }

    public function updateGroup(int $id, array $data): void
    {
        $this->getLib()->getGroupsRepository()->update($id, $data);
    }

    public function deleteGroup(int $id): void
    {
        $this->getLib()->getGroupsRepository()->delete($id);
    }

    public function cloneGroup(int $sourceId, array $newData): int
    {
        return $this->getLib()->getGroupsRepository()->clone($sourceId, $newData);
    }

    // ─── Address formats (ТЗ §14) ────────────────────────────────────────

    public function getAddressFormats(): array
    {
        $rows = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kit_easycheckout_address_formats`
            ORDER BY `scope`, `scope_id`, `language_id`")->rows;
        return array_map(static function ($r) {
            return [
                'format_id'   => (int)$r['format_id'],
                'scope'       => (string)$r['scope'],
                'scope_id'    => (string)$r['scope_id'],
                'language_id' => (int)$r['language_id'],
                'template'    => (string)$r['template'],
            ];
        }, $rows);
    }

    public function saveAddressFormat(int $id, string $scope, string $scopeId, int $languageId, string $template): int
    {
        if ($id > 0) {
            $this->db->query("UPDATE `" . DB_PREFIX . "kit_easycheckout_address_formats`
                SET `scope`='" . $this->db->escape($scope) . "',
                    `scope_id`='" . $this->db->escape($scopeId) . "',
                    `language_id`=" . $languageId . ",
                    `template`='" . $this->db->escape($template) . "'
                WHERE `format_id`=" . $id);
            return $id;
        }
        $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_address_formats`
            SET `scope`='" . $this->db->escape($scope) . "',
                `scope_id`='" . $this->db->escape($scopeId) . "',
                `language_id`=" . $languageId . ",
                `template`='" . $this->db->escape($template) . "'");
        return (int)$this->db->getLastId();
    }

    public function deleteAddressFormat(int $id): void
    {
        if ($id <= 0) return;
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_address_formats` WHERE `format_id`=" . $id);
    }

    // ─── Order restrictions (ТЗ §16) ─────────────────────────────────────

    public function getOrderRestrictions(): array
    {
        $rows = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kit_easycheckout_order_restrictions`
            ORDER BY `sort_order`, `restriction_id`")->rows;
        return array_map(static function ($r) {
            return [
                'restriction_id'     => (int)$r['restriction_id'],
                'group_id'           => (int)$r['group_id'],
                'customer_group_ids' => (string)$r['customer_group_ids'],
                'min_total'          => $r['min_total']  !== null ? (float)$r['min_total']  : null,
                'max_total'          => $r['max_total']  !== null ? (float)$r['max_total']  : null,
                'min_qty'            => $r['min_qty']    !== null ? (int)$r['min_qty']      : null,
                'max_qty'            => $r['max_qty']    !== null ? (int)$r['max_qty']      : null,
                'min_weight'         => $r['min_weight'] !== null ? (float)$r['min_weight'] : null,
                'max_weight'         => $r['max_weight'] !== null ? (float)$r['max_weight'] : null,
                'error_text'         => (string)($r['error_text'] ?? ''),
                'sort_order'         => (int)$r['sort_order'],
            ];
        }, $rows);
    }

    public function saveOrderRestriction(array $data): int
    {
        $id = (int)($data['restriction_id'] ?? 0);
        $sets = [
            "`group_id`=" . (int)$data['group_id'],
            "`customer_group_ids`='" . $this->db->escape((string)$data['customer_group_ids']) . "'",
            "`min_total`="  . ($data['min_total']  === null ? 'NULL' : (float)$data['min_total']),
            "`max_total`="  . ($data['max_total']  === null ? 'NULL' : (float)$data['max_total']),
            "`min_qty`="    . ($data['min_qty']    === null ? 'NULL' : (int)$data['min_qty']),
            "`max_qty`="    . ($data['max_qty']    === null ? 'NULL' : (int)$data['max_qty']),
            "`min_weight`=" . ($data['min_weight'] === null ? 'NULL' : (float)$data['min_weight']),
            "`max_weight`=" . ($data['max_weight'] === null ? 'NULL' : (float)$data['max_weight']),
            "`error_text`='" . $this->db->escape((string)$data['error_text']) . "'",
            "`sort_order`=" . (int)$data['sort_order'],
        ];
        if ($id > 0) {
            $this->db->query("UPDATE `" . DB_PREFIX . "kit_easycheckout_order_restrictions`
                SET " . implode(', ', $sets) . " WHERE `restriction_id`=" . $id);
            return $id;
        }
        $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_order_restrictions`
            SET " . implode(', ', $sets));
        return (int)$this->db->getLastId();
    }

    public function deleteOrderRestriction(int $id): void
    {
        if ($id <= 0) return;
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_order_restrictions` WHERE `restriction_id`=" . $id);
    }
}
