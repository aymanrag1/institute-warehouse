<?php
/**
 * Transactions Management Class
 *
 * إدارة حركات المخزون - إذن إضافة وإذن صرف مع نظام FIFO
 */

if (!defined('ABSPATH')) {
    exit;
}

class IW_Transactions {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        // إذن الإضافة
        add_action('wp_ajax_iw_create_add_permit', array($this, 'ajax_create_add_permit'));
        add_action('wp_ajax_iw_get_add_permit', array($this, 'ajax_get_add_permit'));
        add_action('wp_ajax_iw_get_add_permits', array($this, 'ajax_get_add_permits'));
        add_action('wp_ajax_iw_approve_add_permit', array($this, 'ajax_approve_add_permit'));
        add_action('wp_ajax_iw_cancel_add_permit', array($this, 'ajax_cancel_add_permit'));

        // إذن الصرف
        add_action('wp_ajax_iw_create_withdraw_permit', array($this, 'ajax_create_withdraw_permit'));
        add_action('wp_ajax_iw_get_withdraw_permit', array($this, 'ajax_get_withdraw_permit'));
        add_action('wp_ajax_iw_get_withdraw_permits', array($this, 'ajax_get_withdraw_permits'));
        add_action('wp_ajax_iw_approve_withdraw_permit', array($this, 'ajax_approve_withdraw_permit'));
        add_action('wp_ajax_iw_deliver_withdraw_permit', array($this, 'ajax_deliver_withdraw_permit'));
        add_action('wp_ajax_iw_cancel_withdraw_permit', array($this, 'ajax_cancel_withdraw_permit'));

        // الحركات
        add_action('wp_ajax_iw_get_transactions', array($this, 'ajax_get_transactions'));
        add_action('wp_ajax_iw_get_product_transactions', array($this, 'ajax_get_product_transactions'));
    }

    /**
     * توليد رقم إذن جديد
     */
    public static function generate_permit_number($type = 'add') {
        global $wpdb;

        $prefix = $type === 'add'
            ? IW_Database::get_setting('permit_prefix_add', 'ADD')
            : IW_Database::get_setting('permit_prefix_withdraw', 'WD');

        $year = date('Y');
        $month = date('m');

        // الحصول على آخر رقم
        $table = $type === 'add'
            ? $wpdb->prefix . 'iw_add_permits'
            : $wpdb->prefix . 'iw_withdraw_permits';

        $last_number = $wpdb->get_var($wpdb->prepare(
            "SELECT permit_number FROM $table
             WHERE permit_number LIKE %s
             ORDER BY id DESC LIMIT 1",
            $prefix . '-' . $year . $month . '%'
        ));

        if ($last_number) {
            $parts = explode('-', $last_number);
            $sequence = (int) substr(end($parts), -4) + 1;
        } else {
            $sequence = 1;
        }

        return $prefix . '-' . $year . $month . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // ==================== إذن الإضافة ====================

    /**
     * إنشاء إذن إضافة جديد
     */
    public static function create_add_permit($data) {
        global $wpdb;

        $permits_table = $wpdb->prefix . 'iw_add_permits';
        $items_table = $wpdb->prefix . 'iw_add_permit_items';

        $wpdb->query('START TRANSACTION');

        try {
            // إنشاء الإذن
            $permit_data = array(
                'permit_number' => self::generate_permit_number('add'),
                'warehouse_id' => absint($data['warehouse_id']),
                'supplier_id' => isset($data['supplier_id']) ? absint($data['supplier_id']) : null,
                'supplier_name' => isset($data['supplier_name']) ? sanitize_text_field($data['supplier_name']) : '',
                'invoice_number' => isset($data['invoice_number']) ? sanitize_text_field($data['invoice_number']) : '',
                'invoice_date' => isset($data['invoice_date']) && !empty($data['invoice_date']) ? $data['invoice_date'] : null,
                'notes' => isset($data['notes']) ? sanitize_textarea_field($data['notes']) : '',
                'created_by' => get_current_user_id(),
                'status' => 'pending'
            );

            $wpdb->insert($permits_table, $permit_data);
            $permit_id = $wpdb->insert_id;

            if (!$permit_id) {
                throw new Exception('فشل في إنشاء إذن الإضافة');
            }

            // إضافة الأصناف
            $total_amount = 0;
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $quantity = absint($item['quantity']);
                    $unit_price = floatval($item['unit_price']);
                    $total_price = $quantity * $unit_price;
                    $total_amount += $total_price;

                    $wpdb->insert($items_table, array(
                        'permit_id' => $permit_id,
                        'product_id' => absint($item['product_id']),
                        'quantity' => $quantity,
                        'unit_price' => $unit_price,
                        'total_price' => $total_price,
                        'batch_number' => isset($item['batch_number']) ? sanitize_text_field($item['batch_number']) : '',
                        'expiry_date' => isset($item['expiry_date']) && !empty($item['expiry_date']) ? $item['expiry_date'] : null,
                        'storage_location' => isset($item['storage_location']) ? sanitize_text_field($item['storage_location']) : '',
                        'notes' => isset($item['notes']) ? sanitize_textarea_field($item['notes']) : ''
                    ));
                }
            }

            // تحديث إجمالي المبلغ
            $wpdb->update($permits_table, array('total_amount' => $total_amount), array('id' => $permit_id));

            $wpdb->query('COMMIT');

            IW_Database::log_activity('create', 'add_permit', $permit_id, $permit_data);

            return array(
                'id' => $permit_id,
                'permit_number' => $permit_data['permit_number']
            );

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('create_failed', $e->getMessage());
        }
    }

    /**
     * اعتماد إذن الإضافة (إضافة المخزون الفعلي)
     */
    public static function approve_add_permit($permit_id) {
        global $wpdb;

        $permits_table = $wpdb->prefix . 'iw_add_permits';
        $items_table = $wpdb->prefix . 'iw_add_permit_items';
        $stock_table = $wpdb->prefix . 'iw_stock';
        $transactions_table = $wpdb->prefix . 'iw_transactions';

        // التحقق من حالة الإذن
        $permit = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $permits_table WHERE id = %d",
            $permit_id
        ));

        if (!$permit) {
            return new WP_Error('not_found', 'الإذن غير موجود');
        }

        if ($permit->status !== 'pending') {
            return new WP_Error('invalid_status', 'لا يمكن اعتماد هذا الإذن');
        }

        $wpdb->query('START TRANSACTION');

        try {
            // الحصول على الأصناف
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $items_table WHERE permit_id = %d",
                $permit_id
            ));

            foreach ($items as $item) {
                // إضافة للمخزون
                $wpdb->insert($stock_table, array(
                    'product_id' => $item->product_id,
                    'warehouse_id' => $permit->warehouse_id,
                    'batch_number' => $item->batch_number,
                    'quantity' => $item->quantity,
                    'remaining_quantity' => $item->quantity,
                    'purchase_price' => $item->unit_price,
                    'expiry_date' => $item->expiry_date,
                    'supplier_id' => $permit->supplier_id,
                    'permit_id' => $permit_id,
                    'storage_location' => $item->storage_location,
                    'status' => 'available'
                ));

                $stock_id = $wpdb->insert_id;

                // تسجيل الحركة
                $wpdb->insert($transactions_table, array(
                    'transaction_type' => 'add',
                    'product_id' => $item->product_id,
                    'stock_id' => $stock_id,
                    'warehouse_id' => $permit->warehouse_id,
                    'quantity' => $item->quantity,
                    'quantity_before' => 0,
                    'quantity_after' => $item->quantity,
                    'permit_id' => $permit_id,
                    'permit_type' => 'add',
                    'reference_number' => $permit->permit_number,
                    'supplier_id' => $permit->supplier_id,
                    'notes' => 'إضافة بموجب إذن إضافة رقم ' . $permit->permit_number,
                    'created_by' => get_current_user_id()
                ));
            }

            // تحديث حالة الإذن
            $wpdb->update(
                $permits_table,
                array(
                    'status' => 'approved',
                    'approved_by' => get_current_user_id(),
                    'approved_at' => current_time('mysql')
                ),
                array('id' => $permit_id)
            );

            $wpdb->query('COMMIT');

            IW_Database::log_activity('approve', 'add_permit', $permit_id);

            return true;

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('approve_failed', $e->getMessage());
        }
    }

    /**
     * الحصول على إذن إضافة
     */
    public static function get_add_permit($id) {
        global $wpdb;
        $permits_table = $wpdb->prefix . 'iw_add_permits';
        $items_table = $wpdb->prefix . 'iw_add_permit_items';
        $products_table = $wpdb->prefix . 'iw_products';
        $warehouses_table = $wpdb->prefix . 'iw_warehouses';
        $suppliers_table = $wpdb->prefix . 'iw_suppliers';

        $permit = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, w.name as warehouse_name, s.name as supplier_full_name,
                    u1.display_name as created_by_name, u2.display_name as approved_by_name
             FROM $permits_table p
             LEFT JOIN $warehouses_table w ON p.warehouse_id = w.id
             LEFT JOIN $suppliers_table s ON p.supplier_id = s.id
             LEFT JOIN {$wpdb->users} u1 ON p.created_by = u1.ID
             LEFT JOIN {$wpdb->users} u2 ON p.approved_by = u2.ID
             WHERE p.id = %d",
            $id
        ));

        if ($permit) {
            $permit->items = $wpdb->get_results($wpdb->prepare(
                "SELECT i.*, pr.name as product_name, pr.sku as product_sku, pr.unit
                 FROM $items_table i
                 LEFT JOIN $products_table pr ON i.product_id = pr.id
                 WHERE i.permit_id = %d",
                $id
            ));
        }

        return $permit;
    }

    /**
     * الحصول على قائمة إذونات الإضافة
     */
    public static function get_add_permits($args = array()) {
        global $wpdb;
        $permits_table = $wpdb->prefix . 'iw_add_permits';
        $warehouses_table = $wpdb->prefix . 'iw_warehouses';

        $defaults = array(
            'warehouse_id' => 0,
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 20,
            'page' => 1
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['warehouse_id'])) {
            $where[] = 'p.warehouse_id = %d';
            $values[] = $args['warehouse_id'];
        }

        if (!empty($args['status'])) {
            $where[] = 'p.status = %s';
            $values[] = $args['status'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'DATE(p.created_at) >= %s';
            $values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'DATE(p.created_at) <= %s';
            $values[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];

        $sql = "SELECT p.*, w.name as warehouse_name
                FROM $permits_table p
                LEFT JOIN $warehouses_table w ON p.warehouse_id = w.id
                WHERE $where_clause
                ORDER BY p.created_at DESC
                LIMIT %d OFFSET %d";

        $values[] = $args['per_page'];
        $values[] = $offset;

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    // ==================== إذن الصرف ====================

    /**
     * إنشاء إذن صرف جديد
     */
    public static function create_withdraw_permit($data) {
        global $wpdb;

        $permits_table = $wpdb->prefix . 'iw_withdraw_permits';
        $items_table = $wpdb->prefix . 'iw_withdraw_permit_items';

        $wpdb->query('START TRANSACTION');

        try {
            // إنشاء الإذن
            $permit_data = array(
                'permit_number' => self::generate_permit_number('withdraw'),
                'warehouse_id' => absint($data['warehouse_id']),
                'department_id' => isset($data['department_id']) ? absint($data['department_id']) : null,
                'employee_id' => isset($data['employee_id']) ? absint($data['employee_id']) : null,
                'employee_name' => isset($data['employee_name']) ? sanitize_text_field($data['employee_name']) : '',
                'purpose' => isset($data['purpose']) ? sanitize_textarea_field($data['purpose']) : '',
                'notes' => isset($data['notes']) ? sanitize_textarea_field($data['notes']) : '',
                'storage_location' => isset($data['storage_location']) ? sanitize_text_field($data['storage_location']) : '',
                'created_by' => get_current_user_id(),
                'status' => 'pending'
            );

            $wpdb->insert($permits_table, $permit_data);
            $permit_id = $wpdb->insert_id;

            if (!$permit_id) {
                throw new Exception('فشل في إنشاء إذن الصرف');
            }

            // إضافة الأصناف
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $wpdb->insert($items_table, array(
                        'permit_id' => $permit_id,
                        'product_id' => absint($item['product_id']),
                        'requested_quantity' => absint($item['quantity']),
                        'storage_location' => isset($item['storage_location']) ? sanitize_text_field($item['storage_location']) : '',
                        'notes' => isset($item['notes']) ? sanitize_textarea_field($item['notes']) : ''
                    ));
                }
            }

            $wpdb->query('COMMIT');

            IW_Database::log_activity('create', 'withdraw_permit', $permit_id, $permit_data);

            return array(
                'id' => $permit_id,
                'permit_number' => $permit_data['permit_number']
            );

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('create_failed', $e->getMessage());
        }
    }

    /**
     * اعتماد إذن الصرف
     */
    public static function approve_withdraw_permit($permit_id, $approved_quantities = array()) {
        global $wpdb;

        $permits_table = $wpdb->prefix . 'iw_withdraw_permits';
        $items_table = $wpdb->prefix . 'iw_withdraw_permit_items';

        // التحقق من حالة الإذن
        $permit = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $permits_table WHERE id = %d",
            $permit_id
        ));

        if (!$permit) {
            return new WP_Error('not_found', 'الإذن غير موجود');
        }

        if ($permit->status !== 'pending') {
            return new WP_Error('invalid_status', 'لا يمكن اعتماد هذا الإذن');
        }

        // تحديث الكميات المعتمدة
        if (!empty($approved_quantities)) {
            foreach ($approved_quantities as $item_id => $quantity) {
                $wpdb->update(
                    $items_table,
                    array('approved_quantity' => absint($quantity)),
                    array('id' => absint($item_id))
                );
            }
        } else {
            // اعتماد الكميات المطلوبة كما هي
            $wpdb->query($wpdb->prepare(
                "UPDATE $items_table SET approved_quantity = requested_quantity WHERE permit_id = %d",
                $permit_id
            ));
        }

        // تحديث حالة الإذن
        $wpdb->update(
            $permits_table,
            array(
                'status' => 'approved',
                'approved_by' => get_current_user_id(),
                'approved_at' => current_time('mysql')
            ),
            array('id' => $permit_id)
        );

        IW_Database::log_activity('approve', 'withdraw_permit', $permit_id);

        return true;
    }

    /**
     * تسليم إذن الصرف (الخصم الفعلي من المخزون باستخدام FIFO)
     */
    public static function deliver_withdraw_permit($permit_id) {
        global $wpdb;

        $permits_table = $wpdb->prefix . 'iw_withdraw_permits';
        $items_table = $wpdb->prefix . 'iw_withdraw_permit_items';
        $stock_table = $wpdb->prefix . 'iw_stock';
        $transactions_table = $wpdb->prefix . 'iw_transactions';

        // التحقق من حالة الإذن
        $permit = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $permits_table WHERE id = %d",
            $permit_id
        ));

        if (!$permit) {
            return new WP_Error('not_found', 'الإذن غير موجود');
        }

        if ($permit->status !== 'approved') {
            return new WP_Error('invalid_status', 'يجب اعتماد الإذن أولاً');
        }

        $wpdb->query('START TRANSACTION');

        try {
            // الحصول على الأصناف
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $items_table WHERE permit_id = %d AND approved_quantity > 0",
                $permit_id
            ));

            foreach ($items as $item) {
                $quantity_to_withdraw = $item->approved_quantity;
                $quantity_withdrawn = 0;

                // الحصول على المخزون المتاح باستخدام FIFO (الأقدم أولاً)
                $available_stock = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $stock_table
                     WHERE product_id = %d
                     AND warehouse_id = %d
                     AND status = 'available'
                     AND remaining_quantity > 0
                     ORDER BY received_date ASC",
                    $item->product_id,
                    $permit->warehouse_id
                ));

                if (empty($available_stock)) {
                    throw new Exception('لا يوجد مخزون كافي للصنف ID: ' . $item->product_id);
                }

                foreach ($available_stock as $stock) {
                    if ($quantity_to_withdraw <= 0) {
                        break;
                    }

                    $withdraw_from_this = min($stock->remaining_quantity, $quantity_to_withdraw);
                    $new_remaining = $stock->remaining_quantity - $withdraw_from_this;

                    // تحديث المخزون
                    $wpdb->update(
                        $stock_table,
                        array(
                            'remaining_quantity' => $new_remaining,
                            'status' => $new_remaining <= 0 ? 'depleted' : 'available'
                        ),
                        array('id' => $stock->id)
                    );

                    // تسجيل الحركة
                    $wpdb->insert($transactions_table, array(
                        'transaction_type' => 'withdraw',
                        'product_id' => $item->product_id,
                        'stock_id' => $stock->id,
                        'warehouse_id' => $permit->warehouse_id,
                        'quantity' => $withdraw_from_this,
                        'quantity_before' => $stock->remaining_quantity,
                        'quantity_after' => $new_remaining,
                        'permit_id' => $permit_id,
                        'permit_type' => 'withdraw',
                        'reference_number' => $permit->permit_number,
                        'department_id' => $permit->department_id,
                        'employee_id' => $permit->employee_id,
                        'notes' => 'صرف بموجب إذن صرف رقم ' . $permit->permit_number,
                        'created_by' => get_current_user_id()
                    ));

                    $quantity_to_withdraw -= $withdraw_from_this;
                    $quantity_withdrawn += $withdraw_from_this;
                }

                // تحديث الكمية المسلمة
                $wpdb->update(
                    $items_table,
                    array('delivered_quantity' => $quantity_withdrawn),
                    array('id' => $item->id)
                );

                if ($quantity_to_withdraw > 0) {
                    throw new Exception('الكمية المتاحة غير كافية للصنف ID: ' . $item->product_id);
                }
            }

            // تحديث حالة الإذن
            $wpdb->update(
                $permits_table,
                array(
                    'status' => 'delivered',
                    'delivered_at' => current_time('mysql')
                ),
                array('id' => $permit_id)
            );

            $wpdb->query('COMMIT');

            IW_Database::log_activity('deliver', 'withdraw_permit', $permit_id);

            // فحص المنتجات منخفضة المخزون
            self::check_low_stock_alerts($items, $permit->warehouse_id);

            return true;

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('deliver_failed', $e->getMessage());
        }
    }

    /**
     * فحص وإنشاء تنبيهات المخزون المنخفض
     */
    private static function check_low_stock_alerts($items, $warehouse_id) {
        global $wpdb;
        $alerts_table = $wpdb->prefix . 'iw_alerts';
        $products_table = $wpdb->prefix . 'iw_products';
        $stock_table = $wpdb->prefix . 'iw_stock';

        foreach ($items as $item) {
            // الحصول على بيانات المنتج
            $product = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $products_table WHERE id = %d",
                $item->product_id
            ));

            if (!$product) continue;

            // الحصول على الكمية الحالية
            $current_stock = $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(remaining_quantity), 0)
                 FROM $stock_table
                 WHERE product_id = %d AND warehouse_id = %d AND status = 'available'",
                $item->product_id,
                $warehouse_id
            ));

            // فحص نفاذ المخزون
            if ($current_stock == 0) {
                // التحقق من عدم وجود تنبيه سابق
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $alerts_table
                     WHERE product_id = %d AND warehouse_id = %d
                     AND alert_type = 'out_of_stock' AND is_resolved = 0",
                    $item->product_id,
                    $warehouse_id
                ));

                if (!$existing) {
                    $wpdb->insert($alerts_table, array(
                        'alert_type' => 'out_of_stock',
                        'product_id' => $item->product_id,
                        'warehouse_id' => $warehouse_id,
                        'message' => sprintf('نفذ المخزون من الصنف: %s', $product->name)
                    ));
                }
            }
            // فحص المخزون المنخفض
            elseif ($product->reorder_level > 0 && $current_stock <= $product->reorder_level) {
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $alerts_table
                     WHERE product_id = %d AND warehouse_id = %d
                     AND alert_type IN ('low_stock', 'reorder') AND is_resolved = 0",
                    $item->product_id,
                    $warehouse_id
                ));

                if (!$existing) {
                    $wpdb->insert($alerts_table, array(
                        'alert_type' => 'low_stock',
                        'product_id' => $item->product_id,
                        'warehouse_id' => $warehouse_id,
                        'message' => sprintf('المخزون منخفض للصنف: %s (الكمية الحالية: %d، حد الطلب: %d)',
                            $product->name, $current_stock, $product->reorder_level)
                    ));
                }
            }
        }
    }

    /**
     * الحصول على إذن صرف
     */
    public static function get_withdraw_permit($id) {
        global $wpdb;
        $permits_table = $wpdb->prefix . 'iw_withdraw_permits';
        $items_table = $wpdb->prefix . 'iw_withdraw_permit_items';
        $products_table = $wpdb->prefix . 'iw_products';
        $warehouses_table = $wpdb->prefix . 'iw_warehouses';
        $departments_table = $wpdb->prefix . 'iw_departments';
        $employees_table = $wpdb->prefix . 'iw_employees';

        $permit = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, w.name as warehouse_name, d.name as department_name, e.name as employee_full_name,
                    u1.display_name as created_by_name, u2.display_name as approved_by_name
             FROM $permits_table p
             LEFT JOIN $warehouses_table w ON p.warehouse_id = w.id
             LEFT JOIN $departments_table d ON p.department_id = d.id
             LEFT JOIN $employees_table e ON p.employee_id = e.id
             LEFT JOIN {$wpdb->users} u1 ON p.created_by = u1.ID
             LEFT JOIN {$wpdb->users} u2 ON p.approved_by = u2.ID
             WHERE p.id = %d",
            $id
        ));

        if ($permit) {
            $permit->items = $wpdb->get_results($wpdb->prepare(
                "SELECT i.*, pr.name as product_name, pr.sku as product_sku, pr.unit, pr.storage_location as default_location
                 FROM $items_table i
                 LEFT JOIN $products_table pr ON i.product_id = pr.id
                 WHERE i.permit_id = %d",
                $id
            ));
        }

        return $permit;
    }

    /**
     * الحصول على قائمة إذونات الصرف
     */
    public static function get_withdraw_permits($args = array()) {
        global $wpdb;
        $permits_table = $wpdb->prefix . 'iw_withdraw_permits';
        $warehouses_table = $wpdb->prefix . 'iw_warehouses';
        $departments_table = $wpdb->prefix . 'iw_departments';

        $defaults = array(
            'warehouse_id' => 0,
            'department_id' => 0,
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 20,
            'page' => 1
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['warehouse_id'])) {
            $where[] = 'p.warehouse_id = %d';
            $values[] = $args['warehouse_id'];
        }

        if (!empty($args['department_id'])) {
            $where[] = 'p.department_id = %d';
            $values[] = $args['department_id'];
        }

        if (!empty($args['status'])) {
            $where[] = 'p.status = %s';
            $values[] = $args['status'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'DATE(p.created_at) >= %s';
            $values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'DATE(p.created_at) <= %s';
            $values[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];

        $sql = "SELECT p.*, w.name as warehouse_name, d.name as department_name
                FROM $permits_table p
                LEFT JOIN $warehouses_table w ON p.warehouse_id = w.id
                LEFT JOIN $departments_table d ON p.department_id = d.id
                WHERE $where_clause
                ORDER BY p.created_at DESC
                LIMIT %d OFFSET %d";

        $values[] = $args['per_page'];
        $values[] = $offset;

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * إلغاء إذن
     */
    public static function cancel_permit($permit_id, $type = 'add') {
        global $wpdb;

        $table = $type === 'add'
            ? $wpdb->prefix . 'iw_add_permits'
            : $wpdb->prefix . 'iw_withdraw_permits';

        $permit = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $permit_id
        ));

        if (!$permit) {
            return new WP_Error('not_found', 'الإذن غير موجود');
        }

        if ($permit->status === 'cancelled') {
            return new WP_Error('already_cancelled', 'الإذن ملغي بالفعل');
        }

        if (($type === 'add' && $permit->status === 'approved') ||
            ($type === 'withdraw' && $permit->status === 'delivered')) {
            return new WP_Error('cannot_cancel', 'لا يمكن إلغاء هذا الإذن لأنه تم تنفيذه');
        }

        $wpdb->update(
            $table,
            array('status' => 'cancelled'),
            array('id' => $permit_id)
        );

        IW_Database::log_activity('cancel', $type . '_permit', $permit_id);

        return true;
    }

    /**
     * الحصول على الحركات
     */
    public static function get_transactions($args = array()) {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'iw_transactions';
        $products_table = $wpdb->prefix . 'iw_products';
        $warehouses_table = $wpdb->prefix . 'iw_warehouses';

        $defaults = array(
            'product_id' => 0,
            'warehouse_id' => 0,
            'transaction_type' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 50,
            'page' => 1
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['product_id'])) {
            $where[] = 't.product_id = %d';
            $values[] = $args['product_id'];
        }

        if (!empty($args['warehouse_id'])) {
            $where[] = 't.warehouse_id = %d';
            $values[] = $args['warehouse_id'];
        }

        if (!empty($args['transaction_type'])) {
            $where[] = 't.transaction_type = %s';
            $values[] = $args['transaction_type'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'DATE(t.created_at) >= %s';
            $values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'DATE(t.created_at) <= %s';
            $values[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];

        $sql = "SELECT t.*, p.name as product_name, p.sku as product_sku, w.name as warehouse_name,
                       u.display_name as created_by_name
                FROM $transactions_table t
                LEFT JOIN $products_table p ON t.product_id = p.id
                LEFT JOIN $warehouses_table w ON t.warehouse_id = w.id
                LEFT JOIN {$wpdb->users} u ON t.created_by = u.ID
                WHERE $where_clause
                ORDER BY t.created_at DESC
                LIMIT %d OFFSET %d";

        $values[] = $args['per_page'];
        $values[] = $offset;

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    // ==================== AJAX Handlers ====================

    public function ajax_create_add_permit() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_add_stock')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $data = array(
            'warehouse_id' => isset($_POST['warehouse_id']) ? absint($_POST['warehouse_id']) : 0,
            'supplier_id' => isset($_POST['supplier_id']) ? absint($_POST['supplier_id']) : 0,
            'supplier_name' => isset($_POST['supplier_name']) ? sanitize_text_field($_POST['supplier_name']) : '',
            'invoice_number' => isset($_POST['invoice_number']) ? sanitize_text_field($_POST['invoice_number']) : '',
            'invoice_date' => isset($_POST['invoice_date']) ? sanitize_text_field($_POST['invoice_date']) : '',
            'notes' => isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '',
            'items' => isset($_POST['items']) ? $_POST['items'] : array()
        );

        if (empty($data['warehouse_id'])) {
            wp_send_json_error(array('message' => 'يرجى اختيار المخزن'));
        }

        if (empty($data['items'])) {
            wp_send_json_error(array('message' => 'يرجى إضافة أصناف'));
        }

        $result = self::create_add_permit($data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => 'تم إنشاء إذن الإضافة بنجاح',
            'permit_id' => $result['id'],
            'permit_number' => $result['permit_number']
        ));
    }

    public function ajax_get_add_permit() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_warehouse')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف الإذن مطلوب'));
        }

        $permit = self::get_add_permit($id);

        if (!$permit) {
            wp_send_json_error(array('message' => 'الإذن غير موجود'));
        }

        wp_send_json_success($permit);
    }

    public function ajax_get_add_permits() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_warehouse')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $args = array(
            'warehouse_id' => isset($_POST['warehouse_id']) ? absint($_POST['warehouse_id']) : 0,
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '',
            'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '',
            'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '',
            'per_page' => isset($_POST['per_page']) ? absint($_POST['per_page']) : 20,
            'page' => isset($_POST['page']) ? absint($_POST['page']) : 1
        );

        $permits = self::get_add_permits($args);

        wp_send_json_success($permits);
    }

    public function ajax_approve_add_permit() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_approve_permits')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف الإذن مطلوب'));
        }

        $result = self::approve_add_permit($id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'تم اعتماد الإذن وإضافة المخزون بنجاح'));
    }

    public function ajax_cancel_add_permit() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_manage_permits')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف الإذن مطلوب'));
        }

        $result = self::cancel_permit($id, 'add');

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'تم إلغاء الإذن بنجاح'));
    }

    public function ajax_create_withdraw_permit() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_withdraw_stock')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $data = array(
            'warehouse_id' => isset($_POST['warehouse_id']) ? absint($_POST['warehouse_id']) : 0,
            'department_id' => isset($_POST['department_id']) ? absint($_POST['department_id']) : 0,
            'employee_id' => isset($_POST['employee_id']) ? absint($_POST['employee_id']) : 0,
            'employee_name' => isset($_POST['employee_name']) ? sanitize_text_field($_POST['employee_name']) : '',
            'purpose' => isset($_POST['purpose']) ? sanitize_textarea_field($_POST['purpose']) : '',
            'notes' => isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '',
            'storage_location' => isset($_POST['storage_location']) ? sanitize_text_field($_POST['storage_location']) : '',
            'items' => isset($_POST['items']) ? $_POST['items'] : array()
        );

        if (empty($data['warehouse_id'])) {
            wp_send_json_error(array('message' => 'يرجى اختيار المخزن'));
        }

        if (empty($data['items'])) {
            wp_send_json_error(array('message' => 'يرجى إضافة أصناف'));
        }

        $result = self::create_withdraw_permit($data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => 'تم إنشاء إذن الصرف بنجاح',
            'permit_id' => $result['id'],
            'permit_number' => $result['permit_number']
        ));
    }

    public function ajax_get_withdraw_permit() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_warehouse')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف الإذن مطلوب'));
        }

        $permit = self::get_withdraw_permit($id);

        if (!$permit) {
            wp_send_json_error(array('message' => 'الإذن غير موجود'));
        }

        wp_send_json_success($permit);
    }

    public function ajax_get_withdraw_permits() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_warehouse')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $args = array(
            'warehouse_id' => isset($_POST['warehouse_id']) ? absint($_POST['warehouse_id']) : 0,
            'department_id' => isset($_POST['department_id']) ? absint($_POST['department_id']) : 0,
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '',
            'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '',
            'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '',
            'per_page' => isset($_POST['per_page']) ? absint($_POST['per_page']) : 20,
            'page' => isset($_POST['page']) ? absint($_POST['page']) : 1
        );

        $permits = self::get_withdraw_permits($args);

        wp_send_json_success($permits);
    }

    public function ajax_approve_withdraw_permit() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_approve_permits')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $quantities = isset($_POST['quantities']) ? $_POST['quantities'] : array();

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف الإذن مطلوب'));
        }

        $result = self::approve_withdraw_permit($id, $quantities);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'تم اعتماد الإذن بنجاح'));
    }

    public function ajax_deliver_withdraw_permit() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_withdraw_stock')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف الإذن مطلوب'));
        }

        $result = self::deliver_withdraw_permit($id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'تم تسليم الأصناف وخصمها من المخزون بنجاح'));
    }

    public function ajax_cancel_withdraw_permit() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_manage_permits')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(array('message' => 'معرف الإذن مطلوب'));
        }

        $result = self::cancel_permit($id, 'withdraw');

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'تم إلغاء الإذن بنجاح'));
    }

    public function ajax_get_transactions() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_reports')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $args = array(
            'product_id' => isset($_POST['product_id']) ? absint($_POST['product_id']) : 0,
            'warehouse_id' => isset($_POST['warehouse_id']) ? absint($_POST['warehouse_id']) : 0,
            'transaction_type' => isset($_POST['transaction_type']) ? sanitize_text_field($_POST['transaction_type']) : '',
            'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '',
            'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '',
            'per_page' => isset($_POST['per_page']) ? absint($_POST['per_page']) : 50,
            'page' => isset($_POST['page']) ? absint($_POST['page']) : 1
        );

        $transactions = self::get_transactions($args);

        wp_send_json_success($transactions);
    }

    public function ajax_get_product_transactions() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_view_products')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

        if (!$product_id) {
            wp_send_json_error(array('message' => 'معرف المنتج مطلوب'));
        }

        $transactions = self::get_transactions(array(
            'product_id' => $product_id,
            'per_page' => 100
        ));

        wp_send_json_success($transactions);
    }
}

// Initialize
IW_Transactions::get_instance();
