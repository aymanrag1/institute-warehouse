<?php
if (!defined('ABSPATH')) exit;

class IW_Permissions {

    // All system features for the permissions matrix
    const FEATURES = array(
        'dashboard'          => 'لوحة التحكم',
        'products'           => 'الأصناف',
        'add_stock'          => 'إذن إضافة',
        'withdraw_stock'     => 'إذن صرف',
        'withdrawal_orders'  => 'أوامر الصرف',
        'purchase_requests'  => 'طلبات الشراء',
        'approve_orders'     => 'اعتماد الأوامر',
        'reports'            => 'التقارير',
        'departments'        => 'الأقسام والموظفين',
        'suppliers'          => 'الموردين',
        'opening_balance'    => 'الرصيد الافتتاحي',
        'import_data'        => 'استيراد البيانات',
        'settings'           => 'الإعدادات',
        'permissions'        => 'الصلاحيات',
    );

    public static function init() {
        add_action('wp_ajax_iw_save_permissions', array(__CLASS__, 'save_permissions'));
        add_action('wp_ajax_iw_get_user_permissions', array(__CLASS__, 'get_user_permissions'));
        add_action('wp_ajax_iw_upload_signature', array(__CLASS__, 'upload_signature'));
    }

    public static function create_roles() {
        // Dean / Director role
        add_role('iw_dean', 'عميد المعهد / المدير', array(
            'read'              => true,
            'iw_view_warehouse' => true,
            'iw_view_products'  => true,
            'iw_add_stock'      => true,
            'iw_withdraw_stock' => true,
            'iw_view_reports'   => true,
            'iw_manage_departments' => true,
            'iw_manage_suppliers'   => true,
            'iw_import_data'    => true,
            'iw_approve_orders' => true,
        ));

        // Warehouse supervisor role
        add_role('iw_warehouse_supervisor', 'مشرف المخزن', array(
            'read'              => true,
            'iw_view_warehouse' => true,
            'iw_view_products'  => true,
            'iw_add_stock'      => true,
            'iw_withdraw_stock' => true,
            'iw_view_reports'   => true,
            'iw_manage_departments' => true,
            'iw_manage_suppliers'   => true,
            'iw_import_data'    => true,
        ));

        // Warehouse clerk role
        add_role('iw_warehouse_clerk', 'أمين المخزن', array(
            'read'              => true,
            'iw_view_warehouse' => true,
            'iw_view_products'  => true,
            'iw_add_stock'      => true,
            'iw_withdraw_stock' => true,
        ));

        // Add capabilities to admin
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('iw_view_warehouse');
            $admin->add_cap('iw_view_products');
            $admin->add_cap('iw_add_stock');
            $admin->add_cap('iw_withdraw_stock');
            $admin->add_cap('iw_view_reports');
            $admin->add_cap('iw_manage_departments');
            $admin->add_cap('iw_manage_suppliers');
            $admin->add_cap('iw_import_data');
            $admin->add_cap('iw_approve_orders');
        }
    }

    /**
     * Check if current user can perform action on feature.
     * Uses the custom permissions table first, falls back to WP capabilities.
     */
    public static function current_user_can($feature, $required_level = 'view') {
        if (current_user_can('manage_options')) {
            return true; // Admin can do everything
        }

        $user_id = get_current_user_id();
        if (!$user_id) return false;

        global $wpdb;
        $perm = $wpdb->get_var($wpdb->prepare(
            "SELECT permission_level FROM {$wpdb->prefix}iw_permissions WHERE user_id = %d AND feature = %s",
            $user_id, $feature
        ));

        if ($perm === null) {
            // Fall back to role capabilities
            if ($required_level === 'view' || $required_level === 'read') {
                return current_user_can('iw_view_warehouse');
            }
            if ($required_level === 'read_write') {
                return current_user_can('iw_approve_orders') || current_user_can('iw_add_stock');
            }
            return false;
        }

        $levels = array('none' => 0, 'view' => 1, 'read' => 2, 'read_write' => 3);
        return ($levels[$perm] ?? 0) >= ($levels[$required_level] ?? 0);
    }

    public static function save_permissions() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('manage_options') && !self::current_user_can('permissions', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_permissions';
        $user_id = intval($_POST['user_id']);
        $permissions = $_POST['permissions'] ?? array();

        // Delete existing permissions for this user
        $wpdb->delete($table, array('user_id' => $user_id));

        // Insert new permissions
        foreach ($permissions as $feature => $level) {
            $feature = sanitize_text_field($feature);
            $level   = sanitize_text_field($level);
            if (!array_key_exists($feature, self::FEATURES)) continue;
            if (!in_array($level, array('none', 'view', 'read', 'read_write'))) continue;

            $wpdb->insert($table, array(
                'user_id'          => $user_id,
                'feature'          => $feature,
                'permission_level' => $level,
            ));
        }

        wp_send_json_success(array('message' => 'تم حفظ الصلاحيات'));
    }

    public static function get_user_permissions() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $user_id = intval($_POST['user_id']);
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT feature, permission_level FROM {$wpdb->prefix}iw_permissions WHERE user_id = %d",
            $user_id
        ));
        $perms = array();
        foreach ($rows as $r) {
            $perms[$r->feature] = $r->permission_level;
        }
        wp_send_json_success(array('permissions' => $perms, 'features' => self::FEATURES));
    }

    /**
     * Upload electronic signature image
     */
    public static function upload_signature() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('iw_approve_orders') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية لرفع التوقيع'));
        }

        if (empty($_FILES['signature'])) {
            wp_send_json_error(array('message' => 'لم يتم اختيار ملف'));
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $attachment_id = media_handle_upload('signature', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        }

        $url = wp_get_attachment_url($attachment_id);
        update_user_meta(get_current_user_id(), 'iw_signature_url', $url);
        update_user_meta(get_current_user_id(), 'iw_signature_id', $attachment_id);

        wp_send_json_success(array('url' => $url, 'message' => 'تم رفع التوقيع بنجاح'));
    }
}
