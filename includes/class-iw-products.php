<?php
/**
 * Products Management Class
 *
 * إدارة الأصناف والمنتجات
 */

if (!defined('ABSPATH')) {
    exit;
}

class IW_Products {

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
        add_action('wp_ajax_iw_get_products', array($this, 'ajax_get_products'));
        add_action('wp_ajax_iw_get_product', array($this, 'ajax_get_product'));
        add_action('wp_ajax_iw_save_product', array($this, 'ajax_save_product'));
        add_action('wp_ajax_iw_delete_product', array($this, 'ajax_delete_product'));
        add_action('wp_ajax_iw_search_products', array($this, 'ajax_search_products'));
        add_action('wp_ajax_iw_get_product_stock', array($this, 'ajax_get_product_stock'));
        add_action('wp_ajax_iw_discontinue_product', array($this, 'ajax_discontinue_product'));
    }

    /**
     * إضافة منتج جديد
     */
    public static function add($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_products';

        $insert_data = array(
            'sku' => sanitize_text_field($data['sku']),
            'name' => sanitize_text_field($data['name']),
            'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
            'category_id' => isset($data['category_id']) ? absint($data['category_id']) : null,
            'unit' => isset($data['unit']) ? sanitize_text_field($data['unit']) : '',
            'min_quantity' => isset($data['min_quantity']) ? absint($data['min_quantity']) : 0,
            'reorder_level' => isset($data['reorder_level']) ? absint($data['reorder_level']) : 0,
            'storage_location' => isset($data['storage_location']) ? sanitize_text_field($data['storage_location']) : '',
            'has_expiry' => isset($data['has_expiry']) ? 1 : 0,
            'is_discontinued' => isset($data['is_discontinued']) ? 1 : 0,
            'notes' => isset($data['notes']) ? sanitize_textarea_field($data['notes']) : ''
        );

        $result = $wpdb->insert($table, $insert_data);

        if ($result) {
            $product_id = $wpdb->insert_id;
            IW_Database::log_activity('create', 'product', $product_id, $insert_data);
            return $product_id;
        }

        return false;
    }

    /**
     * تحديث منتج
     */
    public static function update($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_products';

        $update_data = array();

        if (isset($data['sku'])) {
            $update_data['sku'] = sanitize_text_field($data['sku']);
        }
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
        }
        if (isset($data['category_id'])) {
            $update_data['category_id'] = absint($data['category_id']);
        }
        if (isset($data['unit'])) {
            $update_data['unit'] = sanitize_text_field($data['unit']);
        }
        if (isset($data['min_quantity'])) {
            $update_data['min_quantity'] = absint($data['min_quantity']);
        }
        if (isset($data['reorder_level'])) {
            $update_data['reorder_level'] = absint($data['reorder_level']);
        }
        if (isset($data['storage_location'])) {
            $update_data['storage_location'] = sanitize_text_field($data['storage_location']);
        }
        if (isset($data['has_expiry'])) {
            $update_data['has_expiry'] = $data['has_expiry'] ? 1 : 0;
        }
        if (isset($data['is_discontinued'])) {
            $update_data['is_discontinued'] = $data['is_discontinued'] ? 1 : 0;
        }
        if (isset($data['notes'])) {
            $update_data['notes'] = sanitize_textarea_field($data['notes']);
        }

        if (empty($update_data)) {
            return false;
        }

        $result = $wpdb->update($table, $update_data, array('id' => $id));

        if ($result !== false) {
            IW_Database::log_activity('update', 'product', $id, $update_data);
            return true;
        }

        return false;
    }

    /**
     * حذف منتج
     */
    public static function delete($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_products';

        // التحقق من عدم وجود مخزون للمنتج
        $stock_table = $wpdb->prefix . 'iw_stock';
        $has_stock = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(remaining_quantity) FROM $stock_table WHERE product_id = %d",
            $id
        ));

        if ($has_stock > 0) {
            return new WP_Error('has_stock', 'لا يمكن حذف المنتج لوجود كميات في المخزون');
        }

        $result = $wpdb->delete($table, array('id' => $id));

        if ($result) {
            IW_Database::log_activity('delete', 'product', $id);
            return true;
        }

        return false;
    }

    /**
     * الحصول على منتج
     */
    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_products';
        $categories_table = $wpdb->prefix . 'iw_categories';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.name as category_name
             FROM $table p
             LEFT JOIN $categories_table c ON p.category_id = c.id
             WHERE p.id = %d",
            $id
        ));
    }

    /**
     * الحصول على جميع المنتجات
     */
    public static function get_all($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_products';
        $categories_table = $wpdb->prefix . 'iw_categories';
        $stock_table = $wpdb->prefix . 'iw_stock';

        $defaults = array(
            'search' => '',
            'category_id' => 0,
            'include_discontinued' => false,
            'orderby' => 'name',
            'order' => 'ASC',
            'per_page' => 20,
            'page' => 1
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['search'])) {
            $where[] = "(p.name LIKE %s OR p.sku LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
        }

        if (!empty($args['category_id'])) {
            $where[] = "p.category_id = %d";
            $values[] = $args['category_id'];
        }

        if (!$args['include_discontinued']) {
            $where[] = "p.is_discontinued = 0";
        }

        $where_clause = implode(' AND ', $where);

        $allowed_orderby = array('name', 'sku', 'created_at', 'category_name');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'name';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        $offset = ($args['page'] - 1) * $args['per_page'];

        $sql = "SELECT p.*, c.name as category_name,
                COALESCE(SUM(s.remaining_quantity), 0) as total_stock
                FROM $table p
                LEFT JOIN $categories_table c ON p.category_id = c.id
                LEFT JOIN $stock_table s ON p.id = s.product_id AND s.status = 'available'
                WHERE $where_clause
                GROUP BY p.id
                ORDER BY $orderby $order
                LIMIT %d OFFSET %d";

        $values[] = $args['per_page'];
        $values[] = $offset;

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * عدد المنتجات
     */
    public static function count($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_products';

        $defaults = array(
            'search' => '',
            'category_id' => 0,
            'include_discontinued' => false
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['search'])) {
            $where[] = "(name LIKE %s OR sku LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
        }

        if (!empty($args['category_id'])) {
            $where[] = "category_id = %d";
            $values[] = $args['category_id'];
        }

        if (!$args['include_discontinued']) {
            $where[] = "is_discontinued = 0";
        }

        $where_clause = implode(' AND ', $where);

        $sql = "SELECT COUNT(*) FROM $table WHERE $where_clause";

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * الحصول على مخزون منتج معين
     */
    public static function get_stock($product_id, $warehouse_id = null) {
        global $wpdb;
        $stock_table = $wpdb->prefix . 'iw_stock';
        $warehouses_table = $wpdb->prefix . 'iw_warehouses';

        $where = "s.product_id = %d AND s.status = 'available' AND s.remaining_quantity > 0";
        $values = array($product_id);

        if ($warehouse_id) {
            $where .= " AND s.warehouse_id = %d";
            $values[] = $warehouse_id;
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, w.name as warehouse_name
             FROM $stock_table s
             LEFT JOIN $warehouses_table w ON s.warehouse_id = w.id
             WHERE $where
             ORDER BY s.received_date ASC",
            $values
        ));
    }

    /**
     * الحصول على إجمالي المخزون لمنتج
     */
    public static function get_total_stock($product_id, $warehouse_id = null) {
        global $wpdb;
        $stock_table = $wpdb->prefix . 'iw_stock';

        $where = "product_id = %d AND status = 'available'";
        $values = array($product_id);

        if ($warehouse_id) {
            $where .= " AND warehouse_id = %d";
            $values[] = $warehouse_id;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(remaining_quantity), 0) FROM $stock_table WHERE $where",
            $values
        ));
    }

    /**
     * الحصول على المنتجات منخفضة المخزون
     */
    public static function get_low_stock_products($warehouse_id = null) {
        global $wpdb;
        $products_table = $wpdb->prefix . 'iw_products';
        $stock_table = $wpdb->prefix . 'iw_stock';

        $warehouse_condition = '';
        $values = array();

        if ($warehouse_id) {
            $warehouse_condition = "AND s.warehouse_id = %d";
            $values[] = $warehouse_id;
        }

        $sql = "SELECT p.*, COALESCE(SUM(s.remaining_quantity), 0) as current_stock
                FROM $products_table p
                LEFT JOIN $stock_table s ON p.id = s.product_id AND s.status = 'available' $warehouse_condition
                WHERE p.is_discontinued = 0
                GROUP BY p.id
                HAVING current_stock <= p.reorder_level AND p.reorder_level > 0
                ORDER BY current_stock ASC";

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * الحصول على المنتجات النافذة من المخزون
     */
    public static function get_out_of_stock_products($warehouse_id = null) {
        global $wpdb;
        $products_table = $wpdb->prefix . 'iw_products';
        $stock_table = $wpdb->prefix . 'iw_stock';

        $warehouse_condition = '';
        $values = array();

        if ($warehouse_id) {
            $warehouse_condition = "AND s.warehouse_id = %d";
            $values[] = $warehouse_id;
        }

        $sql = "SELECT p.*, COALESCE(SUM(s.remaining_quantity), 0) as current_stock
                FROM $products_table p
                LEFT JOIN $stock_table s ON p.id = s.product_id AND s.status = 'available' $warehouse_condition
                WHERE p.is_discontinued = 0
                GROUP BY p.id
                HAVING current_stock = 0
                ORDER BY p.name ASC";

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * الحصول على المنتجات قريبة انتهاء الصلاحية
     */
    public static function get_expiring_products($days = 30, $warehouse_id = null) {
        global $wpdb;
        $products_table = $wpdb->prefix . 'iw_products';
        $stock_table = $wpdb->prefix . 'iw_stock';

        $where = "s.expiry_date IS NOT NULL
                  AND s.expiry_date <= DATE_ADD(CURDATE(), INTERVAL %d DAY)
                  AND s.expiry_date >= CURDATE()
                  AND s.status = 'available'
                  AND s.remaining_quantity > 0";
        $values = array($days);

        if ($warehouse_id) {
            $where .= " AND s.warehouse_id = %d";
            $values[] = $warehouse_id;
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, s.id as stock_id, s.batch_number, s.expiry_date, s.remaining_quantity, s.warehouse_id
             FROM $products_table p
             INNER JOIN $stock_table s ON p.id = s.product_id
             WHERE $where
             ORDER BY s.expiry_date ASC",
            $values
        ));
    }

    /**
     * البحث عن منتجات
     */
    public static function search($term, $limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_products';
        $stock_table = $wpdb->prefix . 'iw_stock';

        $search_term = '%' . $wpdb->esc_like($term) . '%';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, COALESCE(SUM(s.remaining_quantity), 0) as total_stock
             FROM $table p
             LEFT JOIN $stock_table s ON p.id = s.product_id AND s.status = 'available'
             WHERE p.is_discontinued = 0 AND (p.name LIKE %s OR p.sku LIKE %s)
             GROUP BY p.id
             ORDER BY p.name ASC
             LIMIT %d",
            $search_term,
            $search_term,
            $limit
        ));
    }

    /**
     * التحقق من تكرار SKU
     */
    public static function sku_exists($sku, $exclude_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_products';

        $where = "sku = %s";
        $values = array($sku);

        if ($exclude_id) {
            $where .= " AND id != %d";
            $values[] = $exclude_id;
        }

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE $where",
            $values
        ));
    }

    /**
     * إيقاف منتج (عدم الشراء مرة أخرى)
     */
    public static function discontinue($id) {
        return self::update($id, array('is_discontinued' => true));
    }

    /**
     * إعادة تفعيل منتج
     */
    public static function reactivate($id) {
        return self::update($id, array('is_discontinued' => false));
    }

    // === AJAX Handlers ===

    public function ajax_get_products() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_products')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $args = array(
            'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
            'category_id' => isset($_POST['category_id']) ? absint($_POST['category_id']) : 0,
            'include_discontinued' => isset($_POST['include_discontinued']) && $_POST['include_discontinued'] === 'true',
            'per_page' => isset($_POST['per_page']) ? absint($_POST['per_page']) : 20,
            'page' => isset($_POST['page']) ? absint($_POST['page']) : 1
        );

        $products = self::get_all($args);
        $total = self::count($args);

        wp_send_json_success(array(
            'products' => $products,
            'total' => $total,
            'pages' => ceil($total / $args['per_page'])
        ));
    }

    public function ajax_get_product() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_products')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        if (!$id) {
            wp_send_json_error(array('message' => 'معرف المنتج مطلوب'));
        }

        $product = self::get($id);
        if (!$product) {
            wp_send_json_error(array('message' => 'المنتج غير موجود'));
        }

        wp_send_json_success($product);
    }

    public function ajax_save_product() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_manage_products')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        $data = array(
            'sku' => isset($_POST['sku']) ? sanitize_text_field($_POST['sku']) : '',
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
            'category_id' => isset($_POST['category_id']) ? absint($_POST['category_id']) : 0,
            'unit' => isset($_POST['unit']) ? sanitize_text_field($_POST['unit']) : '',
            'min_quantity' => isset($_POST['min_quantity']) ? absint($_POST['min_quantity']) : 0,
            'reorder_level' => isset($_POST['reorder_level']) ? absint($_POST['reorder_level']) : 0,
            'storage_location' => isset($_POST['storage_location']) ? sanitize_text_field($_POST['storage_location']) : '',
            'has_expiry' => isset($_POST['has_expiry']) && $_POST['has_expiry'] === 'true',
            'is_discontinued' => isset($_POST['is_discontinued']) && $_POST['is_discontinued'] === 'true',
            'notes' => isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : ''
        );

        // التحقق من البيانات
        if (empty($data['sku']) || empty($data['name'])) {
            wp_send_json_error(array('message' => 'الكود والاسم مطلوبان'));
        }

        // التحقق من تكرار SKU
        if (self::sku_exists($data['sku'], $id)) {
            wp_send_json_error(array('message' => 'كود المنتج موجود مسبقاً'));
        }

        if ($id) {
            $result = self::update($id, $data);
            $message = 'تم تحديث المنتج بنجاح';
        } else {
            $result = self::add($data);
            $id = $result;
            $message = 'تم إضافة المنتج بنجاح';
        }

        if ($result) {
            wp_send_json_success(array('message' => $message, 'id' => $id));
        } else {
            wp_send_json_error(array('message' => 'حدث خطأ أثناء الحفظ'));
        }
    }

    public function ajax_delete_product() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_manage_products')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        if (!$id) {
            wp_send_json_error(array('message' => 'معرف المنتج مطلوب'));
        }

        $result = self::delete($id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } elseif ($result) {
            wp_send_json_success(array('message' => 'تم حذف المنتج بنجاح'));
        } else {
            wp_send_json_error(array('message' => 'حدث خطأ أثناء الحذف'));
        }
    }

    public function ajax_search_products() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_products')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 10;

        $products = self::search($term, $limit);

        wp_send_json_success($products);
    }

    public function ajax_get_product_stock() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_products')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $warehouse_id = isset($_POST['warehouse_id']) ? absint($_POST['warehouse_id']) : null;

        if (!$product_id) {
            wp_send_json_error(array('message' => 'معرف المنتج مطلوب'));
        }

        $stock = self::get_stock($product_id, $warehouse_id);
        $total = self::get_total_stock($product_id, $warehouse_id);

        wp_send_json_success(array(
            'stock' => $stock,
            'total' => $total
        ));
    }

    public function ajax_discontinue_product() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_manage_products')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $discontinue = isset($_POST['discontinue']) && $_POST['discontinue'] === 'true';

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف المنتج مطلوب'));
        }

        if ($discontinue) {
            $result = self::discontinue($id);
            $message = 'تم إيقاف المنتج بنجاح';
        } else {
            $result = self::reactivate($id);
            $message = 'تم إعادة تفعيل المنتج بنجاح';
        }

        if ($result) {
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(array('message' => 'حدث خطأ'));
        }
    }

    // === Categories ===

    /**
     * الحصول على جميع الفئات
     */
    public static function get_categories($parent_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_categories';

        if ($parent_id !== null) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE parent_id = %d ORDER BY name ASC",
                $parent_id
            ));
        }

        return $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");
    }

    /**
     * إضافة فئة
     */
    public static function add_category($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_categories';

        $result = $wpdb->insert($table, array(
            'name' => sanitize_text_field($data['name']),
            'parent_id' => isset($data['parent_id']) ? absint($data['parent_id']) : null,
            'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : ''
        ));

        if ($result) {
            return $wpdb->insert_id;
        }
        return false;
    }

    /**
     * تحديث فئة
     */
    public static function update_category($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_categories';

        $update_data = array();

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['parent_id'])) {
            $update_data['parent_id'] = absint($data['parent_id']) ?: null;
        }
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
        }

        return $wpdb->update($table, $update_data, array('id' => $id)) !== false;
    }

    /**
     * حذف فئة
     */
    public static function delete_category($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_categories';
        $products_table = $wpdb->prefix . 'iw_products';

        // التحقق من عدم وجود منتجات في الفئة
        $has_products = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $products_table WHERE category_id = %d",
            $id
        ));

        if ($has_products > 0) {
            return new WP_Error('has_products', 'لا يمكن حذف الفئة لوجود منتجات مرتبطة بها');
        }

        // التحقق من عدم وجود فئات فرعية
        $has_children = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE parent_id = %d",
            $id
        ));

        if ($has_children > 0) {
            return new WP_Error('has_children', 'لا يمكن حذف الفئة لوجود فئات فرعية');
        }

        return $wpdb->delete($table, array('id' => $id));
    }
}

// Initialize
IW_Products::get_instance();
