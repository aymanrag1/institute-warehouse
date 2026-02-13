<?php
if (!defined('ABSPATH')) exit;

class IW_Transactions {

    public static function init() {
        add_action('wp_ajax_iw_add_stock', array(__CLASS__, 'ajax_add_stock'));
        add_action('wp_ajax_iw_get_transactions', array(__CLASS__, 'ajax_get_transactions'));
    }

    /**
     * Add stock (إذن إضافة) - FIFO
     */
    public static function ajax_add_stock() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!IW_Permissions::current_user_can('add_stock', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_transactions';

        $product_id = intval($_POST['product_id']);
        $quantity   = intval($_POST['quantity']);
        $unit_price = floatval($_POST['unit_price']);
        $supplier_id = intval($_POST['supplier_id']);
        $notes      = sanitize_textarea_field($_POST['notes']);

        $wpdb->insert($table, array(
            'transaction_type' => 'add',
            'product_id'       => $product_id,
            'quantity'         => $quantity,
            'unit_price'       => $unit_price,
            'remaining_qty'    => $quantity,
            'supplier_id'      => $supplier_id,
            'notes'            => $notes,
            'created_by'       => get_current_user_id(),
        ));

        IW_Products::update_stock($product_id, $quantity);

        wp_send_json_success(array('message' => 'تمت الإضافة بنجاح'));
    }

    /**
     * Withdraw stock using FIFO (Transaction Safe)
     * Returns total_cost on success, or WP_Error on failure
     */
    public static function withdraw_fifo($product_id, $quantity) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_transactions';

        // Start transaction
        $wpdb->query('START TRANSACTION');

        // Lock rows for update to prevent race conditions
        $batches = $wpdb->get_results($wpdb->prepare(
            "SELECT id, remaining_qty, unit_price FROM $table
             WHERE product_id = %d AND transaction_type = 'add' AND remaining_qty > 0
             ORDER BY created_at ASC
             FOR UPDATE",
            $product_id
        ));

        // Calculate total available quantity
        $total_available = 0;
        foreach ($batches as $batch) {
            $total_available += $batch->remaining_qty;
        }

        // Check if we have enough stock
        if ($total_available < $quantity) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('insufficient_stock', 'الرصيد غير كافي. المتاح: ' . $total_available . '، المطلوب: ' . $quantity);
        }

        $remaining = $quantity;
        $total_cost = 0;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $deduct = min($remaining, $batch->remaining_qty);
            $result = $wpdb->update($table,
                array('remaining_qty' => $batch->remaining_qty - $deduct),
                array('id' => $batch->id),
                array('%d'),
                array('%d')
            );

            if ($result === false) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('update_failed', 'فشل في تحديث الرصيد');
            }

            $total_cost += $deduct * $batch->unit_price;
            $remaining -= $deduct;
        }

        // Update product stock
        IW_Products::update_stock($product_id, -$quantity);

        // Commit transaction
        $wpdb->query('COMMIT');

        return $total_cost;
    }

    public static function ajax_get_transactions() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $type = sanitize_text_field($_POST['type'] ?? '');
        $where = '';
        if ($type) {
            $where = $wpdb->prepare(" AND t.transaction_type = %s", $type);
        }

        $results = $wpdb->get_results(
            "SELECT t.*, p.name as product_name, p.unit as product_unit
             FROM {$prefix}transactions t
             LEFT JOIN {$prefix}products p ON t.product_id = p.id
             WHERE 1=1 $where
             ORDER BY t.created_at DESC LIMIT 200"
        );
        wp_send_json_success($results);
    }
}
