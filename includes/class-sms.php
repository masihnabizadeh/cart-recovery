<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_SMS {

    /**
     * @param object|null $cart_row
     */
    public static function build_message($cart_link, $coupon_code = '', $cart_row = null) {
        $template = WC_Acart_SMS_Settings::get_message_template();
        $names    = self::resolve_name_placeholders($cart_row);

        $replacements = [
            '{cart_link}'   => $cart_link,
            '{coupon}'      => $coupon_code !== '' ? $coupon_code : '—',
            '{expiry}'      => WC_Acart_SMS_Settings::format_expiry_label(),
            '{first_name}'  => $names['first_name'],
            '{last_name}'   => $names['last_name'],
            '{full_name}'   => $names['full_name'],
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * @param object|null $cart_row
     * @return array{first_name:string,last_name:string,full_name:string}
     */
    private static function resolve_name_placeholders($cart_row) {
        $empty = [
            'first_name' => '',
            'last_name'  => '',
            'full_name'  => '',
        ];

        if (!WC_Acart_SMS_Settings::include_customer_name()) {
            return $empty;
        }

        $first = '';
        $last  = '';

        if ($cart_row) {
            $first = isset($cart_row->customer_first_name) ? (string) $cart_row->customer_first_name : '';
            $last  = isset($cart_row->customer_last_name) ? (string) $cart_row->customer_last_name : '';

            if (($first === '' || $last === '') && !empty($cart_row->user_id)) {
                $live = WC_Acart_SMS_Cart_Tracker::resolve_customer_names();
                if ($first === '') {
                    $first = $live['first_name'];
                }
                if ($last === '') {
                    $last = $live['last_name'];
                }
            }
        }

        $first = sanitize_text_field($first);
        $last  = sanitize_text_field($last);
        $full  = trim($first . ' ' . $last);

        return [
            'first_name' => $first,
            'last_name'  => $last,
            'full_name'  => $full,
        ];
    }

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
