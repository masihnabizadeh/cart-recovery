<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Abandon_Detector {

    /**
     * @param bool $force اجرای دستی از ادمین: بدون شرط زمان و بدون بررسی سبد فعال مرورگر.
     * @return array{processed:int,skipped:int,candidates:int}
     */
    public static function process($force = false) {
        $minutes = WC_Acart_SMS_Settings::get_abandon_minutes();
        $cutoff  = self::get_cutoff_datetime($minutes);
        $rows    = WC_Acart_SMS_Database::get_abandoned_candidates($cutoff, $force);

        $result = [
            'processed'  => 0,
            'skipped'    => 0,
            'candidates' => count($rows),
        ];

        if (empty($rows)) {
            return $result;
        }

        foreach ($rows as $row) {
            if (self::process_single_cart($row, $force)) {
                $result['processed']++;
            } else {
                $result['skipped']++;
            }
        }

        return $result;
    }

    private static function get_cutoff_datetime($minutes) {
        $ts = current_time('timestamp') - ($minutes * MINUTE_IN_SECONDS);
        return wp_date('Y-m-d H:i:s', $ts);
    }

    /**
     * @return bool true if processed (abandoned + coupon attempted)
     */
    private static function process_single_cart($row, $force = false) {
        if (self::has_recent_order($row)) {
            return false;
        }

        if (!$force && !self::cart_still_abandoned($row)) {
            return false;
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

        return true;
    }

    private static function has_recent_order($row) {
        if (empty($row->phone)) {
            return false;
        }

        $phones   = self::phone_search_variants($row->phone);
        $after_ts = strtotime($row->last_activity);
        if (!$after_ts) {
            return false;
        }

        foreach ($phones as $phone_variant) {
            $orders = wc_get_orders([
                'limit'         => 1,
                'billing_phone' => $phone_variant,
                'status'        => ['processing', 'completed', 'on-hold'],
                'date_created'  => '>' . $after_ts,
                'orderby'       => 'date',
                'order'         => 'DESC',
                'return'        => 'ids',
            ]);

            if (!empty($orders)) {
                return true;
            }
        }

        return false;
    }

    /**
     * اگر همان کاربر الان در مرورگر سبد پر دارد و هنوز داخل بازه رها شدن است، صبر کن.
     */
    private static function cart_still_abandoned($row) {
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return true;
        }

        $row_phone     = WC_Acart_SMS_Cart_Tracker::normalize_phone($row->phone);
        $current_phone = WC_Acart_SMS_Cart_Tracker::resolve_phone();

        if ($row_phone === '' || $current_phone === '' || $row_phone !== $current_phone) {
            return true;
        }

        $minutes = WC_Acart_SMS_Settings::get_abandon_minutes();
        $last_ts = strtotime($row->last_activity);
        $now_ts  = current_time('timestamp');

        if (!$last_ts) {
            return true;
        }

        return ($now_ts - $last_ts) >= ($minutes * MINUTE_IN_SECONDS);
    }

    /**
     * @return string[]
     */
    private static function phone_search_variants($phone) {
        $normalized = WC_Acart_SMS_Cart_Tracker::normalize_phone($phone);
        $variants   = array_unique(array_filter([
            $phone,
            $normalized,
        ]));

        if ($normalized !== '' && strpos($normalized, '0') === 0) {
            $variants[] = '98' . substr($normalized, 1);
            $variants[] = '+98' . substr($normalized, 1);
        }

        return $variants;
    }
}
