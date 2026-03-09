<?php
if (!defined('ABSPATH')) exit;

class IW_Withdrawal_Orders {

    public static function init() {
        add_action('wp_ajax_iw_create_withdrawal_order',      array(__CLASS__, 'create_order'));
        add_action('wp_ajax_iw_get_withdrawal_orders',        array(__CLASS__, 'get_orders'));
        add_action('wp_ajax_iw_get_withdrawal_order',         array(__CLASS__, 'get_order'));
        add_action('wp_ajax_iw_approve_withdrawal_order',     array(__CLASS__, 'approve_order'));
        add_action('wp_ajax_iw_reject_withdrawal_order',      array(__CLASS__, 'reject_order'));
        add_action('wp_ajax_iw_update_withdrawal_order',      array(__CLASS__, 'update_order'));
        add_action('wp_ajax_iw_update_order_employee',        array(__CLASS__, 'update_order_employee'));
        add_action('wp_ajax_iw_complete_withdrawal_order',    array(__CLASS__, 'complete_order'));
        add_action('wp_ajax_iw_delete_withdrawal_order',      array(__CLASS__, 'delete_order'));
        add_action('wp_ajax_iw_cancel_withdrawal_order',      array(__CLASS__, 'cancel_order'));
        add_action('wp_ajax_iw_create_custody_order',         array(__CLASS__, 'create_custody_order'));
    }

    /**
     * Generate sequential order number
     */
    private static function generate_order_number() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';
        $year = date('Y');

        $last = $wpdb->get_var(
            "SELECT order_number FROM {$prefix}withdrawal_orders
             WHERE order_number LIKE 'WD-{$year}-%'
             ORDER BY id DESC LIMIT 1"
        );

        if ($last && preg_match('/WD-\d{4}-(\d+)/', $last, $matches)) {
            $num = intval($matches[1]) + 1;
        } else {
            $num = 1;
        }

        return 'WD-' . $year . '-' . str_pad($num, 5, '0', STR_PAD_LEFT);
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

        // Validate stock availability
        $errors = array();
        foreach ($items as $item) {
            $product_id = intval($item['product_id']);
            $product = IW_Products::get_by_id($product_id);
            if (!$product) {
                $errors[] = 'صنف غير موجود';
                continue;
            }
            $available_stock = IW_Products::get_real_stock($product_id);
            $requested_qty = intval($item['quantity']);

            if ($available_stock <= 0) {
                $errors[] = 'الصنف "' . $product->name . '" لا يوجد به رصيد متاح (الرصيد المتاح: ' . $available_stock . ')';
            } elseif ($requested_qty > $available_stock) {
                $errors[] = 'الكمية المطلوبة من "' . $product->name . '" (' . $requested_qty . ') أكبر من الرصيد المتاح (' . $available_stock . ')';
            }
        }

        if (!empty($errors)) {
            wp_send_json_error(array('message' => implode("\n", $errors)));
        }

        $dept_obj = IW_Departments::get_by_id($department_id);
        $emp_obj  = IW_Departments::get_employee_by_id($employee_id);

        // Insert without name columns first (safe if upgrade_tables hasn't run yet)
        $wpdb->insert($prefix . 'withdrawal_orders', array(
            'order_number' => $order_number,
            'department_id'=> $department_id,
            'employee_id'  => $employee_id,
            'status'       => 'pending',
            'notes'        => $notes,
            'created_by'   => get_current_user_id(),
        ));

        $order_id = $wpdb->insert_id;

        if (!$order_id) {
            wp_send_json_error(array('message' => 'فشل حفظ الإذن في قاعدة البيانات: ' . $wpdb->last_error));
        }

        // Store names separately — silently ignored if columns don't exist yet
        $dept_name = $dept_obj ? $dept_obj->name : '';
        $emp_name  = $emp_obj  ? $emp_obj->name  : '';
        if ($dept_name || $emp_name) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$prefix}withdrawal_orders
                 SET department_name = %s, employee_name = %s
                 WHERE id = %d",
                $dept_name, $emp_name, $order_id
            ));
        }

        foreach ($items as $item) {
            $product = IW_Products::get_by_id(intval($item['product_id']));
            $wpdb->insert($prefix . 'withdrawal_order_items', array(
                'order_id'   => $order_id,
                'product_id' => intval($item['product_id']),
                'quantity'   => intval($item['quantity']),
                'unit_price' => $product ? $product->price : 0,
            ));
        }

        // Send email to approvers
        self::notify_approvers($order_number);

        wp_send_json_success(array(
            'order_id'     => $order_id,
            'order_number' => $order_number,
            'message'      => 'تم إنشاء إذن الصرف وإرساله للاعتماد',
        ));
    }

    /**
     * Send email notification to users with approval capability
     */
    private static function notify_approvers($order_number) {
        $subject = 'يوجد إذن صرف جديد يحتاج اعتمادك - رقم: ' . $order_number;
        $admin_url = admin_url('admin.php?page=iw-withdraw-stock');

        $message = "مرحباً،\n\n";
        $message .= "تم إنشاء إذن صرف جديد برقم: " . $order_number . "\n";
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
            "SELECT o.*, u.display_name as created_by_name
             FROM {$prefix}withdrawal_orders o
             LEFT JOIN {$wpdb->users} u ON o.created_by = u.ID
             WHERE 1=1 $where
             ORDER BY o.created_at DESC"
        );

        // Use stored names (saved at creation time) with live lookup fallback
        foreach ($orders as $order) {
            $update = array();

            if (empty($order->department_name) && $order->department_id) {
                $dept = IW_Departments::get_by_id($order->department_id);
                if ($dept) {
                    $order->department_name = $dept->name;
                    $update['department_name'] = $dept->name;
                }
            }

            if (empty($order->employee_name) && $order->employee_id) {
                $emp = IW_Departments::get_employee_by_id($order->employee_id);
                if ($emp) {
                    $order->employee_name = $emp->name;
                    $update['employee_name'] = $emp->name;
                }
            }

            // Backfill the stored name for future requests
            if (!empty($update)) {
                $wpdb->update($prefix . 'withdrawal_orders', $update, array('id' => $order->id));
            }
        }

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
            "SELECT o.*, u.display_name as created_by_name
             FROM {$prefix}withdrawal_orders o
             LEFT JOIN {$wpdb->users} u ON o.created_by = u.ID
             WHERE o.id = %d", $order_id
        ));

        // Use stored names with live lookup fallback (backfills missing old records)
        if ($order) {
            $update = array();

            if (empty($order->department_name) && $order->department_id) {
                $dept = IW_Departments::get_by_id($order->department_id);
                if ($dept) {
                    $order->department_name = $dept->name;
                    $update['department_name'] = $dept->name;
                }
            }

            if (empty($order->employee_name) && $order->employee_id) {
                $emp = IW_Departments::get_employee_by_id($order->employee_id);
                if ($emp) {
                    $order->employee_name = $emp->name;
                    $update['employee_name'] = $emp->name;
                }
            }

            if (!empty($update)) {
                $wpdb->update($prefix . 'withdrawal_orders', $update, array('id' => $order->id));
            }
        }

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, p.name as product_name, p.unit as product_unit, p.current_stock
             FROM {$prefix}withdrawal_order_items i
             LEFT JOIN {$prefix}products p ON i.product_id = p.id
             WHERE i.order_id = %d", $order_id
        ));

        // Update items with REAL stock from transactions (source of truth)
        foreach ($items as &$item) {
            $item->current_stock = IW_Products::get_real_stock($item->product_id);
        }

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

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $order_id = intval($_POST['order_id']);
        $items    = json_decode(stripslashes($_POST['items']), true);

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}withdrawal_orders WHERE id = %d", $order_id
        ));

        $is_admin = current_user_can('manage_options');

        if (!$order || !in_array($order->status, array('pending', 'approved'))) {
            wp_send_json_error(array('message' => 'لا يمكن تعديل هذا الإذن'));
        }

        // Approved orders: admin only
        if ($order->status === 'approved' && !$is_admin) {
            wp_send_json_error(array('message' => 'تعديل الأذون المعتمدة متاح للمدير فقط'));
        }

        // Pending orders: creator, dean, or admin
        $is_creator = ($order->created_by == get_current_user_id());
        if ($order->status === 'pending' && !$is_creator && !current_user_can('iw_approve_orders') && !$is_admin) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية لتعديل هذا الإذن'));
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

        // Get order and check status
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}withdrawal_orders WHERE id = %d", $order_id
        ));

        if (!$order || $order->status !== 'pending') {
            wp_send_json_error(array('message' => 'لا يمكن اعتماد هذا الإذن'));
        }

        // Skip stock validation for custody orders
        if ($order->order_type !== 'custody') {
            // Validate stock availability before approval using AVAILABLE stock
            // (available = real stock minus other approved-but-not-completed reservations)
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT i.*, p.name as product_name FROM {$prefix}withdrawal_order_items i
                 LEFT JOIN {$prefix}products p ON i.product_id = p.id
                 WHERE i.order_id = %d", $order_id
            ));

            $errors = array();
            foreach ($items as $item) {
                // Order is still 'pending', so get_real_stock() doesn't include it yet — correct check
                $available_stock = IW_Products::get_real_stock($item->product_id);
                $requested_qty = $item->approved_quantity !== null ? intval($item->approved_quantity) : intval($item->quantity);

                if ($requested_qty > 0) {
                    if ($available_stock <= 0) {
                        $errors[] = 'الصنف "' . $item->product_name . '" لا يوجد به رصيد متاح (الرصيد: 0) - لا يمكن اعتماده';
                    } elseif ($requested_qty > $available_stock) {
                        $errors[] = 'الكمية المطلوبة من "' . $item->product_name . '" (' . $requested_qty . ') أكبر من الرصيد المتاح (' . $available_stock . ')';
                    }
                }
            }

            if (!empty($errors)) {
                wp_send_json_error(array('message' => "لا يمكن اعتماد الإذن:\n" . implode("\n", $errors)));
            }
        }

        $signature_url = get_user_meta($user_id, 'iw_signature_url', true);
        // Admin can approve without signature, dean must have signature
        if (empty($signature_url) && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'يجب رفع التوقيع الإلكتروني أولاً من صفحة "التوقيع الإلكتروني"'));
        }

        $wpdb->update($prefix . 'withdrawal_orders', array(
            'status'        => 'approved',
            'approved_by'   => $user_id,
            'approved_at'   => current_time('mysql'),
            'signature_url' => $signature_url ?: '',
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
        $prefix = $wpdb->prefix . 'iw_';
        $order_id = intval($_POST['order_id']);

        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$prefix}withdrawal_orders WHERE id = %d", $order_id));
        if (!$order || !in_array($order->status, array('pending', 'approved'))) {
            wp_send_json_error(array('message' => 'لا يمكن رفض هذا الإذن بحالته الحالية'));
        }

        $rejection_reason = sanitize_textarea_field($_POST['rejection_reason'] ?? '');
        $wpdb->update($prefix . 'withdrawal_orders', array(
            'status'           => 'rejected',
            'approved_by'      => get_current_user_id(),
            'approved_at'      => current_time('mysql'),
            'rejection_reason' => $rejection_reason,
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

        // Handle custody orders differently
        if ($order->order_type === 'custody') {
            if (self::complete_custody_order($order_id)) {
                wp_send_json_success(array('message' => 'تم تنفيذ إذن صرف العهدة بنجاح (بدون خصم من الرصيد)'));
            } else {
                wp_send_json_error(array('message' => 'حدث خطأ أثناء تنفيذ إذن العهدة'));
            }
            return;
        }

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$prefix}withdrawal_order_items WHERE order_id = %d", $order_id
        ));

        // First pass: validate all items have sufficient stock.
        // Pass $order_id so get_real_stock() excludes THIS order's approved qty —
        // completing an approved order is a status change, not an additional deduction.
        $errors = array();
        foreach ($items as $item) {
            $qty = $item->approved_quantity !== null ? intval($item->approved_quantity) : intval($item->quantity);
            if ($qty > 0) {
                $product = IW_Products::get_by_id($item->product_id);
                $real_stock = IW_Products::get_real_stock($item->product_id, $order_id);
                if ($real_stock < $qty) {
                    $errors[] = 'الصنف "' . ($product ? $product->name : 'غير معروف') . '" الرصيد غير كافي (المتاح: ' . $real_stock . '، المطلوب: ' . $qty . ')';
                }
            }
        }

        if (!empty($errors)) {
            wp_send_json_error(array('message' => implode("\n", $errors)));
        }

        // Second pass: execute withdrawals
        foreach ($items as $item) {
            $qty = $item->approved_quantity !== null ? $item->approved_quantity : $item->quantity;
            if ($qty > 0) {
                $result = IW_Transactions::withdraw_fifo($item->product_id, $qty);

                // Check if withdraw_fifo returned an error
                if (is_wp_error($result)) {
                    wp_send_json_error(array('message' => $result->get_error_message()));
                }

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

        wp_send_json_success(array('message' => 'تم تنفيذ إذن الصرف بنجاح'));
    }

    /**
     * Delete pending order
     */
    public static function delete_order() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $order_id = intval($_POST['order_id']);
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}withdrawal_orders WHERE id = %d", $order_id
        ));

        if (!$order) {
            wp_send_json_error(array('message' => 'الإذن غير موجود'));
        }

        if ($order->status !== 'pending') {
            wp_send_json_error(array('message' => 'لا يمكن حذف إذن معتمد أو منفذ'));
        }

        // Allow creator, dean, or admin to delete pending orders
        $is_creator = ($order->created_by == get_current_user_id());
        if (!$is_creator && !current_user_can('iw_approve_orders') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية لحذف هذا الإذن'));
        }

        // Delete items first
        $wpdb->delete($prefix . 'withdrawal_order_items', array('order_id' => $order_id));
        // Delete order
        $wpdb->delete($prefix . 'withdrawal_orders', array('id' => $order_id));

        wp_send_json_success(array('message' => 'تم حذف الإذن بنجاح'));
    }

    /**
     * Cancel approved order (return stock)
     */
    public static function cancel_order() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $order_id = intval($_POST['order_id']);
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}withdrawal_orders WHERE id = %d", $order_id
        ));

        if (!$order) {
            wp_send_json_error(array('message' => 'الإذن غير موجود'));
        }

        if ($order->status !== 'approved') {
            wp_send_json_error(array('message' => 'يمكن إلغاء الأذون المعتمدة فقط (قبل التنفيذ)'));
        }

        // Allow dean or admin to cancel approved orders
        if (!current_user_can('iw_approve_orders') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية لإلغاء هذا الإذن'));
        }

        // Update order status to cancelled
        $wpdb->update($prefix . 'withdrawal_orders', array(
            'status'       => 'cancelled',
            'cancelled_by' => get_current_user_id(),
            'cancelled_at' => current_time('mysql'),
        ), array('id' => $order_id));

        wp_send_json_success(array('message' => 'تم إلغاء الإذن بنجاح. الرصيد لم يتم خصمه لأن الإذن لم ينفذ.'));
    }

    /**
     * Create custody withdrawal order
     * Custody orders don't deduct from stock
     */
    public static function create_custody_order() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!IW_Permissions::current_user_can('withdraw_stock', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $order_number  = self::generate_custody_order_number();
        $department_id = intval($_POST['department_id']);
        $employee_id   = intval($_POST['employee_id']);
        $notes         = sanitize_textarea_field($_POST['notes'] ?? '');
        $items         = json_decode(stripslashes($_POST['items']), true);

        if (empty($items)) {
            wp_send_json_error(array('message' => 'يجب إضافة أصناف'));
        }

        $dept_obj = IW_Departments::get_by_id($department_id);
        $emp_obj  = IW_Departments::get_employee_by_id($employee_id);

        $wpdb->insert($prefix . 'withdrawal_orders', array(
            'order_number' => $order_number,
            'order_type'   => 'custody',
            'department_id'=> $department_id,
            'employee_id'  => $employee_id,
            'status'       => 'pending',
            'notes'        => $notes,
            'created_by'   => get_current_user_id(),
        ));

        $order_id = $wpdb->insert_id;

        if (!$order_id) {
            wp_send_json_error(array('message' => 'فشل حفظ إذن العهدة في قاعدة البيانات: ' . $wpdb->last_error));
        }

        $dept_name = $dept_obj ? $dept_obj->name : '';
        $emp_name  = $emp_obj  ? $emp_obj->name  : '';
        if ($dept_name || $emp_name) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$prefix}withdrawal_orders
                 SET department_name = %s, employee_name = %s
                 WHERE id = %d",
                $dept_name, $emp_name, $order_id
            ));
        }

        foreach ($items as $item) {
            $product = IW_Products::get_by_id(intval($item['product_id']));
            $wpdb->insert($prefix . 'withdrawal_order_items', array(
                'order_id'              => $order_id,
                'product_id'            => intval($item['product_id']),
                'quantity'              => intval($item['quantity']),
                'unit_price'            => $product ? $product->price : 0,
                'custody_employee_name' => sanitize_text_field($item['custody_employee_name'] ?? ''),
            ));
        }

        // Send email to approvers
        self::notify_approvers($order_number);

        wp_send_json_success(array(
            'order_id'     => $order_id,
            'order_number' => $order_number,
            'message'      => 'تم إنشاء إذن صرف العهدة وإرساله للاعتماد',
        ));
    }

    /**
     * Generate sequential custody order number
     */
    private static function generate_custody_order_number() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';
        $year = date('Y');

        $last = $wpdb->get_var(
            "SELECT order_number FROM {$prefix}withdrawal_orders
             WHERE order_number LIKE 'CWD-{$year}-%'
             ORDER BY id DESC LIMIT 1"
        );

        if ($last && preg_match('/CWD-\d{4}-(\d+)/', $last, $matches)) {
            $num = intval($matches[1]) + 1;
        } else {
            $num = 1;
        }

        return 'CWD-' . $year . '-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Complete custody order (doesn't deduct stock)
     */
    public static function complete_custody_order($order_id) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}withdrawal_orders WHERE id = %d", $order_id
        ));

        if (!$order || $order->status !== 'approved' || $order->order_type !== 'custody') {
            return false;
        }

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$prefix}withdrawal_order_items WHERE order_id = %d", $order_id
        ));

        // Record transactions without deducting stock
        foreach ($items as $item) {
            $qty = $item->approved_quantity !== null ? $item->approved_quantity : $item->quantity;
            if ($qty > 0) {
                $wpdb->insert($prefix . 'transactions', array(
                    'transaction_type' => 'custody',
                    'product_id'       => $item->product_id,
                    'quantity'         => $qty,
                    'unit_price'       => $item->unit_price,
                    'department_id'    => $order->department_id,
                    'employee_id'      => $order->employee_id,
                    'notes'            => 'إذن صرف عهدة رقم: ' . $order->order_number . ' - ' . $item->custody_employee_name,
                    'created_by'       => get_current_user_id(),
                ));
            }
        }

        $wpdb->update($prefix . 'withdrawal_orders',
            array('status' => 'completed'),
            array('id' => $order_id)
        );

        return true;
    }

    /**
     * Admin-only: update department_name / employee_name on any order regardless of status.
     * Does NOT touch items, quantities, or status — only the human-readable name fields.
     */
    public static function update_order_employee() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'هذه الميزة للمدير فقط'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $order_id        = intval($_POST['order_id']);
        $department_name = sanitize_text_field($_POST['department_name'] ?? '');
        $employee_name   = sanitize_text_field($_POST['employee_name']   ?? '');

        if (!$order_id) {
            wp_send_json_error(array('message' => 'رقم إذن غير صحيح'));
        }

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$prefix}withdrawal_orders WHERE id = %d", $order_id
        ));

        if (!$order) {
            wp_send_json_error(array('message' => 'الإذن غير موجود'));
        }

        $wpdb->update(
            $prefix . 'withdrawal_orders',
            array(
                'department_name' => $department_name,
                'employee_name'   => $employee_name,
            ),
            array('id' => $order_id)
        );

        wp_send_json_success(array('message' => 'تم تحديث بيانات الموظف'));
    }
}
