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
     * Normalize a raw department row (array or object) to a standard array.
     */
    private static function normalize_dept($dept) {
        if (is_object($dept)) {
            $dept = (array) $dept;
        }
        $name = $dept['name'] ?? $dept['department_name'] ?? $dept['dept_name'] ?? $dept['title'] ?? '';
        return array(
            'id'           => $dept['id'] ?? $dept['dept_id'] ?? null,
            'name'         => $name,
            'description'  => $dept['description'] ?? $dept['dept_description'] ?? '',
            'code'         => $dept['code'] ?? $dept['dept_code'] ?? '',
            'manager_name' => $dept['manager_name'] ?? $dept['manager'] ?? '',
        );
    }

    /**
     * Normalize a raw employee row (array or object) to a standard array.
     */
    private static function normalize_emp($emp) {
        if (is_object($emp)) {
            $emp = (array) $emp;
        }
        $name = $emp['full_name'] ?? $emp['name'] ?? '';
        if (empty($name)) {
            $first = $emp['first_name'] ?? '';
            $last  = $emp['last_name'] ?? '';
            $name  = trim("$first $last");
        }
        return array(
            'id'              => $emp['id'] ?? $emp['employee_id'] ?? null,
            'name'            => $name,
            'department_id'   => $emp['department_id'] ?? $emp['dept_id'] ?? null,
            'department_name' => $emp['department_name'] ?? $emp['dept_name'] ?? '',
            'position'        => $emp['job_title_name'] ?? $emp['job_title'] ?? $emp['position'] ?? $emp['title'] ?? '',
            'employee_number' => $emp['employee_number'] ?? $emp['emp_number'] ?? $emp['emp_no'] ?? '',
            'user_id'         => $emp['user_id'] ?? null,
        );
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

        $departments = iw_hr_get_departments(['status' => 'active']);

        $result = array();
        foreach ($departments as $dept) {
            $result[] = (object) self::normalize_dept($dept);
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

        $departments = iw_hr_get_departments(['status' => 'active']);

        $result = array();
        foreach ($departments as $dept) {
            $result[] = (object) self::normalize_dept($dept);
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

        $dept = iw_hr_get_department($id);

        if (!$dept) {
            return null;
        }

        return (object) self::normalize_dept($dept);
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

        $employees = iw_hr_get_employees(['status' => 'active']);

        $result = array();
        foreach ($employees as $emp) {
            $result[] = (object) self::normalize_emp($emp);
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

        $employees = iw_hr_department_employees($dept_id);

        $result = array();
        foreach ($employees as $emp) {
            $result[] = (object) self::normalize_emp($emp);
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

        $employees = iw_hr_get_employees(['status' => 'active']);

        $result = array();
        foreach ($employees as $emp) {
            $result[] = (object) self::normalize_emp($emp);
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

        $emp = iw_hr_get_employee($id);

        if (!$emp) {
            return null;
        }

        return (object) self::normalize_emp($emp);
    }

    /**
     * Get employee by WordPress user ID
     */
    public static function get_employee_by_user($user_id) {
        if (!iw_is_hr_active()) {
            return null;
        }

        $emp = iw_hr_get_employee_by_user($user_id);

        if (!$emp) {
            return null;
        }

        return (object) self::normalize_emp($emp);
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

        return iw_hr_get_job_titles([]);
    }
}
