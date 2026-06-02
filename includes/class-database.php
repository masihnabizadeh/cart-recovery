<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Database {

    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'acart_sms';

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table_name} (

            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            user_id BIGINT UNSIGNED NULL,

            phone VARCHAR(20) NOT NULL,

            cart_data LONGTEXT NULL,

            cart_hash VARCHAR(64) NULL,

            cart_total DECIMAL(18,2) DEFAULT 0,

            last_activity DATETIME NULL,

            abandoned_at DATETIME NULL,

            recovery_key VARCHAR(64) NULL,

            coupon_code VARCHAR(100) NULL,

            sms_sent TINYINT(1) DEFAULT 0,

            recovered TINYINT(1) DEFAULT 0,

            recovered_at DATETIME NULL,

            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id),

            KEY phone (phone),

            KEY user_id (user_id),

            KEY recovery_key (recovery_key),

            KEY sms_sent (sms_sent),

            KEY recovered (recovered),

            KEY last_activity (last_activity),

            KEY abandoned_at (abandoned_at)

        ) {$charset_collate};";

        dbDelta($sql);

        self::maybe_upgrade_table();
    }

    /**
     * Upgrade old installations safely
     */
    public static function maybe_upgrade_table() {

        global $wpdb;

        $table_name = $wpdb->prefix . 'acart_sms';

        $columns = $wpdb->get_col(
            "SHOW COLUMNS FROM {$table_name}",
            0
        );

        $required_columns = [

            'user_id'       => "ALTER TABLE {$table_name} ADD user_id BIGINT UNSIGNED NULL",

            'cart_data'     => "ALTER TABLE {$table_name} ADD cart_data LONGTEXT NULL",

            'cart_hash'     => "ALTER TABLE {$table_name} ADD cart_hash VARCHAR(64) NULL",

            'cart_total'    => "ALTER TABLE {$table_name} ADD cart_total DECIMAL(18,2) DEFAULT 0",

            'last_activity' => "ALTER TABLE {$table_name} ADD last_activity DATETIME NULL",

            'abandoned_at'  => "ALTER TABLE {$table_name} ADD abandoned_at DATETIME NULL",

            'coupon_code'   => "ALTER TABLE {$table_name} ADD coupon_code VARCHAR(100) NULL",

            'recovered_at'  => "ALTER TABLE {$table_name} ADD recovered_at DATETIME NULL",

            'updated_at'    => "ALTER TABLE {$table_name} ADD updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",

        ];

        foreach ($required_columns as $column => $query) {

            if (!in_array($column, $columns)) {
                $wpdb->query($query);
            }
        }
    }

    /**
     * Save or update cart
     */
    public static function save_cart($data = []) {

        global $wpdb;

        $table_name = $wpdb->prefix . 'acart_sms';

        $defaults = [

            'user_id'       => 0,
            'phone'         => '',
            'cart_data'     => '',
            'cart_hash'     => '',
            'cart_total'    => 0,
            'last_activity' => current_time('mysql'),
            'updated_at'    => current_time('mysql'),

        ];

        $data = wp_parse_args($data, $defaults);

        if (empty($data['phone'])) {
            return false;
        }

        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name}
                 WHERE phone = %s
                 AND recovered = 0
                 ORDER BY id DESC
                 LIMIT 1",
                $data['phone']
            )
        );

        if ($existing) {

            $wpdb->update(
                $table_name,
                [

                    'user_id'       => $data['user_id'],
                    'cart_data'     => $data['cart_data'],
                    'cart_hash'     => $data['cart_hash'],
                    'cart_total'    => $data['cart_total'],
                    'last_activity' => $data['last_activity'],
                    'updated_at'    => current_time('mysql'),

                ],
                [
                    'id' => $existing->id
                ]
            );

            return $existing->id;
        }

        $wpdb->insert(
            $table_name,
            [

                'user_id'       => $data['user_id'],
                'phone'         => $data['phone'],
                'cart_data'     => $data['cart_data'],
                'cart_hash'     => $data['cart_hash'],
                'cart_total'    => $data['cart_total'],
                'last_activity' => $data['last_activity'],
                'recovery_key'  => wp_generate_password(32, false),
                'created_at'    => current_time('mysql'),
                'updated_at'    => current_time('mysql'),

            ]
        );

        return $wpdb->insert_id;
    }
}