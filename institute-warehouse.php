<?php
/**
 * Plugin Name: نظام إدارة مخازن المعهد
 * Plugin URI: https://example.com
 * Description: نظام متكامل لإدارة مخازن المعاهد التعليمية مع نظام FIFO وصلاحيات تفصيلية وتوقيع إلكتروني
 * Version: 2.3.2
 * Author: AYMAN RAGAB
 * Author URI: tel:00201159230034
 * Text Domain: institute-warehouse
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('IW_VERSION', '2.4.1');
define('IW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IW_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Check if RSYI HR System is active
 * Uses multiple methods for compatibility
 */
function iw_is_hr_active() {
    // Method 1: Check if helper function exists
    if (function_exists('rsyi_hr_get_departments')) {
        return true;
    }

    // Method 2: Check if filter has handlers (safer approach)
    if (has_filter('rsyi_hr_get_departments')) {
        return true;
    }

    // Method 3: Check if plugin is active by checking for its main class
    if (class_exists('RSYI_HR_System') || class_exists('RSYI_HR') || class_exists('RSY_HR_System')) {
        return true;
    }

    // Method 4: Check if HR tables exist in database (most reliable)
    global $wpdb;
    $table = $wpdb->prefix . 'rsyi_hr_departments';
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if ($exists) {
        return true;
    }

    return false;
}

/**
 * Wrapper functions for HR API - use filters for safety
 */
function iw_hr_get_departments($args = []) {
    // Method 1: Direct function call
    if (function_exists('rsyi_hr_get_departments')) {
        $result = rsyi_hr_get_departments($args);
        if (is_array($result) && !empty($result)) {
            return $result;
        }
    }

    // Method 2: Filter hook
    $result = apply_filters('rsyi_hr_get_departments', [], $args);
    if (is_array($result) && !empty($result)) {
        return $result;
    }

    // Method 3: Direct database query fallback
    global $wpdb;
    $table = $wpdb->prefix . 'rsyi_hr_departments';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
        return [];
    }
    $sql = "SELECT * FROM `{$table}`";
    if (!empty($args['status'])) {
        $sql .= $wpdb->prepare(' WHERE `status` = %s', $args['status']);
    }
    $sql .= ' ORDER BY name ASC';
    return $wpdb->get_results($sql, ARRAY_A) ?: [];
}

function iw_hr_get_department($id) {
    if (function_exists('rsyi_hr_get_department')) {
        return rsyi_hr_get_department($id);
    }
    return apply_filters('rsyi_hr_get_department_by_id', null, $id);
}

function iw_hr_get_employees($args = []) {
    // Method 1: Direct function call
    if (function_exists('rsyi_hr_get_employees')) {
        $result = rsyi_hr_get_employees($args);
        if (is_array($result) && !empty($result)) {
            return $result;
        }
    }

    // Method 2: Filter hook
    $result = apply_filters('rsyi_hr_get_employees', [], $args);
    if (is_array($result) && !empty($result)) {
        return $result;
    }

    // Method 3: Direct database query fallback
    global $wpdb;
    $emp_table  = $wpdb->prefix . 'rsyi_hr_employees';
    $dept_table = $wpdb->prefix . 'rsyi_hr_departments';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $emp_table)) !== $emp_table) {
        return [];
    }
    $sql = "SELECT e.*, d.name AS department_name
            FROM `{$emp_table}` e
            LEFT JOIN `{$dept_table}` d ON e.department_id = d.id";
    if (!empty($args['status'])) {
        $sql .= $wpdb->prepare(' WHERE e.`status` = %s', $args['status']);
    }
    return $wpdb->get_results($sql, ARRAY_A) ?: [];
}

function iw_hr_get_employee($id) {
    if (function_exists('rsyi_hr_get_employee')) {
        return rsyi_hr_get_employee($id);
    }
    return apply_filters('rsyi_hr_get_employee_by_id', null, $id);
}

function iw_hr_get_employee_by_user($user_id) {
    if (function_exists('rsyi_hr_get_employee_by_user')) {
        return rsyi_hr_get_employee_by_user($user_id);
    }
    return apply_filters('rsyi_hr_get_employee_by_user_id', null, $user_id);
}

function iw_hr_department_employees($dept_id) {
    // Method 1: Direct function call
    if (function_exists('rsyi_hr_department_employees')) {
        $result = rsyi_hr_department_employees($dept_id);
        if (is_array($result) && !empty($result)) {
            return $result;
        }
    }

    // Method 2: Filter hook
    $result = apply_filters('rsyi_hr_department_employees', [], $dept_id);
    if (is_array($result) && !empty($result)) {
        return $result;
    }

    // Method 3: Direct database query fallback
    global $wpdb;
    $emp_table = $wpdb->prefix . 'rsyi_hr_employees';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $emp_table)) !== $emp_table) {
        return [];
    }
    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM `{$emp_table}` WHERE department_id = %d AND `status` = 'active'",
            $dept_id
        ),
        ARRAY_A
    ) ?: [];
}

function iw_hr_get_job_titles($args = []) {
    if (function_exists('rsyi_hr_get_job_titles')) {
        return rsyi_hr_get_job_titles($args);
    }
    return apply_filters('rsyi_hr_get_job_titles', [], $args);
}

/**
 * Show admin notice if HR System is not active
 */
add_action('admin_notices', function() {
    if (!iw_is_hr_active()) {
        $message = __('نظام إدارة المخازن يتطلب تفعيل RSYI HR System أولاً.', 'institute-warehouse');
        echo '<div class="notice notice-error is-dismissible"><p>' .
             '<strong>' . esc_html__('تنبيه', 'institute-warehouse') . ':</strong> ' .
             esc_html($message) .
             '</p></div>';
    }
});

/**
 * Add Warehouse capabilities to HR roles
 */
add_action('rsyi_hr_extend_roles', function() {
    // عميد ومدير HR: كامل الصلاحيات
    foreach (['rsyi_dean', 'rsyi_hr_manager'] as $slug) {
        $role = get_role($slug);
        if ($role) {
            $role->add_cap('iw_view_warehouse');
            $role->add_cap('iw_view_products');
            $role->add_cap('iw_add_stock');
            $role->add_cap('iw_withdraw_stock');
            $role->add_cap('iw_view_reports');
            $role->add_cap('iw_manage_departments');
            $role->add_cap('iw_manage_suppliers');
            $role->add_cap('iw_import_data');
            $role->add_cap('iw_approve_orders');
        }
    }

    // رئيس قسم: يرى قسمه ويطلب إذونات
    $dept_head = get_role('rsyi_dept_head');
    if ($dept_head) {
        $dept_head->add_cap('iw_view_warehouse');
        $dept_head->add_cap('iw_view_products');
        $dept_head->add_cap('iw_withdraw_stock');
        $dept_head->add_cap('iw_view_reports');
    }

    // موظف: يرى فقط ويطلب
    $staff = get_role('rsyi_staff');
    if ($staff) {
        $staff->add_cap('iw_view_warehouse');
        $staff->add_cap('iw_view_products');
    }
});

class Institute_Warehouse_System {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
        $this->init_ajax();
    }

    private function load_dependencies() {
        require_once IW_PLUGIN_DIR . 'includes/class-iw-database.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-permissions.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-products.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-transactions.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-departments.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-suppliers.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-withdrawal-orders.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-purchase-requests.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-opening-balance.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-add-orders.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-categories.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-reports.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-excel-import.php';
        require_once IW_PLUGIN_DIR . 'admin/class-iw-admin.php';
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'load_textdomain'));
        add_action('admin_init', array($this, 'check_db_update'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Check if database needs updating (handles plugin updates without deactivation)
     */
    public function check_db_update() {
        $current_db_version = get_option('iw_db_version', '0');
        if (version_compare($current_db_version, IW_VERSION, '<')) {
            IW_Database::create_tables();
            IW_Permissions::create_roles();
            update_option('iw_db_version', IW_VERSION);
        }
    }

    private function init_ajax() {
        IW_Products::init();
        IW_Transactions::init();
        IW_Departments::init();
        IW_Suppliers::init();
        IW_Permissions::init();
        IW_Withdrawal_Orders::init();
        IW_Purchase_Requests::init();
        IW_Opening_Balance::init();
        IW_Add_Orders::init();
        IW_Categories::init();
        IW_Reports::init();
        IW_Excel_Import::init();
    }

    public function activate() {
        IW_Database::create_tables();
        IW_Permissions::create_roles();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function load_textdomain() {
        load_plugin_textdomain('institute-warehouse', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function add_admin_menu() {
        // القائمة الرئيسية
        add_menu_page(
            __('إدارة المخازن', 'institute-warehouse'),
            __('إدارة المخازن', 'institute-warehouse'),
            'iw_view_warehouse',
            'institute-warehouse',
            array($this, 'dashboard_page'),
            'dashicons-store',
            30
        );

        // الأصناف
        add_submenu_page(
            'institute-warehouse',
            __('الأصناف', 'institute-warehouse'),
            __('الأصناف', 'institute-warehouse'),
            'iw_view_products',
            'iw-products',
            array('IW_Admin', 'products_page')
        );

        // إذن إضافة
        add_submenu_page(
            'institute-warehouse',
            __('إذن إضافة', 'institute-warehouse'),
            __('إذن إضافة', 'institute-warehouse'),
            'iw_add_stock',
            'iw-add-stock',
            array('IW_Admin', 'add_stock_page')
        );

        // طباعة إذن إضافة
        add_submenu_page(
            'institute-warehouse',
            __('طباعة إذن إضافة', 'institute-warehouse'),
            __('طباعة إذن إضافة', 'institute-warehouse'),
            'iw_add_stock',
            'iw-print-add-permit',
            array('IW_Admin', 'print_add_permit_page')
        );

        // إذن صرف (مع تدفق الاعتماد)
        add_submenu_page(
            'institute-warehouse',
            __('إذن صرف', 'institute-warehouse'),
            __('إذن صرف', 'institute-warehouse'),
            'iw_withdraw_stock',
            'iw-withdraw-stock',
            array('IW_Admin', 'withdraw_stock_page')
        );

        // طباعة إذن صرف
        add_submenu_page(
            'institute-warehouse',
            __('طباعة إذن صرف', 'institute-warehouse'),
            __('طباعة إذن صرف', 'institute-warehouse'),
            'iw_withdraw_stock',
            'iw-print-withdraw-permit',
            array('IW_Admin', 'print_withdraw_permit_page')
        );

        // طلبات الشراء
        add_submenu_page(
            'institute-warehouse',
            __('طلبات الشراء', 'institute-warehouse'),
            __('طلبات الشراء', 'institute-warehouse'),
            'iw_view_warehouse',
            'iw-purchase-requests',
            array('IW_Admin', 'purchase_requests_page')
        );

        // الرصيد الافتتاحي
        add_submenu_page(
            'institute-warehouse',
            __('الرصيد الافتتاحي', 'institute-warehouse'),
            __('الرصيد الافتتاحي', 'institute-warehouse'),
            'iw_add_stock',
            'iw-opening-balance',
            array('IW_Admin', 'opening_balance_page')
        );

        // التقارير (الصفحة العامة)
        add_submenu_page(
            'institute-warehouse',
            __('التقارير', 'institute-warehouse'),
            __('التقارير', 'institute-warehouse'),
            'iw_view_reports',
            'iw-reports',
            array('IW_Admin', 'reports_page')
        );

        // تقرير المخزون
        add_submenu_page(
            'institute-warehouse',
            __('تقرير المخزون', 'institute-warehouse'),
            __('- تقرير المخزون', 'institute-warehouse'),
            'iw_view_reports',
            'iw-stock-report',
            array('IW_Admin', 'stock_report_page')
        );

        // تقرير الأصناف تحت الحد الأدنى
        add_submenu_page(
            'institute-warehouse',
            __('أصناف تحت الحد الأدنى', 'institute-warehouse'),
            __('- أصناف تحت الحد الأدنى', 'institute-warehouse'),
            'iw_view_reports',
            'iw-low-stock-report',
            array('IW_Admin', 'low_stock_report_page')
        );

        // تقرير الأصناف المنتهية
        add_submenu_page(
            'institute-warehouse',
            __('أصناف منتهية', 'institute-warehouse'),
            __('- أصناف منتهية', 'institute-warehouse'),
            'iw_view_reports',
            'iw-out-of-stock-report',
            array('IW_Admin', 'out_of_stock_report_page')
        );

        // تقرير الحركات
        add_submenu_page(
            'institute-warehouse',
            __('تقرير الحركات', 'institute-warehouse'),
            __('- تقرير الحركات', 'institute-warehouse'),
            'iw_view_reports',
            'iw-transactions-report',
            array('IW_Admin', 'transactions_report_page')
        );

        // تقرير استهلاك الأقسام
        add_submenu_page(
            'institute-warehouse',
            __('استهلاك الأقسام', 'institute-warehouse'),
            __('- استهلاك الأقسام', 'institute-warehouse'),
            'iw_view_reports',
            'iw-dept-consumption-report',
            array('IW_Admin', 'department_consumption_report_page')
        );

        // تقرير حركة صنف
        add_submenu_page(
            'institute-warehouse',
            __('حركة صنف', 'institute-warehouse'),
            __('- حركة صنف', 'institute-warehouse'),
            'iw_view_reports',
            'iw-product-movement-report',
            array('IW_Admin', 'product_movement_report_page')
        );

        // الأقسام والموظفين
        add_submenu_page(
            'institute-warehouse',
            __('الأقسام والموظفين', 'institute-warehouse'),
            __('الأقسام والموظفين', 'institute-warehouse'),
            'iw_manage_departments',
            'iw-departments',
            array('IW_Admin', 'departments_page')
        );

        // الموردين
        add_submenu_page(
            'institute-warehouse',
            __('الموردين', 'institute-warehouse'),
            __('الموردين', 'institute-warehouse'),
            'iw_manage_suppliers',
            'iw-suppliers',
            array('IW_Admin', 'suppliers_page')
        );

        // التصنيفات
        add_submenu_page(
            'institute-warehouse',
            __('التصنيفات', 'institute-warehouse'),
            __('التصنيفات', 'institute-warehouse'),
            'iw_view_products',
            'iw-categories',
            array('IW_Admin', 'categories_page')
        );

        // استيراد من Excel
        add_submenu_page(
            'institute-warehouse',
            __('استيراد من Excel', 'institute-warehouse'),
            __('استيراد من Excel', 'institute-warehouse'),
            'iw_import_data',
            'iw-import',
            array('IW_Admin', 'import_page')
        );

        // الصلاحيات
        add_submenu_page(
            'institute-warehouse',
            __('الصلاحيات', 'institute-warehouse'),
            __('الصلاحيات', 'institute-warehouse'),
            'manage_options',
            'iw-permissions',
            array('IW_Admin', 'permissions_page')
        );

        // التوقيع الإلكتروني (accessible to dean and approvers)
        add_submenu_page(
            'institute-warehouse',
            __('التوقيع الإلكتروني', 'institute-warehouse'),
            __('التوقيع الإلكتروني', 'institute-warehouse'),
            'iw_view_warehouse',
            'iw-signature',
            array('IW_Admin', 'signature_page')
        );

        // الإعدادات
        add_submenu_page(
            'institute-warehouse',
            __('الإعدادات', 'institute-warehouse'),
            __('الإعدادات', 'institute-warehouse'),
            'manage_options',
            'iw-settings',
            array('IW_Admin', 'settings_page')
        );
    }

    public function dashboard_page() {
        include IW_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'institute-warehouse') === false && strpos($hook, 'iw-') === false) {
            return;
        }

        // Select2 for searchable dropdowns
        wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', array(), '4.0.13');
        wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.full.min.js', array('jquery'), '4.0.13', true);

        wp_enqueue_style('iw-admin-css', IW_PLUGIN_URL . 'assets/css/admin.css', array('select2-css'), IW_VERSION);
        wp_enqueue_script('iw-admin-js', IW_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'select2-js'), IW_VERSION, true);

        // إضافة مكتبة XLSX لاستيراد Excel
        wp_enqueue_script('xlsx-js', 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js', array(), '0.18.5', true);

        wp_localize_script('iw-admin-js', 'iwAdmin', array(
            'ajaxurl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('iw_admin_nonce'),
            'isAdmin'  => current_user_can('manage_options') ? 1 : 0,
            'strings'  => array(
                'confirm_delete' => __('هل أنت متأكد من الحذف؟', 'institute-warehouse'),
                'error'          => __('حدث خطأ، يرجى المحاولة مرة أخرى', 'institute-warehouse'),
                'success'        => __('تمت العملية بنجاح', 'institute-warehouse'),
            )
        ));
    }
}

// Initialize the plugin
function institute_warehouse_init() {
    return Institute_Warehouse_System::get_instance();
}

institute_warehouse_init();
