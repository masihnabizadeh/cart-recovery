<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Cron {

    const HOOK = 'wc_acart_sms_cron_process';

    public function __construct() {
        add_filter('cron_schedules', [__CLASS__, 'add_five_minute_schedule']);
        add_action(self::HOOK, [__CLASS__, 'run']);
    }

    public static function add_five_minute_schedule($schedules) {
        if (!isset($schedules['every_five_minutes'])) {
            $schedules['every_five_minutes'] = [
                'interval' => 300,
                'display'  => __('هر ۵ دقیقه', 'wc-abandoned-cart-sms'),
            ];
        }
        return $schedules;
    }

    public static function schedule_event() {
        add_filter('cron_schedules', [__CLASS__, 'add_five_minute_schedule']);

        if (!wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time() + 60, 'every_five_minutes', self::HOOK);
        }
    }

    public static function clear_scheduled_event() {
        $timestamp = wp_next_scheduled(self::HOOK);
        while ($timestamp) {
            wp_unschedule_event($timestamp, self::HOOK);
            $timestamp = wp_next_scheduled(self::HOOK);
        }
    }

    public static function run() {
        if (!class_exists('WooCommerce')) {
            return;
        }

        WC_Acart_SMS_Abandon_Detector::process();
    }
}
