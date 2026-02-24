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
        add_action('wp_ajax_iw_get_my_signature', array(__CLASS__, 'get_my_signature'));
    }

    public static function create_roles() {
        // Note: Warehouse plugin now uses roles from RSYI HR System
        // The capabilities are added via rsyi_hr_extend_roles hook in institute-warehouse.php
        // Legacy roles are kept for backward compatibility but not created for new installs

        // Only add capabilities to admin
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

        // Trigger HR roles extension if HR is active
        if (iw_is_hr_active()) {
            do_action('rsyi_hr_extend_roles');
        }
    }

    /**
     * Check if current user can perform action on feature.
     * Uses the custom permissions table first, falls back to WP capabilities.
     */
    public static function current_user_can($feature, $required_level = 'view') {
        $user_id = get_current_user_id();
        if (!$user_id) return false;

        global $wpdb;
        $table = $wpdb->prefix . 'iw_permissions';

        // Check if permissions table exists (avoid errors on fresh install)
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
        if (!$table_exists) {
            // Table not created yet - only allow admin
            return current_user_can('manage_options');
        }

        // Check if this user has ANY custom permissions set
        $has_custom = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d", $user_id
        ));

        if ($has_custom > 0) {
            // User has custom permissions - use those as source of truth
            $perm = $wpdb->get_var($wpdb->prepare(
                "SELECT permission_level FROM {$table} WHERE user_id = %d AND feature = %s",
                $user_id, $feature
            ));

            if ($perm === null) {
                // Feature not in their permissions = no access
                return false;
            }

            $levels = array('none' => 0, 'view' => 1, 'read' => 2, 'read_write' => 3);
            return ($levels[$perm] ?? 0) >= ($levels[$required_level] ?? 0);
        }

        // No custom permissions set for this user
        // Only admin (manage_options) gets full access by default
        if (current_user_can('manage_options')) {
            return true;
        }

        // Fallback: check WordPress capabilities for users without custom permissions
        // Map features to WordPress capabilities
        $feature_to_cap = array(
            'dashboard'          => 'iw_view_warehouse',
            'products'           => 'iw_view_products',
            'add_stock'          => 'iw_add_stock',
            'withdraw_stock'     => 'iw_withdraw_stock',
            'withdrawal_orders'  => 'iw_withdraw_stock',
            'purchase_requests'  => 'iw_add_stock',
            'approve_orders'     => 'iw_approve_orders',
            'reports'            => 'iw_view_reports',
            'departments'        => 'iw_manage_departments',
            'suppliers'          => 'iw_manage_suppliers',
            'opening_balance'    => 'iw_add_stock',
            'import_data'        => 'iw_import_data',
            'settings'           => 'manage_options',
            'permissions'        => 'manage_options',
        );

        if (isset($feature_to_cap[$feature]) && current_user_can($feature_to_cap[$feature])) {
            // User has WP capability - allow view/read, but read_write only for specific caps
            if ($required_level === 'read_write') {
                // For write access, check if they have the specific capability
                return current_user_can($feature_to_cap[$feature]);
            }
            return true;
        }

        // All other users without custom permissions or WP capabilities = no access
        return false;
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

        // Allow any logged-in user who has warehouse access to upload signature
        if (!current_user_can('iw_approve_orders') && !current_user_can('iw_view_warehouse') && !current_user_can('manage_options')) {
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

    public static function get_my_signature() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        $url = get_user_meta(get_current_user_id(), 'iw_signature_url', true);
        wp_send_json_success(array('url' => $url ?: ''));
    }
}
