<?php
/**
 * Plugin Name: نظام إدارة مخازن المعهد
 * Plugin URI: https://example.com
 * Description: نظام متكامل لإدارة مخازن المعاهد التعليمية مع نظام FIFO وصلاحيات تفصيلية
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: institute-warehouse
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('IW_VERSION', '1.0.0');
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
    }
    
    private function load_dependencies() {
        require_once IW_PLUGIN_DIR . 'includes/class-iw-database.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-products.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-transactions.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-departments.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-suppliers.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-permissions.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-reports.php';
        require_once IW_PLUGIN_DIR . 'includes/class-iw-excel-import.php';
        require_once IW_PLUGIN_DIR . 'admin/class-iw-admin.php';
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
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
        
        // إذن صرف
        add_submenu_page(
            'institute-warehouse',
            __('إذن صرف', 'institute-warehouse'),
            __('إذن صرف', 'institute-warehouse'),
            'iw_withdraw_stock',
            'iw-withdraw-stock',
            array('IW_Admin', 'withdraw_stock_page')
        );
        
        // التقارير
        add_submenu_page(
            'institute-warehouse',
            __('التقارير', 'institute-warehouse'),
            __('التقارير', 'institute-warehouse'),
            'iw_view_reports',
            'iw-reports',
            array('IW_Admin', 'reports_page')
        );
        
        // الأقسام
        add_submenu_page(
            'institute-warehouse',
            __('الأقسام', 'institute-warehouse'),
            __('الأقسام', 'institute-warehouse'),
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
        
        // استيراد من Excel
        add_submenu_page(
            'institute-warehouse',
            __('استيراد من Excel', 'institute-warehouse'),
            __('استيراد من Excel', 'institute-warehouse'),
            'iw_import_data',
            'iw-import',
            array('IW_Admin', 'import_page')
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
        
        wp_enqueue_style('iw-admin-css', IW_PLUGIN_URL . 'assets/css/admin.css', array(), IW_VERSION);
        wp_enqueue_script('iw-admin-js', IW_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), IW_VERSION, true);
        
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
