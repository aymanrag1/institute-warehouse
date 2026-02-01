<?php
if (!defined('ABSPATH')) exit;

class IW_Opening_Balance {

    public static function init() {
        add_action('wp_ajax_iw_save_opening_balance', array(__CLASS__, 'save'));
        add_action('wp_ajax_iw_get_opening_balances', array(__CLASS__, 'get_all'));
        add_action('wp_ajax_iw_delete_opening_balance', array(__CLASS__, 'delete'));
    }

    public static function save() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!IW_Permissions::current_user_can('opening_balance', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $items = json_decode(stripslashes($_POST['items']), true);
        $balance_date = sanitize_text_field($_POST['balance_date']);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        if (empty($items)) {
            wp_send_json_error(array('message' => 'يجب إضافة أصناف'));
        }

        foreach ($items as $item) {
            $product_id = intval($item['product_id']);
            $quantity   = intval($item['quantity']);
            $unit_price = floatval($item['unit_price']);

            $wpdb->insert($prefix . 'opening_balances', array(
                'product_id'   => $product_id,
                'quantity'     => $quantity,
                'unit_price'   => $unit_price,
                'balance_date' => $balance_date,
                'notes'        => $notes,
                'created_by'   => get_current_user_id(),
            ));

            // Add to transactions as initial stock
            $wpdb->insert($prefix . 'transactions', array(
                'transaction_type' => 'add',
                'product_id'       => $product_id,
                'quantity'         => $quantity,
                'unit_price'       => $unit_price,
                'remaining_qty'    => $quantity,
                'notes'            => 'رصيد افتتاحي - ' . $balance_date,
                'created_by'       => get_current_user_id(),
            ));

            IW_Products::update_stock($product_id, $quantity);
        }

        wp_send_json_success(array('message' => 'تم حفظ الرصيد الافتتاحي'));
    }

    public static function get_all() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $rows = $wpdb->get_results(
            "SELECT ob.*, p.name as product_name, p.unit as product_unit
             FROM {$prefix}opening_balances ob
             LEFT JOIN {$prefix}products p ON ob.product_id = p.id
             ORDER BY ob.balance_date DESC, ob.id DESC"
        );

        wp_send_json_success($rows);
    }

    public static function delete() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!IW_Permissions::current_user_can('opening_balance', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $id = intval($_POST['balance_id']);
        $wpdb->delete($wpdb->prefix . 'iw_opening_balances', array('id' => $id));
        wp_send_json_success(array('message' => 'تم الحذف'));
    }
}
