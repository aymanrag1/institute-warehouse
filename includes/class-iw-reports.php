<?php
if (!defined('ABSPATH')) exit;

class IW_Reports {

    public static function init() {
        add_action('wp_ajax_iw_get_stock_report', array(__CLASS__, 'stock_report'));
        add_action('wp_ajax_iw_get_transactions_report', array(__CLASS__, 'transactions_report'));
        add_action('wp_ajax_iw_get_low_stock_report', array(__CLASS__, 'low_stock_report'));
        add_action('wp_ajax_iw_get_dept_consumption_detail', array(__CLASS__, 'dept_consumption_detail'));
    }

    public static function stock_report() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;

        $category = sanitize_text_field($_POST['category'] ?? '');

        $where = '1=1';
        if (!empty($category)) {
            $where .= $wpdb->prepare(" AND category = %s", $category);
        }

        $products = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}iw_products WHERE {$where} ORDER BY name ASC"
        );
        wp_send_json_success($products);
    }

    public static function transactions_report() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $from = sanitize_text_field($_POST['from_date'] ?? '');
        $to   = sanitize_text_field($_POST['to_date'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? '');

        $where = '1=1';
        if ($from) $where .= $wpdb->prepare(" AND t.created_at >= %s", $from . ' 00:00:00');
        if ($to)   $where .= $wpdb->prepare(" AND t.created_at <= %s", $to . ' 23:59:59');
        if ($type) $where .= $wpdb->prepare(" AND t.transaction_type = %s", $type);

        // iw_departments / iw_employees tables were removed (plugin uses RSYI HR System).
        // Enrich department/employee names in PHP via IW_Departments helpers, which handle any HR schema.
        $rows = $wpdb->get_results(
            "SELECT t.*, p.name as product_name, p.unit as product_unit,
                    s.name as supplier_name
             FROM {$prefix}transactions t
             LEFT JOIN {$prefix}products p ON t.product_id = p.id
             LEFT JOIN {$prefix}suppliers s ON t.supplier_id = s.id
             WHERE $where
             ORDER BY t.created_at DESC"
        );

        if ($rows) {
            foreach ($rows as $row) {
                $row->department_name = '';
                $row->employee_name   = '';
                if (!empty($row->department_id)) {
                    $dept = IW_Departments::get_by_id($row->department_id);
                    if ($dept) $row->department_name = $dept->name;
                }
                if (!empty($row->employee_id)) {
                    $emp = IW_Departments::get_employee_by_id($row->employee_id);
                    if ($emp) $row->employee_name = $emp->name;
                }
            }
        }

        wp_send_json_success($rows);
    }

    public static function low_stock_report() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;

        $category = sanitize_text_field($_POST['category'] ?? '');

        $where = 'current_stock <= min_stock AND min_stock > 0';
        if (!empty($category)) {
            $where .= $wpdb->prepare(" AND category = %s", $category);
        }

        $products = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}iw_products WHERE {$where} ORDER BY name ASC"
        );
        wp_send_json_success($products);
    }

    /**
     * Department consumption detailed report
     */
    public static function dept_consumption_detail() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $dept_id = intval($_POST['department_id'] ?? 0);
        $from    = sanitize_text_field($_POST['from_date'] ?? '');
        $to      = sanitize_text_field($_POST['to_date'] ?? '');

        $where = "t.transaction_type = 'withdraw'";
        if ($dept_id) $where .= $wpdb->prepare(" AND t.department_id = %d", $dept_id);
        if ($from) $where .= $wpdb->prepare(" AND t.created_at >= %s", $from . ' 00:00:00');
        if ($to)   $where .= $wpdb->prepare(" AND t.created_at <= %s", $to . ' 23:59:59');

        // iw_departments / iw_employees tables were removed (plugin uses RSYI HR System).
        // Enrich department/employee names in PHP via IW_Departments helpers.
        $rows = $wpdb->get_results(
            "SELECT t.*, p.name as product_name, p.unit as product_unit
             FROM {$prefix}transactions t
             LEFT JOIN {$prefix}products p ON t.product_id = p.id
             WHERE $where
             ORDER BY t.created_at DESC"
        );

        if ($rows) {
            foreach ($rows as $row) {
                $row->department_name = '';
                $row->employee_name   = '';
                if (!empty($row->department_id)) {
                    $dept = IW_Departments::get_by_id($row->department_id);
                    if ($dept) $row->department_name = $dept->name;
                }
                if (!empty($row->employee_id)) {
                    $emp = IW_Departments::get_employee_by_id($row->employee_id);
                    if ($emp) $row->employee_name = $emp->name;
                }
            }
        }

        // Calculate summary
        $total_qty = 0;
        $total_value = 0;
        foreach ($rows as $row) {
            $total_qty += intval($row->quantity);
            $total_value += intval($row->quantity) * floatval($row->unit_price);
        }

        wp_send_json_success(array(
            'transactions' => $rows,
            'summary' => array(
                'total_transactions' => count($rows),
                'total_qty' => $total_qty,
                'total_value' => $total_value
            )
        ));
    }
}
