<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Recovery {

    public static function get_recovery_url($recovery_hash) {
        return add_query_arg(
            'recover_cart',
            rawurlencode($recovery_hash),
            home_url('/')
        );
    }

    public static function handle_recovery_request() {
        if (!isset($_GET['recover_cart'])) {
            return;
        }

        $hash = sanitize_text_field(wp_unslash($_GET['recover_cart']));
        if (empty($hash)) {
            return;
        }

        if (!function_exists('WC')) {
            return;
        }

        $row = WC_Acart_SMS_Database::get_by_recovery_hash($hash);
        if (!$row) {
            wc_add_notice(__('لینک بازیابی نامعتبر یا منقضی شده است.', 'wc-abandoned-cart-sms'), 'error');
            return;
        }

        if (!empty($row->recovered)) {
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }

        self::restore_cart_items($row);

        if (!empty($row->coupon_code) && WC()->cart) {
            WC()->cart->apply_coupon($row->coupon_code);
        }

        WC_Acart_SMS_Database::update_row((int) $row->id, [
            'recovered'    => 1,
            'recovered_at' => current_time('mysql'),
        ]);

        wc_add_notice(__('سبد خرید شما بازیابی شد.', 'wc-abandoned-cart-sms'), 'success');

        wp_safe_redirect(wc_get_cart_url());
        exit;
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
            $quantity     = isset($item['quantity']) ? (int) $item['quantity'] : 1;

            if ($product_id < 1 || $quantity < 1) {
                continue;
            }

            $product = wc_get_product($variation_id ?: $product_id);
            if (!$product || !$product->is_purchasable()) {
                continue;
            }

            WC()->cart->add_to_cart($product_id, $quantity, $variation_id);
        }

        WC()->cart->calculate_totals();
    }
}
