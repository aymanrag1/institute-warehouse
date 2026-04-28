<?php
if (!defined('ABSPATH')) exit;

class IW_Return_Orders {

    public static function init() {
        add_action('wp_ajax_iw_create_return_order',   array(__CLASS__, 'ajax_create'));
        add_action('wp_ajax_iw_get_return_orders',     array(__CLASS__, 'ajax_get_list'));
        add_action('wp_ajax_iw_get_return_order',      array(__CLASS__, 'ajax_get_single'));
        add_action('wp_ajax_iw_approve_return_order',  array(__CLASS__, 'ajax_approve'));
        add_action('wp_ajax_iw_complete_return_order', array(__CLASS__, 'ajax_complete'));
        add_action('wp_ajax_iw_reject_return_order',   array(__CLASS__, 'ajax_reject'));
        add_action('wp_ajax_iw_delete_return_order',   array(__CLASS__, 'ajax_delete'));
    }

    // ------------------------------------------------------------------ //
    // Helpers
    // ------------------------------------------------------------------ //

    private static function next_order_number($type = 'normal') {
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';
        $year   = date('Y');
        $slug   = ($type === 'custody') ? 'CRT' : 'RT';
        $last   = $wpdb->get_var($wpdb->prepare(
            "SELECT order_number FROM {$prefix}return_orders
             WHERE order_type = %s AND YEAR(created_at) = %d
             ORDER BY id DESC LIMIT 1",
            $type, $year
        ));
        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq   = intval(end($parts)) + 1;
        }
        return $slug . '-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // ------------------------------------------------------------------ //
    // Create
    // ------------------------------------------------------------------ //

    public static function ajax_create() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        if (!IW_Permissions::current_user_can('withdrawal_orders', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $order_type       = sanitize_text_field($_POST['order_type'] ?? 'normal');
        $original_id      = intval($_POST['original_order_id'] ?? 0);
        $department_id    = intval($_POST['department_id'] ?? 0);
        $employee_id      = intval($_POST['employee_id'] ?? 0);
        $department_name  = sanitize_text_field($_POST['department_name'] ?? '');
        $employee_name    = sanitize_text_field($_POST['employee_name'] ?? '');
        $notes            = sanitize_textarea_field($_POST['notes'] ?? '');
        $items            = json_decode(stripslashes($_POST['items'] ?? '[]'), true);

        if (!in_array($order_type, ['normal', 'custody'])) {
            wp_send_json_error(array('message' => 'نوع الإذن غير صحيح'));
        }
        if (empty($items)) {
            wp_send_json_error(array('message' => 'يجب إضافة أصناف للإذن'));
        }

        $order_number = self::next_order_number($order_type);

        $result = $wpdb->insert($prefix . 'return_orders', array(
            'order_number'     => $order_number,
            'order_type'       => $order_type,
            'original_order_id'=> $original_id ?: null,
            'department_id'    => $department_id,
            'employee_id'      => $employee_id,
            'department_name'  => $department_name,
            'employee_name'    => $employee_name,
            'status'           => 'pending',
            'notes'            => $notes,
            'created_by'       => get_current_user_id(),
        ));

        if (!$result) {
            wp_send_json_error(array('message' => 'فشل في حفظ الإذن: ' . $wpdb->last_error));
        }

        $order_id = $wpdb->insert_id;
        foreach ($items as $item) {
            $wpdb->insert($prefix . 'return_order_items', array(
                'order_id'   => $order_id,
                'product_id' => intval($item['product_id']),
                'quantity'   => intval($item['quantity']),
                'unit_price' => floatval($item['unit_price'] ?? 0),
            ));
        }

        wp_send_json_success(array('message' => 'تم إنشاء إذن الارتجاع ' . $order_number, 'id' => $order_id));
    }

    // ------------------------------------------------------------------ //
    // List
    // ------------------------------------------------------------------ //

    public static function ajax_get_list() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        if (!IW_Permissions::current_user_can('withdrawal_orders', 'read_only')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $prefix     = $wpdb->prefix . 'iw_';
        $status     = sanitize_text_field($_POST['status'] ?? '');
        $order_type = sanitize_text_field($_POST['order_type'] ?? '');

        $where = '1=1';
        if ($status)     $where .= $wpdb->prepare(' AND ro.status = %s', $status);
        if ($order_type) $where .= $wpdb->prepare(' AND ro.order_type = %s', $order_type);

        $rows = $wpdb->get_results(
            "SELECT ro.*, u.display_name as created_by_name
             FROM {$prefix}return_orders ro
             LEFT JOIN {$wpdb->users} u ON ro.created_by = u.ID
             WHERE {$where}
             ORDER BY ro.created_at DESC"
        );

        wp_send_json_success($rows ?: []);
    }

    // ------------------------------------------------------------------ //
    // Single
    // ------------------------------------------------------------------ //

    public static function ajax_get_single() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        if (!IW_Permissions::current_user_can('withdrawal_orders', 'read_only')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $prefix   = $wpdb->prefix . 'iw_';
        $order_id = intval($_POST['order_id'] ?? 0);

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}return_orders WHERE id = %d", $order_id
        ));
        if (!$order) {
            wp_send_json_error(array('message' => 'الإذن غير موجود'));
        }

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT roi.*, p.name as product_name, p.unit as product_unit
             FROM {$prefix}return_order_items roi
             LEFT JOIN {$prefix}products p ON roi.product_id = p.id
             WHERE roi.order_id = %d",
            $order_id
        ));

        // Resolve signature: prefer stored value, fall back to approver meta.
        $signature_url = !empty($order->signature_url)
            ? $order->signature_url
            : ($order->approved_by ? (get_user_meta($order->approved_by, 'iw_signature_url', true) ?: '') : '');

        wp_send_json_success(array(
            'order'          => $order,
            'items'          => $items ?: [],
            'signature_url'  => $signature_url,
            'signature_width'=> intval(get_option('iw_signature_width', 150)),
        ));
    }

    // ------------------------------------------------------------------ //
    // Approve (with signature)
    // ------------------------------------------------------------------ //

    public static function ajax_approve() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        if (!IW_Permissions::current_user_can('withdrawal_orders', 'approve')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية الاعتماد'));
        }

        global $wpdb;
        $prefix    = $wpdb->prefix . 'iw_';
        $order_id  = intval($_POST['order_id'] ?? 0);
        $signature = sanitize_text_field($_POST['signature_url'] ?? '');

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}return_orders WHERE id = %d", $order_id
        ));
        if (!$order || $order->status !== 'pending') {
            wp_send_json_error(array('message' => 'الإذن غير موجود أو لا يمكن اعتماده'));
        }

        // Use existing signature if not provided
        if (!$signature) {
            $user_id   = get_current_user_id();
            $signature = get_user_meta($user_id, 'iw_signature_url', true) ?: '';
        }

        $wpdb->update(
            $prefix . 'return_orders',
            array(
                'status'      => 'approved',
                'approved_by' => get_current_user_id(),
                'approved_at' => current_time('mysql'),
                'signature_url' => $signature,
            ),
            array('id' => $order_id)
        );

        wp_send_json_success(array('message' => 'تم اعتماد إذن الارتجاع'));
    }

    // ------------------------------------------------------------------ //
    // Complete (execute) — adds stock back for normal, records for custody
    // ------------------------------------------------------------------ //

    public static function ajax_complete() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        if (!IW_Permissions::current_user_can('withdrawal_orders', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $prefix   = $wpdb->prefix . 'iw_';
        $order_id = intval($_POST['order_id'] ?? 0);

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}return_orders WHERE id = %d", $order_id
        ));
        if (!$order || $order->status !== 'approved') {
            wp_send_json_error(array('message' => 'الإذن غير موجود أو لم يُعتمد بعد'));
        }

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$prefix}return_order_items WHERE order_id = %d", $order_id
        ));
        if (!$items) {
            wp_send_json_error(array('message' => 'لا توجد أصناف في هذا الإذن'));
        }

        $is_custody = ($order->order_type === 'custody');

        foreach ($items as $item) {
            $qty = isset($item->approved_quantity) && $item->approved_quantity !== null
                ? intval($item->approved_quantity)
                : intval($item->quantity);
            if ($qty <= 0) continue;

            if ($is_custody) {
                // رد عهدة: تسجيل فقط بدون تغيير رصيد
                $wpdb->insert($prefix . 'transactions', array(
                    'transaction_type' => 'custody_return',
                    'product_id'       => $item->product_id,
                    'quantity'         => $qty,
                    'unit_price'       => floatval($item->unit_price),
                    'remaining_qty'    => 0,
                    'notes'            => 'رد عهدة - إذن رقم: ' . $order->order_number,
                    'created_by'       => get_current_user_id(),
                ));
            } else {
                // ارتجاع عادي: إضافة للمخزون (FIFO compatible)
                $wpdb->insert($prefix . 'transactions', array(
                    'transaction_type' => 'return',
                    'product_id'       => $item->product_id,
                    'quantity'         => $qty,
                    'unit_price'       => floatval($item->unit_price),
                    'remaining_qty'    => $qty,
                    'notes'            => 'إذن ارتجاع رقم: ' . $order->order_number,
                    'created_by'       => get_current_user_id(),
                ));
                // Update product current_stock
                IW_Products::update_stock($item->product_id, $qty);
            }
        }

        $wpdb->update(
            $prefix . 'return_orders',
            array('status' => 'completed'),
            array('id' => $order_id)
        );

        $msg = $is_custody
            ? 'تم تنفيذ إذن رد العهدة وتسجيل الإرجاع'
            : 'تم تنفيذ إذن الارتجاع وإضافة الكميات للمخزون';

        wp_send_json_success(array('message' => $msg));
    }

    // ------------------------------------------------------------------ //
    // Reject
    // ------------------------------------------------------------------ //

    public static function ajax_reject() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        if (!IW_Permissions::current_user_can('withdrawal_orders', 'approve')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $prefix   = $wpdb->prefix . 'iw_';
        $order_id = intval($_POST['order_id'] ?? 0);
        $reason   = sanitize_textarea_field($_POST['rejection_reason'] ?? '');

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}return_orders WHERE id = %d", $order_id
        ));
        if (!$order || !in_array($order->status, ['pending', 'approved'])) {
            wp_send_json_error(array('message' => 'لا يمكن رفض هذا الإذن'));
        }

        $wpdb->update(
            $prefix . 'return_orders',
            array('status' => 'rejected', 'rejection_reason' => $reason),
            array('id' => $order_id)
        );

        wp_send_json_success(array('message' => 'تم رفض إذن الارتجاع'));
    }

    // ------------------------------------------------------------------ //
    // Delete
    // ------------------------------------------------------------------ //

    public static function ajax_delete() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        if (!IW_Permissions::current_user_can('withdrawal_orders', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $prefix   = $wpdb->prefix . 'iw_';
        $order_id = intval($_POST['order_id'] ?? 0);

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}return_orders WHERE id = %d", $order_id
        ));
        if (!$order || $order->status !== 'pending') {
            wp_send_json_error(array('message' => 'لا يمكن حذف هذا الإذن (فقط المعلق يُحذف)'));
        }

        $wpdb->delete($prefix . 'return_order_items', array('order_id' => $order_id));
        $wpdb->delete($prefix . 'return_orders', array('id' => $order_id));

        wp_send_json_success(array('message' => 'تم حذف إذن الارتجاع'));
    }
}
