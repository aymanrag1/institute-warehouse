<?php
/**
 * Reports Management Class
 *
 * إدارة التقارير
 */

if (!defined('ABSPATH')) {
    exit;
}

class IW_Reports {

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
        add_action('wp_ajax_iw_get_dashboard_stats', array($this, 'ajax_get_dashboard_stats'));
        add_action('wp_ajax_iw_get_stock_report', array($this, 'ajax_get_stock_report'));
        add_action('wp_ajax_iw_get_low_stock_report', array($this, 'ajax_get_low_stock_report'));
        add_action('wp_ajax_iw_get_transactions_report', array($this, 'ajax_get_transactions_report'));
        add_action('wp_ajax_iw_get_department_consumption', array($this, 'ajax_get_department_consumption'));
        add_action('wp_ajax_iw_get_expiry_report', array($this, 'ajax_get_expiry_report'));
        add_action('wp_ajax_iw_get_alerts', array($this, 'ajax_get_alerts'));
        add_action('wp_ajax_iw_mark_alert_read', array($this, 'ajax_mark_alert_read'));
        add_action('wp_ajax_iw_resolve_alert', array($this, 'ajax_resolve_alert'));
    }

    /**
     * إحصائيات لوحة التحكم
     */
    public static function get_dashboard_stats() {
        global $wpdb;

        $products_table = $wpdb->prefix . 'iw_products';
        $stock_table = $wpdb->prefix . 'iw_stock';
        $add_permits_table = $wpdb->prefix . 'iw_add_permits';
        $withdraw_permits_table = $wpdb->prefix . 'iw_withdraw_permits';
        $alerts_table = $wpdb->prefix . 'iw_alerts';

        // إجمالي الأصناف
        $total_products = $wpdb->get_var("SELECT COUNT(*) FROM $products_table WHERE is_discontinued = 0");

        // إجمالي قيمة المخزون
        $total_stock_value = $wpdb->get_var(
            "SELECT COALESCE(SUM(remaining_quantity * purchase_price), 0) FROM $stock_table WHERE status = 'available'"
        );

        // أصناف منخفضة المخزون
        $low_stock_count = $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.id)
             FROM $products_table p
             LEFT JOIN $stock_table s ON p.id = s.product_id AND s.status = 'available'
             WHERE p.is_discontinued = 0 AND p.reorder_level > 0
             GROUP BY p.id
             HAVING COALESCE(SUM(s.remaining_quantity), 0) <= p.reorder_level"
        );

        // أصناف نافذة المخزون
        $out_of_stock_count = $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.id)
             FROM $products_table p
             LEFT JOIN $stock_table s ON p.id = s.product_id AND s.status = 'available'
             WHERE p.is_discontinued = 0
             GROUP BY p.id
             HAVING COALESCE(SUM(s.remaining_quantity), 0) = 0"
        );

        // إذونات الإضافة المعلقة
        $pending_add_permits = $wpdb->get_var(
            "SELECT COUNT(*) FROM $add_permits_table WHERE status = 'pending'"
        );

        // إذونات الصرف المعلقة
        $pending_withdraw_permits = $wpdb->get_var(
            "SELECT COUNT(*) FROM $withdraw_permits_table WHERE status IN ('pending', 'approved')"
        );

        // التنبيهات غير المقروءة
        $unread_alerts = $wpdb->get_var(
            "SELECT COUNT(*) FROM $alerts_table WHERE is_read = 0 AND is_resolved = 0"
        );

        // إحصائيات الشهر الحالي
        $current_month_start = date('Y-m-01');
        $current_month_end = date('Y-m-t');

        // إضافات الشهر
        $month_additions = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total_amount), 0) FROM $add_permits_table
             WHERE status = 'approved' AND DATE(approved_at) BETWEEN %s AND %s",
            $current_month_start,
            $current_month_end
        ));

        // صرفيات الشهر (عدد الإذونات)
        $month_withdrawals = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $withdraw_permits_table
             WHERE status = 'delivered' AND DATE(delivered_at) BETWEEN %s AND %s",
            $current_month_start,
            $current_month_end
        ));

        return array(
            'total_products' => (int) $total_products,
            'total_stock_value' => (float) $total_stock_value,
            'low_stock_count' => (int) $low_stock_count ?: 0,
            'out_of_stock_count' => (int) $out_of_stock_count ?: 0,
            'pending_add_permits' => (int) $pending_add_permits,
            'pending_withdraw_permits' => (int) $pending_withdraw_permits,
            'unread_alerts' => (int) $unread_alerts,
            'month_additions' => (float) $month_additions,
            'month_withdrawals' => (int) $month_withdrawals
        );
    }

    /**
     * تقرير المخزون الحالي
     */
    public static function get_stock_report($args = array()) {
        global $wpdb;

        $products_table = $wpdb->prefix . 'iw_products';
        $stock_table = $wpdb->prefix . 'iw_stock';
        $categories_table = $wpdb->prefix . 'iw_categories';
        $warehouses_table = $wpdb->prefix . 'iw_warehouses';

        $defaults = array(
            'warehouse_id' => 0,
            'category_id' => 0,
            'include_discontinued' => false,
            'include_zero_stock' => true
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!$args['include_discontinued']) {
            $where[] = 'p.is_discontinued = 0';
        }

        if (!empty($args['category_id'])) {
            $where[] = 'p.category_id = %d';
            $values[] = $args['category_id'];
        }

        $warehouse_condition = '';
        if (!empty($args['warehouse_id'])) {
            $warehouse_condition = "AND s.warehouse_id = " . absint($args['warehouse_id']);
        }

        $where_clause = implode(' AND ', $where);

        $having = '';
        if (!$args['include_zero_stock']) {
            $having = 'HAVING total_quantity > 0';
        }

        $sql = "SELECT p.id, p.sku, p.name, p.unit, p.reorder_level, p.storage_location,
                       c.name as category_name,
                       COALESCE(SUM(s.remaining_quantity), 0) as total_quantity,
                       COALESCE(SUM(s.remaining_quantity * s.purchase_price), 0) as total_value,
                       MIN(s.expiry_date) as nearest_expiry
                FROM $products_table p
                LEFT JOIN $categories_table c ON p.category_id = c.id
                LEFT JOIN $stock_table s ON p.id = s.product_id AND s.status = 'available' $warehouse_condition
                WHERE $where_clause
                GROUP BY p.id
                $having
                ORDER BY p.name ASC";

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * تقرير المخزون المنخفض
     */
    public static function get_low_stock_report($warehouse_id = null) {
        global $wpdb;

        $products_table = $wpdb->prefix . 'iw_products';
        $stock_table = $wpdb->prefix . 'iw_stock';
        $categories_table = $wpdb->prefix . 'iw_categories';

        $warehouse_condition = '';
        if ($warehouse_id) {
            $warehouse_condition = "AND s.warehouse_id = " . absint($warehouse_id);
        }

        return $wpdb->get_results(
            "SELECT p.id, p.sku, p.name, p.unit, p.reorder_level, p.storage_location,
                    c.name as category_name,
                    COALESCE(SUM(s.remaining_quantity), 0) as current_stock,
                    (p.reorder_level - COALESCE(SUM(s.remaining_quantity), 0)) as shortage
             FROM $products_table p
             LEFT JOIN $categories_table c ON p.category_id = c.id
             LEFT JOIN $stock_table s ON p.id = s.product_id AND s.status = 'available' $warehouse_condition
             WHERE p.is_discontinued = 0 AND p.reorder_level > 0
             GROUP BY p.id
             HAVING current_stock <= p.reorder_level
             ORDER BY shortage DESC"
        );
    }

    /**
     * تقرير حركة الأصناف
     */
    public static function get_transactions_report($args = array()) {
        global $wpdb;

        $transactions_table = $wpdb->prefix . 'iw_transactions';
        $products_table = $wpdb->prefix . 'iw_products';
        $warehouses_table = $wpdb->prefix . 'iw_warehouses';
        $departments_table = $wpdb->prefix . 'iw_departments';

        $defaults = array(
            'product_id' => 0,
            'warehouse_id' => 0,
            'department_id' => 0,
            'transaction_type' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 100,
            'page' => 1
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['product_id'])) {
            $where[] = 't.product_id = %d';
            $values[] = $args['product_id'];
        }

        if (!empty($args['warehouse_id'])) {
            $where[] = 't.warehouse_id = %d';
            $values[] = $args['warehouse_id'];
        }

        if (!empty($args['department_id'])) {
            $where[] = 't.department_id = %d';
            $values[] = $args['department_id'];
        }

        if (!empty($args['transaction_type'])) {
            $where[] = 't.transaction_type = %s';
            $values[] = $args['transaction_type'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'DATE(t.created_at) >= %s';
            $values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'DATE(t.created_at) <= %s';
            $values[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];

        $sql = "SELECT t.*, p.name as product_name, p.sku as product_sku,
                       w.name as warehouse_name, d.name as department_name,
                       u.display_name as created_by_name
                FROM $transactions_table t
                LEFT JOIN $products_table p ON t.product_id = p.id
                LEFT JOIN $warehouses_table w ON t.warehouse_id = w.id
                LEFT JOIN $departments_table d ON t.department_id = d.id
                LEFT JOIN {$wpdb->users} u ON t.created_by = u.ID
                WHERE $where_clause
                ORDER BY t.created_at DESC
                LIMIT %d OFFSET %d";

        $values[] = $args['per_page'];
        $values[] = $offset;

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * تقرير استهلاك الأقسام
     */
    public static function get_department_consumption($args = array()) {
        global $wpdb;

        $transactions_table = $wpdb->prefix . 'iw_transactions';
        $departments_table = $wpdb->prefix . 'iw_departments';
        $products_table = $wpdb->prefix . 'iw_products';

        $defaults = array(
            'department_id' => 0,
            'date_from' => date('Y-m-01'),
            'date_to' => date('Y-m-t')
        );

        $args = wp_parse_args($args, $defaults);

        $where = array("t.transaction_type = 'withdraw'");
        $values = array();

        if (!empty($args['department_id'])) {
            $where[] = 't.department_id = %d';
            $values[] = $args['department_id'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'DATE(t.created_at) >= %s';
            $values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'DATE(t.created_at) <= %s';
            $values[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where);

        // استهلاك حسب القسم
        $sql_by_dept = "SELECT d.id, d.name as department_name,
                               COUNT(DISTINCT t.permit_id) as permit_count,
                               SUM(t.quantity) as total_quantity
                        FROM $transactions_table t
                        INNER JOIN $departments_table d ON t.department_id = d.id
                        WHERE $where_clause
                        GROUP BY d.id
                        ORDER BY total_quantity DESC";

        if (!empty($values)) {
            $sql_by_dept = $wpdb->prepare($sql_by_dept, $values);
        }

        $by_department = $wpdb->get_results($sql_by_dept);

        // استهلاك حسب المنتج
        $sql_by_product = "SELECT p.id, p.name as product_name, p.sku,
                                  SUM(t.quantity) as total_quantity
                           FROM $transactions_table t
                           INNER JOIN $products_table p ON t.product_id = p.id
                           WHERE $where_clause
                           GROUP BY p.id
                           ORDER BY total_quantity DESC
                           LIMIT 20";

        if (!empty($values)) {
            $sql_by_product = $wpdb->prepare($sql_by_product, $values);
        }

        $by_product = $wpdb->get_results($sql_by_product);

        return array(
            'by_department' => $by_department,
            'by_product' => $by_product
        );
    }

    /**
     * تقرير انتهاء الصلاحية
     */
    public static function get_expiry_report($days = 30, $warehouse_id = null) {
        global $wpdb;

        $products_table = $wpdb->prefix . 'iw_products';
        $stock_table = $wpdb->prefix . 'iw_stock';
        $warehouses_table = $wpdb->prefix . 'iw_warehouses';

        $where = "s.expiry_date IS NOT NULL
                  AND s.expiry_date <= DATE_ADD(CURDATE(), INTERVAL %d DAY)
                  AND s.status = 'available'
                  AND s.remaining_quantity > 0";
        $values = array($days);

        if ($warehouse_id) {
            $where .= " AND s.warehouse_id = %d";
            $values[] = $warehouse_id;
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.id, p.sku, p.name, s.batch_number, s.expiry_date,
                    s.remaining_quantity, w.name as warehouse_name,
                    DATEDIFF(s.expiry_date, CURDATE()) as days_remaining
             FROM $stock_table s
             INNER JOIN $products_table p ON s.product_id = p.id
             LEFT JOIN $warehouses_table w ON s.warehouse_id = w.id
             WHERE $where
             ORDER BY s.expiry_date ASC",
            $values
        ));
    }

    /**
     * الحصول على التنبيهات
     */
    public static function get_alerts($args = array()) {
        global $wpdb;

        $alerts_table = $wpdb->prefix . 'iw_alerts';
        $products_table = $wpdb->prefix . 'iw_products';
        $warehouses_table = $wpdb->prefix . 'iw_warehouses';

        $defaults = array(
            'alert_type' => '',
            'is_read' => null,
            'is_resolved' => null,
            'per_page' => 50,
            'page' => 1
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['alert_type'])) {
            $where[] = 'a.alert_type = %s';
            $values[] = $args['alert_type'];
        }

        if ($args['is_read'] !== null) {
            $where[] = 'a.is_read = %d';
            $values[] = $args['is_read'] ? 1 : 0;
        }

        if ($args['is_resolved'] !== null) {
            $where[] = 'a.is_resolved = %d';
            $values[] = $args['is_resolved'] ? 1 : 0;
        }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];

        $sql = "SELECT a.*, p.name as product_name, p.sku, w.name as warehouse_name
                FROM $alerts_table a
                LEFT JOIN $products_table p ON a.product_id = p.id
                LEFT JOIN $warehouses_table w ON a.warehouse_id = w.id
                WHERE $where_clause
                ORDER BY a.created_at DESC
                LIMIT %d OFFSET %d";

        $values[] = $args['per_page'];
        $values[] = $offset;

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * تحديث قراءة التنبيه
     */
    public static function mark_alert_read($alert_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_alerts';

        return $wpdb->update($table, array('is_read' => 1), array('id' => $alert_id));
    }

    /**
     * حل التنبيه
     */
    public static function resolve_alert($alert_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_alerts';

        return $wpdb->update(
            $table,
            array(
                'is_resolved' => 1,
                'resolved_by' => get_current_user_id(),
                'resolved_at' => current_time('mysql')
            ),
            array('id' => $alert_id)
        );
    }

    /**
     * فحص وإنشاء تنبيهات انتهاء الصلاحية
     */
    public static function check_expiry_alerts() {
        global $wpdb;

        $stock_table = $wpdb->prefix . 'iw_stock';
        $alerts_table = $wpdb->prefix . 'iw_alerts';
        $products_table = $wpdb->prefix . 'iw_products';

        $warning_days = IW_Database::get_setting('expiry_warning_days', 30);

        // البحث عن منتجات قريبة من انتهاء الصلاحية
        $expiring = $wpdb->get_results($wpdb->prepare(
            "SELECT s.id as stock_id, s.product_id, s.warehouse_id, s.expiry_date, p.name
             FROM $stock_table s
             INNER JOIN $products_table p ON s.product_id = p.id
             WHERE s.expiry_date IS NOT NULL
             AND s.expiry_date <= DATE_ADD(CURDATE(), INTERVAL %d DAY)
             AND s.expiry_date > CURDATE()
             AND s.status = 'available'
             AND s.remaining_quantity > 0
             AND s.id NOT IN (
                 SELECT stock_id FROM $alerts_table
                 WHERE alert_type = 'expiry_warning' AND is_resolved = 0 AND stock_id IS NOT NULL
             )",
            $warning_days
        ));

        foreach ($expiring as $item) {
            $wpdb->insert($alerts_table, array(
                'alert_type' => 'expiry_warning',
                'product_id' => $item->product_id,
                'warehouse_id' => $item->warehouse_id,
                'stock_id' => $item->stock_id,
                'message' => sprintf(
                    'الصنف "%s" قارب على انتهاء الصلاحية في %s',
                    $item->name,
                    date_i18n('Y-m-d', strtotime($item->expiry_date))
                )
            ));
        }

        // البحث عن منتجات منتهية الصلاحية
        $expired = $wpdb->get_results(
            "SELECT s.id as stock_id, s.product_id, s.warehouse_id, s.expiry_date, p.name
             FROM $stock_table s
             INNER JOIN $products_table p ON s.product_id = p.id
             WHERE s.expiry_date IS NOT NULL
             AND s.expiry_date <= CURDATE()
             AND s.status = 'available'
             AND s.remaining_quantity > 0
             AND s.id NOT IN (
                 SELECT stock_id FROM $alerts_table
                 WHERE alert_type = 'expiry' AND is_resolved = 0 AND stock_id IS NOT NULL
             )"
        );

        foreach ($expired as $item) {
            // تحديث حالة المخزون
            $wpdb->update($stock_table, array('status' => 'expired'), array('id' => $item->stock_id));

            // إنشاء تنبيه
            $wpdb->insert($alerts_table, array(
                'alert_type' => 'expiry',
                'product_id' => $item->product_id,
                'warehouse_id' => $item->warehouse_id,
                'stock_id' => $item->stock_id,
                'message' => sprintf(
                    'الصنف "%s" انتهت صلاحيته في %s',
                    $item->name,
                    date_i18n('Y-m-d', strtotime($item->expiry_date))
                )
            ));
        }
    }

    // ==================== AJAX Handlers ====================

    public function ajax_get_dashboard_stats() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_warehouse')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $stats = self::get_dashboard_stats();
        wp_send_json_success($stats);
    }

    public function ajax_get_stock_report() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_reports')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $args = array(
            'warehouse_id' => isset($_POST['warehouse_id']) ? absint($_POST['warehouse_id']) : 0,
            'category_id' => isset($_POST['category_id']) ? absint($_POST['category_id']) : 0,
            'include_discontinued' => isset($_POST['include_discontinued']) && $_POST['include_discontinued'] === 'true',
            'include_zero_stock' => !isset($_POST['exclude_zero_stock']) || $_POST['exclude_zero_stock'] !== 'true'
        );

        $report = self::get_stock_report($args);
        wp_send_json_success($report);
    }

    public function ajax_get_low_stock_report() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_reports')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $warehouse_id = isset($_POST['warehouse_id']) ? absint($_POST['warehouse_id']) : null;
        $report = self::get_low_stock_report($warehouse_id);
        wp_send_json_success($report);
    }

    public function ajax_get_transactions_report() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_reports')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $args = array(
            'product_id' => isset($_POST['product_id']) ? absint($_POST['product_id']) : 0,
            'warehouse_id' => isset($_POST['warehouse_id']) ? absint($_POST['warehouse_id']) : 0,
            'department_id' => isset($_POST['department_id']) ? absint($_POST['department_id']) : 0,
            'transaction_type' => isset($_POST['transaction_type']) ? sanitize_text_field($_POST['transaction_type']) : '',
            'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '',
            'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '',
            'per_page' => isset($_POST['per_page']) ? absint($_POST['per_page']) : 100,
            'page' => isset($_POST['page']) ? absint($_POST['page']) : 1
        );

        $report = self::get_transactions_report($args);
        wp_send_json_success($report);
    }

    public function ajax_get_department_consumption() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_reports')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $args = array(
            'department_id' => isset($_POST['department_id']) ? absint($_POST['department_id']) : 0,
            'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : date('Y-m-01'),
            'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : date('Y-m-t')
        );

        $report = self::get_department_consumption($args);
        wp_send_json_success($report);
    }

    public function ajax_get_expiry_report() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_reports')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $days = isset($_POST['days']) ? absint($_POST['days']) : 30;
        $warehouse_id = isset($_POST['warehouse_id']) ? absint($_POST['warehouse_id']) : null;

        $report = self::get_expiry_report($days, $warehouse_id);
        wp_send_json_success($report);
    }

    public function ajax_get_alerts() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_alerts')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $args = array(
            'alert_type' => isset($_POST['alert_type']) ? sanitize_text_field($_POST['alert_type']) : '',
            'is_read' => isset($_POST['is_read']) ? ($_POST['is_read'] === 'true' ? true : false) : null,
            'is_resolved' => isset($_POST['is_resolved']) ? ($_POST['is_resolved'] === 'true' ? true : false) : null,
            'per_page' => isset($_POST['per_page']) ? absint($_POST['per_page']) : 50,
            'page' => isset($_POST['page']) ? absint($_POST['page']) : 1
        );

        $alerts = self::get_alerts($args);
        wp_send_json_success($alerts);
    }

    public function ajax_mark_alert_read() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_alerts')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف التنبيه مطلوب'));
        }

        self::mark_alert_read($id);
        wp_send_json_success(array('message' => 'تم تحديث التنبيه'));
    }

    public function ajax_resolve_alert() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_alerts')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف التنبيه مطلوب'));
        }

        self::resolve_alert($id);
        wp_send_json_success(array('message' => 'تم حل التنبيه'));
    }
}

// Initialize
IW_Reports::get_instance();
