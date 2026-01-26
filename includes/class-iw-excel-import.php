<?php
/**
 * Excel Import Class
 *
 * استيراد البيانات من ملفات Excel
 */

if (!defined('ABSPATH')) {
    exit;
}

class IW_Excel_Import {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('wp_ajax_iw_import_products', array($this, 'ajax_import_products'));
        add_action('wp_ajax_iw_import_employees', array($this, 'ajax_import_employees'));
        add_action('wp_ajax_iw_import_suppliers', array($this, 'ajax_import_suppliers'));
        add_action('wp_ajax_iw_import_stock', array($this, 'ajax_import_stock'));
        add_action('wp_ajax_iw_get_import_template', array($this, 'ajax_get_import_template'));
    }

    /**
     * استيراد المنتجات
     */
    public static function import_products($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_products';
        $categories_table = $wpdb->prefix . 'iw_categories';

        $imported = 0;
        $updated = 0;
        $errors = array();

        foreach ($data as $index => $row) {
            $row_num = $index + 2; // +2 لأن الصف الأول هو العنوان والفهرس يبدأ من 0

            // التحقق من البيانات المطلوبة
            if (empty($row['sku']) || empty($row['name'])) {
                $errors[] = "الصف $row_num: الكود والاسم مطلوبان";
                continue;
            }

            // البحث عن الفئة
            $category_id = null;
            if (!empty($row['category'])) {
                $category = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $categories_table WHERE name = %s",
                    $row['category']
                ));

                if ($category) {
                    $category_id = $category->id;
                } else {
                    // إنشاء الفئة إذا لم تكن موجودة
                    $wpdb->insert($categories_table, array('name' => sanitize_text_field($row['category'])));
                    $category_id = $wpdb->insert_id;
                }
            }

            $product_data = array(
                'sku' => sanitize_text_field($row['sku']),
                'name' => sanitize_text_field($row['name']),
                'description' => isset($row['description']) ? sanitize_textarea_field($row['description']) : '',
                'category_id' => $category_id,
                'unit' => isset($row['unit']) ? sanitize_text_field($row['unit']) : '',
                'min_quantity' => isset($row['min_quantity']) ? absint($row['min_quantity']) : 0,
                'reorder_level' => isset($row['reorder_level']) ? absint($row['reorder_level']) : 0,
                'storage_location' => isset($row['storage_location']) ? sanitize_text_field($row['storage_location']) : '',
                'has_expiry' => isset($row['has_expiry']) && strtolower($row['has_expiry']) === 'نعم' ? 1 : 0,
            );

            // التحقق من وجود المنتج
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE sku = %s",
                $product_data['sku']
            ));

            if ($existing) {
                // تحديث المنتج
                $wpdb->update($table, $product_data, array('id' => $existing));
                $updated++;
            } else {
                // إضافة منتج جديد
                $wpdb->insert($table, $product_data);
                $imported++;
            }
        }

        return array(
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors
        );
    }

    /**
     * استيراد الموظفين
     */
    public static function import_employees($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_employees';
        $departments_table = $wpdb->prefix . 'iw_departments';

        $imported = 0;
        $updated = 0;
        $errors = array();

        foreach ($data as $index => $row) {
            $row_num = $index + 2;

            if (empty($row['name'])) {
                $errors[] = "الصف $row_num: اسم الموظف مطلوب";
                continue;
            }

            // البحث عن القسم
            $department_id = null;
            if (!empty($row['department'])) {
                $department = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $departments_table WHERE name = %s",
                    $row['department']
                ));

                if ($department) {
                    $department_id = $department->id;
                } else {
                    // إنشاء القسم إذا لم يكن موجوداً
                    $wpdb->insert($departments_table, array(
                        'name' => sanitize_text_field($row['department']),
                        'status' => 'active'
                    ));
                    $department_id = $wpdb->insert_id;
                }
            }

            $employee_data = array(
                'employee_number' => isset($row['employee_number']) ? sanitize_text_field($row['employee_number']) : '',
                'name' => sanitize_text_field($row['name']),
                'department_id' => $department_id,
                'position' => isset($row['position']) ? sanitize_text_field($row['position']) : '',
                'phone' => isset($row['phone']) ? sanitize_text_field($row['phone']) : '',
                'email' => isset($row['email']) ? sanitize_email($row['email']) : '',
                'status' => 'active'
            );

            // التحقق من وجود الموظف (بالرقم الوظيفي أو الاسم)
            $existing = null;
            if (!empty($employee_data['employee_number'])) {
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table WHERE employee_number = %s",
                    $employee_data['employee_number']
                ));
            }

            if ($existing) {
                $wpdb->update($table, $employee_data, array('id' => $existing));
                $updated++;
            } else {
                $wpdb->insert($table, $employee_data);
                $imported++;
            }
        }

        return array(
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors
        );
    }

    /**
     * استيراد الموردين
     */
    public static function import_suppliers($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_suppliers';

        $imported = 0;
        $updated = 0;
        $errors = array();

        foreach ($data as $index => $row) {
            $row_num = $index + 2;

            if (empty($row['name'])) {
                $errors[] = "الصف $row_num: اسم المورد مطلوب";
                continue;
            }

            $supplier_data = array(
                'name' => sanitize_text_field($row['name']),
                'contact_person' => isset($row['contact_person']) ? sanitize_text_field($row['contact_person']) : '',
                'phone' => isset($row['phone']) ? sanitize_text_field($row['phone']) : '',
                'email' => isset($row['email']) ? sanitize_email($row['email']) : '',
                'address' => isset($row['address']) ? sanitize_textarea_field($row['address']) : '',
                'tax_number' => isset($row['tax_number']) ? sanitize_text_field($row['tax_number']) : '',
                'status' => 'active'
            );

            // التحقق من وجود المورد
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE name = %s",
                $supplier_data['name']
            ));

            if ($existing) {
                $wpdb->update($table, $supplier_data, array('id' => $existing));
                $updated++;
            } else {
                $wpdb->insert($table, $supplier_data);
                $imported++;
            }
        }

        return array(
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors
        );
    }

    /**
     * استيراد المخزون الابتدائي
     */
    public static function import_stock($data, $warehouse_id) {
        global $wpdb;

        $products_table = $wpdb->prefix . 'iw_products';
        $stock_table = $wpdb->prefix . 'iw_stock';

        $imported = 0;
        $errors = array();

        foreach ($data as $index => $row) {
            $row_num = $index + 2;

            if (empty($row['sku']) || empty($row['quantity'])) {
                $errors[] = "الصف $row_num: كود المنتج والكمية مطلوبان";
                continue;
            }

            // البحث عن المنتج
            $product = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $products_table WHERE sku = %s",
                $row['sku']
            ));

            if (!$product) {
                $errors[] = "الصف $row_num: المنتج بالكود {$row['sku']} غير موجود";
                continue;
            }

            $stock_data = array(
                'product_id' => $product->id,
                'warehouse_id' => $warehouse_id,
                'batch_number' => isset($row['batch_number']) ? sanitize_text_field($row['batch_number']) : '',
                'quantity' => absint($row['quantity']),
                'remaining_quantity' => absint($row['quantity']),
                'purchase_price' => isset($row['purchase_price']) ? floatval($row['purchase_price']) : 0,
                'expiry_date' => isset($row['expiry_date']) && !empty($row['expiry_date']) ? $row['expiry_date'] : null,
                'storage_location' => isset($row['storage_location']) ? sanitize_text_field($row['storage_location']) : '',
                'status' => 'available'
            );

            $wpdb->insert($stock_table, $stock_data);
            $imported++;
        }

        return array(
            'imported' => $imported,
            'errors' => $errors
        );
    }

    /**
     * الحصول على قالب الاستيراد
     */
    public static function get_import_template($type) {
        $templates = array(
            'products' => array(
                'headers' => array(
                    'sku' => 'كود المنتج *',
                    'name' => 'اسم المنتج *',
                    'category' => 'الفئة',
                    'unit' => 'الوحدة',
                    'description' => 'الوصف',
                    'min_quantity' => 'الحد الأدنى',
                    'reorder_level' => 'حد إعادة الطلب',
                    'storage_location' => 'مكان التخزين',
                    'has_expiry' => 'له صلاحية (نعم/لا)'
                ),
                'sample' => array(
                    array('SKU001', 'كتاب الرياضيات', 'كتب دراسية', 'قطعة', 'كتاب الرياضيات للصف الأول', 10, 20, 'رف أ-1', 'لا'),
                    array('SKU002', 'قلم جاف أزرق', 'قرطاسية', 'علبة', 'علبة أقلام 12 قلم', 5, 10, 'رف ب-2', 'لا'),
                )
            ),
            'employees' => array(
                'headers' => array(
                    'employee_number' => 'الرقم الوظيفي',
                    'name' => 'اسم الموظف *',
                    'department' => 'القسم',
                    'position' => 'المسمى الوظيفي',
                    'phone' => 'رقم الهاتف',
                    'email' => 'البريد الإلكتروني'
                ),
                'sample' => array(
                    array('EMP001', 'أحمد محمد', 'قسم الإدارة', 'موظف إداري', '0501234567', 'ahmed@example.com'),
                    array('EMP002', 'سارة علي', 'قسم المالية', 'محاسب', '0509876543', 'sara@example.com'),
                )
            ),
            'suppliers' => array(
                'headers' => array(
                    'name' => 'اسم المورد *',
                    'contact_person' => 'جهة الاتصال',
                    'phone' => 'رقم الهاتف',
                    'email' => 'البريد الإلكتروني',
                    'address' => 'العنوان',
                    'tax_number' => 'الرقم الضريبي'
                ),
                'sample' => array(
                    array('مكتبة العلم', 'محمد أحمد', '0112345678', 'info@alilm.com', 'الرياض - شارع الملك فهد', '300123456789'),
                )
            ),
            'stock' => array(
                'headers' => array(
                    'sku' => 'كود المنتج *',
                    'quantity' => 'الكمية *',
                    'batch_number' => 'رقم الدفعة',
                    'purchase_price' => 'سعر الشراء',
                    'expiry_date' => 'تاريخ الصلاحية (YYYY-MM-DD)',
                    'storage_location' => 'مكان التخزين'
                ),
                'sample' => array(
                    array('SKU001', 100, 'BATCH001', 25.00, '', 'رف أ-1'),
                    array('SKU002', 50, 'BATCH002', 15.50, '2025-12-31', 'رف ب-2'),
                )
            )
        );

        return isset($templates[$type]) ? $templates[$type] : null;
    }

    // ==================== AJAX Handlers ====================

    public function ajax_import_products() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_import_data')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $data = isset($_POST['data']) ? $_POST['data'] : array();

        if (empty($data)) {
            wp_send_json_error(array('message' => 'لا توجد بيانات للاستيراد'));
        }

        $result = self::import_products($data);

        IW_Database::log_activity('import', 'products', null, array(
            'imported' => $result['imported'],
            'updated' => $result['updated']
        ));

        wp_send_json_success(array(
            'message' => sprintf(
                'تم استيراد %d منتج جديد، تحديث %d منتج',
                $result['imported'],
                $result['updated']
            ),
            'result' => $result
        ));
    }

    public function ajax_import_employees() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_import_data')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $data = isset($_POST['data']) ? $_POST['data'] : array();

        if (empty($data)) {
            wp_send_json_error(array('message' => 'لا توجد بيانات للاستيراد'));
        }

        $result = self::import_employees($data);

        IW_Database::log_activity('import', 'employees', null, array(
            'imported' => $result['imported'],
            'updated' => $result['updated']
        ));

        wp_send_json_success(array(
            'message' => sprintf(
                'تم استيراد %d موظف جديد، تحديث %d موظف',
                $result['imported'],
                $result['updated']
            ),
            'result' => $result
        ));
    }

    public function ajax_import_suppliers() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_import_data')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $data = isset($_POST['data']) ? $_POST['data'] : array();

        if (empty($data)) {
            wp_send_json_error(array('message' => 'لا توجد بيانات للاستيراد'));
        }

        $result = self::import_suppliers($data);

        IW_Database::log_activity('import', 'suppliers', null, array(
            'imported' => $result['imported'],
            'updated' => $result['updated']
        ));

        wp_send_json_success(array(
            'message' => sprintf(
                'تم استيراد %d مورد جديد، تحديث %d مورد',
                $result['imported'],
                $result['updated']
            ),
            'result' => $result
        ));
    }

    public function ajax_import_stock() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_import_data')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $data = isset($_POST['data']) ? $_POST['data'] : array();
        $warehouse_id = isset($_POST['warehouse_id']) ? absint($_POST['warehouse_id']) : 0;

        if (empty($data)) {
            wp_send_json_error(array('message' => 'لا توجد بيانات للاستيراد'));
        }

        if (!$warehouse_id) {
            wp_send_json_error(array('message' => 'يرجى اختيار المخزن'));
        }

        $result = self::import_stock($data, $warehouse_id);

        IW_Database::log_activity('import', 'stock', null, array(
            'imported' => $result['imported'],
            'warehouse_id' => $warehouse_id
        ));

        wp_send_json_success(array(
            'message' => sprintf('تم استيراد %d سجل مخزون', $result['imported']),
            'result' => $result
        ));
    }

    public function ajax_get_import_template() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_import_data')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';

        $template = self::get_import_template($type);

        if (!$template) {
            wp_send_json_error(array('message' => 'نوع القالب غير صحيح'));
        }

        wp_send_json_success($template);
    }
}

// Initialize
IW_Excel_Import::get_instance();
