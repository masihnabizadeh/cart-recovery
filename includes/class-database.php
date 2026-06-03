<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Acart_SMS_Database {

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'acart_sms_carts';
    }

    public static function create_table() {
        global $wpdb;

        $table_name      = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(100) NULL,
            user_id BIGINT UNSIGNED NULL,
            phone VARCHAR(20) NULL,
            cart_data LONGTEXT NULL,
            cart_total DECIMAL(10,2) NULL,
            coupon_code VARCHAR(50) NULL,
            recovery_hash VARCHAR(64) NULL,
            sms_sent TINYINT(1) DEFAULT 0,
            recovered TINYINT(1) DEFAULT 0,
            created_at DATETIME NOT NULL,
            last_activity DATETIME NOT NULL,
            abandoned_at DATETIME NULL,
            recovered_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY phone (phone),
            KEY sms_sent (sms_sent),
            KEY abandoned_at (abandoned_at),
            KEY recovery_hash (recovery_hash)
        ) {$charset_collate};";

        dbDelta($sql);

        self::migrate_legacy_table();
    }

    /**
     * Migrate rows from old wp_acart_sms table if present.
     */
    private static function migrate_legacy_table() {
        global $wpdb;

        $legacy = $wpdb->prefix . 'acart_sms';
        $new    = self::table_name();

        $legacy_exists = $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', $legacy)
        );

        if ($legacy_exists !== $legacy) {
            return;
        }

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$new}");
        if ($count > 0) {
            return;
        }

        $wpdb->query(
            "INSERT INTO {$new} (
                session_id, user_id, phone, cart_data, cart_total, coupon_code,
                recovery_hash, sms_sent, recovered, created_at, last_activity,
                abandoned_at, recovered_at
            )
            SELECT
                NULL,
                user_id,
                phone,
                cart_data,
                cart_total,
                coupon_code,
                recovery_key,
                sms_sent,
                recovered,
                COALESCE(created_at, NOW()),
                COALESCE(last_activity, NOW()),
                abandoned_at,
                recovered_at
            FROM {$legacy}"
        );
    }

    public static function upsert_active_cart(array $data) {
        global $wpdb;

        $table = self::table_name();
        $now   = current_time('mysql');

        $defaults = [
            'session_id'    => '',
            'user_id'       => null,
            'phone'         => '',
            'cart_data'     => '',
            'cart_total'    => 0,
            'last_activity' => $now,
        ];

        $data = wp_parse_args($data, $defaults);

        if (empty($data['phone']) || empty($data['cart_data'])) {
            return false;
        }

        $where  = ['recovered = 0', 'sms_sent = 0'];
        $params = [];

        if (!empty($data['user_id'])) {
            $where[]  = 'user_id = %d';
            $params[] = (int) $data['user_id'];
        } elseif (!empty($data['session_id'])) {
            $where[]  = 'session_id = %s';
            $params[] = $data['session_id'];
        } else {
            $where[]  = 'phone = %s';
            $params[] = $data['phone'];
        }

        $sql = "SELECT id, recovery_hash FROM {$table} WHERE " . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT 1';

        $existing = $wpdb->get_row($wpdb->prepare($sql, $params));

        $recovery_hash = $existing && !empty($existing->recovery_hash)
            ? $existing->recovery_hash
            : wp_generate_password(32, false, false);

        $row = [
            'session_id'    => $data['session_id'] ?: null,
            'user_id'       => $data['user_id'] ?: null,
            'phone'         => $data['phone'],
            'cart_data'     => $data['cart_data'],
            'cart_total'    => $data['cart_total'],
            'last_activity' => $data['last_activity'],
            'recovery_hash' => $recovery_hash,
        ];

        if ($existing) {
            $wpdb->update($table, $row, ['id' => $existing->id]);
            return (int) $existing->id;
        }

        $row['created_at'] = $now;
        $wpdb->insert($table, $row);

        return (int) $wpdb->insert_id;
    }

    public static function get_abandoned_candidates($cutoff_mysql) {
        global $wpdb;

        $table = self::table_name();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE last_activity <= %s
                 AND sms_sent = 0
                 AND recovered = 0
                 AND phone IS NOT NULL
                 AND phone != ''
                 AND cart_data IS NOT NULL
                 AND cart_data != ''
                 ORDER BY id ASC",
                $cutoff_mysql
            )
        );
    }

    public static function update_row($id, array $fields) {
        global $wpdb;
        return $wpdb->update(self::table_name(), $fields, ['id' => (int) $id]);
    }

    public static function get_by_recovery_hash($hash) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . self::table_name() . ' WHERE recovery_hash = %s LIMIT 1',
                $hash
            )
        );
    }

    public static function get_stats() {
        global $wpdb;

        $table = self::table_name();

        return [
            'abandoned'  => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table} WHERE abandoned_at IS NOT NULL"
            ),
            'sms_sent'   => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table} WHERE sms_sent = 1"
            ),
            'recovered'  => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table} WHERE recovered = 1"
            ),
            'revenue'    => (float) $wpdb->get_var(
                "SELECT COALESCE(SUM(cart_total), 0) FROM {$table} WHERE recovered = 1"
            ),
            'active'     => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table} WHERE recovered = 0 AND sms_sent = 0"
            ),
        ];
    }

    public static function get_carts_list($limit = 100) {
        global $wpdb;

        $table = self::table_name();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d",
                $limit
            )
        );
    }

    public static function get_cart_status_label($row) {
        if (!empty($row->recovered)) {
            return 'recovered';
        }
        if (!empty($row->sms_sent)) {
            return 'sms_sent';
        }
        if (!empty($row->abandoned_at)) {
            return 'abandoned';
        }
        return 'active';
    }
}
