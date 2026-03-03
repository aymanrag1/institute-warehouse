<?php
if (!defined('ABSPATH')) exit;

class IW_Suppliers {

    public static function init() {
        add_action('wp_ajax_iw_save_supplier', array(__CLASS__, 'save_supplier'));
        add_action('wp_ajax_iw_create_supplier', array(__CLASS__, 'create_supplier'));
        add_action('wp_ajax_iw_delete_supplier', array(__CLASS__, 'delete_supplier'));
        add_action('wp_ajax_iw_get_suppliers', array(__CLASS__, 'get_suppliers'));
        add_action('wp_ajax_iw_get_supplier', array(__CLASS__, 'get_supplier'));
    }

    /**
     * Check if user has permission to manage suppliers
     */
    private static function can_manage_suppliers() {
        // Admin always has permission
        if (current_user_can('manage_options')) {
            return true;
        }
        // Check custom permission
        if (class_exists('IW_Permissions') && IW_Permissions::current_user_can('suppliers', 'read_write')) {
            return true;
        }
        return false;
    }

    /**
     * Ensure suppliers table exists and has all required columns
     */
    private static function ensure_table_exists() {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_suppliers';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");

        if (!$table_exists) {
            // Create the table
            $charset = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                supplier_number varchar(50) DEFAULT '',
                name varchar(255) NOT NULL,
                address text,
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

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        } else {
            // Table exists - check for missing columns and add them
            $columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}");

            // Define columns that should exist (without AFTER to avoid dependency issues)
            $required_columns = array(
                'supplier_number'       => "varchar(50) DEFAULT ''",
                'address'               => "text",
                'phone_mobile'          => "varchar(50) DEFAULT ''",
                'email'                 => "varchar(255) DEFAULT ''",
                'contact_person'        => "varchar(255) DEFAULT ''",
                'tax_card_number'       => "varchar(100) DEFAULT ''",
                'tax_card_file'         => "varchar(500) DEFAULT ''",
                'commercial_reg_number' => "varchar(100) DEFAULT ''",
                'commercial_reg_file'   => "varchar(500) DEFAULT ''",
                'specialty'             => "varchar(255) DEFAULT ''",
            );

            foreach ($required_columns as $col_name => $col_def) {
                if (!in_array($col_name, $columns)) {
                    $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$col_name} {$col_def}");
                }
            }

            // Migrate old 'phone' column to 'phone_mobile' if exists
            $columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}"); // refresh
            if (in_array('phone', $columns) && !in_array('phone_mobile', $columns)) {
                $wpdb->query("ALTER TABLE {$table} CHANGE phone phone_mobile varchar(50) DEFAULT ''");
            }
        }

        return $table;
    }

    /**
     * Generate sequential supplier number
     */
    private static function generate_supplier_number() {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_suppliers';
        $last = $wpdb->get_var("SELECT supplier_number FROM {$table} ORDER BY id DESC LIMIT 1");

        if ($last && preg_match('/SUP-(\d+)/', $last, $matches)) {
            $num = intval($matches[1]) + 1;
        } else {
            $num = 1;
        }

        return 'SUP-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    public static function save_supplier() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        // Check permission
        if (!self::can_manage_suppliers()) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;

        // Ensure table exists first
        $table = self::ensure_table_exists();

        // Validate name
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        if (empty($name)) {
            wp_send_json_error(array('message' => 'اسم المورد مطلوب'));
        }

        // Get existing columns in the table
        $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}");

        // All possible data fields
        $all_data = array(
            'name'                  => $name,
            'address'               => sanitize_textarea_field($_POST['address'] ?? ''),
            'phone'                 => sanitize_text_field($_POST['phone_mobile'] ?? ''), // old column name
            'phone_mobile'          => sanitize_text_field($_POST['phone_mobile'] ?? ''),
            'email'                 => sanitize_email($_POST['email'] ?? ''),
            'contact_person'        => sanitize_text_field($_POST['contact_person'] ?? ''),
            'tax_card_number'       => sanitize_text_field($_POST['tax_card_number'] ?? ''),
            'tax_card_file'         => esc_url_raw($_POST['tax_card_file'] ?? ''),
            'commercial_reg_number' => sanitize_text_field($_POST['commercial_reg_number'] ?? ''),
            'commercial_reg_file'   => esc_url_raw($_POST['commercial_reg_file'] ?? ''),
            'specialty'             => sanitize_text_field($_POST['specialty'] ?? ''),
            'supplier_number'       => '', // will be set for new suppliers
        );

        // Filter to only include columns that exist in the table
        $data = array();
        $format = array();
        foreach ($all_data as $col => $val) {
            if (in_array($col, $existing_columns)) {
                $data[$col] = $val;
                $format[] = '%s';
            }
        }

        $id = intval($_POST['supplier_id'] ?? 0);

        if ($id > 0) {
            // Update existing supplier - remove supplier_number from update
            unset($data['supplier_number']);
            $format = array_values(array_fill(0, count($data), '%s'));

            $result = $wpdb->update($table, $data, array('id' => $id), $format, array('%d'));
            if ($result === false) {
                wp_send_json_error(array('message' => 'خطأ في تحديث البيانات: ' . $wpdb->last_error));
            }
        } else {
            // Insert new supplier - set supplier_number if column exists
            if (in_array('supplier_number', $existing_columns)) {
                $data['supplier_number'] = self::generate_supplier_number();
            }
            $format = array_values(array_fill(0, count($data), '%s'));

            $result = $wpdb->insert($table, $data, $format);

            if ($result === false) {
                wp_send_json_error(array('message' => 'خطأ في حفظ البيانات: ' . $wpdb->last_error));
            }

            $id = $wpdb->insert_id;

            if (!$id) {
                wp_send_json_error(array('message' => 'فشل في إنشاء المورد'));
            }
        }

        wp_send_json_success(array('id' => $id, 'message' => 'تم الحفظ بنجاح'));
    }

    /**
     * Quick create supplier (from add-stock page)
     */
    public static function create_supplier() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        // Check permission
        if (!self::can_manage_suppliers()) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;

        // Ensure table exists first
        $table = self::ensure_table_exists();

        // Validate name
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        if (empty($name)) {
            wp_send_json_error(array('message' => 'اسم المورد مطلوب'));
        }

        // Get existing columns
        $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}");

        // Build data based on existing columns
        $all_data = array(
            'name'            => $name,
            'supplier_number' => self::generate_supplier_number(),
            'phone'           => sanitize_text_field($_POST['phone_mobile'] ?? $_POST['phone'] ?? ''),
            'phone_mobile'    => sanitize_text_field($_POST['phone_mobile'] ?? $_POST['phone'] ?? ''),
            'email'           => sanitize_email($_POST['email'] ?? ''),
            'address'         => sanitize_textarea_field($_POST['address'] ?? ''),
        );

        $data = array();
        foreach ($all_data as $col => $val) {
            if (in_array($col, $existing_columns)) {
                $data[$col] = $val;
            }
        }

        $format = array_fill(0, count($data), '%s');
        $result = $wpdb->insert($table, $data, $format);

        if ($result === false) {
            wp_send_json_error(array('message' => 'خطأ في حفظ المورد: ' . $wpdb->last_error));
        }

        $id = $wpdb->insert_id;

        if (!$id) {
            wp_send_json_error(array('message' => 'فشل في إنشاء المورد'));
        }

        wp_send_json_success(array('id' => $id, 'name' => $name, 'message' => 'تم إضافة المورد'));
    }

    public static function delete_supplier() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        // Check permission
        if (!self::can_manage_suppliers()) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_suppliers';

        $id = intval($_POST['supplier_id'] ?? 0);
        if ($id <= 0) {
            wp_send_json_error(array('message' => 'معرف المورد غير صحيح'));
        }

        $result = $wpdb->delete($table, array('id' => $id), array('%d'));

        if ($result === false) {
            wp_send_json_error(array('message' => 'خطأ في حذف المورد'));
        }

        wp_send_json_success(array('message' => 'تم الحذف'));
    }

    public static function get_suppliers() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        global $wpdb;

        // Ensure table exists
        $table = self::ensure_table_exists();

        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY name ASC");

        if ($rows === null) {
            $rows = array();
        }

        wp_send_json_success($rows);
    }

    public static function get_supplier() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'iw_suppliers';

        $id = intval($_POST['supplier_id'] ?? 0);
        if ($id <= 0) {
            wp_send_json_error(array('message' => 'معرف المورد غير صحيح'));
        }

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d", $id
        ));

        if (!$row) {
            wp_send_json_error(array('message' => 'المورد غير موجود'));
        }

        wp_send_json_success($row);
    }

    public static function get_all() {
        global $wpdb;

        // Ensure table exists
        $table = self::ensure_table_exists();

        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY name ASC");
    }
}
