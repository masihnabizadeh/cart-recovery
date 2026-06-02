<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Settings {

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    /*
    |--------------------------------------------------------------------------
    | Register Settings
    |--------------------------------------------------------------------------
    */

    public function register_settings() {

        register_setting('wc_acart_sms_settings_group', 'wc_acart_sms_api_key');
        register_setting('wc_acart_sms_settings_group', 'wc_acart_sms_line_number');

        register_setting('wc_acart_sms_settings_group', 'wc_acart_sms_text_template');

        register_setting('wc_acart_sms_settings_group', 'wc_acart_sms_coupon_type');
        register_setting('wc_acart_sms_settings_group', 'wc_acart_sms_coupon_amount');
        register_setting('wc_acart_sms_settings_group', 'wc_acart_sms_coupon_expiry');
        register_setting('wc_acart_sms_settings_group', 'wc_acart_sms_coupon_prefix');
    }

    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
    */

    public static function get_api_key() {
        return get_option('wc_acart_sms_api_key', '');
    }

    public static function get_line_number() {
        return get_option('wc_acart_sms_line_number', '');
    }

    public static function get_sms_template() {

        $default = "سلام 👋
سبد خرید شما در [site_name] هنوز منتظر شماست.

برای تکمیل خرید روی لینک زیر کلیک کنید:
[link]

کد تخفیف شما:
[coupon]";

        return get_option('wc_acart_sms_text_template', $default);
    }

    public static function get_coupon_type() {
        return get_option('wc_acart_sms_coupon_type', 'percent');
    }

    public static function get_coupon_amount() {
        return get_option('wc_acart_sms_coupon_amount', 10);
    }

    public static function get_coupon_expiry() {
        return get_option('wc_acart_sms_coupon_expiry', 24);
    }

    public static function get_coupon_prefix() {
        return get_option('wc_acart_sms_coupon_prefix', 'ACART');
    }
}
