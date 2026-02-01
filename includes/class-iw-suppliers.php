<?php
if (!defined('ABSPATH')) exit;

class IW_Suppliers {

    public static function init() {
        add_action('wp_ajax_iw_save_supplier', array(__CLASS__, 'save_supplier'));
        add_action('wp_ajax_iw_delete_supplier', array(__CLASS__, 'delete_supplier'));
        add_action('wp_ajax_iw_get_suppliers', array(__CLASS__, 'get_suppliers'));
    }

    public static function save_supplier() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        if (!IW_Permissions::current_user_can('suppliers', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }
        global $wpdb;
        $table = $wpdb->prefix . 'iw_suppliers';
        $data = array(
            'name'    => sanitize_text_field($_POST['name']),
            'phone'   => sanitize_text_field($_POST['phone']),
            'email'   => sanitize_email($_POST['email']),
            'address' => sanitize_textarea_field($_POST['address']),
        );
        $id = intval($_POST['supplier_id']);
        if ($id > 0) {
            $wpdb->update($table, $data, array('id' => $id));
        } else {
            $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }
        wp_send_json_success(array('id' => $id, 'message' => 'تم الحفظ'));
    }

    public static function delete_supplier() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        if (!IW_Permissions::current_user_can('suppliers', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'iw_suppliers', array('id' => intval($_POST['supplier_id'])));
        wp_send_json_success(array('message' => 'تم الحذف'));
    }

    public static function get_suppliers() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}iw_suppliers ORDER BY name ASC");
        wp_send_json_success($rows);
    }

    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}iw_suppliers ORDER BY name ASC");
    }
}
