<?php
/**
 * Plugin Name: WooCommerce Abandoned Cart SMS
 * Plugin URI:  https://honix.ir
 * Description: تشخیص سبد خرید رها شده و ارسال پیامک بازیابی با sms.ir برای فروشگاه‌های ایرانی
 * Version:     1.1.0
 * Author:      Honix
 * Text Domain: wc-abandoned-cart-sms
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WC_ACART_SMS_VERSION', '1.1.0');
define('WC_ACART_SMS_PATH', plugin_dir_path(__FILE__));
define('WC_ACART_SMS_URL', plugin_dir_url(__FILE__));
define('WC_ACART_SMS_BASENAME', plugin_basename(__FILE__));

final class WC_Acart_SMS_Plugin {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
            return;
        }

        $this->includes();
        $this->hooks();
    }

    private function includes() {
        require_once WC_ACART_SMS_PATH . 'includes/class-database.php';
        require_once WC_ACART_SMS_PATH . 'includes/class-settings.php';
        require_once WC_ACART_SMS_PATH . 'includes/class-cart-tracker.php';
        require_once WC_ACART_SMS_PATH . 'includes/class-coupon.php';
        require_once WC_ACART_SMS_PATH . 'includes/class-sms.php';
        require_once WC_ACART_SMS_PATH . 'includes/class-recovery.php';
        require_once WC_ACART_SMS_PATH . 'includes/class-abandon-detector.php';
        require_once WC_ACART_SMS_PATH . 'includes/class-cron.php';

        if (is_admin()) {
            require_once WC_ACART_SMS_PATH . 'admin/class-admin-menu.php';
            require_once WC_ACART_SMS_PATH . 'admin/class-admin-page.php';
        }
    }

    private function hooks() {
        new WC_Acart_SMS_Settings();
        new WC_Acart_SMS_Cart_Tracker();
        new WC_Acart_SMS_Cron();

        WC_Acart_SMS_Recovery::init();

        add_action('admin_init', function () {
            if (version_compare((string) get_option('wc_acart_sms_db_version', '0'), WC_ACART_SMS_VERSION, '<')) {
                WC_Acart_SMS_Database::create_table();
                WC_Acart_SMS_Recovery::flush_rewrite_rules();
                update_option('wc_acart_sms_db_version', WC_ACART_SMS_VERSION);
            }
        });

        if (is_admin()) {
            new WC_Acart_SMS_Admin_Menu();
        }
    }

    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        esc_html_e('افزونه Abandoned Cart SMS به WooCommerce نیاز دارد.', 'wc-abandoned-cart-sms');
        echo '</p></div>';
    }
}

register_activation_hook(__FILE__, function () {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('این افزونه به WooCommerce نیاز دارد.', 'wc-abandoned-cart-sms'),
            esc_html__('خطا در فعال‌سازی', 'wc-abandoned-cart-sms'),
            ['back_link' => true]
        );
    }

    require_once WC_ACART_SMS_PATH . 'includes/class-database.php';
    require_once WC_ACART_SMS_PATH . 'includes/class-cron.php';

    require_once WC_ACART_SMS_PATH . 'includes/class-recovery.php';

    WC_Acart_SMS_Database::create_table();
    WC_Acart_SMS_Cron::schedule_event();
    WC_Acart_SMS_Recovery::flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    require_once WC_ACART_SMS_PATH . 'includes/class-cron.php';
    WC_Acart_SMS_Cron::clear_scheduled_event();
});

WC_Acart_SMS_Plugin::instance();
