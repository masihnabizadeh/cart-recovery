<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Admin_Page {

    public static function render_dashboard() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $stats = WC_Acart_SMS_Database::get_stats();
        $next  = wp_next_scheduled(WC_Acart_SMS_Cron::HOOK);
        ?>
        <div class="wrap wc-acart-sms-wrap">
            <h1><?php esc_html_e('Abandoned Cart SMS', 'wc-abandoned-cart-sms'); ?></h1>

            <div class="wc-acart-stats-grid">
                <div class="wc-acart-stat-item">
                    <h3><?php esc_html_e('سبدهای فعال (در انتظار)', 'wc-abandoned-cart-sms'); ?></h3>
                    <div class="value"><?php echo esc_html($stats['active']); ?></div>
                </div>
                <div class="wc-acart-stat-item">
                    <h3><?php esc_html_e('سبدهای رها شده', 'wc-abandoned-cart-sms'); ?></h3>
                    <div class="value"><?php echo esc_html($stats['abandoned']); ?></div>
                </div>
                <div class="wc-acart-stat-item">
                    <h3><?php esc_html_e('پیامک ارسال شده', 'wc-abandoned-cart-sms'); ?></h3>
                    <div class="value"><?php echo esc_html($stats['sms_sent']); ?></div>
                </div>
                <div class="wc-acart-stat-item">
                    <h3><?php esc_html_e('بازیابی شده', 'wc-abandoned-cart-sms'); ?></h3>
                    <div class="value"><?php echo esc_html($stats['recovered']); ?></div>
                </div>
                <div class="wc-acart-stat-item">
                    <h3><?php esc_html_e('درآمد بازیابی (تقریبی)', 'wc-abandoned-cart-sms'); ?></h3>
                    <div class="value"><?php echo wp_kses_post(wc_price($stats['revenue'])); ?></div>
                </div>
            </div>

            <div class="wc-acart-report-card">
                <h2><?php esc_html_e('وضعیت Cron', 'wc-abandoned-cart-sms'); ?></h2>
                <?php if ($next) : ?>
                    <p>
                        <?php
                        printf(
                            /* translators: %s: datetime */
                            esc_html__('اجرای بعدی: %s', 'wc-abandoned-cart-sms'),
                            esc_html(wp_date('Y-m-d H:i:s', $next))
                        );
                        ?>
                    </p>
                <?php else : ?>
                    <p class="notice notice-warning inline">
                        <?php esc_html_e('رویداد Cron ثبت نشده است. افزونه را غیرفعال و دوباره فعال کنید.', 'wc-abandoned-cart-sms'); ?>
                    </p>
                <?php endif; ?>

                <p>
                    <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=wc-acart-sms-settings')); ?>">
                        <?php esc_html_e('تنظیمات', 'wc-abandoned-cart-sms'); ?>
                    </a>
                    <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=wc-acart-sms-reports')); ?>">
                        <?php esc_html_e('گزارش و لیست سبده‌ها', 'wc-abandoned-cart-sms'); ?>
                    </a>
                </p>

                <?php if (isset($_GET['wc_acart_test']) && check_admin_referer('wc_acart_test_cron')) : ?>
                    <?php
                    WC_Acart_SMS_Abandon_Detector::process();
                    ?>
                    <p class="notice notice-success inline"><?php esc_html_e('پردازش دستی سبدهای رها شده اجرا شد.', 'wc-abandoned-cart-sms'); ?></p>
                <?php endif; ?>

                <p>
                    <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=wc-acart-sms&wc_acart_test=1'), 'wc_acart_test_cron')); ?>">
                        <?php esc_html_e('اجرای دستی الان (تست)', 'wc-abandoned-cart-sms'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    public static function render_settings() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        ?>
        <div class="wrap wc-acart-sms-wrap">
            <h1><?php esc_html_e('تنظیمات Abandoned Cart SMS', 'wc-abandoned-cart-sms'); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields('wc_acart_sms_settings_group'); ?>

                <h2><?php esc_html_e('اتصال sms.ir', 'wc-abandoned-cart-sms'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="wc_acart_sms_api_key"><?php esc_html_e('API Key', 'wc-abandoned-cart-sms'); ?></label></th>
                        <td>
                            <input type="text" class="regular-text" id="wc_acart_sms_api_key" name="wc_acart_sms_api_key"
                                value="<?php echo esc_attr(WC_Acart_SMS_Settings::get_api_key()); ?>" autocomplete="off" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wc_acart_sms_line_number"><?php esc_html_e('Line Number', 'wc-abandoned-cart-sms'); ?></label></th>
                        <td>
                            <input type="text" class="regular-text" id="wc_acart_sms_line_number" name="wc_acart_sms_line_number"
                                value="<?php echo esc_attr(WC_Acart_SMS_Settings::get_line_number()); ?>" />
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('سبد رها شده', 'wc-abandoned-cart-sms'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="wc_acart_sms_abandon_minutes"><?php esc_html_e('زمان عدم فعالیت (دقیقه)', 'wc-abandoned-cart-sms'); ?></label></th>
                        <td>
                            <input type="number" min="1" class="small-text" id="wc_acart_sms_abandon_minutes" name="wc_acart_sms_abandon_minutes"
                                value="<?php echo esc_attr(WC_Acart_SMS_Settings::get_abandon_minutes()); ?>" />
                            <p class="description"><?php esc_html_e('پیش‌فرض: ۴۵ دقیقه', 'wc-abandoned-cart-sms'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('قالب پیامک', 'wc-abandoned-cart-sms'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="wc_acart_sms_message_template"><?php esc_html_e('متن پیامک', 'wc-abandoned-cart-sms'); ?></label></th>
                        <td>
                            <textarea id="wc_acart_sms_message_template" name="wc_acart_sms_message_template" rows="6" class="large-text"><?php echo esc_textarea(WC_Acart_SMS_Settings::get_message_template()); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('متغیرها: {cart_link} {coupon} {expiry}', 'wc-abandoned-cart-sms'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('کد تخفیف', 'wc-abandoned-cart-sms'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="wc_acart_sms_enable_coupon"><?php esc_html_e('فعال‌سازی کوپن', 'wc-abandoned-cart-sms'); ?></label></th>
                        <td>
                            <select id="wc_acart_sms_enable_coupon" name="wc_acart_sms_enable_coupon">
                                <option value="yes" <?php selected(WC_Acart_SMS_Settings::is_coupon_enabled(), true); ?>><?php esc_html_e('بله', 'wc-abandoned-cart-sms'); ?></option>
                                <option value="no" <?php selected(WC_Acart_SMS_Settings::is_coupon_enabled(), false); ?>><?php esc_html_e('خیر', 'wc-abandoned-cart-sms'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wc_acart_sms_coupon_prefix"><?php esc_html_e('پیشوند کد', 'wc-abandoned-cart-sms'); ?></label></th>
                        <td>
                            <input type="text" class="regular-text" id="wc_acart_sms_coupon_prefix" name="wc_acart_sms_coupon_prefix"
                                value="<?php echo esc_attr(WC_Acart_SMS_Settings::get_coupon_prefix()); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wc_acart_sms_coupon_type"><?php esc_html_e('نوع تخفیف', 'wc-abandoned-cart-sms'); ?></label></th>
                        <td>
                            <select id="wc_acart_sms_coupon_type" name="wc_acart_sms_coupon_type">
                                <option value="percent" <?php selected(WC_Acart_SMS_Settings::get_coupon_type(), 'percent'); ?>><?php esc_html_e('درصد', 'wc-abandoned-cart-sms'); ?></option>
                                <option value="fixed" <?php selected(WC_Acart_SMS_Settings::get_coupon_type(), 'fixed'); ?>><?php esc_html_e('مبلغ ثابت', 'wc-abandoned-cart-sms'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wc_acart_sms_coupon_amount"><?php esc_html_e('مقدار تخفیف', 'wc-abandoned-cart-sms'); ?></label></th>
                        <td>
                            <input type="number" step="0.01" min="0" class="small-text" id="wc_acart_sms_coupon_amount" name="wc_acart_sms_coupon_amount"
                                value="<?php echo esc_attr(WC_Acart_SMS_Settings::get_coupon_amount()); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wc_acart_sms_coupon_expiry_hours"><?php esc_html_e('مدت اعتبار (ساعت)', 'wc-abandoned-cart-sms'); ?></label></th>
                        <td>
                            <input type="number" min="1" class="small-text" id="wc_acart_sms_coupon_expiry_hours" name="wc_acart_sms_coupon_expiry_hours"
                                value="<?php echo esc_attr(WC_Acart_SMS_Settings::get_coupon_expiry_hours()); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wc_acart_sms_coupon_code_length"><?php esc_html_e('طول بخش تصادفی کد', 'wc-abandoned-cart-sms'); ?></label></th>
                        <td>
                            <input type="number" min="4" max="20" class="small-text" id="wc_acart_sms_coupon_code_length" name="wc_acart_sms_coupon_code_length"
                                value="<?php echo esc_attr(WC_Acart_SMS_Settings::get_coupon_code_length()); ?>" />
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('ذخیره تنظیمات', 'wc-abandoned-cart-sms')); ?>
            </form>
        </div>
        <?php
    }

    public static function render_reports() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $stats = WC_Acart_SMS_Database::get_stats();
        $carts = WC_Acart_SMS_Database::get_carts_list(200);
        ?>
        <div class="wrap wc-acart-sms-wrap">
            <h1><?php esc_html_e('گزارش سبدهای رها شده', 'wc-abandoned-cart-sms'); ?></h1>

            <div class="wc-acart-stats-grid">
                <div class="wc-acart-stat-item">
                    <h3><?php esc_html_e('رها شده (ثبت‌شده)', 'wc-abandoned-cart-sms'); ?></h3>
                    <div class="value"><?php echo esc_html($stats['abandoned']); ?></div>
                </div>
                <div class="wc-acart-stat-item">
                    <h3><?php esc_html_e('پیامک ارسال شده', 'wc-abandoned-cart-sms'); ?></h3>
                    <div class="value"><?php echo esc_html($stats['sms_sent']); ?></div>
                </div>
                <div class="wc-acart-stat-item">
                    <h3><?php esc_html_e('بازیابی شده', 'wc-abandoned-cart-sms'); ?></h3>
                    <div class="value"><?php echo esc_html($stats['recovered']); ?></div>
                </div>
                <div class="wc-acart-stat-item">
                    <h3><?php esc_html_e('درآمد بازیابی', 'wc-abandoned-cart-sms'); ?></h3>
                    <div class="value"><?php echo wp_kses_post(wc_price($stats['revenue'])); ?></div>
                </div>
            </div>

            <h2><?php esc_html_e('لیست سبدهای ذخیره‌شده', 'wc-abandoned-cart-sms'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('شناسه', 'wc-abandoned-cart-sms'); ?></th>
                        <th><?php esc_html_e('موبایل', 'wc-abandoned-cart-sms'); ?></th>
                        <th><?php esc_html_e('مبلغ سبد', 'wc-abandoned-cart-sms'); ?></th>
                        <th><?php esc_html_e('کوپن', 'wc-abandoned-cart-sms'); ?></th>
                        <th><?php esc_html_e('وضعیت', 'wc-abandoned-cart-sms'); ?></th>
                        <th><?php esc_html_e('آخرین فعالیت', 'wc-abandoned-cart-sms'); ?></th>
                        <th><?php esc_html_e('رها شده در', 'wc-abandoned-cart-sms'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($carts)) : ?>
                        <tr><td colspan="7"><?php esc_html_e('رکوردی یافت نشد.', 'wc-abandoned-cart-sms'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($carts as $cart) : ?>
                            <?php $status = WC_Acart_SMS_Database::get_cart_status_label($cart); ?>
                            <tr>
                                <td><?php echo esc_html($cart->id); ?></td>
                                <td class="column-phone"><?php echo esc_html($cart->phone); ?></td>
                                <td><?php echo wp_kses_post(wc_price((float) $cart->cart_total)); ?></td>
                                <td><?php echo esc_html($cart->coupon_code ?: '—'); ?></td>
                                <td><span class="status-<?php echo esc_attr($status); ?>"><?php echo esc_html(self::status_text($status)); ?></span></td>
                                <td><?php echo esc_html($cart->last_activity); ?></td>
                                <td><?php echo esc_html($cart->abandoned_at ?: '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function status_text($status) {
        $map = [
            'active'    => __('فعال', 'wc-abandoned-cart-sms'),
            'abandoned' => __('رها شده', 'wc-abandoned-cart-sms'),
            'sms_sent'  => __('پیامک ارسال شد', 'wc-abandoned-cart-sms'),
            'recovered' => __('بازیابی شد', 'wc-abandoned-cart-sms'),
        ];
        return $map[$status] ?? $status;
    }
}
