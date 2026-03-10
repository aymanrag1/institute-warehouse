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

        // Note: iw_departments / iw_employees tables may not exist (plugin uses RSYI HR System).
        // Use LEFT JOINs against HR tables if they exist, otherwise skip them to avoid fatal query errors.
        $hr_dept_table = $wpdb->prefix . 'rsyi_hr_departments';
        $hr_emp_table  = $wpdb->prefix . 'rsyi_hr_employees';
        $dept_join = '';
        $emp_join  = '';
        $dept_col  = "'' as department_name";
        $emp_col   = "'' as employee_name";

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $hr_dept_table)) === $hr_dept_table) {
            $dept_join = "LEFT JOIN `{$hr_dept_table}` hd ON t.department_id = hd.id";
            $dept_col  = 'hd.name as department_name';
        }
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $hr_emp_table)) === $hr_emp_table) {
            $emp_join = "LEFT JOIN `{$hr_emp_table}` he ON t.employee_id = he.id";
            $emp_col  = 'he.name as employee_name';
        }

        $rows = $wpdb->get_results(
            "SELECT t.*, p.name as product_name, p.unit as product_unit,
                    {$dept_col}, {$emp_col}, s.name as supplier_name
             FROM {$prefix}transactions t
             LEFT JOIN {$prefix}products p ON t.product_id = p.id
             {$dept_join}
             {$emp_join}
             LEFT JOIN {$prefix}suppliers s ON t.supplier_id = s.id
             WHERE $where
             ORDER BY t.created_at DESC"
        );
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

        $rows = $wpdb->get_results(
            "SELECT t.*, p.name as product_name, p.unit as product_unit,
                    d.name as department_name, e.name as employee_name
             FROM {$prefix}transactions t
             LEFT JOIN {$prefix}products p ON t.product_id = p.id
             LEFT JOIN {$prefix}departments d ON t.department_id = d.id
             LEFT JOIN {$prefix}employees e ON t.employee_id = e.id
             WHERE $where
             ORDER BY t.created_at DESC"
        );

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
