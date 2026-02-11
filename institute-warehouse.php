<?php
/**
 * Plugin Name: نظام إدارة مخازن المعهد
 * Plugin URI: https://example.com
 * Description: نظام متكامل لإدارة مخازن المعاهد التعليمية مع نظام FIFO وصلاحيات تفصيلية وتوقيع إلكتروني
 * Version: 2.2.2
 * Author: AYMAN RAGAB
 * Author URI: tel:00201159230034
 * Text Domain: institute-warehouse
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('IW_VERSION', '2.2.2');
define('IW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IW_PLUGIN_URL', plugin_dir_url(__FILE__));

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
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('iw_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('هل أنت متأكد من الحذف؟', 'institute-warehouse'),
                'error' => __('حدث خطأ، يرجى المحاولة مرة أخرى', 'institute-warehouse'),
                'success' => __('تمت العملية بنجاح', 'institute-warehouse'),
            )
        ));
    }
}

// Initialize the plugin
function institute_warehouse_init() {
    return Institute_Warehouse_System::get_instance();
}

institute_warehouse_init();
