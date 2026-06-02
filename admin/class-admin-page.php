<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Admin_Page {

    public static function render_dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Abandoned Cart SMS Dashboard', 'wc-abandoned-cart-sms'); ?></h1>
            <p><?php esc_html_e('Manage abandoned cart SMS recovery, settings, and reports.', 'wc-abandoned-cart-sms'); ?></p>
        </div>
        <?php
    }

    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Abandoned Cart SMS Settings', 'wc-abandoned-cart-sms'); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields('wc_acart_sms_settings_group'); ?>

                <h2><?php esc_html_e('SMS.ir Settings', 'wc-abandoned-cart-sms'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wc_acart_sms_api_key"><?php esc_html_e('API Key', 'wc-abandoned-cart-sms'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="wc_acart_sms_api_key"
                                name="wc_acart_sms_api_key"
                                value="<?php echo esc_attr(get_option('wc_acart_sms_api_key', '')); ?>"
                                class="regular-text"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wc_acart_sms_line_number"><?php esc_html_e('Line Number', 'wc-abandoned-cart-sms'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="wc_acart_sms_line_number"
                                name="wc_acart_sms_line_number"
                                value="<?php echo esc_attr(get_option('wc_acart_sms_line_number', '')); ?>"
                                class="regular-text"
                            />
                        </td>
                    </tr>
                </table>

                <hr>

                <h2><?php esc_html_e('SMS Message Template', 'wc-abandoned-cart-sms'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wc_acart_sms_text_template"><?php esc_html_e('SMS Text', 'wc-abandoned-cart-sms'); ?></label>
                        </th>
                        <td>
                            <textarea
                                id="wc_acart_sms_text_template"
                                name="wc_acart_sms_text_template"
                                rows="8"
                                class="large-text"
                            ><?php echo esc_textarea(WC_Acart_SMS_Settings::get_sms_template()); ?></textarea>

                            <p class="description">
                                <?php esc_html_e('Available variables: [site_name], [link], [coupon]', 'wc-abandoned-cart-sms'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <hr>

                <h2><?php esc_html_e('Coupon Settings', 'wc-abandoned-cart-sms'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wc_acart_sms_coupon_type"><?php esc_html_e('Coupon Type', 'wc-abandoned-cart-sms'); ?></label>
                        </th>
                        <td>
                            <select id="wc_acart_sms_coupon_type" name="wc_acart_sms_coupon_type">
                                <option value="percent" <?php selected(get_option('wc_acart_sms_coupon_type', 'percent'), 'percent'); ?>>
                                    <?php esc_html_e('Percentage discount', 'wc-abandoned-cart-sms'); ?>
                                </option>
                                <option value="fixed_cart" <?php selected(get_option('wc_acart_sms_coupon_type', 'percent'), 'fixed_cart'); ?>>
                                    <?php esc_html_e('Fixed cart discount', 'wc-abandoned-cart-sms'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wc_acart_sms_coupon_amount"><?php esc_html_e('Coupon Amount', 'wc-abandoned-cart-sms'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                step="0.01"
                                id="wc_acart_sms_coupon_amount"
                                name="wc_acart_sms_coupon_amount"
                                value="<?php echo esc_attr(get_option('wc_acart_sms_coupon_amount', 10)); ?>"
                                class="small-text"
                            />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wc_acart_sms_coupon_expiry"><?php esc_html_e('Coupon Expiry (hours)', 'wc-abandoned-cart-sms'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="wc_acart_sms_coupon_expiry"
                                name="wc_acart_sms_coupon_expiry"
                                value="<?php echo esc_attr(get_option('wc_acart_sms_coupon_expiry', 24)); ?>"
                                class="small-text"
                            />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wc_acart_sms_coupon_prefix"><?php esc_html_e('Coupon Prefix', 'wc-abandoned-cart-sms'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="wc_acart_sms_coupon_prefix"
                                name="wc_acart_sms_coupon_prefix"
                                value="<?php echo esc_attr(get_option('wc_acart_sms_coupon_prefix', 'ACART')); ?>"
                                class="regular-text"
                            />
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public static function render_reports_page() {

        $abandoned_carts = class_exists('WC_Acart_SMS_Database') && method_exists('WC_Acart_SMS_Database', 'get_abandoned_carts')
            ? WC_Acart_SMS_Database::get_abandoned_carts()
            : [];

        $sms_sent_count = class_exists('WC_Acart_SMS_Database') && method_exists('WC_Acart_SMS_Database', 'get_sms_sent_count')
            ? WC_Acart_SMS_Database::get_sms_sent_count()
            : 0;

        $recovered_carts_count = class_exists('WC_Acart_SMS_Database') && method_exists('WC_Acart_SMS_Database', 'get_recovered_carts_count')
            ? WC_Acart_SMS_Database::get_recovered_carts_count()
            : 0;

        $recovered_revenue = class_exists('WC_Acart_SMS_Database') && method_exists('WC_Acart_SMS_Database', 'get_recovered_revenue')
            ? WC_Acart_SMS_Database::get_recovered_revenue()
            : 0;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Abandoned Cart SMS Reports', 'wc-abandoned-cart-sms'); ?></h1>

            <table class="widefat striped" style="max-width: 900px; margin-bottom: 30px;">
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e('Total SMS Sent', 'wc-abandoned-cart-sms'); ?></strong></td>
                        <td><?php echo esc_html($sms_sent_count); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Recovered Carts', 'wc-abandoned-cart-sms'); ?></strong></td>
                        <td><?php echo esc_html($recovered_carts_count); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Recovered Revenue', 'wc-abandoned-cart-sms'); ?></strong></td>
                        <td><?php echo esc_html(wc_price($recovered_revenue)); ?></td>
                    </tr>
                </tbody>
            </table>

            <h2><?php esc_html_e('Abandoned Carts', 'wc-abandoned-cart-sms'); ?></h2>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'wc-abandoned-cart-sms'); ?></th>
                        <th><?php esc_html_e('Phone', 'wc-abandoned-cart-sms'); ?></th>
                        <th><?php esc_html_e('Status', 'wc-abandoned-cart-sms'); ?></th>
                        <th><?php esc_html_e('Created At', 'wc-abandoned-cart-sms'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($abandoned_carts)) : ?>
                        <?php foreach ($abandoned_carts as $cart) : ?>
                            <tr>
                                <td><?php echo esc_html($cart->id ?? ''); ?></td>
                                <td><?php echo esc_html($cart->phone ?? ''); ?></td>
                                <td><?php echo esc_html($cart->status ?? ''); ?></td>
                                <td><?php echo esc_html($cart->created_at ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4"><?php esc_html_e('No abandoned carts found.', 'wc-abandoned-cart-sms'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
