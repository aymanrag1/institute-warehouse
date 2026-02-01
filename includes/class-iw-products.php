<?php
if (!defined('ABSPATH')) exit;

class IW_Products {

    public static function init() {
        add_action('wp_ajax_iw_save_product', array(__CLASS__, 'save_product'));
        add_action('wp_ajax_iw_delete_product', array(__CLASS__, 'delete_product'));
        add_action('wp_ajax_iw_get_product', array(__CLASS__, 'get_product'));
        add_action('wp_ajax_iw_get_products_list', array(__CLASS__, 'get_products_list'));
    }

    public static function save_product() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!IW_Permissions::current_user_can('products', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_products';

        $data = array(
            'name'          => sanitize_text_field($_POST['name']),
            'sku'           => sanitize_text_field($_POST['sku']),
            'category'      => sanitize_text_field($_POST['category']),
            'unit'          => sanitize_text_field($_POST['unit']),
            'min_stock'     => intval($_POST['min_stock']),
            'max_stock'     => intval($_POST['max_stock']),
            'price'         => floatval($_POST['price']),
            'description'   => sanitize_textarea_field($_POST['description']),
        );

        $id = intval($_POST['product_id']);

        if ($id > 0) {
            $wpdb->update($table, $data, array('id' => $id));
        } else {
            $data['current_stock'] = 0;
            $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }

        wp_send_json_success(array('id' => $id, 'message' => 'تم الحفظ بنجاح'));
    }

    public static function delete_product() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!IW_Permissions::current_user_can('products', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $id = intval($_POST['product_id']);
        $wpdb->delete($wpdb->prefix . 'iw_products', array('id' => $id));
        wp_send_json_success(array('message' => 'تم الحذف'));
    }

    public static function get_product() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $id = intval($_POST['product_id']);
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}iw_products WHERE id = %d", $id
        ));
        wp_send_json_success($product);
    }

    public static function get_products_list() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $products = $wpdb->get_results("SELECT id, name, sku, current_stock, min_stock, max_stock, unit, price FROM {$wpdb->prefix}iw_products ORDER BY name ASC");
        wp_send_json_success($products);
    }

    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}iw_products ORDER BY name ASC");
    }

    public static function get_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}iw_products WHERE id = %d", $id
        ));
    }

    public static function update_stock($product_id, $quantity_change) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}iw_products SET current_stock = current_stock + %d WHERE id = %d",
            $quantity_change, $product_id
        ));
    }

    public static function get_low_stock_products() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}iw_products WHERE current_stock <= min_stock AND min_stock > 0 ORDER BY name ASC"
        );
    }
}
