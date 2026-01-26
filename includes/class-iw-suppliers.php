<?php
/**
 * Suppliers Management Class
 *
 * إدارة الموردين
 */

if (!defined('ABSPATH')) {
    exit;
}

class IW_Suppliers {

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
        add_action('wp_ajax_iw_get_suppliers', array($this, 'ajax_get_suppliers'));
        add_action('wp_ajax_iw_get_supplier', array($this, 'ajax_get_supplier'));
        add_action('wp_ajax_iw_save_supplier', array($this, 'ajax_save_supplier'));
        add_action('wp_ajax_iw_delete_supplier', array($this, 'ajax_delete_supplier'));
        add_action('wp_ajax_iw_search_suppliers', array($this, 'ajax_search_suppliers'));
    }

    /**
     * إضافة مورد جديد
     */
    public static function add($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_suppliers';

        $result = $wpdb->insert($table, array(
            'name' => sanitize_text_field($data['name']),
            'contact_person' => isset($data['contact_person']) ? sanitize_text_field($data['contact_person']) : '',
            'phone' => isset($data['phone']) ? sanitize_text_field($data['phone']) : '',
            'email' => isset($data['email']) ? sanitize_email($data['email']) : '',
            'address' => isset($data['address']) ? sanitize_textarea_field($data['address']) : '',
            'tax_number' => isset($data['tax_number']) ? sanitize_text_field($data['tax_number']) : '',
            'notes' => isset($data['notes']) ? sanitize_textarea_field($data['notes']) : '',
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'active'
        ));

        if ($result) {
            $id = $wpdb->insert_id;
            IW_Database::log_activity('create', 'supplier', $id, $data);
            return $id;
        }

        return false;
    }

    /**
     * تحديث مورد
     */
    public static function update($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_suppliers';

        $update_data = array();

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['contact_person'])) {
            $update_data['contact_person'] = sanitize_text_field($data['contact_person']);
        }
        if (isset($data['phone'])) {
            $update_data['phone'] = sanitize_text_field($data['phone']);
        }
        if (isset($data['email'])) {
            $update_data['email'] = sanitize_email($data['email']);
        }
        if (isset($data['address'])) {
            $update_data['address'] = sanitize_textarea_field($data['address']);
        }
        if (isset($data['tax_number'])) {
            $update_data['tax_number'] = sanitize_text_field($data['tax_number']);
        }
        if (isset($data['notes'])) {
            $update_data['notes'] = sanitize_textarea_field($data['notes']);
        }
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }

        if (empty($update_data)) {
            return false;
        }

        $result = $wpdb->update($table, $update_data, array('id' => $id));

        if ($result !== false) {
            IW_Database::log_activity('update', 'supplier', $id, $update_data);
            return true;
        }

        return false;
    }

    /**
     * حذف مورد
     */
    public static function delete($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_suppliers';
        $permits_table = $wpdb->prefix . 'iw_add_permits';

        // التحقق من عدم وجود إذونات إضافة من المورد
        $has_permits = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $permits_table WHERE supplier_id = %d",
            $id
        ));

        if ($has_permits > 0) {
            return new WP_Error('has_permits', 'لا يمكن حذف المورد لوجود إذونات إضافة مرتبطة به');
        }

        $result = $wpdb->delete($table, array('id' => $id));

        if ($result) {
            IW_Database::log_activity('delete', 'supplier', $id);
            return true;
        }

        return false;
    }

    /**
     * الحصول على مورد
     */
    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_suppliers';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
    }

    /**
     * الحصول على جميع الموردين
     */
    public static function get_all($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_suppliers';

        $defaults = array(
            'status' => '',
            'search' => '',
            'per_page' => 50,
            'page' => 1
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if (!empty($args['search'])) {
            $where[] = '(name LIKE %s OR contact_person LIKE %s OR phone LIKE %s)';
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $search;
            $values[] = $search;
            $values[] = $search;
        }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];

        $sql = "SELECT * FROM $table
                WHERE $where_clause
                ORDER BY name ASC
                LIMIT %d OFFSET %d";

        $values[] = $args['per_page'];
        $values[] = $offset;

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * البحث عن موردين
     */
    public static function search($term, $limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_suppliers';

        $search = '%' . $wpdb->esc_like($term) . '%';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
             WHERE status = 'active' AND (name LIKE %s OR contact_person LIKE %s)
             ORDER BY name ASC
             LIMIT %d",
            $search,
            $search,
            $limit
        ));
    }

    /**
     * الحصول على إحصائيات المورد
     */
    public static function get_statistics($supplier_id) {
        global $wpdb;
        $permits_table = $wpdb->prefix . 'iw_add_permits';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_permits,
                COALESCE(SUM(total_amount), 0) as total_amount,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_permits
             FROM $permits_table
             WHERE supplier_id = %d",
            $supplier_id
        ));
    }

    // ==================== AJAX Handlers ====================

    public function ajax_get_suppliers() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_warehouse')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $args = array(
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '',
            'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
            'per_page' => isset($_POST['per_page']) ? absint($_POST['per_page']) : 50,
            'page' => isset($_POST['page']) ? absint($_POST['page']) : 1
        );

        $suppliers = self::get_all($args);

        wp_send_json_success($suppliers);
    }

    public function ajax_get_supplier() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_warehouse')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف المورد مطلوب'));
        }

        $supplier = self::get($id);

        if (!$supplier) {
            wp_send_json_error(array('message' => 'المورد غير موجود'));
        }

        $supplier->statistics = self::get_statistics($id);

        wp_send_json_success($supplier);
    }

    public function ajax_save_supplier() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_manage_suppliers')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        $data = array(
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'contact_person' => isset($_POST['contact_person']) ? sanitize_text_field($_POST['contact_person']) : '',
            'phone' => isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '',
            'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
            'address' => isset($_POST['address']) ? sanitize_textarea_field($_POST['address']) : '',
            'tax_number' => isset($_POST['tax_number']) ? sanitize_text_field($_POST['tax_number']) : '',
            'notes' => isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '',
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active'
        );

        if (empty($data['name'])) {
            wp_send_json_error(array('message' => 'اسم المورد مطلوب'));
        }

        if ($id) {
            $result = self::update($id, $data);
            $message = 'تم تحديث المورد بنجاح';
        } else {
            $result = self::add($data);
            $id = $result;
            $message = 'تم إضافة المورد بنجاح';
        }

        if ($result) {
            wp_send_json_success(array('message' => $message, 'id' => $id));
        } else {
            wp_send_json_error(array('message' => 'حدث خطأ أثناء الحفظ'));
        }
    }

    public function ajax_delete_supplier() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_manage_suppliers')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف المورد مطلوب'));
        }

        $result = self::delete($id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } elseif ($result) {
            wp_send_json_success(array('message' => 'تم حذف المورد بنجاح'));
        } else {
            wp_send_json_error(array('message' => 'حدث خطأ أثناء الحذف'));
        }
    }

    public function ajax_search_suppliers() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_warehouse')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 10;

        $suppliers = self::search($term, $limit);

        wp_send_json_success($suppliers);
    }
}

// Initialize
IW_Suppliers::get_instance();
