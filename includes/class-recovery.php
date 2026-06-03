<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Recovery {

    const QUERY_VAR = 'wc_acart_r';

    public static function init() {
        add_action('init', [__CLASS__, 'register_rewrite_rules']);
        add_filter('query_vars', [__CLASS__, 'register_query_vars']);
        add_action('template_redirect', [__CLASS__, 'handle_recovery_request'], 5);
    }

    public static function register_rewrite_rules() {
        add_rewrite_rule(
            '^r/([a-zA-Z0-9]{6,12})/?$',
            'index.php?' . self::QUERY_VAR . '=$matches[1]',
            'top'
        );
    }

    public static function register_query_vars($vars) {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    public static function flush_rewrite_rules() {
        self::register_rewrite_rules();
        flush_rewrite_rules();
    }

    /**
     * لینک کوتاه: example.com/r/a1b2c3d4
     *
     * @param object|string $row_or_hash
     */
    public static function get_recovery_url($row_or_hash) {
        if (is_object($row_or_hash)) {
            if (!empty($row_or_hash->recovery_code)) {
                return home_url('/r/' . rawurlencode($row_or_hash->recovery_code));
            }
            if (!empty($row_or_hash->recovery_hash)) {
                return self::get_legacy_url($row_or_hash->recovery_hash);
            }
            return '';
        }

        $row = WC_Acart_SMS_Database::get_by_recovery_hash($row_or_hash);
        if ($row && !empty($row->recovery_code)) {
            return home_url('/r/' . rawurlencode($row->recovery_code));
        }

        return self::get_legacy_url($row_or_hash);
    }

    private static function get_legacy_url($hash) {
        return add_query_arg('recover_cart', rawurlencode($hash), home_url('/'));
    }

    public static function handle_recovery_request() {
        $code = null;

        if (get_query_var(self::QUERY_VAR)) {
            $code = sanitize_text_field(get_query_var(self::QUERY_VAR));
        } elseif (isset($_GET['recover_cart'])) {
            $code = sanitize_text_field(wp_unslash($_GET['recover_cart']));
        }

        if ($code === null || $code === '') {
            return;
        }

        if (!function_exists('WC')) {
            return;
        }

        if (is_null(WC()->cart)) {
            wc_load_cart();
        }

        $row = self::find_cart_by_code($code);
        if (!$row) {
            wc_add_notice(__('لینک بازیابی نامعتبر یا منقضی شده است.', 'wc-abandoned-cart-sms'), 'error');
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }

        if (!empty($row->recovered)) {
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }

        self::restore_cart_items($row);

        if (!empty($row->coupon_code) && WC()->cart) {
            WC()->cart->apply_coupon($row->coupon_code);
            WC()->cart->calculate_totals();
        }

        WC_Acart_SMS_Database::update_row((int) $row->id, [
            'recovered'    => 1,
            'recovered_at' => current_time('mysql'),
        ]);

        wc_add_notice(__('سبد خرید شما بازیابی شد. می‌توانید خرید را تکمیل کنید.', 'wc-abandoned-cart-sms'), 'success');

        wp_safe_redirect(wc_get_cart_url());
        exit;
    }

    private static function find_cart_by_code($code) {
        $row = WC_Acart_SMS_Database::get_by_recovery_code($code);
        if ($row) {
            return $row;
        }

        return WC_Acart_SMS_Database::get_by_recovery_hash($code);
    }

    private static function restore_cart_items($row) {
        if (!WC()->cart) {
            return;
        }

        $items = json_decode($row->cart_data, true);
        if (!is_array($items) || empty($items)) {
            return;
        }

        WC()->cart->empty_cart();

        foreach ($items as $item) {
            $product_id   = isset($item['product_id']) ? (int) $item['product_id'] : 0;
            $variation_id = isset($item['variation_id']) ? (int) $item['variation_id'] : 0;
            $quantity     = isset($item['quantity']) ? max(1, (int) $item['quantity']) : 1;

            if ($product_id < 1) {
                continue;
            }

            $product = wc_get_product($variation_id > 0 ? $variation_id : $product_id);
            if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
                continue;
            }

            $variation_attrs = [];
            if ($variation_id > 0) {
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    $variation_attrs = $variation->get_variation_attributes();
                }
            }

            WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation_attrs);
        }

        WC()->cart->calculate_totals();
    }
}
