<?php
class ProductFormatter {
    private $registry;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->config = $registry->get('config');
    }

    public function __get($name) {
        return $this->registry->get($name);
    }

    public function applyMarkup($price, $in_stock = false) {
        if ($in_stock) return $price;
        $markup_percentage = $this->config->get('config_markup') ?: 11.3;
        return $price + ($price * $markup_percentage / 100);
    }

    public function safeFilename($str) {
        $str = strtolower($str);
        $str = preg_replace('/[^a-z0-9\-_]/', '-', $str);   // залишаємо лише латиницю, цифри, тире, підкреслення
        $str = preg_replace('/-+/', '-', $str);             // заміна кількох тире на одне
        $str = trim($str, '-_');                            // обрізання тире та підкреслень по краях
        return $str;
    }

    public function format($product_info, $options = array()) {

        if (empty($product_info['product_id'])) {
            return false;
        }

        // Значення за замовчуванням
        $defaults = array(
            'width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'),
            'height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'),
            'show_description' => true,
            'description_length' => null,
        );

        $options = array_merge($defaults, $options);

        $manufacturer = $product_info['manufacturer'] ?? '';
        $name = $product_info['name_en'] ?? 0;
        if (stripos($name, $manufacturer) === 0) {
            $seo_name = $name;
        } else {
            $seo_name = $manufacturer . '-' . $name;
        }

        $product_id = $product_info['product_id'] ?? 0;
        $is_zoomed = isset($product_info['location']) && $product_info['location'] != 'jomashop';

        if ($product_info['image']) {
            $image = $this->model_tool_image->cropTrim(
                $product_info['image'],
                $options['width'],
                $options['height'],
                90,
                $is_zoomed,
                $product_id,
                $this->safeFilename($product_info['location'] ?? ''),
                $this->safeFilename($seo_name)
            );
        } else {
            $image = $this->model_tool_image->cropTrim('placeholder.png', $options['width'], $options['height']);
        }

        $product_info['tax_class_id'] = $product_info['tax_class_id'] ?? 0;

        if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
            $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'] ?? false, $this->config->get('config_tax')), $this->session->data['currency']);
        } else {
            $price = false;
        }

        if (!is_null($product_info['special']) && (float)$product_info['special'] >= 0) {
            $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            $tax_price = (float)$product_info['special'];
            $special_percentage = round((($product_info['price'] - $product_info['special']) / $product_info['price']) * 100);
        } else {
            $special = false;
            $tax_price = (float)$product_info['price'];
            $special_percentage = 0;
        }

        if ($this->config->get('config_tax')) {
            $tax = $this->currency->format($tax_price, $this->session->data['currency']);
        } else {
            $tax = false;
        }

//        if ($this->config->get('config_review_status')) {
//            $rating = $product_info['rating'];
//        } else {
//            $rating = false;
//        }

        $wishlist_count = $this->model_catalog_product->getWishlistCount($product_info['product_id']);

        $additional_image = $this->model_catalog_product->getAdditionalImage($product_info['product_id']);
        $additional_thumb = $additional_image ? $this->model_tool_image->cropTrim(
            $additional_image,
            $options['width'],
            $options['height'],
            90,
            $is_zoomed,
            $product_info['product_id'],
            $this->safeFilename($product_info['location'] ?? ''),
            $this->safeFilename($seo_name) . '-1',
        ) : '';

        $in_stock = $product_info['stock_status_id'] == $this->config->get('config_stock_status_id');

        $result = array(
            'product_id'            => $product_info['product_id'],
            'in_cart'               => in_array($product_info['product_id'], $options['cart_products']),
            'in_stock'              => $in_stock,
            'quantity'              => $product_info['quantity'] ?? false,
            'manufacturer'          => $product_info['manufacturer'] ?? false,
            'model'                 => $product_info['model'] ?? false,
            'thumb_width'           => $options['width'] ? $this->config->get('theme_' . $this->config->get('config_theme') . '_image_wishlist_width') : false,
            'thumb_height'          => $options['height'] ? $this->config->get('theme_' . $this->config->get('config_theme') . '_image_wishlist_height') : false,
            'thumb'                 => $image,
            'additional_thumb'      => $additional_thumb,
            'name'                  => $product_info['name'],
            'price'                 => $price,
            'special'               => $special,
//            'tax'                   => $tax,
//            'rating'                => $rating,
            'href'                  => $this->url->link('product/product', 'product_id=' . $product_info['product_id']),
            'href_short'            => '/' . str_replace(HTTPS_SERVER, '', $this->url->link('product/product', 'product_id=' . $product_info['product_id'])),
            'special_percentage'    => $special_percentage,
            'wishlist_count'        => $wishlist_count,
            'remove'                => isset($options['remove']) ? $this->url->link('account/wishlist', 'remove=' . $product_info['product_id']) : false,
        );

        // Додаємо опис якщо потрібно
        if ($options['show_description']) {
            $length = $options['description_length'] ?? $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length');
            $result['description'] = isset($product_info['description']) ? utf8_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, $length) . '..' : false;
        }

        return $result;
    }
}