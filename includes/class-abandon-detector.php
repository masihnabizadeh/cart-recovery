<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Abandon_Detector {

    public static function process() {
        $minutes = WC_Acart_SMS_Settings::get_abandon_minutes();

        $cutoff = gmdate('Y-m-d H:i:s', time() - ($minutes * 60));
        $rows   = WC_Acart_SMS_Database::get_abandoned_candidates($cutoff);

        if (empty($rows)) {
            return;
        }

        foreach ($rows as $row) {
            self::process_single_cart($row);
        }
    }

    private static function process_single_cart($row) {
        if (self::has_recent_order($row)) {
            return;
        }

        if (!self::cart_still_abandoned($row)) {
            return;
        }

        WC_Acart_SMS_Database::update_row((int) $row->id, [
            'abandoned_at' => current_time('mysql'),
        ]);

        $coupon_code = WC_Acart_SMS_Coupon::generate_for_cart($row);

        if ($coupon_code) {
            WC_Acart_SMS_Database::update_row((int) $row->id, [
                'coupon_code' => $coupon_code,
            ]);
            $row->coupon_code = $coupon_code;
        }

        $recovery_url = WC_Acart_SMS_Recovery::get_recovery_url($row->recovery_hash);
        $message      = WC_Acart_SMS_SMS::build_message($recovery_url, $row->coupon_code ?? '');

        $sent = WC_Acart_SMS_SMS::send($row->phone, $message);

        if ($sent) {
            WC_Acart_SMS_Database::update_row((int) $row->id, [
                'sms_sent' => 1,
            ]);
        }
    }

    /**
     * User completed an order after this cart's last activity.
     */
    private static function has_recent_order($row) {
        if (empty($row->phone)) {
            return false;
        }

        $orders = wc_get_orders([
            'limit'         => 1,
            'billing_phone' => $row->phone,
            'status'        => ['processing', 'completed', 'on-hold'],
            'date_created'  => '>' . strtotime($row->last_activity),
            'orderby'       => 'date',
            'order'         => 'DESC',
        ]);

        return !empty($orders);
    }

    /**
     * WooCommerce live cart for this user/session should be empty or inactive.
     */
    private static function cart_still_abandoned($row) {
        if (!function_exists('WC') || !WC()->cart) {
            return true;
        }

        $current_phone = WC_Acart_SMS_Cart_Tracker::resolve_phone();
        if ($current_phone && $current_phone === $row->phone && !WC()->cart->is_empty()) {
            $last = strtotime($row->last_activity);
            $now  = strtotime(current_time('mysql'));
            if (($now - $last) < (WC_Acart_SMS_Settings::get_abandon_minutes() * 60)) {
                return false;
            }
        }

        return true;
    }
}
