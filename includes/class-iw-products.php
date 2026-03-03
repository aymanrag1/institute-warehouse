<?php
if (!defined('ABSPATH')) exit;

class IW_Products {

    public static function init() {
        add_action('wp_ajax_iw_save_product', array(__CLASS__, 'save_product'));
        add_action('wp_ajax_iw_delete_product', array(__CLASS__, 'delete_product'));
        add_action('wp_ajax_iw_get_product', array(__CLASS__, 'get_product'));
        add_action('wp_ajax_iw_get_products_list', array(__CLASS__, 'get_products_list'));
        add_action('wp_ajax_iw_sync_all_stocks', array(__CLASS__, 'ajax_sync_all_stocks'));
        add_action('wp_ajax_iw_stock_debug', array(__CLASS__, 'ajax_stock_debug'));
    }

    public static function save_product() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!IW_Permissions::current_user_can('products', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_products';

        $id = intval($_POST['product_id']);

        // Bulk category change - update only category field
        if (!empty($_POST['bulk_category']) && $id > 0) {
            $wpdb->update($table, array('category' => sanitize_text_field($_POST['category'])), array('id' => $id));
            wp_send_json_success(array('id' => $id, 'message' => 'تم تغيير التصنيف'));
        }

        $data = array(
            'name'          => sanitize_text_field($_POST['name']),
            'sku'           => sanitize_text_field($_POST['sku']),
            'category'      => sanitize_text_field($_POST['category']),
            'unit'          => sanitize_text_field($_POST['unit']),
            'min_stock'     => intval($_POST['min_stock']),
            'max_stock'     => intval($_POST['max_stock']),
            'price'         => floatval($_POST['price']),
            'description'   => sanitize_textarea_field($_POST['description']),
        );

        if ($id > 0) {
            $wpdb->update($table, $data, array('id' => $id));
        } else {
            $data['current_stock'] = 0;
            $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }

        wp_send_json_success(array('id' => $id, 'message' => 'تم الحفظ بنجاح'));
    }

    public static function delete_product() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!IW_Permissions::current_user_can('products', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $id = intval($_POST['product_id']);
        $wpdb->delete($wpdb->prefix . 'iw_products', array('id' => $id));
        wp_send_json_success(array('message' => 'تم الحذف'));
    }

    public static function get_product() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $id = intval($_POST['product_id']);
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}iw_products WHERE id = %d", $id
        ));
        wp_send_json_success($product);
    }

    public static function get_products_list() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $table = $wpdb->prefix . 'iw_products';

        // Sync all stocks - calculates from add_orders and completed withdrawals
        self::sync_all_stocks();

        // Use SELECT * to be compatible with old and new table schemas
        $products = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id ASC");

        if ($wpdb->last_error) {
            wp_send_json_error(array('message' => 'خطأ في قاعدة البيانات: ' . $wpdb->last_error));
            return;
        }

        // Normalize field names for compatibility with old tables
        foreach ($products as &$p) {
            // Handle different possible name columns
            if (!isset($p->name) || empty($p->name)) {
                if (isset($p->product_name)) $p->name = $p->product_name;
                elseif (isset($p->title)) $p->name = $p->title;
                else $p->name = 'صنف #' . $p->id;
            }
            if (!isset($p->current_stock)) $p->current_stock = 0;
            if (!isset($p->min_stock)) $p->min_stock = 0;
            if (!isset($p->max_stock)) $p->max_stock = 0;
            if (!isset($p->sku)) $p->sku = '';
            if (!isset($p->unit)) $p->unit = '';
            if (!isset($p->price)) $p->price = 0;
        }

        wp_send_json_success($products);
    }

    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}iw_products ORDER BY name ASC");
    }

    public static function get_by_id($id) {
        global $wpdb;
        // Sync this product's stock from real data
        self::sync_product_stock($id);
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}iw_products WHERE id = %d", $id
        ));
    }

    public static function update_stock($product_id, $quantity_change) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}iw_products SET current_stock = current_stock + %d WHERE id = %d",
            $quantity_change, $product_id
        ));
    }

    public static function get_low_stock_products() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}iw_products WHERE current_stock <= min_stock AND min_stock > 0 ORDER BY name ASC"
        );
    }

    /**
     * Get real stock by calculating from add orders and completed withdrawals
     * This is the DEFINITIVE source of truth - no dependency on transactions table
     */
    public static function get_real_stock($product_id) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        // 1. Calculate total added from add_order_items
        $total_added = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(i.quantity), 0)
             FROM {$prefix}add_order_items i
             WHERE i.product_id = %d",
            $product_id
        ));

        // 2. Calculate total added from opening_balances (if table exists)
        $opening_balance = 0;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$prefix}opening_balances'");
        if ($table_exists) {
            $opening_balance = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(quantity), 0)
                 FROM {$prefix}opening_balances
                 WHERE product_id = %d",
                $product_id
            ));
        }

        // 3. Calculate total withdrawn from completed withdrawal orders (NOT custody)
        // IMPORTANT: Use COALESCE for order_type because NULL != 'custody' = NULL in MySQL
        // which would wrongly exclude rows with NULL order_type from the sum.
        $total_withdrawn = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(COALESCE(i.approved_quantity, i.quantity)), 0)
             FROM {$prefix}withdrawal_order_items i
             INNER JOIN {$prefix}withdrawal_orders o ON i.order_id = o.id
             WHERE i.product_id = %d
               AND o.status = 'completed'
               AND COALESCE(o.order_type, 'normal') != 'custody'",
            $product_id
        ));

        // Real stock = additions + opening balance - withdrawals
        $real_stock = $total_added + $opening_balance - $total_withdrawn;

        return max(0, $real_stock);
    }

    /**
     * Get available stock for new orders.
     * = get_real_stock() minus approved (committed but not yet dispensed) withdrawals.
     * Use this for display and new-order validation so approved reservations show as deducted.
     * Use get_real_stock() only for complete_order() validation (physical check).
     */
    public static function get_available_stock($product_id) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $real_stock = self::get_real_stock($product_id);

        // Subtract approved-but-not-completed withdrawals (reserved stock)
        $approved_reserved = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(COALESCE(i.approved_quantity, i.quantity)), 0)
             FROM {$prefix}withdrawal_order_items i
             INNER JOIN {$prefix}withdrawal_orders o ON i.order_id = o.id
             WHERE i.product_id = %d
               AND o.status = 'approved'
               AND COALESCE(o.order_type, 'normal') != 'custody'",
            $product_id
        ));

        return max(0, $real_stock - $approved_reserved);
    }

    /**
     * Sync a single product's current_stock with available stock
     */
    public static function sync_product_stock($product_id) {
        global $wpdb;
        $available_stock = self::get_available_stock($product_id);
        $wpdb->update(
            $wpdb->prefix . 'iw_products',
            array('current_stock' => $available_stock),
            array('id' => $product_id),
            array('%d'),
            array('%d')
        );
        return $available_stock;
    }

    /**
     * Sync all products' current_stock
     */
    public static function sync_all_stocks() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';

        $products = $wpdb->get_results("SELECT id FROM {$prefix}products");

        foreach ($products as $product) {
            self::sync_product_stock($product->id);
        }
    }

    /**
     * AJAX handler for manual sync
     */
    public static function ajax_sync_all_stocks() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        self::sync_all_stocks();
        wp_send_json_success(array('message' => 'تم مزامنة جميع الأرصدة بنجاح'));
    }

    /**
     * AJAX: Diagnostic breakdown of stock for a specific product (admin only)
     * Returns detailed data to diagnose stock discrepancies
     */
    public static function ajax_stock_debug() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'iw_';
        $product_id = intval($_POST['product_id'] ?? 0);

        if (!$product_id) {
            // Return diagnostics for ALL products if no product_id given
            $products = $wpdb->get_results("SELECT id, name, current_stock FROM {$prefix}products ORDER BY name ASC");
            $result = array();
            foreach ($products as $p) {
                $result[] = array(
                    'id'              => $p->id,
                    'name'            => $p->name,
                    'current_stock_db'=> $p->current_stock,
                    'real_stock'      => self::get_real_stock($p->id),
                    'available_stock' => self::get_available_stock($p->id),
                );
            }
            wp_send_json_success($result);
            return;
        }

        $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$prefix}products WHERE id = %d", $product_id));

        // Add order items
        $add_items = $wpdb->get_results($wpdb->prepare(
            "SELECT ao.order_number, aoi.quantity, ao.created_at
             FROM {$prefix}add_order_items aoi
             JOIN {$prefix}add_orders ao ON aoi.order_id = ao.id
             WHERE aoi.product_id = %d ORDER BY ao.created_at ASC", $product_id
        ));

        // Opening balances
        $balances = $wpdb->get_results($wpdb->prepare(
            "SELECT quantity, balance_date FROM {$prefix}opening_balances WHERE product_id = %d", $product_id
        ));

        // Withdrawal orders by status
        $withdrawals = $wpdb->get_results($wpdb->prepare(
            "SELECT wo.order_number, wo.status, wo.order_type,
                    COALESCE(woi.approved_quantity, woi.quantity) as qty
             FROM {$prefix}withdrawal_order_items woi
             JOIN {$prefix}withdrawal_orders wo ON woi.order_id = wo.id
             WHERE woi.product_id = %d ORDER BY wo.created_at ASC", $product_id
        ));

        wp_send_json_success(array(
            'product'         => $product,
            'add_items'       => $add_items,
            'opening_balances'=> $balances,
            'withdrawals'     => $withdrawals,
            'real_stock'      => self::get_real_stock($product_id),
            'available_stock' => self::get_available_stock($product_id),
        ));
    }
}
