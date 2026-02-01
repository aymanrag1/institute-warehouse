<?php
if (!defined('ABSPATH')) exit;

class IW_Withdrawal_Orders {

    public static function init() {
        add_action('wp_ajax_iw_create_withdrawal_order', array(__CLASS__, 'create_order'));
        add_action('wp_ajax_iw_get_withdrawal_orders', array(__CLASS__, 'get_orders'));
        add_action('wp_ajax_iw_get_withdrawal_order', array(__CLASS__, 'get_order'));
        add_action('wp_ajax_iw_approve_withdrawal_order', array(__CLASS__, 'approve_order'));
        add_action('wp_ajax_iw_reject_withdrawal_order', array(__CLASS__, 'reject_order'));
        add_action('wp_ajax_iw_update_withdrawal_order', array(__CLASS__, 'update_order'));
        add_action('wp_ajax_iw_complete_withdrawal_order', array(__CLASS__, 'complete_order'));
    }

    /**
     * Generate order number
     */
    private static function generate_order_number() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}iw_withdrawal_orders") + 1;
        return 'WD-' . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Step 1: Warehouse clerk creates withdrawal order (pending)
     */
    public static function create_order() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!IW_Permissions::current_user_can('withdraw_stock', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $order_number  = self::generate_order_number();
        $department_id = intval($_POST['department_id']);
        $employee_id   = intval($_POST['employee_id']);
        $notes         = sanitize_textarea_field($_POST['notes'] ?? '');
        $items         = json_decode(stripslashes($_POST['items']), true);

        if (empty($items)) {
            wp_send_json_error(array('message' => 'يجب إضافة أصناف'));
        }

        $wpdb->insert($prefix . 'withdrawal_orders', array(
            'order_number'  => $order_number,
            'department_id' => $department_id,
            'employee_id'   => $employee_id,
            'status'        => 'pending',
            'notes'         => $notes,
            'created_by'    => get_current_user_id(),
        ));

        $order_id = $wpdb->insert_id;

        foreach ($items as $item) {
            $product = IW_Products::get_by_id(intval($item['product_id']));
            $wpdb->insert($prefix . 'withdrawal_order_items', array(
                'order_id'   => $order_id,
                'product_id' => intval($item['product_id']),
                'quantity'   => intval($item['quantity']),
                'unit_price' => $product ? $product->price : 0,
            ));
        }

        wp_send_json_success(array(
            'order_id'     => $order_id,
            'order_number' => $order_number,
            'message'      => 'تم إنشاء إذن الصرف وإرساله للاعتماد',
        ));
    }

    /**
     * Get all orders (filtered by status optionally)
     */
    public static function get_orders() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $status = sanitize_text_field($_POST['status'] ?? '');
        $where = '';
        if ($status) {
            $where = $wpdb->prepare(" AND o.status = %s", $status);
        }

        $orders = $wpdb->get_results(
            "SELECT o.*, d.name as department_name, e.name as employee_name,
                    u.display_name as created_by_name
             FROM {$prefix}withdrawal_orders o
             LEFT JOIN {$prefix}departments d ON o.department_id = d.id
             LEFT JOIN {$prefix}employees e ON o.employee_id = e.id
             LEFT JOIN {$wpdb->users} u ON o.created_by = u.ID
             WHERE 1=1 $where
             ORDER BY o.created_at DESC"
        );

        wp_send_json_success($orders);
    }

    /**
     * Get single order with items
     */
    public static function get_order() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $order_id = intval($_POST['order_id']);

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT o.*, d.name as department_name, e.name as employee_name,
                    u.display_name as created_by_name
             FROM {$prefix}withdrawal_orders o
             LEFT JOIN {$prefix}departments d ON o.department_id = d.id
             LEFT JOIN {$prefix}employees e ON o.employee_id = e.id
             LEFT JOIN {$wpdb->users} u ON o.created_by = u.ID
             WHERE o.id = %d", $order_id
        ));

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, p.name as product_name, p.unit as product_unit, p.current_stock
             FROM {$prefix}withdrawal_order_items i
             LEFT JOIN {$prefix}products p ON i.product_id = p.id
             WHERE i.order_id = %d", $order_id
        ));

        // Get approver signature if approved
        $signature_url = '';
        if ($order && $order->approved_by) {
            $signature_url = get_user_meta($order->approved_by, 'iw_signature_url', true);
        }

        wp_send_json_success(array(
            'order'         => $order,
            'items'         => $items,
            'signature_url' => $signature_url,
        ));
    }

    /**
     * Step 2: Dean updates order items before approval (optional)
     */
    public static function update_order() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_approve_orders') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $order_id = intval($_POST['order_id']);
        $items    = json_decode(stripslashes($_POST['items']), true);

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}withdrawal_orders WHERE id = %d", $order_id
        ));

        if (!$order || $order->status !== 'pending') {
            wp_send_json_error(array('message' => 'لا يمكن تعديل هذا الإذن'));
        }

        // Delete old items and insert updated ones
        $wpdb->delete($prefix . 'withdrawal_order_items', array('order_id' => $order_id));

        foreach ($items as $item) {
            $product = IW_Products::get_by_id(intval($item['product_id']));
            $wpdb->insert($prefix . 'withdrawal_order_items', array(
                'order_id'          => $order_id,
                'product_id'        => intval($item['product_id']),
                'quantity'          => intval($item['quantity']),
                'approved_quantity' => isset($item['approved_quantity']) ? intval($item['approved_quantity']) : intval($item['quantity']),
                'unit_price'        => $product ? $product->price : 0,
            ));
        }

        if (!empty($_POST['notes'])) {
            $wpdb->update($prefix . 'withdrawal_orders',
                array('notes' => sanitize_textarea_field($_POST['notes'])),
                array('id' => $order_id)
            );
        }

        wp_send_json_success(array('message' => 'تم تعديل الإذن'));
    }

    /**
     * Step 3: Dean approves with electronic signature
     */
    public static function approve_order() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_approve_orders') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية للاعتماد'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $order_id = intval($_POST['order_id']);
        $user_id  = get_current_user_id();

        $signature_url = get_user_meta($user_id, 'iw_signature_url', true);
        if (empty($signature_url)) {
            wp_send_json_error(array('message' => 'يجب رفع التوقيع الإلكتروني أولاً'));
        }

        $wpdb->update($prefix . 'withdrawal_orders', array(
            'status'        => 'approved',
            'approved_by'   => $user_id,
            'approved_at'   => current_time('mysql'),
            'signature_url' => $signature_url,
        ), array('id' => $order_id));

        wp_send_json_success(array('message' => 'تم اعتماد إذن الصرف'));
    }

    /**
     * Reject order
     */
    public static function reject_order() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_approve_orders') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $order_id = intval($_POST['order_id']);
        $wpdb->update($wpdb->prefix . 'iw_withdrawal_orders', array(
            'status'      => 'rejected',
            'approved_by' => get_current_user_id(),
            'approved_at' => current_time('mysql'),
            'notes'       => sanitize_textarea_field($_POST['rejection_reason'] ?? ''),
        ), array('id' => $order_id));

        wp_send_json_success(array('message' => 'تم رفض الإذن'));
    }

    /**
     * Step 4: Warehouse completes the withdrawal (actually deducts stock)
     */
    public static function complete_order() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!IW_Permissions::current_user_can('withdraw_stock', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $order_id = intval($_POST['order_id']);
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}withdrawal_orders WHERE id = %d", $order_id
        ));

        if (!$order || $order->status !== 'approved') {
            wp_send_json_error(array('message' => 'الإذن غير معتمد'));
        }

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$prefix}withdrawal_order_items WHERE order_id = %d", $order_id
        ));

        foreach ($items as $item) {
            $qty = $item->approved_quantity !== null ? $item->approved_quantity : $item->quantity;
            if ($qty > 0) {
                IW_Transactions::withdraw_fifo($item->product_id, $qty);

                $wpdb->insert($prefix . 'transactions', array(
                    'transaction_type' => 'withdraw',
                    'product_id'       => $item->product_id,
                    'quantity'         => $qty,
                    'unit_price'       => $item->unit_price,
                    'department_id'    => $order->department_id,
                    'employee_id'      => $order->employee_id,
                    'notes'            => 'إذن صرف رقم: ' . $order->order_number,
                    'created_by'       => get_current_user_id(),
                ));
            }
        }

        $wpdb->update($prefix . 'withdrawal_orders',
            array('status' => 'completed'),
            array('id' => $order_id)
        );

        // Check for low stock and auto-generate purchase requests
        IW_Purchase_Requests::auto_generate();

        wp_send_json_success(array('message' => 'تم تنفيذ إذن الصرف بنجاح'));
    }
}
