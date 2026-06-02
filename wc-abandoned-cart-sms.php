<?php
/**
 * Plugin Name: WooCommerce Abandoned Cart SMS
 * Version: 2.0.0
 * Description: Modular abandoned cart recovery via Order Lifecycle
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS {

    public function __construct() {

        $this->define_constants();

        $this->includes();

        $this->init_hooks();
    }

    private function define_constants() {

        define('WC_ACART_SMS_PATH', plugin_dir_path(__FILE__));

        define('WC_ACART_SMS_URL', plugin_dir_url(__FILE__));
    }

    private function includes() {

        require_once WC_ACART_SMS_PATH . 'includes/class-database.php';

        require_once WC_ACART_SMS_PATH . 'includes/class-settings.php';

        require_once WC_ACART_SMS_PATH . 'includes/class-cart-tracker.php';

        require_once WC_ACART_SMS_PATH . 'includes/class-abandon-detector.php';

        require_once WC_ACART_SMS_PATH . 'includes/class-cron.php';

        require_once WC_ACART_SMS_PATH . 'includes/class-sms.php';

        require_once WC_ACART_SMS_PATH . 'includes/class-recovery.php';

        require_once WC_ACART_SMS_PATH . 'includes/class-coupon.php';

        if (is_admin()) {

            require_once WC_ACART_SMS_PATH . 'admin/class-admin-menu.php';

            require_once WC_ACART_SMS_PATH . 'admin/class-admin-page.php';
        }
    }

    private function init_hooks() {

        // Start cron system
        new WC_Acart_SMS_Cron();

        new WC_Acart_SMS_Cart_Tracker();

        // Handle recovery links
        add_action(
            'init',
            ['WC_Acart_SMS_Recovery', 'handle_recovery_request']
        );
    }
}


/**
 * Plugin activation
 */
register_activation_hook(
    __FILE__,
    ['WC_Acart_SMS_Database', 'create_table']
);

register_activation_hook(
    __FILE__,
    ['WC_Acart_SMS_Cron', 'schedule_event']
);


/**
 * Plugin deactivation
 */
register_deactivation_hook(
    __FILE__,
    ['WC_Acart_SMS_Cron', 'clear_scheduled_event']
);


new WC_Acart_SMS();