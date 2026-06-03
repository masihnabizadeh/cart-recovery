<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Cart_Tracker {

    public function __construct() {
        $events = [
            'woocommerce_add_to_cart',
            'woocommerce_cart_item_removed',
            'woocommerce_after_cart_item_quantity_update',
            'woocommerce_cart_loaded_from_session',
            'woocommerce_checkout_update_order_review',
        ];

        foreach ($events as $hook) {
            add_action($hook, [$this, 'track_cart'], 20);
        }
    }

    public function track_cart() {
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return;
        }

        $phone = self::resolve_phone();
        if (empty($phone)) {
            return;
        }

        $cart_items = $this->build_cart_snapshot();
        if (empty($cart_items)) {
            return;
        }

        WC_Acart_SMS_Database::upsert_active_cart([
            'session_id'    => self::get_session_id(),
            'user_id'       => get_current_user_id() ?: null,
            'phone'         => $phone,
            'cart_data'     => wp_json_encode($cart_items),
            'cart_total'    => (float) WC()->cart->get_cart_contents_total(),
            'last_activity' => current_time('mysql'),
        ]);
    }

    /**
     * Priority: Digits → billing_phone (spec).
     */
    public static function resolve_phone() {
        $user_id = get_current_user_id();

        if ($user_id) {
            $digits_keys = ['digits_phone', 'digits_phone_no'];

            foreach ($digits_keys as $key) {
                $phone = get_user_meta($user_id, $key, true);
                $phone = self::normalize_phone($phone);
                if ($phone) {
                    return $phone;
                }
            }

            $billing = get_user_meta($user_id, 'billing_phone', true);
            $billing = self::normalize_phone($billing);
            if ($billing) {
                return $billing;
            }
        }

        if (function_exists('WC') && WC()->customer) {
            $guest_phone = WC()->customer->get_billing_phone();
            return self::normalize_phone($guest_phone);
        }

        return '';
    }

    public static function normalize_phone($phone) {
        if (empty($phone)) {
            return '';
        }

        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (strlen($digits) < 10) {
            return '';
        }

        if (strlen($digits) === 10 && $digits[0] === '9') {
            $digits = '0' . $digits;
        }

        if (strlen($digits) === 12 && strpos($digits, '98') === 0) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }

    private function build_cart_snapshot() {
        $items = [];

        foreach (WC()->cart->get_cart() as $item) {
            if (empty($item['product_id'])) {
                continue;
            }

            $product = wc_get_product($item['product_id']);
            if (!$product) {
                continue;
            }

            $items[] = [
                'product_id'   => (int) $item['product_id'],
                'variation_id' => isset($item['variation_id']) ? (int) $item['variation_id'] : 0,
                'quantity'     => (int) $item['quantity'],
                'name'         => $product->get_name(),
            ];
        }

        return $items;
    }

    public static function get_session_id() {
        if (!function_exists('WC') || !WC()->session) {
            return '';
        }

        $customer_id = WC()->session->get_customer_id();
        if ($customer_id) {
            return (string) $customer_id;
        }

        $cookie = WC()->session->get_session_cookie();
        return (is_array($cookie) && isset($cookie[0])) ? (string) $cookie[0] : '';
    }
}
