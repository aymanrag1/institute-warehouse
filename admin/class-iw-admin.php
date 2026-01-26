<?php
/**
 * Admin Class
 *
 * إدارة الواجهات الإدارية
 */

if (!defined('ABSPATH')) {
    exit;
}

class IW_Admin {

    /**
     * عرض صفحة الأصناف
     */
    public static function products_page() {
        include IW_PLUGIN_DIR . 'admin/views/products.php';
    }

    /**
     * عرض صفحة إذن الإضافة
     */
    public static function add_stock_page() {
        include IW_PLUGIN_DIR . 'admin/views/add-stock.php';
    }

    /**
     * عرض صفحة إذن الصرف
     */
    public static function withdraw_stock_page() {
        include IW_PLUGIN_DIR . 'admin/views/withdraw-stock.php';
    }

    /**
     * عرض صفحة التقارير
     */
    public static function reports_page() {
        include IW_PLUGIN_DIR . 'admin/views/reports.php';
    }

    /**
     * عرض صفحة الأقسام
     */
    public static function departments_page() {
        include IW_PLUGIN_DIR . 'admin/views/departments.php';
    }

    /**
     * عرض صفحة الموردين
     */
    public static function suppliers_page() {
        include IW_PLUGIN_DIR . 'admin/views/suppliers.php';
    }

    /**
     * عرض صفحة الاستيراد
     */
    public static function import_page() {
        include IW_PLUGIN_DIR . 'admin/views/import.php';
    }

    /**
     * عرض صفحة الإعدادات
     */
    public static function settings_page() {
        // حفظ الإعدادات
        if (isset($_POST['iw_save_settings']) && check_admin_referer('iw_settings_nonce')) {
            self::save_settings();
        }

        include IW_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * حفظ الإعدادات
     */
    private static function save_settings() {
        $settings = array(
            'institute_name' => isset($_POST['institute_name']) ? sanitize_text_field($_POST['institute_name']) : '',
            'institute_type' => isset($_POST['institute_type']) ? sanitize_text_field($_POST['institute_type']) : '',
            'institute_address' => isset($_POST['institute_address']) ? sanitize_textarea_field($_POST['institute_address']) : '',
            'institute_phone' => isset($_POST['institute_phone']) ? sanitize_text_field($_POST['institute_phone']) : '',
            'institute_email' => isset($_POST['institute_email']) ? sanitize_email($_POST['institute_email']) : '',
            'dean_name' => isset($_POST['dean_name']) ? sanitize_text_field($_POST['dean_name']) : '',
            'low_stock_threshold' => isset($_POST['low_stock_threshold']) ? absint($_POST['low_stock_threshold']) : 10,
            'expiry_warning_days' => isset($_POST['expiry_warning_days']) ? absint($_POST['expiry_warning_days']) : 30,
            'permit_prefix_add' => isset($_POST['permit_prefix_add']) ? sanitize_text_field($_POST['permit_prefix_add']) : 'ADD',
            'permit_prefix_withdraw' => isset($_POST['permit_prefix_withdraw']) ? sanitize_text_field($_POST['permit_prefix_withdraw']) : 'WD',
            'currency' => isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : 'SAR',
            'date_format' => isset($_POST['date_format']) ? sanitize_text_field($_POST['date_format']) : 'd/m/Y',
        );

        // حفظ اللوجو
        if (!empty($_FILES['institute_logo']['name'])) {
            $upload = wp_handle_upload($_FILES['institute_logo'], array('test_form' => false));
            if (isset($upload['url'])) {
                $settings['institute_logo'] = $upload['url'];
            }
        } elseif (isset($_POST['institute_logo_url'])) {
            $settings['institute_logo'] = esc_url_raw($_POST['institute_logo_url']);
        }

        foreach ($settings as $key => $value) {
            IW_Database::set_setting($key, $value);
        }

        // تحديث حالة التنصيب
        if (!empty($settings['institute_name'])) {
            IW_Database::set_setting('setup_completed', '1');
        }

        add_settings_error('iw_settings', 'settings_updated', 'تم حفظ الإعدادات بنجاح', 'success');
    }

    /**
     * الحصول على الإعدادات الحالية
     */
    public static function get_settings() {
        return IW_Database::get_all_settings();
    }

    /**
     * عرض صفحة طباعة إذن الإضافة
     */
    public static function print_add_permit() {
        $permit_id = isset($_GET['permit_id']) ? absint($_GET['permit_id']) : 0;

        if (!$permit_id) {
            wp_die('معرف الإذن مطلوب');
        }

        $permit = IW_Transactions::get_add_permit($permit_id);

        if (!$permit) {
            wp_die('الإذن غير موجود');
        }

        $settings = self::get_settings();

        include IW_PLUGIN_DIR . 'admin/views/print-add-permit.php';
        exit;
    }

    /**
     * عرض صفحة طباعة إذن الصرف
     */
    public static function print_withdraw_permit() {
        $permit_id = isset($_GET['permit_id']) ? absint($_GET['permit_id']) : 0;

        if (!$permit_id) {
            wp_die('معرف الإذن مطلوب');
        }

        $permit = IW_Transactions::get_withdraw_permit($permit_id);

        if (!$permit) {
            wp_die('الإذن غير موجود');
        }

        $settings = self::get_settings();

        include IW_PLUGIN_DIR . 'admin/views/print-withdraw-permit.php';
        exit;
    }

    /**
     * الحصول على المخازن النشطة
     */
    public static function get_active_warehouses() {
        return IW_Departments::get_warehouses('active');
    }

    /**
     * الحصول على الأقسام النشطة
     */
    public static function get_active_departments() {
        return IW_Departments::get_departments(array('status' => 'active'));
    }

    /**
     * الحصول على الفئات
     */
    public static function get_categories() {
        return IW_Products::get_categories();
    }

    /**
     * تنسيق التاريخ
     */
    public static function format_date($date, $format = null) {
        if (empty($date)) {
            return '-';
        }

        if ($format === null) {
            $format = IW_Database::get_setting('date_format', 'd/m/Y');
        }

        return date_i18n($format, strtotime($date));
    }

    /**
     * تنسيق المبلغ
     */
    public static function format_currency($amount) {
        $currency = IW_Database::get_setting('currency', 'SAR');
        return number_format((float)$amount, 2) . ' ' . $currency;
    }

    /**
     * الحصول على حالة الإذن بالعربية
     */
    public static function get_permit_status_label($status, $type = 'add') {
        $statuses = array(
            'pending' => array('label' => 'معلق', 'class' => 'warning'),
            'approved' => array('label' => 'معتمد', 'class' => 'success'),
            'delivered' => array('label' => 'تم التسليم', 'class' => 'info'),
            'cancelled' => array('label' => 'ملغي', 'class' => 'danger'),
        );

        return isset($statuses[$status]) ? $statuses[$status] : array('label' => $status, 'class' => 'secondary');
    }

    /**
     * الحصول على نوع المعاملة بالعربية
     */
    public static function get_transaction_type_label($type) {
        $types = array(
            'add' => 'إضافة',
            'withdraw' => 'صرف',
            'transfer' => 'تحويل',
            'adjustment' => 'تعديل',
            'return' => 'إرجاع'
        );

        return isset($types[$type]) ? $types[$type] : $type;
    }

    /**
     * الحصول على نوع التنبيه بالعربية
     */
    public static function get_alert_type_label($type) {
        $types = array(
            'low_stock' => array('label' => 'مخزون منخفض', 'icon' => 'warning', 'class' => 'warning'),
            'out_of_stock' => array('label' => 'نفاذ المخزون', 'icon' => 'dismiss', 'class' => 'danger'),
            'expiry_warning' => array('label' => 'قرب انتهاء الصلاحية', 'icon' => 'clock', 'class' => 'warning'),
            'expiry' => array('label' => 'انتهاء الصلاحية', 'icon' => 'no', 'class' => 'danger'),
            'reorder' => array('label' => 'حد إعادة الطلب', 'icon' => 'cart', 'class' => 'info'),
        );

        return isset($types[$type]) ? $types[$type] : array('label' => $type, 'icon' => 'info', 'class' => 'secondary');
    }
}
