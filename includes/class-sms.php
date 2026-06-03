<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_SMS {

    public static function build_message($cart_link, $coupon_code = '') {
        $template = WC_Acart_SMS_Settings::get_message_template();

        $replacements = [
            '{cart_link}' => $cart_link,
            '{coupon}'    => $coupon_code !== '' ? $coupon_code : '—',
            '{expiry}'    => WC_Acart_SMS_Settings::format_expiry_label(),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * ارسال پیامک از طریق API رسمی sms.ir (نسخه bulk v1).
     */
    public static function send($phone, $message) {
        $api_key     = WC_Acart_SMS_Settings::get_api_key();
        $line_number = WC_Acart_SMS_Settings::get_line_number();

        if ($api_key === '' || $line_number === '' || $phone === '' || $message === '') {
            self::log('ارسال رد شد: API Key، Line Number، شماره یا متن خالی است.');
            return false;
        }

        $phone = WC_Acart_SMS_Cart_Tracker::normalize_phone($phone);
        if ($phone === '') {
            self::log('ارسال رد شد: شماره موبایل نامعتبر.');
            return false;
        }

        $mobile = $phone;
        if (strpos($mobile, '0') === 0) {
            $mobile = '98' . substr($mobile, 1);
        }

        $response = wp_remote_post(
            'https://api.sms.ir/v1/send/bulk',
            [
                'timeout' => 25,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
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
            self::log('خطای HTTP: ' . $response->get_error_message());
            return false;
        }

        $http_code = (int) wp_remote_retrieve_response_code($response);
        $body      = wp_remote_retrieve_body($response);
        $data      = json_decode($body, true);

        if ($http_code < 200 || $http_code >= 300) {
            self::log(sprintf('پاسخ HTTP %d: %s', $http_code, $body));
            return false;
        }

        if (is_array($data) && isset($data['status']) && (int) $data['status'] !== 1) {
            $msg = isset($data['message']) ? $data['message'] : $body;
            self::log('sms.ir status ناموفق: ' . $msg);
            return false;
        }

        return true;
    }

    private static function log($message) {
        if (!function_exists('wc_get_logger')) {
            return;
        }

        wc_get_logger()->info($message, ['source' => 'wc-abandoned-cart-sms']);
    }
}
