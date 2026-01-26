<?php
/**
 * Departments and Employees Management Class
 *
 * إدارة الأقسام والموظفين
 */

if (!defined('ABSPATH')) {
    exit;
}

class IW_Departments {

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
        // الأقسام
        add_action('wp_ajax_iw_get_departments', array($this, 'ajax_get_departments'));
        add_action('wp_ajax_iw_get_department', array($this, 'ajax_get_department'));
        add_action('wp_ajax_iw_save_department', array($this, 'ajax_save_department'));
        add_action('wp_ajax_iw_delete_department', array($this, 'ajax_delete_department'));

        // الموظفين
        add_action('wp_ajax_iw_get_employees', array($this, 'ajax_get_employees'));
        add_action('wp_ajax_iw_get_employee', array($this, 'ajax_get_employee'));
        add_action('wp_ajax_iw_save_employee', array($this, 'ajax_save_employee'));
        add_action('wp_ajax_iw_delete_employee', array($this, 'ajax_delete_employee'));
        add_action('wp_ajax_iw_search_employees', array($this, 'ajax_search_employees'));
        add_action('wp_ajax_iw_get_department_employees', array($this, 'ajax_get_department_employees'));

        // المخازن
        add_action('wp_ajax_iw_get_warehouses', array($this, 'ajax_get_warehouses'));
        add_action('wp_ajax_iw_get_warehouse', array($this, 'ajax_get_warehouse'));
        add_action('wp_ajax_iw_save_warehouse', array($this, 'ajax_save_warehouse'));
        add_action('wp_ajax_iw_delete_warehouse', array($this, 'ajax_delete_warehouse'));
    }

    // ==================== الأقسام ====================

    /**
     * إضافة قسم جديد
     */
    public static function add_department($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_departments';

        $result = $wpdb->insert($table, array(
            'name' => sanitize_text_field($data['name']),
            'code' => isset($data['code']) ? sanitize_text_field($data['code']) : '',
            'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
            'parent_id' => isset($data['parent_id']) && $data['parent_id'] ? absint($data['parent_id']) : null,
            'manager_id' => isset($data['manager_id']) && $data['manager_id'] ? absint($data['manager_id']) : null,
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'active'
        ));

        if ($result) {
            $id = $wpdb->insert_id;
            IW_Database::log_activity('create', 'department', $id, $data);
            return $id;
        }

        return false;
    }

    /**
     * تحديث قسم
     */
    public static function update_department($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_departments';

        $update_data = array();

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['code'])) {
            $update_data['code'] = sanitize_text_field($data['code']);
        }
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
        }
        if (isset($data['parent_id'])) {
            $update_data['parent_id'] = $data['parent_id'] ? absint($data['parent_id']) : null;
        }
        if (isset($data['manager_id'])) {
            $update_data['manager_id'] = $data['manager_id'] ? absint($data['manager_id']) : null;
        }
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }

        if (empty($update_data)) {
            return false;
        }

        $result = $wpdb->update($table, $update_data, array('id' => $id));

        if ($result !== false) {
            IW_Database::log_activity('update', 'department', $id, $update_data);
            return true;
        }

        return false;
    }

    /**
     * حذف قسم
     */
    public static function delete_department($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_departments';
        $employees_table = $wpdb->prefix . 'iw_employees';

        // التحقق من عدم وجود موظفين في القسم
        $has_employees = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $employees_table WHERE department_id = %d",
            $id
        ));

        if ($has_employees > 0) {
            return new WP_Error('has_employees', 'لا يمكن حذف القسم لوجود موظفين مرتبطين به');
        }

        // التحقق من عدم وجود أقسام فرعية
        $has_children = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE parent_id = %d",
            $id
        ));

        if ($has_children > 0) {
            return new WP_Error('has_children', 'لا يمكن حذف القسم لوجود أقسام فرعية');
        }

        $result = $wpdb->delete($table, array('id' => $id));

        if ($result) {
            IW_Database::log_activity('delete', 'department', $id);
            return true;
        }

        return false;
    }

    /**
     * الحصول على قسم
     */
    public static function get_department($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_departments';
        $employees_table = $wpdb->prefix . 'iw_employees';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT d.*, p.name as parent_name, e.name as manager_name
             FROM $table d
             LEFT JOIN $table p ON d.parent_id = p.id
             LEFT JOIN $employees_table e ON d.manager_id = e.id
             WHERE d.id = %d",
            $id
        ));
    }

    /**
     * الحصول على جميع الأقسام
     */
    public static function get_departments($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_departments';
        $employees_table = $wpdb->prefix . 'iw_employees';

        $defaults = array(
            'parent_id' => null,
            'status' => '',
            'include_count' => true
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if ($args['parent_id'] !== null) {
            if ($args['parent_id'] === 0) {
                $where[] = 'd.parent_id IS NULL';
            } else {
                $where[] = 'd.parent_id = %d';
                $values[] = $args['parent_id'];
            }
        }

        if (!empty($args['status'])) {
            $where[] = 'd.status = %s';
            $values[] = $args['status'];
        }

        $where_clause = implode(' AND ', $where);

        $sql = "SELECT d.*, p.name as parent_name";

        if ($args['include_count']) {
            $sql .= ", (SELECT COUNT(*) FROM $employees_table WHERE department_id = d.id) as employee_count";
        }

        $sql .= " FROM $table d
                  LEFT JOIN $table p ON d.parent_id = p.id
                  WHERE $where_clause
                  ORDER BY d.name ASC";

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * الحصول على الأقسام كشجرة
     */
    public static function get_departments_tree($parent_id = null) {
        $departments = self::get_departments(array('parent_id' => $parent_id !== null ? $parent_id : 0));

        foreach ($departments as &$dept) {
            $dept->children = self::get_departments_tree($dept->id);
        }

        return $departments;
    }

    // ==================== الموظفين ====================

    /**
     * إضافة موظف جديد
     */
    public static function add_employee($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_employees';

        $result = $wpdb->insert($table, array(
            'user_id' => isset($data['user_id']) && $data['user_id'] ? absint($data['user_id']) : null,
            'employee_number' => isset($data['employee_number']) ? sanitize_text_field($data['employee_number']) : '',
            'name' => sanitize_text_field($data['name']),
            'department_id' => isset($data['department_id']) && $data['department_id'] ? absint($data['department_id']) : null,
            'position' => isset($data['position']) ? sanitize_text_field($data['position']) : '',
            'phone' => isset($data['phone']) ? sanitize_text_field($data['phone']) : '',
            'email' => isset($data['email']) ? sanitize_email($data['email']) : '',
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'active'
        ));

        if ($result) {
            $id = $wpdb->insert_id;
            IW_Database::log_activity('create', 'employee', $id, $data);
            return $id;
        }

        return false;
    }

    /**
     * تحديث موظف
     */
    public static function update_employee($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_employees';

        $update_data = array();

        if (isset($data['user_id'])) {
            $update_data['user_id'] = $data['user_id'] ? absint($data['user_id']) : null;
        }
        if (isset($data['employee_number'])) {
            $update_data['employee_number'] = sanitize_text_field($data['employee_number']);
        }
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['department_id'])) {
            $update_data['department_id'] = $data['department_id'] ? absint($data['department_id']) : null;
        }
        if (isset($data['position'])) {
            $update_data['position'] = sanitize_text_field($data['position']);
        }
        if (isset($data['phone'])) {
            $update_data['phone'] = sanitize_text_field($data['phone']);
        }
        if (isset($data['email'])) {
            $update_data['email'] = sanitize_email($data['email']);
        }
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }

        if (empty($update_data)) {
            return false;
        }

        $result = $wpdb->update($table, $update_data, array('id' => $id));

        if ($result !== false) {
            IW_Database::log_activity('update', 'employee', $id, $update_data);
            return true;
        }

        return false;
    }

    /**
     * حذف موظف
     */
    public static function delete_employee($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_employees';
        $permits_table = $wpdb->prefix . 'iw_withdraw_permits';

        // التحقق من عدم وجود إذونات صرف للموظف
        $has_permits = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $permits_table WHERE employee_id = %d",
            $id
        ));

        if ($has_permits > 0) {
            return new WP_Error('has_permits', 'لا يمكن حذف الموظف لوجود إذونات صرف مرتبطة به');
        }

        $result = $wpdb->delete($table, array('id' => $id));

        if ($result) {
            IW_Database::log_activity('delete', 'employee', $id);
            return true;
        }

        return false;
    }

    /**
     * الحصول على موظف
     */
    public static function get_employee($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_employees';
        $departments_table = $wpdb->prefix . 'iw_departments';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, d.name as department_name
             FROM $table e
             LEFT JOIN $departments_table d ON e.department_id = d.id
             WHERE e.id = %d",
            $id
        ));
    }

    /**
     * الحصول على جميع الموظفين
     */
    public static function get_employees($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_employees';
        $departments_table = $wpdb->prefix . 'iw_departments';

        $defaults = array(
            'department_id' => 0,
            'status' => '',
            'search' => '',
            'per_page' => 50,
            'page' => 1
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['department_id'])) {
            $where[] = 'e.department_id = %d';
            $values[] = $args['department_id'];
        }

        if (!empty($args['status'])) {
            $where[] = 'e.status = %s';
            $values[] = $args['status'];
        }

        if (!empty($args['search'])) {
            $where[] = '(e.name LIKE %s OR e.employee_number LIKE %s OR e.email LIKE %s)';
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $search;
            $values[] = $search;
            $values[] = $search;
        }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];

        $sql = "SELECT e.*, d.name as department_name
                FROM $table e
                LEFT JOIN $departments_table d ON e.department_id = d.id
                WHERE $where_clause
                ORDER BY e.name ASC
                LIMIT %d OFFSET %d";

        $values[] = $args['per_page'];
        $values[] = $offset;

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * البحث عن موظفين
     */
    public static function search_employees($term, $department_id = null, $limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_employees';
        $departments_table = $wpdb->prefix . 'iw_departments';

        $search = '%' . $wpdb->esc_like($term) . '%';

        $where = "e.status = 'active' AND (e.name LIKE %s OR e.employee_number LIKE %s)";
        $values = array($search, $search);

        if ($department_id) {
            $where .= " AND e.department_id = %d";
            $values[] = $department_id;
        }

        $values[] = $limit;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, d.name as department_name
             FROM $table e
             LEFT JOIN $departments_table d ON e.department_id = d.id
             WHERE $where
             ORDER BY e.name ASC
             LIMIT %d",
            $values
        ));
    }

    /**
     * الحصول على موظفي قسم معين
     */
    public static function get_department_employees($department_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_employees';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE department_id = %d AND status = 'active' ORDER BY name ASC",
            $department_id
        ));
    }

    // ==================== المخازن ====================

    /**
     * إضافة مخزن جديد
     */
    public static function add_warehouse($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_warehouses';

        $result = $wpdb->insert($table, array(
            'name' => sanitize_text_field($data['name']),
            'location' => isset($data['location']) ? sanitize_text_field($data['location']) : '',
            'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
            'manager_id' => isset($data['manager_id']) && $data['manager_id'] ? absint($data['manager_id']) : null,
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'active'
        ));

        if ($result) {
            $id = $wpdb->insert_id;
            IW_Database::log_activity('create', 'warehouse', $id, $data);
            return $id;
        }

        return false;
    }

    /**
     * تحديث مخزن
     */
    public static function update_warehouse($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_warehouses';

        $update_data = array();

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['location'])) {
            $update_data['location'] = sanitize_text_field($data['location']);
        }
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
        }
        if (isset($data['manager_id'])) {
            $update_data['manager_id'] = $data['manager_id'] ? absint($data['manager_id']) : null;
        }
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }

        if (empty($update_data)) {
            return false;
        }

        $result = $wpdb->update($table, $update_data, array('id' => $id));

        if ($result !== false) {
            IW_Database::log_activity('update', 'warehouse', $id, $update_data);
            return true;
        }

        return false;
    }

    /**
     * حذف مخزن
     */
    public static function delete_warehouse($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_warehouses';
        $stock_table = $wpdb->prefix . 'iw_stock';

        // التحقق من عدم وجود مخزون في المخزن
        $has_stock = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $stock_table WHERE warehouse_id = %d AND remaining_quantity > 0",
            $id
        ));

        if ($has_stock > 0) {
            return new WP_Error('has_stock', 'لا يمكن حذف المخزن لوجود أصناف فيه');
        }

        $result = $wpdb->delete($table, array('id' => $id));

        if ($result) {
            IW_Database::log_activity('delete', 'warehouse', $id);
            return true;
        }

        return false;
    }

    /**
     * الحصول على مخزن
     */
    public static function get_warehouse($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_warehouses';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
    }

    /**
     * الحصول على جميع المخازن
     */
    public static function get_warehouses($status = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_warehouses';

        $where = '1=1';
        $values = array();

        if (!empty($status)) {
            $where .= ' AND status = %s';
            $values[] = $status;
        }

        $sql = "SELECT * FROM $table WHERE $where ORDER BY name ASC";

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    // ==================== AJAX Handlers - الأقسام ====================

    public function ajax_get_departments() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_warehouse')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $args = array(
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : ''
        );

        $departments = self::get_departments($args);

        wp_send_json_success($departments);
    }

    public function ajax_get_department() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_warehouse')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف القسم مطلوب'));
        }

        $department = self::get_department($id);

        if (!$department) {
            wp_send_json_error(array('message' => 'القسم غير موجود'));
        }

        wp_send_json_success($department);
    }

    public function ajax_save_department() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_manage_departments')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        $data = array(
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'code' => isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
            'parent_id' => isset($_POST['parent_id']) ? absint($_POST['parent_id']) : 0,
            'manager_id' => isset($_POST['manager_id']) ? absint($_POST['manager_id']) : 0,
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active'
        );

        if (empty($data['name'])) {
            wp_send_json_error(array('message' => 'اسم القسم مطلوب'));
        }

        if ($id) {
            $result = self::update_department($id, $data);
            $message = 'تم تحديث القسم بنجاح';
        } else {
            $result = self::add_department($data);
            $id = $result;
            $message = 'تم إضافة القسم بنجاح';
        }

        if ($result) {
            wp_send_json_success(array('message' => $message, 'id' => $id));
        } else {
            wp_send_json_error(array('message' => 'حدث خطأ أثناء الحفظ'));
        }
    }

    public function ajax_delete_department() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_manage_departments')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف القسم مطلوب'));
        }

        $result = self::delete_department($id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } elseif ($result) {
            wp_send_json_success(array('message' => 'تم حذف القسم بنجاح'));
        } else {
            wp_send_json_error(array('message' => 'حدث خطأ أثناء الحذف'));
        }
    }

    // ==================== AJAX Handlers - الموظفين ====================

    public function ajax_get_employees() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_warehouse')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $args = array(
            'department_id' => isset($_POST['department_id']) ? absint($_POST['department_id']) : 0,
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '',
            'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
            'per_page' => isset($_POST['per_page']) ? absint($_POST['per_page']) : 50,
            'page' => isset($_POST['page']) ? absint($_POST['page']) : 1
        );

        $employees = self::get_employees($args);

        wp_send_json_success($employees);
    }

    public function ajax_get_employee() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_warehouse')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف الموظف مطلوب'));
        }

        $employee = self::get_employee($id);

        if (!$employee) {
            wp_send_json_error(array('message' => 'الموظف غير موجود'));
        }

        wp_send_json_success($employee);
    }

    public function ajax_save_employee() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_manage_departments')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        $data = array(
            'user_id' => isset($_POST['user_id']) ? absint($_POST['user_id']) : 0,
            'employee_number' => isset($_POST['employee_number']) ? sanitize_text_field($_POST['employee_number']) : '',
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'department_id' => isset($_POST['department_id']) ? absint($_POST['department_id']) : 0,
            'position' => isset($_POST['position']) ? sanitize_text_field($_POST['position']) : '',
            'phone' => isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '',
            'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active'
        );

        if (empty($data['name'])) {
            wp_send_json_error(array('message' => 'اسم الموظف مطلوب'));
        }

        if ($id) {
            $result = self::update_employee($id, $data);
            $message = 'تم تحديث بيانات الموظف بنجاح';
        } else {
            $result = self::add_employee($data);
            $id = $result;
            $message = 'تم إضافة الموظف بنجاح';
        }

        if ($result) {
            wp_send_json_success(array('message' => $message, 'id' => $id));
        } else {
            wp_send_json_error(array('message' => 'حدث خطأ أثناء الحفظ'));
        }
    }

    public function ajax_delete_employee() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_manage_departments')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف الموظف مطلوب'));
        }

        $result = self::delete_employee($id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } elseif ($result) {
            wp_send_json_success(array('message' => 'تم حذف الموظف بنجاح'));
        } else {
            wp_send_json_error(array('message' => 'حدث خطأ أثناء الحذف'));
        }
    }

    public function ajax_search_employees() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_warehouse')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';
        $department_id = isset($_POST['department_id']) ? absint($_POST['department_id']) : null;
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 10;

        $employees = self::search_employees($term, $department_id, $limit);

        wp_send_json_success($employees);
    }

    public function ajax_get_department_employees() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_warehouse')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $department_id = isset($_POST['department_id']) ? absint($_POST['department_id']) : 0;

        if (!$department_id) {
            wp_send_json_error(array('message' => 'معرف القسم مطلوب'));
        }

        $employees = self::get_department_employees($department_id);

        wp_send_json_success($employees);
    }

    // ==================== AJAX Handlers - المخازن ====================

    public function ajax_get_warehouses() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_warehouse')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        $warehouses = self::get_warehouses($status);

        wp_send_json_success($warehouses);
    }

    public function ajax_get_warehouse() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_warehouse')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف المخزن مطلوب'));
        }

        $warehouse = self::get_warehouse($id);

        if (!$warehouse) {
            wp_send_json_error(array('message' => 'المخزن غير موجود'));
        }

        wp_send_json_success($warehouse);
    }

    public function ajax_save_warehouse() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_manage_warehouses')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        $data = array(
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'location' => isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
            'manager_id' => isset($_POST['manager_id']) ? absint($_POST['manager_id']) : 0,
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active'
        );

        if (empty($data['name'])) {
            wp_send_json_error(array('message' => 'اسم المخزن مطلوب'));
        }

        if ($id) {
            $result = self::update_warehouse($id, $data);
            $message = 'تم تحديث المخزن بنجاح';
        } else {
            $result = self::add_warehouse($data);
            $id = $result;
            $message = 'تم إضافة المخزن بنجاح';
        }

        if ($result) {
            wp_send_json_success(array('message' => $message, 'id' => $id));
        } else {
            wp_send_json_error(array('message' => 'حدث خطأ أثناء الحفظ'));
        }
    }

    public function ajax_delete_warehouse() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_manage_warehouses')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف المخزن مطلوب'));
        }

        $result = self::delete_warehouse($id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } elseif ($result) {
            wp_send_json_success(array('message' => 'تم حذف المخزن بنجاح'));
        } else {
            wp_send_json_error(array('message' => 'حدث خطأ أثناء الحذف'));
        }
    }
}

// Initialize
IW_Departments::get_instance();
