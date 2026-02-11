<?php
if (!defined('ABSPATH')) exit;

class IW_Admin {

    public static function products_page() {
        include IW_PLUGIN_DIR . 'admin/views/products.php';
    }

    public static function add_stock_page() {
        include IW_PLUGIN_DIR . 'admin/views/add-stock.php';
    }

    public static function withdraw_stock_page() {
        include IW_PLUGIN_DIR . 'admin/views/withdraw-stock.php';
    }

    public static function reports_page() {
        include IW_PLUGIN_DIR . 'admin/views/reports.php';
    }

    public static function departments_page() {
        include IW_PLUGIN_DIR . 'admin/views/departments.php';
    }

    public static function suppliers_page() {
        include IW_PLUGIN_DIR . 'admin/views/suppliers.php';
    }

    public static function categories_page() {
        include IW_PLUGIN_DIR . 'admin/views/categories.php';
    }

    public static function import_page() {
        include IW_PLUGIN_DIR . 'admin/views/import.php';
    }

    public static function settings_page() {
        include IW_PLUGIN_DIR . 'admin/views/settings.php';
    }

    public static function withdrawal_orders_page() {
        include IW_PLUGIN_DIR . 'admin/views/withdrawal-orders.php';
    }

    public static function purchase_requests_page() {
        include IW_PLUGIN_DIR . 'admin/views/purchase-requests.php';
    }

    public static function approval_page() {
        include IW_PLUGIN_DIR . 'admin/views/approval.php';
    }

    public static function opening_balance_page() {
        include IW_PLUGIN_DIR . 'admin/views/opening-balance.php';
    }

    public static function permissions_page() {
        include IW_PLUGIN_DIR . 'admin/views/permissions.php';
    }

    public static function signature_page() {
        include IW_PLUGIN_DIR . 'admin/views/signature.php';
    }

    public static function print_add_permit_page() {
        include IW_PLUGIN_DIR . 'admin/views/print-add-permit.php';
    }

    public static function print_withdraw_permit_page() {
        include IW_PLUGIN_DIR . 'admin/views/print-withdraw-permit.php';
    }

    public static function stock_report_page() {
        include IW_PLUGIN_DIR . 'admin/views/reports/stock-report.php';
    }

    public static function low_stock_report_page() {
        include IW_PLUGIN_DIR . 'admin/views/reports/low-stock-report.php';
    }

    public static function out_of_stock_report_page() {
        include IW_PLUGIN_DIR . 'admin/views/reports/out-of-stock-report.php';
    }

    public static function transactions_report_page() {
        include IW_PLUGIN_DIR . 'admin/views/reports/transactions-report.php';
    }

    public static function department_consumption_report_page() {
        include IW_PLUGIN_DIR . 'admin/views/reports/department-consumption-report.php';
    }

    public static function product_movement_report_page() {
        include IW_PLUGIN_DIR . 'admin/views/reports/product-movement-report.php';
    }

    /**
     * Get print header with logo and institute name from settings
     */
    public static function get_print_header() {
        $logo_url      = get_option('iw_logo_url', '');
        $institute_name = get_option('iw_institute_name', '');

        $html = '<div class="iw-print-header" style="text-align:center;margin-bottom:20px;">';
        if ($logo_url) {
            $html .= '<img src="' . esc_url($logo_url) . '" style="max-height:80px;margin-bottom:10px;" /><br>';
        }
        if ($institute_name) {
            $html .= '<h2 style="margin:5px 0;">' . esc_html($institute_name) . '</h2>';
        }
        $html .= '</div>';

        return $html;
    }
}
