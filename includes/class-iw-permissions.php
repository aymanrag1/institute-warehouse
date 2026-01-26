<?php
/**
 * Permissions Management Class
 *
 * إدارة الصلاحيات والأدوار التفصيلية
 */

if (!defined('ABSPATH')) {
    exit;
}

class IW_Permissions {

    /**
     * قائمة الصلاحيات المتاحة
     */
    private static $capabilities = array(
        // صلاحيات العرض
        'iw_view_warehouse' => 'عرض المخازن',
        'iw_view_products' => 'عرض الأصناف',
        'iw_view_reports' => 'عرض التقارير',
        'iw_view_alerts' => 'عرض التنبيهات',

        // صلاحيات الإدارة
        'iw_manage_products' => 'إدارة الأصناف (إضافة، تعديل، حذف)',
        'iw_manage_warehouses' => 'إدارة المخازن',
        'iw_manage_departments' => 'إدارة الأقسام والموظفين',
        'iw_manage_suppliers' => 'إدارة الموردين',
        'iw_manage_permits' => 'إدارة الإذونات',

        // صلاحيات المعاملات
        'iw_add_stock' => 'إنشاء إذن إضافة',
        'iw_withdraw_stock' => 'إنشاء إذن صرف',
        'iw_approve_permits' => 'اعتماد الإذونات',
        'iw_cancel_permits' => 'إلغاء الإذونات',

        // صلاحيات خاصة
        'iw_import_data' => 'استيراد البيانات',
        'iw_export_data' => 'تصدير البيانات',
        'iw_manage_settings' => 'إدارة الإعدادات',
        'iw_view_activity_log' => 'عرض سجل النشاط',
    );

    /**
     * الأدوار الافتراضية
     */
    private static $default_roles = array(
        'iw_admin' => array(
            'name' => 'مدير المخازن',
            'capabilities' => 'all'
        ),
        'iw_supervisor' => array(
            'name' => 'مشرف المخزن',
            'capabilities' => array(
                'iw_view_warehouse',
                'iw_view_products',
                'iw_view_reports',
                'iw_view_alerts',
                'iw_manage_products',
                'iw_manage_departments',
                'iw_manage_suppliers',
                'iw_manage_permits',
                'iw_add_stock',
                'iw_withdraw_stock',
                'iw_approve_permits',
                'iw_export_data',
            )
        ),
        'iw_clerk' => array(
            'name' => 'موظف مخزن',
            'capabilities' => array(
                'iw_view_warehouse',
                'iw_view_products',
                'iw_view_alerts',
                'iw_add_stock',
                'iw_withdraw_stock',
            )
        ),
        'iw_viewer' => array(
            'name' => 'مشاهد فقط',
            'capabilities' => array(
                'iw_view_warehouse',
                'iw_view_products',
                'iw_view_reports',
            )
        )
    );

    /**
     * إنشاء الأدوار والصلاحيات
     */
    public static function create_roles() {
        // إضافة الصلاحيات للمدير
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach (self::$capabilities as $cap => $label) {
                $admin_role->add_cap($cap);
            }
        }

        // إنشاء الأدوار الخاصة بالبلجن
        foreach (self::$default_roles as $role_key => $role_data) {
            // حذف الدور إذا كان موجوداً
            remove_role($role_key);

            // إنشاء الدور مع الصلاحيات
            $caps = array('read' => true);

            if ($role_data['capabilities'] === 'all') {
                foreach (self::$capabilities as $cap => $label) {
                    $caps[$cap] = true;
                }
            } else {
                foreach ($role_data['capabilities'] as $cap) {
                    $caps[$cap] = true;
                }
            }

            add_role($role_key, $role_data['name'], $caps);
        }
    }

    /**
     * حذف الأدوار والصلاحيات
     */
    public static function remove_roles() {
        // إزالة الصلاحيات من المدير
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach (self::$capabilities as $cap => $label) {
                $admin_role->remove_cap($cap);
            }
        }

        // حذف الأدوار الخاصة بالبلجن
        foreach (array_keys(self::$default_roles) as $role_key) {
            remove_role($role_key);
        }
    }

    /**
     * الحصول على قائمة الصلاحيات
     */
    public static function get_capabilities() {
        return self::$capabilities;
    }

    /**
     * الحصول على قائمة الأدوار
     */
    public static function get_roles() {
        $roles = array();

        foreach (self::$default_roles as $role_key => $role_data) {
            $wp_role = get_role($role_key);
            if ($wp_role) {
                $roles[$role_key] = array(
                    'name' => $role_data['name'],
                    'capabilities' => array_keys(array_filter($wp_role->capabilities, function($v, $k) {
                        return strpos($k, 'iw_') === 0 && $v;
                    }, ARRAY_FILTER_USE_BOTH))
                );
            }
        }

        return $roles;
    }

    /**
     * تحديث صلاحيات دور معين
     */
    public static function update_role_capabilities($role_key, $capabilities) {
        $role = get_role($role_key);

        if (!$role) {
            return false;
        }

        // إزالة جميع صلاحيات البلجن
        foreach (self::$capabilities as $cap => $label) {
            $role->remove_cap($cap);
        }

        // إضافة الصلاحيات المحددة
        foreach ($capabilities as $cap) {
            if (isset(self::$capabilities[$cap])) {
                $role->add_cap($cap);
            }
        }

        return true;
    }

    /**
     * الحصول على صلاحيات مستخدم معين
     */
    public static function get_user_capabilities($user_id) {
        $user = get_userdata($user_id);

        if (!$user) {
            return array();
        }

        $user_caps = array();

        foreach (self::$capabilities as $cap => $label) {
            if ($user->has_cap($cap)) {
                $user_caps[] = $cap;
            }
        }

        return $user_caps;
    }

    /**
     * منح صلاحية لمستخدم
     */
    public static function grant_capability($user_id, $capability) {
        if (!isset(self::$capabilities[$capability])) {
            return false;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $user->add_cap($capability);
        return true;
    }

    /**
     * سحب صلاحية من مستخدم
     */
    public static function revoke_capability($user_id, $capability) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $user->remove_cap($capability);
        return true;
    }

    /**
     * التحقق من صلاحية المستخدم الحالي
     */
    public static function current_user_can($capability) {
        return current_user_can($capability);
    }

    /**
     * التحقق من عدة صلاحيات (أي منها)
     */
    public static function current_user_can_any($capabilities) {
        foreach ($capabilities as $cap) {
            if (current_user_can($cap)) {
                return true;
            }
        }
        return false;
    }

    /**
     * التحقق من عدة صلاحيات (جميعها)
     */
    public static function current_user_can_all($capabilities) {
        foreach ($capabilities as $cap) {
            if (!current_user_can($cap)) {
                return false;
            }
        }
        return true;
    }

    /**
     * الحصول على المستخدمين حسب الدور
     */
    public static function get_users_by_role($role) {
        return get_users(array('role' => $role));
    }

    /**
     * الحصول على جميع مستخدمي البلجن
     */
    public static function get_plugin_users() {
        $users = array();

        foreach (array_keys(self::$default_roles) as $role) {
            $role_users = get_users(array('role' => $role));
            $users = array_merge($users, $role_users);
        }

        // إضافة المدراء
        $admins = get_users(array('role' => 'administrator'));
        $users = array_merge($users, $admins);

        return array_unique($users, SORT_REGULAR);
    }

    /**
     * تعيين دور لمستخدم
     */
    public static function assign_role($user_id, $role) {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        // التحقق من أن الدور صالح
        if (!isset(self::$default_roles[$role]) && $role !== 'administrator') {
            return false;
        }

        $user->set_role($role);
        return true;
    }

    /**
     * إضافة دور إضافي لمستخدم
     */
    public static function add_role_to_user($user_id, $role) {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        if (!isset(self::$default_roles[$role])) {
            return false;
        }

        $user->add_role($role);
        return true;
    }

    /**
     * إزالة دور من مستخدم
     */
    public static function remove_role_from_user($user_id, $role) {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        $user->remove_role($role);
        return true;
    }

    /**
     * الحصول على صلاحيات مجمعة
     */
    public static function get_capabilities_grouped() {
        return array(
            'view' => array(
                'label' => 'صلاحيات العرض',
                'capabilities' => array(
                    'iw_view_warehouse' => 'عرض المخازن',
                    'iw_view_products' => 'عرض الأصناف',
                    'iw_view_reports' => 'عرض التقارير',
                    'iw_view_alerts' => 'عرض التنبيهات',
                )
            ),
            'manage' => array(
                'label' => 'صلاحيات الإدارة',
                'capabilities' => array(
                    'iw_manage_products' => 'إدارة الأصناف',
                    'iw_manage_warehouses' => 'إدارة المخازن',
                    'iw_manage_departments' => 'إدارة الأقسام والموظفين',
                    'iw_manage_suppliers' => 'إدارة الموردين',
                    'iw_manage_permits' => 'إدارة الإذونات',
                )
            ),
            'transactions' => array(
                'label' => 'صلاحيات المعاملات',
                'capabilities' => array(
                    'iw_add_stock' => 'إنشاء إذن إضافة',
                    'iw_withdraw_stock' => 'إنشاء إذن صرف',
                    'iw_approve_permits' => 'اعتماد الإذونات',
                    'iw_cancel_permits' => 'إلغاء الإذونات',
                )
            ),
            'special' => array(
                'label' => 'صلاحيات خاصة',
                'capabilities' => array(
                    'iw_import_data' => 'استيراد البيانات',
                    'iw_export_data' => 'تصدير البيانات',
                    'iw_manage_settings' => 'إدارة الإعدادات',
                    'iw_view_activity_log' => 'عرض سجل النشاط',
                )
            )
        );
    }
}
