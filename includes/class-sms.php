<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Sms {

    private $api_key;
    private $line_number;

    public function __construct() {

        $this->api_key     = get_option('wc_acart_sms_api_key');
        $this->line_number = get_option('wc_acart_sms_line_number');
    }

    /**
     * Send recovery SMS
     */
    public function send_recovery_sms($phone, $link) {

        if (empty($phone) || empty($link)) {
            return false;
        }

        $message = $this->build_message($link);

        return $this->send_sms($phone, $message);
    }

    /**
     * Build SMS text from template
     */
    private function build_message($link) {

        $template = get_option(
            'wc_acart_sms_text_template',
            'سبد خرید شما در [site_name] ناتمام مانده است. برای تکمیل خرید روی لینک زیر کلیک کنید: [link]'
        );

        $site_name = get_bloginfo('name');

        $message = str_replace(
            ['[site_name]', '[link]'],
            [$site_name, $link],
            $template
        );

        return $message;
    }

    /**
     * Send SMS via sms.ir
     */
    private function send_sms($phone, $message) {

        if (empty($this->api_key) || empty($this->line_number)) {
            return false;
        }

        $endpoint = "https://api.sms.ir/v1/send/bulk";

        $body = [
            "lineNumber" => $this->line_number,
            "messageText" => $message,
            "mobiles" => [$phone]
        ];

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'text/plain',
                'x-api-key'     => $this->api_key
            ],
            'body' => json_encode($body),
            'timeout' => 20
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $status = wp_remote_retrieve_response_code($response);

        if ($status != 200) {
            return false;
        }

        return true;
    }
}
