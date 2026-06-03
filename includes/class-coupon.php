<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Coupon {

    /**
     * Create a unique WooCommerce coupon for an abandoned cart row.
     */
    public static function generate_for_cart($cart_row) {
        if (!WC_Acart_SMS_Settings::is_coupon_enabled()) {
            return '';
        }

        if (!empty($cart_row->coupon_code)) {
            $existing = new WC_Coupon($cart_row->coupon_code);
            if ($existing->get_id()) {
                return $cart_row->coupon_code;
            }
        }

        $code = self::build_unique_code((int) $cart_row->id);

        $type   = WC_Acart_SMS_Settings::get_coupon_type();
        $amount = WC_Acart_SMS_Settings::get_coupon_amount();
        $hours  = WC_Acart_SMS_Settings::get_coupon_expiry_hours();

        $coupon = new WC_Coupon();
        $coupon->set_code($code);
        $coupon->set_discount_type($type === 'fixed' ? 'fixed_cart' : 'percent');
        $coupon->set_amount($amount);
        $coupon->set_usage_limit(1);
        $coupon->set_usage_limit_per_user(1);
        $coupon->set_individual_use(true);
        $coupon->set_date_expires(time() + ($hours * HOUR_IN_SECONDS));
        $coupon->set_description(
            sprintf(
                /* translators: %d: cart id */
                __('کوپن بازیابی سبد رها شده #%d', 'wc-abandoned-cart-sms'),
                (int) $cart_row->id
            )
        );
        $coupon->save();

        return $code;
    }

    private static function build_unique_code($cart_id) {
        $prefix = preg_replace('/[^A-Z0-9]/i', '', WC_Acart_SMS_Settings::get_coupon_prefix());
        $prefix = $prefix ?: 'ACART';
        $length = WC_Acart_SMS_Settings::get_coupon_code_length();

        $attempts = 0;
        do {
            $random = strtoupper(wp_generate_password($length, false, false));
            $code   = $prefix . '-' . $random;
            $exists = wc_get_coupon_id_by_code($code);
            $attempts++;
        } while ($exists && $attempts < 10);

        return $code;
    }
}
