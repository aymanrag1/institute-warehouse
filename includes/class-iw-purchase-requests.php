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
        add_action('wp_ajax_iw_delete_purchase_request', array(__CLASS__, 'delete_request'));
        add_action('wp_ajax_iw_delete_purchase_request_item', array(__CLASS__, 'delete_item'));
        add_action('wp_ajax_iw_auto_generate_purchase_requests', array(__CLASS__, 'ajax_auto_generate'));
    }

    private static function generate_request_number() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}iw_purchase_requests") + 1;
        return 'PR-' . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get last purchase price for a product
     */
    public static function get_last_purchase_price($product_id) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        // Get last add transaction price
        $last_price = $wpdb->get_var($wpdb->prepare(
            "SELECT unit_price FROM {$prefix}transactions
             WHERE product_id = %d AND transaction_type = 'add'
             ORDER BY created_at DESC LIMIT 1",
            $product_id
        ));

        if ($last_price !== null) {
            return floatval($last_price);
        }

        // Fallback to product price
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT price FROM {$prefix}products WHERE id = %d",
            $product_id
        ));

        return $product ? floatval($product->price) : 0;
    }

    /**
     * Auto-generate purchase requests for products at or below min stock.
     * Requested quantity = max_stock - current_stock
     * Can filter by category (optional)
     */
    public static function auto_generate($category = '') {
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        // Build query with optional category filter
        $where = "WHERE min_stock > 0 AND current_stock <= min_stock";
        if (!empty($category)) {
            $where .= $wpdb->prepare(" AND category = %s", $category);
        }

        // Get products at or below min_stock (min_stock must be > 0)
        $low_stock = $wpdb->get_results(
            "SELECT * FROM {$prefix}products {$where} ORDER BY name ASC"
        );

        if (empty($low_stock)) {
            $msg = empty($category) ? 'لا توجد أصناف وصلت للحد الأدنى' : 'لا توجد أصناف في تصنيف "' . $category . '" وصلت للحد الأدنى';
            return array('created' => 0, 'skipped' => 0, 'message' => $msg);
        }

        // Check if there's already a pending/approved request for these products
        $pending_product_ids = $wpdb->get_col(
            "SELECT DISTINCT pri.product_id FROM {$prefix}purchase_request_items pri
             INNER JOIN {$prefix}purchase_requests pr ON pri.request_id = pr.id
             WHERE pr.status IN ('pending', 'approved')"
        );
        if (!is_array($pending_product_ids)) $pending_product_ids = array();

        $items_to_request = array();
        $skipped = 0;
        foreach ($low_stock as $product) {
            if (in_array($product->id, $pending_product_ids)) {
                $skipped++;
                continue;
            }

            $needed = $product->max_stock - $product->current_stock;
            if ($needed <= 0) $needed = $product->min_stock; // fallback if max not set

            // Get last purchase price
            $last_price = self::get_last_purchase_price($product->id);

            $items_to_request[] = array(
                'product_id'          => $product->id,
                'product_name'        => $product->name,
                'quantity'            => $needed,
                'estimated_price'     => $last_price, // Use last purchase price as estimated
                'last_purchase_price' => $last_price,
            );
        }

        if (empty($items_to_request)) {
            return array('created' => 0, 'skipped' => $skipped, 'low_stock_count' => count($low_stock),
                'message' => 'توجد ' . count($low_stock) . ' أصناف تحت الحد الأدنى لكن جميعها لديها طلبات شراء معلقة بالفعل');
        }

        $request_number = self::generate_request_number();
        $notes = empty($category) ? 'طلب شراء - أصناف وصلت للحد الأدنى' : 'طلب شراء - تصنيف: ' . $category;

        $wpdb->insert($prefix . 'purchase_requests', array(
            'request_number' => $request_number,
            'status'         => 'pending',
            'notes'          => $notes,
            'created_by'     => get_current_user_id(),
        ));

        $request_id = $wpdb->insert_id;

        foreach ($items_to_request as $item) {
            $wpdb->insert($prefix . 'purchase_request_items', array(
                'request_id'          => $request_id,
                'product_id'          => $item['product_id'],
                'quantity'            => $item['quantity'],
                'estimated_price'     => $item['estimated_price'],
                'last_purchase_price' => $item['last_purchase_price'],
            ));
        }

        // Send email to approvers
        self::notify_approvers($request_number);

        return array(
            'created' => count($items_to_request),
            'skipped' => $skipped,
            'request_id' => $request_id,
            'request_number' => $request_number,
            'items' => $items_to_request,
            'message' => 'تم إنشاء طلب شراء رقم ' . $request_number . ' يحتوي على ' . count($items_to_request) . ' أصناف',
        );
    }

    /**
     * Auto-generate for multiple categories (creates one combined request)
     */
    public static function auto_generate_multi($categories) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        // Build IN clause for multiple categories
        $placeholders = implode(',', array_fill(0, count($categories), '%s'));
        $where = $wpdb->prepare(
            "WHERE min_stock > 0 AND current_stock <= min_stock AND category IN ($placeholders)",
            $categories
        );

        $low_stock = $wpdb->get_results(
            "SELECT * FROM {$prefix}products {$where} ORDER BY name ASC"
        );

        if (empty($low_stock)) {
            return array('created' => 0, 'skipped' => 0, 'message' => 'لا توجد أصناف في التصنيفات المحددة وصلت للحد الأدنى');
        }

        // Check pending requests
        $pending_product_ids = $wpdb->get_col(
            "SELECT DISTINCT pri.product_id FROM {$prefix}purchase_request_items pri
             INNER JOIN {$prefix}purchase_requests pr ON pri.request_id = pr.id
             WHERE pr.status IN ('pending', 'approved')"
        );
        if (!is_array($pending_product_ids)) $pending_product_ids = array();

        $items_to_request = array();
        $skipped = 0;
        foreach ($low_stock as $product) {
            if (in_array($product->id, $pending_product_ids)) {
                $skipped++;
                continue;
            }
            $needed = $product->max_stock - $product->current_stock;
            if ($needed <= 0) $needed = $product->min_stock;
            $last_price = self::get_last_purchase_price($product->id);
            $items_to_request[] = array(
                'product_id'          => $product->id,
                'product_name'        => $product->name,
                'quantity'            => $needed,
                'estimated_price'     => $last_price,
                'last_purchase_price' => $last_price,
            );
        }

        if (empty($items_to_request)) {
            return array('created' => 0, 'skipped' => $skipped,
                'message' => 'جميع الأصناف في التصنيفات المحددة لديها طلبات شراء معلقة بالفعل');
        }

        $request_number = self::generate_request_number();
        $notes = 'طلب شراء - تصنيفات: ' . implode('، ', $categories);

        $wpdb->insert($prefix . 'purchase_requests', array(
            'request_number' => $request_number,
            'status'         => 'pending',
            'notes'          => $notes,
            'created_by'     => get_current_user_id(),
        ));

        $request_id = $wpdb->insert_id;

        foreach ($items_to_request as $item) {
            $wpdb->insert($prefix . 'purchase_request_items', array(
                'request_id'          => $request_id,
                'product_id'          => $item['product_id'],
                'quantity'            => $item['quantity'],
                'estimated_price'     => $item['estimated_price'],
                'last_purchase_price' => $item['last_purchase_price'],
            ));
        }

        self::notify_approvers($request_number);

        return array(
            'created' => count($items_to_request),
            'skipped' => $skipped,
            'request_id' => $request_id,
            'request_number' => $request_number,
            'items' => $items_to_request,
            'message' => 'تم إنشاء طلب شراء رقم ' . $request_number . ' يحتوي على ' . count($items_to_request) . ' أصناف',
        );
    }

    /**
     * AJAX: Generate purchase request with category filter (manual button click)
     */
    public static function ajax_auto_generate() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        $category_raw = sanitize_text_field($_POST['category'] ?? '');
        // Support multiple categories separated by comma
        if (!empty($category_raw) && strpos($category_raw, ',') !== false) {
            $categories = array_map('trim', explode(',', $category_raw));
            $categories = array_filter($categories);
            $result = self::auto_generate_multi($categories);
        } else {
            $result = self::auto_generate($category_raw);
        }

        if (!$result) {
            $result = array('created' => 0, 'message' => 'لا توجد أصناف وصلت للحد الأدنى');
        }
        wp_send_json_success($result);
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

        // Send email to approvers
        self::notify_approvers($request_number);

        wp_send_json_success(array(
            'request_id'     => $request_id,
            'request_number' => $request_number,
            'message'        => 'تم إنشاء طلب الشراء وإرساله للاعتماد',
        ));
    }

    /**
     * Send email notification to users with approval capability
     */
    private static function notify_approvers($request_number) {
        $subject = 'يوجد طلب شراء جديد يحتاج اعتمادك - رقم: ' . $request_number;
        $admin_url = admin_url('admin.php?page=iw-purchase-requests');

        $message = "مرحباً،\n\n";
        $message .= "تم إنشاء طلب شراء جديد برقم: " . $request_number . "\n";
        $message .= "يرجى الدخول للنظام لمراجعته واعتماده.\n\n";
        $message .= "رابط الصفحة: " . $admin_url . "\n\n";
        $message .= "نظام إدارة المخازن";

        $approvers = get_users(array('role__in' => array('administrator', 'iw_dean')));
        $cap_users = get_users(array('capability' => 'iw_approve_orders'));
        $all = array_merge($approvers, $cap_users);
        $sent = array();

        foreach ($all as $user) {
            if (in_array($user->ID, $sent) || $user->ID === get_current_user_id()) continue;
            wp_mail($user->user_email, $subject, $message);
            $sent[] = $user->ID;
        }
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

        // Add last purchase price to each item if not set
        foreach ($items as &$item) {
            if (empty($item->last_purchase_price) || $item->last_purchase_price == 0) {
                $item->last_purchase_price = self::get_last_purchase_price($item->product_id);
            }
        }

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
     * Update request items before approval
     * Allowed: creator, dean, admin, accountant
     */
    public static function update_request() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

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

        // Allow creator, dean, admin, or accountant to edit pending requests
        $is_creator = ($request->created_by == get_current_user_id());
        $is_accountant = current_user_can('iw_accountant') || IW_Permissions::current_user_can('purchase_requests', 'read_write');
        if (!$is_creator && !$is_accountant && !current_user_can('iw_approve_orders') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية لتعديل هذا الطلب'));
        }

        $wpdb->delete($prefix . 'purchase_request_items', array('request_id' => $request_id));

        foreach ($items as $item) {
            $last_price = self::get_last_purchase_price(intval($item['product_id']));
            $wpdb->insert($prefix . 'purchase_request_items', array(
                'request_id'          => $request_id,
                'product_id'          => intval($item['product_id']),
                'quantity'            => intval($item['quantity']),
                'approved_quantity'   => isset($item['approved_quantity']) ? intval($item['approved_quantity']) : intval($item['quantity']),
                'estimated_price'     => floatval($item['estimated_price'] ?? 0),
                'last_purchase_price' => $last_price,
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
     * Delete item from purchase request
     */
    public static function delete_item() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $item_id = intval($_POST['item_id']);

        // Get item and request info
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}purchase_request_items WHERE id = %d", $item_id
        ));

        if (!$item) {
            wp_send_json_error(array('message' => 'الصنف غير موجود'));
        }

        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}purchase_requests WHERE id = %d", $item->request_id
        ));

        if (!$request || $request->status !== 'pending') {
            wp_send_json_error(array('message' => 'لا يمكن حذف صنف من طلب معتمد'));
        }

        // Allow creator, dean, admin, or accountant
        $is_creator = ($request->created_by == get_current_user_id());
        $is_accountant = current_user_can('iw_accountant') || IW_Permissions::current_user_can('purchase_requests', 'read_write');
        if (!$is_creator && !$is_accountant && !current_user_can('iw_approve_orders') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        $wpdb->delete($prefix . 'purchase_request_items', array('id' => $item_id));

        // Check if request has no more items
        $remaining = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}purchase_request_items WHERE request_id = %d", $item->request_id
        ));

        if ($remaining == 0) {
            // Delete the request itself
            $wpdb->delete($prefix . 'purchase_requests', array('id' => $item->request_id));
            wp_send_json_success(array('message' => 'تم حذف الصنف وإلغاء الطلب لأنه أصبح فارغاً', 'request_deleted' => true));
        }

        wp_send_json_success(array('message' => 'تم حذف الصنف من الطلب'));
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
        if (empty($signature_url) && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'يجب رفع التوقيع الإلكتروني أولاً من صفحة "التوقيع الإلكتروني"'));
        }

        $wpdb->update($prefix . 'purchase_requests', array(
            'status'        => 'approved',
            'approved_by'   => $user_id,
            'approved_at'   => current_time('mysql'),
            'signature_url' => $signature_url ?: '',
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

    /**
     * Delete pending purchase request
     */
    public static function delete_request() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $request_id = intval($_POST['request_id']);
        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}purchase_requests WHERE id = %d", $request_id
        ));

        if (!$request) {
            wp_send_json_error(array('message' => 'الطلب غير موجود'));
        }

        if ($request->status !== 'pending') {
            wp_send_json_error(array('message' => 'لا يمكن حذف طلب معتمد أو مكتمل'));
        }

        // Allow creator, dean, or admin to delete pending requests
        $is_creator = ($request->created_by == get_current_user_id());
        if (!$is_creator && !current_user_can('iw_approve_orders') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية لحذف هذا الطلب'));
        }

        // Delete items first
        $wpdb->delete($prefix . 'purchase_request_items', array('request_id' => $request_id));
        // Delete request
        $wpdb->delete($prefix . 'purchase_requests', array('id' => $request_id));

        wp_send_json_success(array('message' => 'تم حذف الطلب بنجاح'));
    }
}
