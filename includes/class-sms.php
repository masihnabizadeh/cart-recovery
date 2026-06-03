<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_SMS {

    /**
     * Build message from admin template and placeholders.
     */
    public static function build_message($cart_link, $coupon_code = '') {
        $template = WC_Acart_SMS_Settings::get_message_template();

        $replacements = [
            '{cart_link}' => $cart_link,
            '{coupon}'    => $coupon_code ?: '—',
            '{expiry}'    => WC_Acart_SMS_Settings::format_expiry_label(),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Send SMS via sms.ir (bulk API v1).
     */
    public static function send($phone, $message) {
        $api_key     = WC_Acart_SMS_Settings::get_api_key();
        $line_number = WC_Acart_SMS_Settings::get_line_number();

        if (empty($api_key) || empty($line_number) || empty($phone) || empty($message)) {
            return false;
        }

        $phone = WC_Acart_SMS_Cart_Tracker::normalize_phone($phone);
        if (empty($phone)) {
            return false;
        }

        $mobile = $phone;
        if (strpos($mobile, '0') === 0) {
            $mobile = '98' . substr($mobile, 1);
        }

        $response = wp_remote_post(
            'https://api.sms.ir/v1/send/bulk',
            [
                'timeout' => 20,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'text/plain',
                    'x-api-key'    => $api_key,
                ],
                'body' => wp_json_encode([
                    'lineNumber'  => (int) $line_number,
                    'messageText' => $message,
                    'mobiles'     => [$mobile],
                ]),
            ]
        );

        if (is_wp_error($response)) {
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        return $code >= 200 && $code < 300;
    }
}
