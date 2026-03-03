<?php
if (!defined('ABSPATH')) exit;

class IW_Excel_Import {

    public static function init() {
        add_action('wp_ajax_iw_import_products', array(__CLASS__, 'import_products'));
    }

    public static function import_products() {
        check_ajax_referer('iw_admin_nonce', 'nonce');

        if (!IW_Permissions::current_user_can('import_data', 'read_write')) {
            wp_send_json_error(array('message' => 'ليس لديك صلاحية'));
        }

        $data = json_decode(stripslashes($_POST['data']), true);

        if (empty($data)) {
            wp_send_json_error(array('message' => 'لا توجد بيانات'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_products';
        $count = 0;

        foreach ($data as $row) {
            $name = sanitize_text_field($row['name'] ?? $row[0] ?? '');
            if (empty($name)) continue;

            $wpdb->insert($table, array(
                'name'      => $name,
                'sku'       => sanitize_text_field($row['sku'] ?? $row[1] ?? ''),
                'category'  => sanitize_text_field($row['category'] ?? $row[2] ?? ''),
                'unit'      => sanitize_text_field($row['unit'] ?? $row[3] ?? ''),
                'min_stock' => intval($row['min_stock'] ?? $row[4] ?? 0),
                'max_stock' => intval($row['max_stock'] ?? $row[5] ?? 0),
                'price'     => floatval($row['price'] ?? $row[6] ?? 0),
            ));
            $count++;
        }

        wp_send_json_success(array('message' => "تم استيراد $count صنف بنجاح"));
    }
}
