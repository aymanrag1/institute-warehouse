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
     * Ensure transactions exist for a product (create from add_order_items if missing)
     */
    public static function ensure_product_transactions($product_id) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        // Get all add order items for this product
        $add_items = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, o.order_number, o.supplier_id, o.created_by, o.created_at as order_date
             FROM {$prefix}add_order_items i
             INNER JOIN {$prefix}add_orders o ON i.order_id = o.id
             WHERE i.product_id = %d
             ORDER BY o.created_at ASC",
            $product_id
        ));

        foreach ($add_items as $item) {
            $order_note = 'إذن إضافة رقم: ' . $item->order_number;

            // Check if transaction already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$prefix}transactions
                 WHERE notes = %s AND product_id = %d AND transaction_type = 'add'",
                $order_note, $product_id
            ));

            if (!$existing) {
                // Create transaction record
                $wpdb->insert($prefix . 'transactions', array(
                    'transaction_type' => 'add',
                    'product_id'       => $product_id,
                    'quantity'         => $item->quantity,
                    'unit_price'       => $item->unit_price,
                    'remaining_qty'    => $item->quantity,
                    'supplier_id'      => $item->supplier_id,
                    'notes'            => $order_note,
                    'created_by'       => $item->created_by,
                    'created_at'       => $item->order_date
                ));
            }
        }

        // Also handle opening balances
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$prefix}opening_balances'");
        if ($table_exists) {
            $balances = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$prefix}opening_balances WHERE product_id = %d",
                $product_id
            ));

            foreach ($balances as $balance) {
                $balance_note = 'رصيد افتتاحي - ' . $balance->balance_date;

                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$prefix}transactions
                     WHERE notes LIKE %s AND product_id = %d AND transaction_type = 'add'",
                    '%رصيد افتتاحي%', $product_id
                ));

                if (!$existing) {
                    $wpdb->insert($prefix . 'transactions', array(
                        'transaction_type' => 'add',
                        'product_id'       => $product_id,
                        'quantity'         => $balance->quantity,
                        'unit_price'       => $balance->unit_price,
                        'remaining_qty'    => $balance->quantity,
                        'notes'            => $balance_note,
                        'created_by'       => $balance->created_by,
                        'created_at'       => $balance->created_at
                    ));
                }
            }
        }
    }

    /**
     * Withdraw stock using FIFO (Transaction Safe)
     * Returns total_cost on success, or WP_Error on failure
     */
    public static function withdraw_fifo($product_id, $quantity) {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_transactions';

        // CRITICAL: First ensure transactions exist for this product
        self::ensure_product_transactions($product_id);

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

        // If still no stock, use real stock calculation as fallback
        if ($total_available == 0) {
            $total_available = IW_Products::get_real_stock($product_id);
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

        // Commit FIFO remaining_qty changes first, then update stock
        $wpdb->query('COMMIT');

        // Update product stock AFTER commit so it's consistent with committed data
        IW_Products::update_stock($product_id, -$quantity);

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
            "SELECT t.*, p.name as product_name, p.unit as product_unit,
                    s.name as supplier_name
             FROM {$prefix}transactions t
             LEFT JOIN {$prefix}products p ON t.product_id = p.id
             LEFT JOIN {$prefix}suppliers s ON t.supplier_id = s.id
             WHERE 1=1 $where
             ORDER BY t.created_at DESC LIMIT 200"
        );
        wp_send_json_success($results);
    }
}
