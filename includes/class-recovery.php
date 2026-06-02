<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Recovery {

    /**
     * Generate secure recovery link
     */
    public function generate_link($row) {

        if (empty($row) || empty($row->id)) {
            return false;
        }

        $recovery_key = $this->generate_key();

        global $wpdb;
        $table = $wpdb->prefix . 'wc_acart_sms';

        // Save recovery key
        $wpdb->update(
            $table,
            ['recovery_key' => $recovery_key],
            ['id' => $row->id],
            ['%s'],
            ['%d']
        );

        $coupon_code = $this->generate_coupon($row);

        $url = add_query_arg(
            [
                'acart_recover' => 1,
                'key'           => $recovery_key,
                'coupon'        => $coupon_code
            ],
            wc_get_cart_url()
        );

        return $url;
    }

    /**
     * Generate unique recovery key
     */
    private function generate_key() {
        return wp_generate_password(32, false, false);
    }

    /**
     * Generate coupon dynamically
     */
    private function generate_coupon($row) {

        $prefix  = get_option('wc_acart_sms_coupon_prefix', 'ACART');
        $amount  = get_option('wc_acart_sms_coupon_amount', 10);
        $type    = get_option('wc_acart_sms_coupon_type', 'percent');
        $expiry  = get_option('wc_acart_sms_coupon_expiry', 24);

        $code = $prefix . '-' . strtoupper(wp_generate_password(6, false, false));

        $coupon = new WC_Coupon();
        $coupon->set_code($code);
        $coupon->set_discount_type($type === 'percent' ? 'percent' : 'fixed_cart');
        $coupon->set_amount($amount);
        $coupon->set_usage_limit(1);
        $coupon->set_individual_use(true);

        $expiry_date = (new DateTime())->modify("+{$expiry} hours");
        $coupon->set_date_expires($expiry_date);

        $coupon->save();

        return $code;
    }

    /**
     * Handle recovery request
     */
    public static function handle_recovery_request() {

        if (!isset($_GET['acart_recover'], $_GET['key'])) {
            return;
        }

        $key = sanitize_text_field($_GET['key']);

        global $wpdb;
        $table = $wpdb->prefix . 'wc_acart_sms';

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE recovery_key = %s
                 AND recovered = 0
                 LIMIT 1",
                $key
            )
        );

        if (!$row) {
            return;
        }

        if (isset($_GET['coupon'])) {
            $coupon = sanitize_text_field($_GET['coupon']);
            WC()->cart->apply_coupon($coupon);
        }

        // Mark as recovered
        $wpdb->update(
            $table,
            ['recovered' => 1],
            ['id' => $row->id],
            ['%d'],
            ['%d']
        );
    }
}
