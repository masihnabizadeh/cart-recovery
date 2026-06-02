<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Cart_Tracker {

    public function __construct() {

        // وقتی محصول به سبد اضافه میشود
        add_action(
            'woocommerce_add_to_cart',
            [$this, 'track_cart']
        );

        // وقتی محصول حذف میشود
        add_action(
            'woocommerce_cart_item_removed',
            [$this, 'track_cart']
        );

        // وقتی تعداد تغییر میکند
        add_action(
            'woocommerce_after_cart_item_quantity_update',
            [$this, 'track_cart']
        );

        // وقتی سبد از session لود میشود
        add_action(
            'woocommerce_cart_loaded_from_session',
            [$this, 'track_cart']
        );

        // وقتی صفحه checkout باز میشود
        add_action(
            'woocommerce_checkout_init',
            [$this, 'track_cart']
        );
    }

    /**
     * ذخیره یا بروزرسانی سبد خرید
     */
    public function track_cart() {

        // ووکامرس لود نشده
        if (!function_exists('WC')) {
            return;
        }

        // سبد وجود ندارد
        if (!WC()->cart) {
            return;
        }

        // سبد خالی است
        if (WC()->cart->is_empty()) {
            return;
        }

        $user_id = get_current_user_id();

        // اگر کاربر لاگین نیست
        if (!$user_id) {
            return;
        }

        /**
         * دریافت شماره موبایل از دیجیتس
         */

        $phone = get_user_meta(
            $user_id,
            'digits_phone_no',
            true
        );

        // fallback
        if (empty($phone)) {

            $phone = get_user_meta(
                $user_id,
                'billing_phone',
                true
            );
        }

        // شماره نداریم
        if (empty($phone)) {
            return;
        }

        /**
         * دریافت اطلاعات سبد
         */

        $cart_items = [];

        foreach (WC()->cart->get_cart() as $cart_item_key => $item) {

            if (empty($item['product_id'])) {
                continue;
            }

            $product = wc_get_product($item['product_id']);

            if (!$product) {
                continue;
            }

            $cart_items[] = [

                'product_id' => $item['product_id'],

                'variation_id' => isset($item['variation_id'])
                    ? $item['variation_id']
                    : 0,

                'quantity' => $item['quantity'],

                'name' => $product->get_name(),

                'price' => $product->get_price(),

            ];
        }

        // اگر محصولی نداریم
        if (empty($cart_items)) {
            return;
        }

        /**
         * ساخت hash سبد
         */

        $cart_hash = md5(
            wp_json_encode($cart_items)
        );

        /**
         * ذخیره در دیتابیس
         */

        WC_Acart_SMS_Database::save_cart([

            'user_id'       => $user_id,

            'phone'         => $phone,

            'cart_data'     => wp_json_encode($cart_items),

            'cart_hash'     => $cart_hash,

            'cart_total'    => WC()->cart->get_total('edit'),

            'last_activity' => current_time('mysql'),

        ]);
    }
}