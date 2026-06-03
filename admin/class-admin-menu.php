<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Admin_Menu {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menus'], 60);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_menus() {
        add_submenu_page(
            'woocommerce',
            __('سبد رها شده SMS', 'wc-abandoned-cart-sms'),
            __('Abandoned Cart SMS', 'wc-abandoned-cart-sms'),
            'manage_woocommerce',
            'wc-acart-sms',
            ['WC_Acart_SMS_Admin_Page', 'render_dashboard']
        );

        add_submenu_page(
            'woocommerce',
            __('تنظیمات سبد رها شده', 'wc-abandoned-cart-sms'),
            __('ACart SMS — تنظیمات', 'wc-abandoned-cart-sms'),
            'manage_woocommerce',
            'wc-acart-sms-settings',
            ['WC_Acart_SMS_Admin_Page', 'render_settings']
        );

        add_submenu_page(
            'woocommerce',
            __('گزارش سبدهای رها شده', 'wc-abandoned-cart-sms'),
            __('ACart SMS — گزارش', 'wc-abandoned-cart-sms'),
            'manage_woocommerce',
            'wc-acart-sms-reports',
            ['WC_Acart_SMS_Admin_Page', 'render_reports']
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'wc-acart-sms') === false) {
            return;
        }

        wp_enqueue_style(
            'wc-acart-sms-admin',
            WC_ACART_SMS_URL . 'assets/css/admin-style.css',
            [],
            WC_ACART_SMS_VERSION
        );
    }
}
