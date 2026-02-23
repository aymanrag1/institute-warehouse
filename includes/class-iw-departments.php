<?php
if (!defined('ABSPATH')) exit;

/**
 * IW_Departments - Integration with RSYI HR System
 *
 * This class now reads departments and employees from RSYI HR System
 * instead of local tables.
 */
class IW_Departments {

    public static function init() {
        // AJAX handlers - now read-only from HR System
        add_action('wp_ajax_iw_get_departments', array(__CLASS__, 'get_departments'));
        add_action('wp_ajax_iw_get_employees', array(__CLASS__, 'get_employees'));
        add_action('wp_ajax_iw_get_employees_by_dept', array(__CLASS__, 'get_employees_by_dept'));

        // Legacy AJAX handlers - disabled since we don't create/edit in HR from warehouse
        add_action('wp_ajax_iw_save_department', array(__CLASS__, 'disabled_action'));
        add_action('wp_ajax_iw_delete_department', array(__CLASS__, 'disabled_action'));
        add_action('wp_ajax_iw_save_employee', array(__CLASS__, 'disabled_action'));
        add_action('wp_ajax_iw_delete_employee', array(__CLASS__, 'disabled_action'));
    }

    /**
     * Disabled action handler - redirects users to HR System
     */
    public static function disabled_action() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        wp_send_json_error(array(
            'message' => 'إدارة الأقسام والموظفين متاحة الآن من نظام الموارد البشرية (RSYI HR System)'
        ));
    }

    /**
     * Get all departments from HR System
     */
    public static function get_departments() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!iw_is_hr_active()) {
            wp_send_json_error(array('message' => 'نظام الموارد البشرية غير مفعل'));
            return;
        }

        $departments = rsyi_hr_get_departments(['status' => 'active']);

        // Transform to expected format
        $result = array();
        foreach ($departments as $dept) {
            $result[] = (object) array(
                'id' => $dept['id'],
                'name' => $dept['name'],
                'description' => $dept['description'] ?? '',
                'code' => $dept['code'] ?? '',
                'manager_name' => $dept['manager_name'] ?? ''
            );
        }

        wp_send_json_success($result);
    }

    /**
     * Get all departments (static method for internal use)
     */
    public static function get_all() {
        if (!iw_is_hr_active()) {
            return array();
        }

        $departments = rsyi_hr_get_departments(['status' => 'active']);

        $result = array();
        foreach ($departments as $dept) {
            $result[] = (object) array(
                'id' => $dept['id'],
                'name' => $dept['name'],
                'description' => $dept['description'] ?? '',
                'code' => $dept['code'] ?? '',
                'manager_name' => $dept['manager_name'] ?? ''
            );
        }

        return $result;
    }

    /**
     * Get single department by ID
     */
    public static function get_by_id($id) {
        if (!iw_is_hr_active()) {
            return null;
        }

        $dept = rsyi_hr_get_department($id);

        if (!$dept) {
            return null;
        }

        return (object) array(
            'id' => $dept['id'],
            'name' => $dept['name'],
            'description' => $dept['description'] ?? '',
            'code' => $dept['code'] ?? '',
            'manager_name' => $dept['manager_name'] ?? ''
        );
    }

    /**
     * Get all employees from HR System
     */
    public static function get_employees() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!iw_is_hr_active()) {
            wp_send_json_error(array('message' => 'نظام الموارد البشرية غير مفعل'));
            return;
        }

        $employees = rsyi_hr_get_employees(['status' => 'active']);

        // Transform to expected format
        $result = array();
        foreach ($employees as $emp) {
            $result[] = (object) array(
                'id' => $emp['id'],
                'name' => $emp['full_name'],
                'department_id' => $emp['department_id'],
                'department_name' => $emp['department_name'] ?? '',
                'position' => $emp['job_title_name'] ?? '',
                'employee_number' => $emp['employee_number'] ?? '',
                'user_id' => $emp['user_id'] ?? null
            );
        }

        wp_send_json_success($result);
    }

    /**
     * Get employees by department from HR System
     */
    public static function get_employees_by_dept() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!iw_is_hr_active()) {
            wp_send_json_error(array('message' => 'نظام الموارد البشرية غير مفعل'));
            return;
        }

        $dept_id = intval($_POST['department_id']);

        $employees = rsyi_hr_department_employees($dept_id);

        // Transform to expected format
        $result = array();
        foreach ($employees as $emp) {
            $result[] = (object) array(
                'id' => $emp['id'],
                'name' => $emp['full_name'],
                'department_id' => $emp['department_id'],
                'position' => $emp['job_title_name'] ?? '',
                'employee_number' => $emp['employee_number'] ?? '',
                'user_id' => $emp['user_id'] ?? null
            );
        }

        wp_send_json_success($result);
    }

    /**
     * Get all employees (static method for internal use)
     */
    public static function get_all_employees() {
        if (!iw_is_hr_active()) {
            return array();
        }

        $employees = rsyi_hr_get_employees(['status' => 'active']);

        $result = array();
        foreach ($employees as $emp) {
            $result[] = (object) array(
                'id' => $emp['id'],
                'name' => $emp['full_name'],
                'department_id' => $emp['department_id'],
                'department_name' => $emp['department_name'] ?? '',
                'position' => $emp['job_title_name'] ?? '',
                'employee_number' => $emp['employee_number'] ?? '',
                'user_id' => $emp['user_id'] ?? null
            );
        }

        return $result;
    }

    /**
     * Get single employee by ID
     */
    public static function get_employee_by_id($id) {
        if (!iw_is_hr_active()) {
            return null;
        }

        $emp = rsyi_hr_get_employee($id);

        if (!$emp) {
            return null;
        }

        return (object) array(
            'id' => $emp['id'],
            'name' => $emp['full_name'],
            'department_id' => $emp['department_id'],
            'department_name' => $emp['department_name'] ?? '',
            'position' => $emp['job_title_name'] ?? '',
            'employee_number' => $emp['employee_number'] ?? '',
            'user_id' => $emp['user_id'] ?? null
        );
    }

    /**
     * Get employee by WordPress user ID
     */
    public static function get_employee_by_user($user_id) {
        if (!iw_is_hr_active()) {
            return null;
        }

        $emp = rsyi_hr_get_employee_by_user($user_id);

        if (!$emp) {
            return null;
        }

        return (object) array(
            'id' => $emp['id'],
            'name' => $emp['full_name'],
            'department_id' => $emp['department_id'],
            'department_name' => $emp['department_name'] ?? '',
            'position' => $emp['job_title_name'] ?? '',
            'employee_number' => $emp['employee_number'] ?? '',
            'user_id' => $emp['user_id'] ?? null
        );
    }

    /**
     * Get current user's department ID
     */
    public static function get_current_user_department() {
        $emp = self::get_employee_by_user(get_current_user_id());
        return $emp ? $emp->department_id : null;
    }

    /**
     * Get job titles from HR System
     */
    public static function get_job_titles() {
        if (!iw_is_hr_active()) {
            return array();
        }

        return rsyi_hr_get_job_titles([]);
    }
}
