<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Settings {

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings() {
        $group = 'wc_acart_sms_settings_group';

        $options = [
            'wc_acart_sms_api_key',
            'wc_acart_sms_line_number',
            'wc_acart_sms_message_template',
            'wc_acart_sms_abandon_minutes',
            'wc_acart_sms_enable_coupon',
            'wc_acart_sms_coupon_prefix',
            'wc_acart_sms_coupon_type',
            'wc_acart_sms_coupon_amount',
            'wc_acart_sms_coupon_expiry_hours',
            'wc_acart_sms_coupon_code_length',
        ];

        foreach ($options as $option) {
            register_setting($group, $option, [
                'type'              => 'string',
                'sanitize_callback' => [$this, 'sanitize_option'],
            ]);
        }
    }

    public function sanitize_option($value) {
        return is_string($value) ? sanitize_text_field($value) : $value;
    }

    public static function get_api_key() {
        return (string) get_option('wc_acart_sms_api_key', '');
    }

    public static function get_line_number() {
        return (string) get_option('wc_acart_sms_line_number', '');
    }

    public static function get_abandon_minutes() {
        $minutes = (int) get_option('wc_acart_sms_abandon_minutes', 45);
        return max(1, $minutes);
    }

    public static function is_coupon_enabled() {
        return get_option('wc_acart_sms_enable_coupon', 'yes') === 'yes';
    }

    public static function get_message_template() {
        $default = 'سبد خرید شما کامل نشد. کد تخفیف: {coupon} — مهلت: {expiry} — ادامه خرید: {cart_link}';

        return (string) get_option('wc_acart_sms_message_template', $default);
    }

    public static function get_coupon_prefix() {
        return (string) get_option('wc_acart_sms_coupon_prefix', 'ACART');
    }

    public static function get_coupon_type() {
        $type = get_option('wc_acart_sms_coupon_type', 'percent');
        return in_array($type, ['percent', 'fixed'], true) ? $type : 'percent';
    }

    public static function get_coupon_amount() {
        return (float) get_option('wc_acart_sms_coupon_amount', 10);
    }

    public static function get_coupon_expiry_hours() {
        return max(1, (int) get_option('wc_acart_sms_coupon_expiry_hours', 24));
    }

    public static function get_coupon_code_length() {
        $length = (int) get_option('wc_acart_sms_coupon_code_length', 6);
        return max(4, min(20, $length));
    }

    public static function format_expiry_label() {
        $hours = self::get_coupon_expiry_hours();

        if ($hours >= 24 && $hours % 24 === 0) {
            $days = $hours / 24;
            return sprintf('%d روز', $days);
        }

        return sprintf('%d ساعت', $hours);
    }
}
