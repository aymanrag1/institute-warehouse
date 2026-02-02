<?php
if (!defined('ABSPATH')) exit;

class IW_Opening_Balance {

    public static function init() {
        add_action('wp_ajax_iw_save_opening_balance', array(__CLASS__, 'save'));
        add_action('wp_ajax_iw_get_opening_balances', array(__CLASS__, 'get_all'));
        add_action('wp_ajax_iw_delete_opening_balance', array(__CLASS__, 'delete'));
        add_action('wp_ajax_iw_reset_opening_balance', array(__CLASS__, 'reset_all'));
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

        $updated = 0;
        $added = 0;

        foreach ($items as $item) {
            $product_id = intval($item['product_id']);
            $quantity   = intval($item['quantity']);
            $unit_price = floatval($item['unit_price']);

            // Check if opening balance already exists for this product
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$prefix}opening_balances WHERE product_id = %d ORDER BY id DESC LIMIT 1",
                $product_id
            ));

            if ($existing) {
                // Reverse the old stock change
                IW_Products::update_stock($product_id, -intval($existing->quantity));

                // Delete old opening balance record
                $wpdb->delete($prefix . 'opening_balances', array('id' => $existing->id));

                // Delete old opening balance transaction
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$prefix}transactions WHERE product_id = %d AND notes LIKE %s AND transaction_type = 'add' ORDER BY id DESC LIMIT 1",
                    $product_id, 'رصيد افتتاحي%'
                ));

                $updated++;
            } else {
                $added++;
            }

            // Insert new opening balance
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

        $msg = 'تم حفظ الرصيد الافتتاحي';
        if ($updated > 0) {
            $msg .= ' (تم تحديث ' . $updated . ' أصناف موجودة مسبقاً)';
        }

        wp_send_json_success(array('message' => $msg, 'updated' => $updated, 'added' => $added));
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
        $prefix = $wpdb->prefix . 'iw_';
        $id = intval($_POST['balance_id']);

        // Get the balance record to reverse stock
        $balance = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}opening_balances WHERE id = %d", $id
        ));

        if ($balance) {
            // Reverse the stock change
            IW_Products::update_stock($balance->product_id, -intval($balance->quantity));

            // Delete the corresponding transaction
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$prefix}transactions WHERE product_id = %d AND notes LIKE %s AND transaction_type = 'add' ORDER BY id DESC LIMIT 1",
                $balance->product_id, 'رصيد افتتاحي%'
            ));
        }

        $wpdb->delete($prefix . 'opening_balances', array('id' => $id));
        wp_send_json_success(array('message' => 'تم حذف الرصيد الافتتاحي وتعديل المخزون'));
    }

    /**
     * Reset all opening balances (delete all and reverse stock)
     */
    public static function reset_all() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!IW_Permissions::current_user_can('opening_balance', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        // Get all opening balances
        $balances = $wpdb->get_results("SELECT * FROM {$prefix}opening_balances");

        foreach ($balances as $b) {
            IW_Products::update_stock($b->product_id, -intval($b->quantity));
        }

        // Delete all opening balance records
        $wpdb->query("DELETE FROM {$prefix}opening_balances");

        // Delete all opening balance transactions
        $wpdb->query("DELETE FROM {$prefix}transactions WHERE notes LIKE 'رصيد افتتاحي%' AND transaction_type = 'add'");

        wp_send_json_success(array('message' => 'تم حذف جميع الأرصدة الافتتاحية وإعادة ضبط المخزون'));
    }
}
