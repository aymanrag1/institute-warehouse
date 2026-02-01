<?php
if (!defined('ABSPATH')) exit;

class IW_Database {

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $prefix  = $wpdb->prefix . 'iw_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Products table with min/max stock levels
        $sql = "CREATE TABLE {$prefix}products (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            sku varchar(100) DEFAULT '',
            category varchar(255) DEFAULT '',
            unit varchar(50) DEFAULT '',
            min_stock int(11) NOT NULL DEFAULT 0,
            max_stock int(11) NOT NULL DEFAULT 0,
            current_stock int(11) NOT NULL DEFAULT 0,
            price decimal(12,2) NOT NULL DEFAULT 0.00,
            description text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        dbDelta($sql);

        // Departments table
        $sql = "CREATE TABLE {$prefix}departments (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        dbDelta($sql);

        // Employees table
        $sql = "CREATE TABLE {$prefix}employees (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            department_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            position varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        dbDelta($sql);

        // Suppliers table
        $sql = "CREATE TABLE {$prefix}suppliers (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            phone varchar(50) DEFAULT '',
            email varchar(255) DEFAULT '',
            address text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        dbDelta($sql);

        // Stock transactions (FIFO) - add/withdraw
        $sql = "CREATE TABLE {$prefix}transactions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            transaction_type enum('add','withdraw') NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            quantity int(11) NOT NULL,
            unit_price decimal(12,2) NOT NULL DEFAULT 0.00,
            remaining_qty int(11) NOT NULL DEFAULT 0,
            supplier_id bigint(20) UNSIGNED DEFAULT NULL,
            department_id bigint(20) UNSIGNED DEFAULT NULL,
            employee_id bigint(20) UNSIGNED DEFAULT NULL,
            notes text DEFAULT '',
            batch_number varchar(100) DEFAULT '',
            created_by bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        dbDelta($sql);

        // Withdrawal orders (approval flow)
        $sql = "CREATE TABLE {$prefix}withdrawal_orders (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_number varchar(50) NOT NULL,
            department_id bigint(20) UNSIGNED NOT NULL,
            employee_id bigint(20) UNSIGNED NOT NULL,
            status enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
            notes text DEFAULT '',
            approved_by bigint(20) UNSIGNED DEFAULT NULL,
            approved_at datetime DEFAULT NULL,
            signature_url varchar(500) DEFAULT '',
            created_by bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        dbDelta($sql);

        // Withdrawal order items
        $sql = "CREATE TABLE {$prefix}withdrawal_order_items (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            quantity int(11) NOT NULL,
            approved_quantity int(11) DEFAULT NULL,
            unit_price decimal(12,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY (id)
        ) $charset;";
        dbDelta($sql);

        // Purchase requests (auto-generated or manual)
        $sql = "CREATE TABLE {$prefix}purchase_requests (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            request_number varchar(50) NOT NULL,
            status enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
            notes text DEFAULT '',
            approved_by bigint(20) UNSIGNED DEFAULT NULL,
            approved_at datetime DEFAULT NULL,
            signature_url varchar(500) DEFAULT '',
            created_by bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        dbDelta($sql);

        // Purchase request items
        $sql = "CREATE TABLE {$prefix}purchase_request_items (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            request_id bigint(20) UNSIGNED NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            quantity int(11) NOT NULL,
            approved_quantity int(11) DEFAULT NULL,
            estimated_price decimal(12,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY (id)
        ) $charset;";
        dbDelta($sql);

        // Opening balances
        $sql = "CREATE TABLE {$prefix}opening_balances (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id bigint(20) UNSIGNED NOT NULL,
            quantity int(11) NOT NULL,
            unit_price decimal(12,2) NOT NULL DEFAULT 0.00,
            balance_date date NOT NULL,
            notes text DEFAULT '',
            created_by bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        dbDelta($sql);

        // Permissions matrix
        $sql = "CREATE TABLE {$prefix}permissions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            feature varchar(100) NOT NULL,
            permission_level enum('none','view','read','read_write') NOT NULL DEFAULT 'none',
            PRIMARY KEY (id),
            UNIQUE KEY user_feature (user_id, feature)
        ) $charset;";
        dbDelta($sql);

        update_option('iw_db_version', IW_VERSION);
    }
}
