<?php
if (!defined('ABSPATH')) exit;

class IW_Database {

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $prefix  = $wpdb->prefix . 'iw_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // First: migrate old tables if they exist with incompatible schema
        self::migrate_old_tables();

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
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        dbDelta($sql);

        // Departments table
        $sql = "CREATE TABLE {$prefix}departments (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
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

        // Suppliers table with extended fields
        $sql = "CREATE TABLE {$prefix}suppliers (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            supplier_number varchar(50) DEFAULT '',
            name varchar(255) NOT NULL,
            address text,
            phone_landline varchar(50) DEFAULT '',
            phone_mobile varchar(50) DEFAULT '',
            email varchar(255) DEFAULT '',
            contact_person varchar(255) DEFAULT '',
            tax_card_number varchar(100) DEFAULT '',
            tax_card_file varchar(500) DEFAULT '',
            commercial_reg_number varchar(100) DEFAULT '',
            commercial_reg_file varchar(500) DEFAULT '',
            specialty varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        dbDelta($sql);

        // Add orders (multi-product) with sequential numbering
        $sql = "CREATE TABLE {$prefix}add_orders (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_number varchar(50) NOT NULL,
            supplier_id bigint(20) UNSIGNED DEFAULT NULL,
            notes text,
            total_quantity int(11) NOT NULL DEFAULT 0,
            total_value decimal(12,2) NOT NULL DEFAULT 0.00,
            created_by bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        dbDelta($sql);

        // Add order items
        $sql = "CREATE TABLE {$prefix}add_order_items (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            quantity int(11) NOT NULL,
            unit_price decimal(12,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY (id)
        ) $charset;";
        dbDelta($sql);

        // Stock transactions (FIFO)
        $sql = "CREATE TABLE {$prefix}transactions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            transaction_type varchar(20) NOT NULL DEFAULT 'add',
            product_id bigint(20) UNSIGNED NOT NULL,
            quantity int(11) NOT NULL,
            unit_price decimal(12,2) NOT NULL DEFAULT 0.00,
            remaining_qty int(11) NOT NULL DEFAULT 0,
            supplier_id bigint(20) UNSIGNED DEFAULT NULL,
            department_id bigint(20) UNSIGNED DEFAULT NULL,
            employee_id bigint(20) UNSIGNED DEFAULT NULL,
            notes text,
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
            department_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            employee_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'pending',
            notes text,
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

        // Purchase requests
        $sql = "CREATE TABLE {$prefix}purchase_requests (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            request_number varchar(50) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            notes text,
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
            notes text,
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
            permission_level varchar(20) NOT NULL DEFAULT 'none',
            PRIMARY KEY (id),
            UNIQUE KEY user_feature (user_id, feature)
        ) $charset;";
        dbDelta($sql);

        update_option('iw_db_version', IW_VERSION);
    }

    /**
     * Migrate old tables: add missing columns to existing tables
     */
    private static function migrate_old_tables() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        // Check if products table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$prefix}products'");
        if ($table_exists) {
            // Add missing columns if they don't exist
            $columns = $wpdb->get_col("SHOW COLUMNS FROM {$prefix}products");

            if (!in_array('min_stock', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}products ADD COLUMN min_stock int(11) NOT NULL DEFAULT 0 AFTER unit");
            }
            if (!in_array('max_stock', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}products ADD COLUMN max_stock int(11) NOT NULL DEFAULT 0 AFTER min_stock");
            }
            if (!in_array('current_stock', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}products ADD COLUMN current_stock int(11) NOT NULL DEFAULT 0 AFTER max_stock");
            }
            if (!in_array('sku', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}products ADD COLUMN sku varchar(100) DEFAULT '' AFTER name");
            }
            if (!in_array('category', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}products ADD COLUMN category varchar(255) DEFAULT '' AFTER sku");
            }
            if (!in_array('unit', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}products ADD COLUMN unit varchar(50) DEFAULT '' AFTER category");
            }
            if (!in_array('price', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}products ADD COLUMN price decimal(12,2) NOT NULL DEFAULT 0.00 AFTER current_stock");
            }
            if (!in_array('description', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}products ADD COLUMN description text AFTER price");
            }
        }

        // Check if transactions table exists and add missing columns
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$prefix}transactions'");
        if ($table_exists) {
            $columns = $wpdb->get_col("SHOW COLUMNS FROM {$prefix}transactions");

            if (!in_array('remaining_qty', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}transactions ADD COLUMN remaining_qty int(11) NOT NULL DEFAULT 0 AFTER unit_price");
            }
            if (!in_array('employee_id', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}transactions ADD COLUMN employee_id bigint(20) UNSIGNED DEFAULT NULL AFTER department_id");
            }
            if (!in_array('batch_number', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}transactions ADD COLUMN batch_number varchar(100) DEFAULT '' AFTER notes");
            }
        }

        // Check departments table
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$prefix}departments'");
        if ($table_exists) {
            $columns = $wpdb->get_col("SHOW COLUMNS FROM {$prefix}departments");
            if (!in_array('description', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}departments ADD COLUMN description text AFTER name");
            }
        }

        // Check suppliers table for new columns
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$prefix}suppliers'");
        if ($table_exists) {
            $columns = $wpdb->get_col("SHOW COLUMNS FROM {$prefix}suppliers");
            if (!in_array('supplier_number', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}suppliers ADD COLUMN supplier_number varchar(50) DEFAULT '' AFTER id");
            }
            if (!in_array('phone_landline', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}suppliers ADD COLUMN phone_landline varchar(50) DEFAULT '' AFTER address");
            }
            if (!in_array('phone_mobile', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}suppliers ADD COLUMN phone_mobile varchar(50) DEFAULT '' AFTER phone_landline");
            }
            if (!in_array('contact_person', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}suppliers ADD COLUMN contact_person varchar(255) DEFAULT '' AFTER email");
            }
            if (!in_array('tax_card_number', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}suppliers ADD COLUMN tax_card_number varchar(100) DEFAULT '' AFTER contact_person");
            }
            if (!in_array('tax_card_file', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}suppliers ADD COLUMN tax_card_file varchar(500) DEFAULT '' AFTER tax_card_number");
            }
            if (!in_array('commercial_reg_number', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}suppliers ADD COLUMN commercial_reg_number varchar(100) DEFAULT '' AFTER tax_card_file");
            }
            if (!in_array('commercial_reg_file', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}suppliers ADD COLUMN commercial_reg_file varchar(500) DEFAULT '' AFTER commercial_reg_number");
            }
            if (!in_array('specialty', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}suppliers ADD COLUMN specialty varchar(255) DEFAULT '' AFTER commercial_reg_file");
            }
            // Migrate old phone column if exists
            if (in_array('phone', $columns) && !in_array('phone_mobile', $columns)) {
                $wpdb->query("ALTER TABLE {$prefix}suppliers CHANGE phone phone_mobile varchar(50) DEFAULT ''");
            }
        }
    }
}
