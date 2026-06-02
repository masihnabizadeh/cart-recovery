<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Cron {

    const CRON_HOOK = 'wc_acart_sms_process_abandoned';

    public function __construct() {
        add_action(self::CRON_HOOK, [$this, 'process_abandoned_carts']);
    }

    /**
     * Schedule cron if not already scheduled
     */
    public static function schedule_event() {

        if (!wp_next_scheduled(self::CRON_HOOK)) {

            wp_schedule_event(
                time(),
                'five_minutes',
                self::CRON_HOOK
            );
        }
    }

    /**
     * Clear cron on deactivation
     */
    public static function clear_event() {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    /**
     * Add custom interval (5 minutes)
     */
    public static function register_interval($schedules) {

        if (!isset($schedules['five_minutes'])) {

            $schedules['five_minutes'] = [
                'interval' => 300,
                'display'  => __('Every Five Minutes')
            ];
        }

        return $schedules;
    }

    /**
     * Main processor
     */
    public function process_abandoned_carts() {

        $detector = new WC_Acart_SMS_Abandon_Detector();
        $sms      = new WC_Acart_SMS_Sms();

        $candidates = $detector->get_candidates();

        if (empty($candidates)) {
            return;
        }

        foreach ($candidates as $row) {

            // Mark as abandoned
            $detector->mark_as_abandoned($row->id);

            // Generate recovery link
            $recovery = new WC_Acart_SMS_Recovery();
            $link = $recovery->generate_link($row);

            if (empty($link)) {
                continue;
            }

            // Send SMS
            $sent = $sms->send_recovery_sms(
                $row->phone,
                $link
            );

            if ($sent) {
                WC_Acart_SMS_Database::mark_sms_sent($row->id);
            }
        }
    }
}
