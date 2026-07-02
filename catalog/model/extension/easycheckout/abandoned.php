<?php
/**
 * EasyCheckout — OpenCart 3.x Module
 *
 * Abandoned-checkout tracking. Запис створюється у `loadMethods` коли користувач
 * вже почав заповнювати checkout, але ще не натиснув confirm. На confirm —
 * `markRecovered($abandonedId, $orderId)` скидає прапорець.
 *
 * Дедуплікація: одна сесія = один запис (recovery_token у session.data['okec_abandoned_token']).
 *
 * @package   OcKit\EasyCheckout
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ModelExtensionEasycheckoutAbandoned extends Model
{
    /**
     * Створює або оновлює abandoned-запис на основі поточної сесії checkout.
     * Повертає abandoned_id (int) або 0 якщо tracking пропущено (порожній email/cart).
     */
    public function track(array $okec, int $storeId, int $groupId, int $customerId, string $token): int
    {
        $email = trim((string)($okec['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 0; // нічого осмисленого ще не введено — не трекаємо
        }
        if (!$this->cart->hasProducts()) return 0;

        $existing = $this->db->query("SELECT `abandoned_id` FROM `" . DB_PREFIX . "kit_easycheckout_abandoned`
            WHERE `recovery_token` = '" . $this->db->escape($token) . "' LIMIT 1");

        $cartTotal    = (float)$this->cart->getTotal();
        $currencyCode = (string)$this->session->data['currency'] ?? '';
        $langId       = (int)$this->config->get('config_language_id');
        $ip           = (string)($this->request->server['REMOTE_ADDR'] ?? '');
        $ua           = (string)($this->request->server['HTTP_USER_AGENT'] ?? '');

        if ($existing->num_rows) {
            $abandonedId = (int)$existing->row['abandoned_id'];
            $this->db->query("UPDATE `" . DB_PREFIX . "kit_easycheckout_abandoned` SET
                `customer_id` = " . ($customerId ?: 'NULL') . ",
                `email`       = '" . $this->db->escape($email) . "',
                `firstname`   = '" . $this->db->escape((string)($okec['firstname'] ?? '')) . "',
                `lastname`    = '" . $this->db->escape((string)($okec['lastname']  ?? '')) . "',
                `telephone`   = '" . $this->db->escape((string)($okec['telephone'] ?? '')) . "',
                `total`       = " . (float)$cartTotal . ",
                `currency_code` = '" . $this->db->escape($currencyCode) . "',
                `language_id` = " . (int)$langId . ",
                `ip`          = '" . $this->db->escape($ip) . "',
                `user_agent`  = '" . $this->db->escape(mb_substr($ua, 0, 255)) . "',
                `date_modified` = NOW()
                WHERE `abandoned_id` = " . (int)$abandonedId);
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_abandoned` SET
                `store_id`    = " . (int)$storeId . ",
                `group_id`    = " . (int)$groupId . ",
                `customer_id` = " . ($customerId ?: 'NULL') . ",
                `email`       = '" . $this->db->escape($email) . "',
                `firstname`   = '" . $this->db->escape((string)($okec['firstname'] ?? '')) . "',
                `lastname`    = '" . $this->db->escape((string)($okec['lastname']  ?? '')) . "',
                `telephone`   = '" . $this->db->escape((string)($okec['telephone'] ?? '')) . "',
                `total`       = " . (float)$cartTotal . ",
                `currency_code` = '" . $this->db->escape($currencyCode) . "',
                `language_id` = " . (int)$langId . ",
                `recovery_token` = '" . $this->db->escape($token) . "',
                `ip`          = '" . $this->db->escape($ip) . "',
                `user_agent`  = '" . $this->db->escape(mb_substr($ua, 0, 255)) . "',
                `date_added`    = NOW(),
                `date_modified` = NOW()");
            $abandonedId = (int)$this->db->getLastId();
        }

        // Snapshot товарів — overwrite на кожен update
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kit_easycheckout_abandoned_products`
            WHERE `abandoned_id` = " . (int)$abandonedId);

        foreach ($this->cart->getProducts() as $p) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "kit_easycheckout_abandoned_products` SET
                `abandoned_id` = " . (int)$abandonedId . ",
                `product_id`   = " . (int)$p['product_id'] . ",
                `name`         = '" . $this->db->escape((string)$p['name']) . "',
                `model`        = '" . $this->db->escape((string)$p['model']) . "',
                `quantity`     = " . (int)$p['quantity'] . ",
                `price`        = " . (float)$p['price'] . ",
                `option_data`  = '" . $this->db->escape(json_encode($p['option'] ?? [], JSON_UNESCAPED_UNICODE)) . "'");
        }

        return $abandonedId;
    }

    /**
     * Маркує abandoned-запис цього токена як recovered → не відображається в admin
     * як «недозамовлене», бо order створено.
     */
    public function markRecovered(string $token, int $orderId): void
    {
        if ($token === '' || !$orderId) return;
        $this->db->query("UPDATE `" . DB_PREFIX . "kit_easycheckout_abandoned`
            SET `recovered_order_id` = " . (int)$orderId . ", `date_modified` = NOW()
            WHERE `recovery_token` = '" . $this->db->escape($token) . "'");
    }
}
