<?php
/**
 * Database Management Class
 *
 * يتعامل مع إنشاء جداول قاعدة البيانات وعمليات الترقية
 */

if (!defined('ABSPATH')) {
    exit;
}

class IW_Database {

    /**
     * إصدار قاعدة البيانات
     */
    const DB_VERSION = '1.0.0';

    /**
     * إنشاء جميع الجداول
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // جدول المخازن
        $warehouses_table = $wpdb->prefix . 'iw_warehouses';
        $sql_warehouses = "CREATE TABLE $warehouses_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            location varchar(255) DEFAULT NULL,
            description text,
            manager_id bigint(20) UNSIGNED DEFAULT NULL,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY manager_id (manager_id)
        ) $charset_collate;";
        dbDelta($sql_warehouses);

        // جدول الفئات
        $categories_table = $wpdb->prefix . 'iw_categories';
        $sql_categories = "CREATE TABLE $categories_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            parent_id bigint(20) UNSIGNED DEFAULT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parent_id (parent_id)
        ) $charset_collate;";
        dbDelta($sql_categories);

        // جدول الأصناف/المنتجات
        $products_table = $wpdb->prefix . 'iw_products';
        $sql_products = "CREATE TABLE $products_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            sku varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            description text,
            category_id bigint(20) UNSIGNED DEFAULT NULL,
            unit varchar(50) DEFAULT NULL,
            min_quantity int(11) DEFAULT 0,
            reorder_level int(11) DEFAULT 0,
            storage_location varchar(255) DEFAULT NULL,
            has_expiry tinyint(1) DEFAULT 0,
            is_discontinued tinyint(1) DEFAULT 0,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY sku (sku),
            KEY category_id (category_id),
            KEY is_discontinued (is_discontinued)
        ) $charset_collate;";
        dbDelta($sql_products);

        // جدول المخزون (الكميات في كل مخزن) مع دعم FIFO
        $stock_table = $wpdb->prefix . 'iw_stock';
        $sql_stock = "CREATE TABLE $stock_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id bigint(20) UNSIGNED NOT NULL,
            warehouse_id bigint(20) UNSIGNED NOT NULL,
            batch_number varchar(100) DEFAULT NULL,
            quantity int(11) NOT NULL DEFAULT 0,
            remaining_quantity int(11) NOT NULL DEFAULT 0,
            purchase_price decimal(10,2) DEFAULT 0.00,
            expiry_date date DEFAULT NULL,
            received_date datetime DEFAULT CURRENT_TIMESTAMP,
            supplier_id bigint(20) UNSIGNED DEFAULT NULL,
            permit_id bigint(20) UNSIGNED DEFAULT NULL,
            storage_location varchar(255) DEFAULT NULL,
            status enum('available','depleted','expired') DEFAULT 'available',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY warehouse_id (warehouse_id),
            KEY supplier_id (supplier_id),
            KEY received_date (received_date),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_stock);

        // جدول الأقسام
        $departments_table = $wpdb->prefix . 'iw_departments';
        $sql_departments = "CREATE TABLE $departments_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            code varchar(50) DEFAULT NULL,
            description text,
            parent_id bigint(20) UNSIGNED DEFAULT NULL,
            manager_id bigint(20) UNSIGNED DEFAULT NULL,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parent_id (parent_id),
            KEY manager_id (manager_id)
        ) $charset_collate;";
        dbDelta($sql_departments);

        // جدول الموظفين
        $employees_table = $wpdb->prefix . 'iw_employees';
        $sql_employees = "CREATE TABLE $employees_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            employee_number varchar(50) DEFAULT NULL,
            name varchar(255) NOT NULL,
            department_id bigint(20) UNSIGNED DEFAULT NULL,
            position varchar(255) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY department_id (department_id),
            KEY employee_number (employee_number)
        ) $charset_collate;";
        dbDelta($sql_employees);

        // جدول الموردين
        $suppliers_table = $wpdb->prefix . 'iw_suppliers';
        $sql_suppliers = "CREATE TABLE $suppliers_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            contact_person varchar(255) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            address text,
            tax_number varchar(100) DEFAULT NULL,
            notes text,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_suppliers);

        // جدول إذونات الإضافة
        $add_permits_table = $wpdb->prefix . 'iw_add_permits';
        $sql_add_permits = "CREATE TABLE $add_permits_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            permit_number varchar(50) NOT NULL,
            warehouse_id bigint(20) UNSIGNED NOT NULL,
            supplier_id bigint(20) UNSIGNED DEFAULT NULL,
            supplier_name varchar(255) DEFAULT NULL,
            invoice_number varchar(100) DEFAULT NULL,
            invoice_date date DEFAULT NULL,
            total_amount decimal(15,2) DEFAULT 0.00,
            notes text,
            created_by bigint(20) UNSIGNED NOT NULL,
            approved_by bigint(20) UNSIGNED DEFAULT NULL,
            status enum('pending','approved','cancelled') DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            approved_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY permit_number (permit_number),
            KEY warehouse_id (warehouse_id),
            KEY supplier_id (supplier_id),
            KEY created_by (created_by)
        ) $charset_collate;";
        dbDelta($sql_add_permits);

        // جدول تفاصيل إذونات الإضافة
        $add_permit_items_table = $wpdb->prefix . 'iw_add_permit_items';
        $sql_add_permit_items = "CREATE TABLE $add_permit_items_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            permit_id bigint(20) UNSIGNED NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            quantity int(11) NOT NULL,
            unit_price decimal(10,2) DEFAULT 0.00,
            total_price decimal(15,2) DEFAULT 0.00,
            batch_number varchar(100) DEFAULT NULL,
            expiry_date date DEFAULT NULL,
            storage_location varchar(255) DEFAULT NULL,
            notes text,
            PRIMARY KEY (id),
            KEY permit_id (permit_id),
            KEY product_id (product_id)
        ) $charset_collate;";
        dbDelta($sql_add_permit_items);

        // جدول إذونات الصرف
        $withdraw_permits_table = $wpdb->prefix . 'iw_withdraw_permits';
        $sql_withdraw_permits = "CREATE TABLE $withdraw_permits_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            permit_number varchar(50) NOT NULL,
            warehouse_id bigint(20) UNSIGNED NOT NULL,
            department_id bigint(20) UNSIGNED DEFAULT NULL,
            employee_id bigint(20) UNSIGNED DEFAULT NULL,
            employee_name varchar(255) DEFAULT NULL,
            purpose text,
            notes text,
            storage_location varchar(255) DEFAULT NULL,
            created_by bigint(20) UNSIGNED NOT NULL,
            approved_by bigint(20) UNSIGNED DEFAULT NULL,
            dean_approved_by bigint(20) UNSIGNED DEFAULT NULL,
            status enum('pending','approved','delivered','cancelled') DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            approved_at datetime DEFAULT NULL,
            delivered_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY permit_number (permit_number),
            KEY warehouse_id (warehouse_id),
            KEY department_id (department_id),
            KEY employee_id (employee_id),
            KEY created_by (created_by)
        ) $charset_collate;";
        dbDelta($sql_withdraw_permits);

        // جدول تفاصيل إذونات الصرف
        $withdraw_permit_items_table = $wpdb->prefix . 'iw_withdraw_permit_items';
        $sql_withdraw_permit_items = "CREATE TABLE $withdraw_permit_items_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            permit_id bigint(20) UNSIGNED NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            stock_id bigint(20) UNSIGNED DEFAULT NULL,
            requested_quantity int(11) NOT NULL,
            approved_quantity int(11) DEFAULT NULL,
            delivered_quantity int(11) DEFAULT NULL,
            storage_location varchar(255) DEFAULT NULL,
            notes text,
            PRIMARY KEY (id),
            KEY permit_id (permit_id),
            KEY product_id (product_id),
            KEY stock_id (stock_id)
        ) $charset_collate;";
        dbDelta($sql_withdraw_permit_items);

        // جدول حركات المخزون
        $transactions_table = $wpdb->prefix . 'iw_transactions';
        $sql_transactions = "CREATE TABLE $transactions_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            transaction_type enum('add','withdraw','transfer','adjustment','return') NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            stock_id bigint(20) UNSIGNED DEFAULT NULL,
            warehouse_id bigint(20) UNSIGNED NOT NULL,
            quantity int(11) NOT NULL,
            quantity_before int(11) DEFAULT 0,
            quantity_after int(11) DEFAULT 0,
            permit_id bigint(20) UNSIGNED DEFAULT NULL,
            permit_type enum('add','withdraw') DEFAULT NULL,
            reference_number varchar(100) DEFAULT NULL,
            department_id bigint(20) UNSIGNED DEFAULT NULL,
            employee_id bigint(20) UNSIGNED DEFAULT NULL,
            supplier_id bigint(20) UNSIGNED DEFAULT NULL,
            notes text,
            created_by bigint(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY transaction_type (transaction_type),
            KEY product_id (product_id),
            KEY warehouse_id (warehouse_id),
            KEY permit_id (permit_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_transactions);

        // جدول التنبيهات
        $alerts_table = $wpdb->prefix . 'iw_alerts';
        $sql_alerts = "CREATE TABLE $alerts_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            alert_type enum('low_stock','out_of_stock','expiry_warning','expiry','reorder') NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            warehouse_id bigint(20) UNSIGNED DEFAULT NULL,
            stock_id bigint(20) UNSIGNED DEFAULT NULL,
            message text NOT NULL,
            is_read tinyint(1) DEFAULT 0,
            is_resolved tinyint(1) DEFAULT 0,
            resolved_by bigint(20) UNSIGNED DEFAULT NULL,
            resolved_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY alert_type (alert_type),
            KEY product_id (product_id),
            KEY is_read (is_read),
            KEY is_resolved (is_resolved)
        ) $charset_collate;";
        dbDelta($sql_alerts);

        // جدول إعدادات البلجن
        $settings_table = $wpdb->prefix . 'iw_settings';
        $sql_settings = "CREATE TABLE $settings_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";
        dbDelta($sql_settings);

        // جدول سجل النشاط
        $activity_log_table = $wpdb->prefix . 'iw_activity_log';
        $sql_activity_log = "CREATE TABLE $activity_log_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            action varchar(100) NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id bigint(20) UNSIGNED DEFAULT NULL,
            details text,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY object_type (object_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_activity_log);

        // حفظ إصدار قاعدة البيانات
        update_option('iw_db_version', self::DB_VERSION);

        // إدخال البيانات الافتراضية
        self::insert_default_data();
    }

    /**
     * إدخال البيانات الافتراضية
     */
    private static function insert_default_data() {
        global $wpdb;

        // التحقق من وجود مخزن افتراضي
        $warehouses_table = $wpdb->prefix . 'iw_warehouses';
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $warehouses_table");

        if ($existing == 0) {
            $wpdb->insert($warehouses_table, array(
                'name' => 'المخزن الرئيسي',
                'location' => 'المبنى الرئيسي',
                'description' => 'المخزن الرئيسي للمعهد',
                'status' => 'active'
            ));
        }

        // إدخال الفئات الافتراضية
        $categories_table = $wpdb->prefix . 'iw_categories';
        $existing_cats = $wpdb->get_var("SELECT COUNT(*) FROM $categories_table");

        if ($existing_cats == 0) {
            $default_categories = array(
                'كتب دراسية',
                'قرطاسية',
                'أدوات تعليمية',
                'أجهزة ومعدات',
                'مستلزمات مكتبية'
            );

            foreach ($default_categories as $cat) {
                $wpdb->insert($categories_table, array('name' => $cat));
            }
        }

        // إدخال الإعدادات الافتراضية
        self::set_default_settings();
    }

    /**
     * تعيين الإعدادات الافتراضية
     */
    private static function set_default_settings() {
        $default_settings = array(
            'institute_name' => '',
            'institute_type' => '',
            'institute_logo' => '',
            'institute_address' => '',
            'institute_phone' => '',
            'institute_email' => '',
            'dean_name' => '',
            'low_stock_threshold' => 10,
            'expiry_warning_days' => 30,
            'permit_prefix_add' => 'ADD',
            'permit_prefix_withdraw' => 'WD',
            'currency' => 'SAR',
            'date_format' => 'd/m/Y',
            'fiscal_year_start' => '01-01',
            'setup_completed' => '0',
        );

        foreach ($default_settings as $key => $value) {
            self::set_setting($key, $value);
        }
    }

    /**
     * الحصول على إعداد
     */
    public static function get_setting($key, $default = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_settings';

        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM $table WHERE setting_key = %s",
            $key
        ));

        return $value !== null ? $value : $default;
    }

    /**
     * تعيين إعداد
     */
    public static function set_setting($key, $value) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_settings';

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE setting_key = %s",
            $key
        ));

        if ($existing) {
            $wpdb->update(
                $table,
                array('setting_value' => $value),
                array('setting_key' => $key)
            );
        } else {
            $wpdb->insert($table, array(
                'setting_key' => $key,
                'setting_value' => $value
            ));
        }
    }

    /**
     * الحصول على جميع الإعدادات
     */
    public static function get_all_settings() {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_settings';

        $results = $wpdb->get_results("SELECT setting_key, setting_value FROM $table", ARRAY_A);

        $settings = array();
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    /**
     * حذف جميع الجداول (للإلغاء التثبيت)
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = array(
            'iw_activity_log',
            'iw_settings',
            'iw_alerts',
            'iw_transactions',
            'iw_withdraw_permit_items',
            'iw_withdraw_permits',
            'iw_add_permit_items',
            'iw_add_permits',
            'iw_suppliers',
            'iw_employees',
            'iw_departments',
            'iw_stock',
            'iw_products',
            'iw_categories',
            'iw_warehouses'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        }

        delete_option('iw_db_version');
    }

    /**
     * تسجيل نشاط
     */
    public static function log_activity($action, $object_type, $object_id = null, $details = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_activity_log';

        $wpdb->insert($table, array(
            'user_id' => get_current_user_id(),
            'action' => $action,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'details' => is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : $details,
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : ''
        ));
    }
}
