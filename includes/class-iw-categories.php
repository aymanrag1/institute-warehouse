<?php
if (!defined('ABSPATH')) exit;

/**
 * Product Categories Management
 * Manages categories that appear as dropdowns in all screens
 */
class IW_Categories {

    public static function init() {
        add_action('wp_ajax_iw_get_categories', array(__CLASS__, 'get_categories'));
        add_action('wp_ajax_iw_save_category', array(__CLASS__, 'save_category'));
        add_action('wp_ajax_iw_delete_category', array(__CLASS__, 'delete_category'));
        add_action('wp_ajax_iw_get_category', array(__CLASS__, 'get_category'));
    }

    /**
     * Get all categories
     */
    public static function get_all() {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_categories';
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY name ASC");
    }

    /**
     * AJAX: Get all categories
     */
    public static function get_categories() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        wp_send_json_success(self::get_all());
    }

    /**
     * AJAX: Get single category
     */
    public static function get_category() {
        check_ajax_referer('iw_admin_nonce', 'nonce');
        global $wpdb;
        $table = $wpdb->prefix . 'iw_categories';
        $id = intval($_POST['category_id']);
        $cat = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        wp_send_json_success($cat);
    }

    /**
     * AJAX: Save (create or update) category
     */
    public static function save_category() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('manage_options') && !IW_Permissions::current_user_can('products', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_categories';

        $id = intval($_POST['category_id'] ?? 0);
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description'] ?? '');

        if (empty($name)) {
            wp_send_json_error(array('message' => 'اسم التصنيف مطلوب'));
        }

        if ($id > 0) {
            $wpdb->update($table, array(
                'name' => $name,
                'description' => $description,
            ), array('id' => $id));
            wp_send_json_success(array('message' => 'تم تحديث التصنيف'));
        } else {
            $wpdb->insert($table, array(
                'name' => $name,
                'description' => $description,
            ));
            wp_send_json_success(array('message' => 'تم إضافة التصنيف', 'id' => $wpdb->insert_id));
        }
    }

    /**
     * AJAX: Delete category
     */
    public static function delete_category() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_categories';
        $id = intval($_POST['category_id']);

        // Check if category is used by products
        $products_table = $wpdb->prefix . 'iw_products';
        $cat = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$table} WHERE id = %d", $id));
        if ($cat) {
            $used = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$products_table} WHERE category = %s",
                $cat->name
            ));
            if ($used > 0) {
                wp_send_json_error(array('message' => 'لا يمكن حذف التصنيف، يوجد ' . $used . ' أصناف مرتبطة به'));
            }
        }

        $wpdb->delete($table, array('id' => $id));
        wp_send_json_success(array('message' => 'تم حذف التصنيف'));
    }

    /**
     * Get categories as options HTML
     */
    public static function get_options_html($selected = '') {
        $categories = self::get_all();
        $html = '<option value="">-- اختر التصنيف --</option>';
        foreach ($categories as $cat) {
            $sel = ($selected === $cat->name) ? ' selected' : '';
            $html .= '<option value="' . esc_attr($cat->name) . '"' . $sel . '>' . esc_html($cat->name) . '</option>';
        }
        return $html;
    }
}
