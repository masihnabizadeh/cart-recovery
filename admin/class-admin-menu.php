<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Admin_Menu {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
    }

    public function register_admin_menu() {

        add_menu_page(
            __('Abandoned Cart SMS', 'wc-abandoned-cart-sms'),
            __('Abandoned Cart SMS', 'wc-abandoned-cart-sms'),
            'manage_woocommerce',
            'wc-acart-sms',
            ['WC_Acart_SMS_Admin_Page', 'render_dashboard_page'],
            'dashicons-email-alt',
            56
        );

        add_submenu_page(
            'wc-acart-sms',
            __('Dashboard', 'wc-abandoned-cart-sms'),
            __('Dashboard', 'wc-abandoned-cart-sms'),
            'manage_woocommerce',
            'wc-acart-sms',
            ['WC_Acart_SMS_Admin_Page', 'render_dashboard_page']
        );

        add_submenu_page(
            'wc-acart-sms',
            __('Settings', 'wc-abandoned-cart-sms'),
            __('Settings', 'wc-abandoned-cart-sms'),
            'manage_woocommerce',
            'wc-acart-sms-settings',
            ['WC_Acart_SMS_Admin_Page', 'render_settings_page']
        );

        add_submenu_page(
            'wc-acart-sms',
            __('Reports', 'wc-abandoned-cart-sms'),
            __('Reports', 'wc-abandoned-cart-sms'),
            'manage_woocommerce',
            'wc-acart-sms-reports',
            ['WC_Acart_SMS_Admin_Page', 'render_reports_page']
        );
    }
}

new WC_Acart_SMS_Admin_Menu();
