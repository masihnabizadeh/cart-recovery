<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Coupon {

    public function generate_coupon($cart) {

        if (empty($cart->phone)) {
            return false;
        }

        $code = $this->generate_code($cart->id);

        if ($this->coupon_exists($code)) {
            return $code;
        }

        $amount = get_option('wc_acart_coupon_amount', 10);
        $expiry = get_option('wc_acart_coupon_expiry', 24); // hours

        $coupon = new WC_Coupon();

        $coupon->set_code($code);
        $coupon->set_discount_type('percent');
        $coupon->set_amount($amount);
        $coupon->set_usage_limit(1);
        $coupon->set_individual_use(true);

        $expiry_date = strtotime("+{$expiry} hours");
        $coupon->set_date_expires($expiry_date);

        $coupon->save();

        return $code;
    }

    private function generate_code($cart_id) {

        return 'ACART-' . strtoupper(wp_generate_password(6, false)) . '-' . $cart_id;
    }

    private function coupon_exists($code) {

        $coupon = new WC_Coupon($code);

        return $coupon->get_id() ? true : false;
    }
}
