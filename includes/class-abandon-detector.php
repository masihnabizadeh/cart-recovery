<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Abandon_Detector {

    /**
     * پیدا کردن سبدهای رها شده
     */
    public static function process_abandoned_carts() {

        global $wpdb;

        $table_name = $wpdb->prefix . 'acart_sms';

        /**
         * مدت زمان انتظار
         * پیشفرض: 45 دقیقه
         */

        $minutes = intval(
            get_option(
                'wc_acart_sms_abandon_time',
                45
            )
        );

        // حداقل 1 دقیقه
        if ($minutes < 1) {
            $minutes = 1;
        }

        /**
         * زمان cutoff
         */

        $cutoff_time = gmdate(
            'Y-m-d H:i:s',
            time() - ($minutes * 60)
        );

        /**
         * دریافت سبدهای رها شده
         */

        $rows = $wpdb->get_results(
            $wpdb->prepare(

                "SELECT *
                 FROM {$table_name}

                 WHERE last_activity <= %s

                 AND sms_sent = 0

                 AND recovered = 0

                 AND phone != ''

                 ORDER BY id ASC",

                $cutoff_time
            )
        );

        // چیزی پیدا نشد
        if (empty($rows)) {
            return;
        }

        foreach ($rows as $row) {

            /**
             * بررسی ثبت سفارش
             */

            if (self::has_completed_order($row->phone)) {

                // دیگر abandoned نیست
                continue;
            }

            /**
             * ثبت زمان abandoned
             */

            if (empty($row->abandoned_at)) {

                $wpdb->update(
                    $table_name,
                    [
                        'abandoned_at' => current_time('mysql')
                    ],
                    [
                        'id' => $row->id
                    ]
                );
            }

            /**
             * ساخت لینک بازیابی
             */

            $recovery_url = add_query_arg(
                [
                    'acart_recover' => 1,
                    'key' => $row->recovery_key,
                ],
                wc_get_cart_url()
            );

            /**
             * ساخت کد تخفیف
             */

            $coupon_code = '';

            $enable_coupon = get_option(
                'wc_acart_sms_enable_coupon',
                'yes'
            );

            if ($enable_coupon === 'yes') {

                $coupon_code = WC_Acart_SMS_Coupon::generate_coupon(
                    $row
                );

                $wpdb->update(
                    $table_name,
                    [
                        'coupon_code' => $coupon_code
                    ],
                    [
                        'id' => $row->id
                    ]
                );
            }

            /**
             * متن پیامک
             */

            $message = get_option(
                'wc_acart_sms_message',
                'سبد خرید شما هنوز تکمیل نشده است: {cart_url}'
            );

            /**
             * جایگزینی متغیرها
             */

            $message = str_replace(
                '{cart_url}',
                $recovery_url,
                $message
            );

            $message = str_replace(
                '{coupon}',
                $coupon_code,
                $message
            );

            $message = str_replace(
                '{site_name}',
                get_bloginfo('name'),
                $message
            );

            $message = str_replace(
                '{phone}',
                $row->phone,
                $message
            );

            /**
             * ارسال پیامک
             */

            $sent = WC_Acart_SMS_SMS::send_sms(
                $row->phone,
                $message
            );

            /**
             * ثبت وضعیت
             */

            if ($sent) {

                $wpdb->update(
                    $table_name,
                    [
                        'sms_sent' => 1
                    ],
                    [
                        'id' => $row->id
                    ]
                );
            }
        }
    }

    /**
     * آیا کاربر سفارش ثبت کرده؟
     */

    private static function has_completed_order($phone) {

        if (empty($phone)) {
            return false;
        }

        $orders = wc_get_orders([

            'limit' => 1,

            'billing_phone' => $phone,

            'status' => [

                'processing',
                'completed',
                'on-hold',
            ],

            'orderby' => 'date',

            'order' => 'DESC',
        ]);

        return !empty($orders);
    }
}