<?php
if (!defined('ABSPATH')) exit;

class IW_Suppliers {

    public static function init() {
        add_action('wp_ajax_iw_save_supplier', array(__CLASS__, 'save_supplier'));
        add_action('wp_ajax_iw_create_supplier', array(__CLASS__, 'create_supplier'));
        add_action('wp_ajax_iw_delete_supplier', array(__CLASS__, 'delete_supplier'));
        add_action('wp_ajax_iw_get_suppliers', array(__CLASS__, 'get_suppliers'));
        add_action('wp_ajax_iw_get_supplier', array(__CLASS__, 'get_supplier'));
    }

    /**
     * Generate sequential supplier number
     */
    private static function generate_supplier_number() {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_suppliers';
        $last = $wpdb->get_var("SELECT supplier_number FROM {$table} ORDER BY id DESC LIMIT 1");

        if ($last && preg_match('/SUP-(\d+)/', $last, $matches)) {
            $num = intval($matches[1]) + 1;
        } else {
            $num = 1;
        }

        return 'SUP-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    public static function save_supplier() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        if (!IW_Permissions::current_user_can('suppliers', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }
        global $wpdb;
        $table = $wpdb->prefix . 'iw_suppliers';

        $data = array(
            'name'                  => sanitize_text_field($_POST['name']),
            'address'               => sanitize_textarea_field($_POST['address'] ?? ''),
            'phone_landline'        => sanitize_text_field($_POST['phone_landline'] ?? ''),
            'phone_mobile'          => sanitize_text_field($_POST['phone_mobile'] ?? ''),
            'email'                 => sanitize_email($_POST['email'] ?? ''),
            'contact_person'        => sanitize_text_field($_POST['contact_person'] ?? ''),
            'tax_card_number'       => sanitize_text_field($_POST['tax_card_number'] ?? ''),
            'tax_card_file'         => esc_url_raw($_POST['tax_card_file'] ?? ''),
            'commercial_reg_number' => sanitize_text_field($_POST['commercial_reg_number'] ?? ''),
            'commercial_reg_file'   => esc_url_raw($_POST['commercial_reg_file'] ?? ''),
            'specialty'             => sanitize_text_field($_POST['specialty'] ?? ''),
        );

        $id = intval($_POST['supplier_id'] ?? 0);
        if ($id > 0) {
            $wpdb->update($table, $data, array('id' => $id));
        } else {
            $data['supplier_number'] = self::generate_supplier_number();
            $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }
        wp_send_json_success(array('id' => $id, 'message' => 'تم الحفظ'));
    }

    /**
     * Quick create supplier (from add-stock page)
     */
    public static function create_supplier() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'iw_suppliers';

        $data = array(
            'supplier_number' => self::generate_supplier_number(),
            'name'            => sanitize_text_field($_POST['name']),
            'phone_mobile'    => sanitize_text_field($_POST['phone_mobile'] ?? $_POST['phone'] ?? ''),
            'email'           => sanitize_email($_POST['email'] ?? ''),
            'address'         => sanitize_textarea_field($_POST['address'] ?? ''),
        );

        $wpdb->insert($table, $data);
        $id = $wpdb->insert_id;

        wp_send_json_success(array('id' => $id, 'message' => 'تم إضافة المورد'));
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

    public static function get_supplier() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $id = intval($_POST['supplier_id']);
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}iw_suppliers WHERE id = %d", $id
        ));
        wp_send_json_success($row);
    }

    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}iw_suppliers ORDER BY name ASC");
    }
}
