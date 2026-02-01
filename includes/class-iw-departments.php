<?php
if (!defined('ABSPATH')) exit;

class IW_Departments {

    public static function init() {
        add_action('wp_ajax_iw_save_department', array(__CLASS__, 'save_department'));
        add_action('wp_ajax_iw_delete_department', array(__CLASS__, 'delete_department'));
        add_action('wp_ajax_iw_get_departments', array(__CLASS__, 'get_departments'));
        add_action('wp_ajax_iw_save_employee', array(__CLASS__, 'save_employee'));
        add_action('wp_ajax_iw_delete_employee', array(__CLASS__, 'delete_employee'));
        add_action('wp_ajax_iw_get_employees', array(__CLASS__, 'get_employees'));
        add_action('wp_ajax_iw_get_employees_by_dept', array(__CLASS__, 'get_employees_by_dept'));
    }

    public static function save_department() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        if (!IW_Permissions::current_user_can('departments', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }
        global $wpdb;
        $table = $wpdb->prefix . 'iw_departments';
        $data = array(
            'name'        => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
        );
        $id = intval($_POST['department_id']);
        if ($id > 0) {
            $wpdb->update($table, $data, array('id' => $id));
        } else {
            $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }
        wp_send_json_success(array('id' => $id, 'message' => 'تم الحفظ'));
    }

    public static function delete_department() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        if (!IW_Permissions::current_user_can('departments', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'iw_departments', array('id' => intval($_POST['department_id'])));
        wp_send_json_success(array('message' => 'تم الحذف'));
    }

    public static function get_departments() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}iw_departments ORDER BY name ASC");
        wp_send_json_success($rows);
    }

    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}iw_departments ORDER BY name ASC");
    }

    // Employees
    public static function save_employee() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        if (!IW_Permissions::current_user_can('departments', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }
        global $wpdb;
        $table = $wpdb->prefix . 'iw_employees';
        $data = array(
            'name'          => sanitize_text_field($_POST['name']),
            'department_id' => intval($_POST['department_id']),
            'position'      => sanitize_text_field($_POST['position']),
        );
        $id = intval($_POST['employee_id']);
        if ($id > 0) {
            $wpdb->update($table, $data, array('id' => $id));
        } else {
            $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }
        wp_send_json_success(array('id' => $id, 'message' => 'تم الحفظ'));
    }

    public static function delete_employee() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        if (!IW_Permissions::current_user_can('departments', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'iw_employees', array('id' => intval($_POST['employee_id'])));
        wp_send_json_success(array('message' => 'تم الحذف'));
    }

    public static function get_employees() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT e.*, d.name as department_name FROM {$wpdb->prefix}iw_employees e
             LEFT JOIN {$wpdb->prefix}iw_departments d ON e.department_id = d.id
             ORDER BY e.name ASC"
        );
        wp_send_json_success($rows);
    }

    public static function get_employees_by_dept() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $dept_id = intval($_POST['department_id']);
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}iw_employees WHERE department_id = %d ORDER BY name ASC",
            $dept_id
        ));
        wp_send_json_success($rows);
    }

    public static function get_all_employees() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT e.*, d.name as department_name FROM {$wpdb->prefix}iw_employees e
             LEFT JOIN {$wpdb->prefix}iw_departments d ON e.department_id = d.id
             ORDER BY e.name ASC"
        );
    }
}
