<?php
if (!defined('ABSPATH')) exit;

class IW_Add_Orders {

    public static function init() {
        add_action('wp_ajax_iw_create_add_order', array(__CLASS__, 'create_order'));
        add_action('wp_ajax_iw_get_add_orders', array(__CLASS__, 'get_orders'));
        add_action('wp_ajax_iw_get_add_order', array(__CLASS__, 'get_order'));
        add_action('wp_ajax_iw_update_add_order', array(__CLASS__, 'update_order'));
        add_action('wp_ajax_iw_delete_add_order', array(__CLASS__, 'delete_order'));
    }

    /**
     * Generate sequential order number
     */
    private static function generate_order_number() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';
        $year = date('Y');

        $last = $wpdb->get_var(
            "SELECT order_number FROM {$prefix}add_orders
             WHERE order_number LIKE 'ADD-{$year}-%'
             ORDER BY id DESC LIMIT 1"
        );

        if ($last) {
            $parts = explode('-', $last);
            $num = intval(end($parts)) + 1;
        } else {
            $num = 1;
        }

        return 'ADD-' . $year . '-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Create new add order with multiple products
     */
    public static function create_order() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $supplier_id = intval($_POST['supplier_id'] ?? 0);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        $items = json_decode(stripslashes($_POST['items'] ?? '[]'), true);

        if (empty($items)) {
            wp_send_json_error(array('message' => 'يجب إضافة صنف واحد على الأقل'));
        }

        $order_number = self::generate_order_number();
        $total_qty = 0;
        $total_value = 0;

        // Calculate totals
        foreach ($items as $item) {
            $total_qty += intval($item['quantity']);
            $total_value += intval($item['quantity']) * floatval($item['unit_price']);
        }

        // Insert order
        $wpdb->insert($prefix . 'add_orders', array(
            'order_number' => $order_number,
            'supplier_id' => $supplier_id ?: null,
            'notes' => $notes,
            'total_quantity' => $total_qty,
            'total_value' => $total_value,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ));

        $order_id = $wpdb->insert_id;

        // Insert items and update stock
        foreach ($items as $item) {
            $product_id = intval($item['product_id']);
            $quantity = intval($item['quantity']);
            $unit_price = floatval($item['unit_price']);

            // Insert order item
            $wpdb->insert($prefix . 'add_order_items', array(
                'order_id' => $order_id,
                'product_id' => $product_id,
                'quantity' => $quantity,
                'unit_price' => $unit_price
            ));

            // Add stock
            IW_Products::update_stock($product_id, $quantity);

            // Record transaction
            $wpdb->insert($prefix . 'transactions', array(
                'transaction_type' => 'add',
                'product_id' => $product_id,
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'remaining_qty' => $quantity,
                'supplier_id' => $supplier_id ?: null,
                'notes' => 'إذن إضافة رقم: ' . $order_number,
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ));
        }

        wp_send_json_success(array(
            'message' => 'تم إنشاء إذن الإضافة رقم: ' . $order_number,
            'order_id' => $order_id,
            'order_number' => $order_number
        ));
    }

    /**
     * Get all add orders
     */
    public static function get_orders() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $orders = $wpdb->get_results(
            "SELECT o.*, s.name as supplier_name, u.display_name as created_by_name
             FROM {$prefix}add_orders o
             LEFT JOIN {$prefix}suppliers s ON o.supplier_id = s.id
             LEFT JOIN {$wpdb->users} u ON o.created_by = u.ID
             ORDER BY o.id DESC"
        );

        wp_send_json_success($orders);
    }

    /**
     * Get single add order with items
     */
    public static function get_order() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $order_id = intval($_POST['order_id']);

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT o.*, s.name as supplier_name
             FROM {$prefix}add_orders o
             LEFT JOIN {$prefix}suppliers s ON o.supplier_id = s.id
             WHERE o.id = %d", $order_id
        ));

        if (!$order) {
            wp_send_json_error(array('message' => 'الإذن غير موجود'));
        }

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, p.name as product_name, p.unit as product_unit
             FROM {$prefix}add_order_items i
             LEFT JOIN {$prefix}products p ON i.product_id = p.id
             WHERE i.order_id = %d", $order_id
        ));

        wp_send_json_success(array('order' => $order, 'items' => $items));
    }

    /**
     * Update add order (admin only)
     */
    public static function update_order() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية لتعديل الأذونات'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $order_id = intval($_POST['order_id']);
        $supplier_id = intval($_POST['supplier_id'] ?? 0);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        $items = json_decode(stripslashes($_POST['items'] ?? '[]'), true);

        if (empty($items)) {
            wp_send_json_error(array('message' => 'يجب إضافة صنف واحد على الأقل'));
        }

        // Get order details
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}add_orders WHERE id = %d", $order_id
        ));

        if (!$order) {
            wp_send_json_error(array('message' => 'الإذن غير موجود'));
        }

        // Get old items to reverse stock
        $old_items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$prefix}add_order_items WHERE order_id = %d", $order_id
        ));

        // Reverse old stock
        foreach ($old_items as $old) {
            IW_Products::update_stock($old->product_id, -intval($old->quantity));
        }

        // Save consumed quantities per product BEFORE deleting transactions
        // consumed = original_quantity - remaining_qty (ما صُرف فعلاً من هذا الإذن)
        $consumption_map = array();
        $old_transactions = $wpdb->get_results($wpdb->prepare(
            "SELECT product_id, quantity, remaining_qty FROM {$prefix}transactions
             WHERE notes = %s AND transaction_type = 'add'",
            'إذن إضافة رقم: ' . $order->order_number
        ));
        foreach ($old_transactions as $t) {
            $consumed = intval($t->quantity) - intval($t->remaining_qty);
            $consumption_map[intval($t->product_id)] = ($consumption_map[intval($t->product_id)] ?? 0) + $consumed;
        }

        // Delete old transactions for this order
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$prefix}transactions WHERE notes = %s AND transaction_type = 'add'",
            'إذن إضافة رقم: ' . $order->order_number
        ));

        // Delete old items
        $wpdb->delete($prefix . 'add_order_items', array('order_id' => $order_id));

        // Calculate new totals and insert new items
        $total_qty = 0;
        $total_value = 0;

        $effective_supplier = $supplier_id ?: intval($order->supplier_id);

        foreach ($items as $item) {
            $product_id = intval($item['product_id']);
            $quantity = intval($item['quantity']);
            $unit_price = floatval($item['unit_price']);

            $total_qty += $quantity;
            $total_value += $quantity * $unit_price;

            $wpdb->insert($prefix . 'add_order_items', array(
                'order_id' => $order_id,
                'product_id' => $product_id,
                'quantity' => $quantity,
                'unit_price' => $unit_price
            ));

            // Add new stock
            IW_Products::update_stock($product_id, $quantity);

            // Preserve already-consumed quantity so FIFO remaining_qty stays accurate
            $already_consumed = $consumption_map[$product_id] ?? 0;
            $new_remaining_qty = max(0, $quantity - $already_consumed);

            // Create new transaction for FIFO tracking
            $wpdb->insert($prefix . 'transactions', array(
                'transaction_type' => 'add',
                'product_id' => $product_id,
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'remaining_qty' => $new_remaining_qty,
                'supplier_id' => $effective_supplier ?: null,
                'notes' => 'إذن إضافة رقم: ' . $order->order_number,
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ));
        }

        // Update order
        $wpdb->update($prefix . 'add_orders', array(
            'supplier_id' => $supplier_id ?: null,
            'notes' => $notes,
            'total_quantity' => $total_qty,
            'total_value' => $total_value
        ), array('id' => $order_id));

        wp_send_json_success(array('message' => 'تم تحديث الإذن بنجاح'));
    }

    /**
     * Delete add order (admin only)
     */
    public static function delete_order() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية لحذف الأذونات'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $order_id = intval($_POST['order_id']);

        // Get order details
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}add_orders WHERE id = %d", $order_id
        ));

        if (!$order) {
            wp_send_json_error(array('message' => 'الإذن غير موجود'));
        }

        // Get items to reverse stock
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$prefix}add_order_items WHERE order_id = %d", $order_id
        ));

        // Reverse stock
        foreach ($items as $item) {
            IW_Products::update_stock($item->product_id, -intval($item->quantity));
        }

        // Delete related transactions
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$prefix}transactions WHERE notes = %s AND transaction_type = 'add'",
            'إذن إضافة رقم: ' . $order->order_number
        ));

        // Delete items and order
        $wpdb->delete($prefix . 'add_order_items', array('order_id' => $order_id));
        $wpdb->delete($prefix . 'add_orders', array('id' => $order_id));

        wp_send_json_success(array('message' => 'تم حذف الإذن بنجاح'));
    }
}
