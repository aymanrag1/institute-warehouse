<?php
if (!defined('ABSPATH')) exit;

class IW_Purchase_Requests {

    public static function init() {
        add_action('wp_ajax_iw_create_purchase_request', array(__CLASS__, 'create_request'));
        add_action('wp_ajax_iw_get_purchase_requests', array(__CLASS__, 'get_requests'));
        add_action('wp_ajax_iw_get_purchase_request', array(__CLASS__, 'get_request'));
        add_action('wp_ajax_iw_approve_purchase_request', array(__CLASS__, 'approve_request'));
        add_action('wp_ajax_iw_reject_purchase_request', array(__CLASS__, 'reject_request'));
        add_action('wp_ajax_iw_update_purchase_request', array(__CLASS__, 'update_request'));
        add_action('wp_ajax_iw_complete_purchase_request', array(__CLASS__, 'complete_request'));
        add_action('wp_ajax_iw_auto_generate_purchase_requests', array(__CLASS__, 'ajax_auto_generate'));
    }

    private static function generate_request_number() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}iw_purchase_requests") + 1;
        return 'PR-' . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Auto-generate purchase requests for products at or below min stock.
     * Requested quantity = max_stock - current_stock
     */
    public static function auto_generate() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $low_stock = IW_Products::get_low_stock_products();

        if (empty($low_stock)) return;

        // Check if there's already a pending request for these products
        $pending_product_ids = $wpdb->get_col(
            "SELECT DISTINCT pri.product_id FROM {$prefix}purchase_request_items pri
             INNER JOIN {$prefix}purchase_requests pr ON pri.request_id = pr.id
             WHERE pr.status IN ('pending', 'approved')"
        );

        $items_to_request = array();
        foreach ($low_stock as $product) {
            if (in_array($product->id, $pending_product_ids)) continue;

            $needed = $product->max_stock - $product->current_stock;
            if ($needed <= 0) continue;

            $items_to_request[] = array(
                'product_id'      => $product->id,
                'quantity'        => $needed,
                'estimated_price' => $product->price,
            );
        }

        if (empty($items_to_request)) return;

        $request_number = self::generate_request_number();

        $wpdb->insert($prefix . 'purchase_requests', array(
            'request_number' => $request_number,
            'status'         => 'pending',
            'notes'          => 'طلب شراء تلقائي - أصناف وصلت للحد الأدنى',
            'created_by'     => get_current_user_id(),
        ));

        $request_id = $wpdb->insert_id;

        foreach ($items_to_request as $item) {
            $wpdb->insert($prefix . 'purchase_request_items', array(
                'request_id'      => $request_id,
                'product_id'      => $item['product_id'],
                'quantity'        => $item['quantity'],
                'estimated_price' => $item['estimated_price'],
            ));
        }
    }

    public static function ajax_auto_generate() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        self::auto_generate();
        wp_send_json_success(array('message' => 'تم فحص المخزون وإنشاء طلبات الشراء'));
    }

    /**
     * Manually create purchase request
     */
    public static function create_request() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!IW_Permissions::current_user_can('purchase_requests', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $items = json_decode(stripslashes($_POST['items']), true);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        if (empty($items)) {
            wp_send_json_error(array('message' => 'يجب إضافة أصناف'));
        }

        $request_number = self::generate_request_number();

        $wpdb->insert($prefix . 'purchase_requests', array(
            'request_number' => $request_number,
            'status'         => 'pending',
            'notes'          => $notes,
            'created_by'     => get_current_user_id(),
        ));

        $request_id = $wpdb->insert_id;

        foreach ($items as $item) {
            $wpdb->insert($prefix . 'purchase_request_items', array(
                'request_id'      => $request_id,
                'product_id'      => intval($item['product_id']),
                'quantity'        => intval($item['quantity']),
                'estimated_price' => floatval($item['estimated_price'] ?? 0),
            ));
        }

        wp_send_json_success(array(
            'request_id'     => $request_id,
            'request_number' => $request_number,
            'message'        => 'تم إنشاء طلب الشراء وإرساله للاعتماد',
        ));
    }

    public static function get_requests() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $status = sanitize_text_field($_POST['status'] ?? '');
        $where = '';
        if ($status) {
            $where = $wpdb->prepare(" AND pr.status = %s", $status);
        }

        $requests = $wpdb->get_results(
            "SELECT pr.*, u.display_name as created_by_name
             FROM {$prefix}purchase_requests pr
             LEFT JOIN {$wpdb->users} u ON pr.created_by = u.ID
             WHERE 1=1 $where
             ORDER BY pr.created_at DESC"
        );

        wp_send_json_success($requests);
    }

    public static function get_request() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $request_id = intval($_POST['request_id']);

        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT pr.*, u.display_name as created_by_name
             FROM {$prefix}purchase_requests pr
             LEFT JOIN {$wpdb->users} u ON pr.created_by = u.ID
             WHERE pr.id = %d", $request_id
        ));

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, p.name as product_name, p.unit as product_unit, p.current_stock, p.min_stock, p.max_stock
             FROM {$prefix}purchase_request_items i
             LEFT JOIN {$prefix}products p ON i.product_id = p.id
             WHERE i.request_id = %d", $request_id
        ));

        $signature_url = '';
        if ($request && $request->approved_by) {
            $signature_url = get_user_meta($request->approved_by, 'iw_signature_url', true);
        }

        wp_send_json_success(array(
            'request'       => $request,
            'items'         => $items,
            'signature_url' => $signature_url,
        ));
    }

    /**
     * Dean updates request items before approval
     */
    public static function update_request() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_approve_orders') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $request_id = intval($_POST['request_id']);
        $items      = json_decode(stripslashes($_POST['items']), true);

        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}purchase_requests WHERE id = %d", $request_id
        ));

        if (!$request || $request->status !== 'pending') {
            wp_send_json_error(array('message' => 'لا يمكن تعديل هذا الطلب'));
        }

        $wpdb->delete($prefix . 'purchase_request_items', array('request_id' => $request_id));

        foreach ($items as $item) {
            $wpdb->insert($prefix . 'purchase_request_items', array(
                'request_id'        => $request_id,
                'product_id'        => intval($item['product_id']),
                'quantity'          => intval($item['quantity']),
                'approved_quantity' => isset($item['approved_quantity']) ? intval($item['approved_quantity']) : intval($item['quantity']),
                'estimated_price'   => floatval($item['estimated_price'] ?? 0),
            ));
        }

        if (!empty($_POST['notes'])) {
            $wpdb->update($prefix . 'purchase_requests',
                array('notes' => sanitize_textarea_field($_POST['notes'])),
                array('id' => $request_id)
            );
        }

        wp_send_json_success(array('message' => 'تم تعديل طلب الشراء'));
    }

    /**
     * Dean approves with electronic signature
     */
    public static function approve_request() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_approve_orders') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية للاعتماد'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $request_id = intval($_POST['request_id']);
        $user_id    = get_current_user_id();

        $signature_url = get_user_meta($user_id, 'iw_signature_url', true);
        if (empty($signature_url)) {
            wp_send_json_error(array('message' => 'يجب رفع التوقيع الإلكتروني أولاً'));
        }

        $wpdb->update($prefix . 'purchase_requests', array(
            'status'        => 'approved',
            'approved_by'   => $user_id,
            'approved_at'   => current_time('mysql'),
            'signature_url' => $signature_url,
        ), array('id' => $request_id));

        wp_send_json_success(array('message' => 'تم اعتماد طلب الشراء'));
    }

    public static function reject_request() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_approve_orders') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $request_id = intval($_POST['request_id']);
        $wpdb->update($wpdb->prefix . 'iw_purchase_requests', array(
            'status'      => 'rejected',
            'approved_by' => get_current_user_id(),
            'approved_at' => current_time('mysql'),
            'notes'       => sanitize_textarea_field($_POST['rejection_reason'] ?? ''),
        ), array('id' => $request_id));

        wp_send_json_success(array('message' => 'تم رفض الطلب'));
    }

    /**
     * Complete purchase - adds stock
     */
    public static function complete_request() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!IW_Permissions::current_user_can('purchase_requests', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $request_id = intval($_POST['request_id']);
        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}purchase_requests WHERE id = %d", $request_id
        ));

        if (!$request || $request->status !== 'approved') {
            wp_send_json_error(array('message' => 'الطلب غير معتمد'));
        }

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$prefix}purchase_request_items WHERE request_id = %d", $request_id
        ));

        foreach ($items as $item) {
            $qty = $item->approved_quantity !== null ? $item->approved_quantity : $item->quantity;
            if ($qty > 0) {
                // Add stock
                $wpdb->insert($prefix . 'transactions', array(
                    'transaction_type' => 'add',
                    'product_id'       => $item->product_id,
                    'quantity'         => $qty,
                    'unit_price'       => $item->estimated_price,
                    'remaining_qty'    => $qty,
                    'notes'            => 'طلب شراء رقم: ' . $request->request_number,
                    'created_by'       => get_current_user_id(),
                ));

                IW_Products::update_stock($item->product_id, $qty);
            }
        }

        $wpdb->update($prefix . 'purchase_requests',
            array('status' => 'completed'),
            array('id' => $request_id)
        );

        wp_send_json_success(array('message' => 'تم استلام البضاعة وإضافتها للمخزون'));
    }
}
