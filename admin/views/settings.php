<?php if (!defined('ABSPATH')) exit;

// Handle settings save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['iw_settings_nonce'])) {
    if (wp_verify_nonce($_POST['iw_settings_nonce'], 'iw_save_settings')) {
        update_option('iw_institute_name', sanitize_text_field($_POST['iw_institute_name']));
        update_option('iw_address', sanitize_textarea_field($_POST['iw_address']));
        update_option('iw_phone', sanitize_text_field($_POST['iw_phone']));
        update_option('iw_language', sanitize_text_field($_POST['iw_language']));

        // Handle logo upload
        if (!empty($_FILES['iw_logo']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $attachment_id = media_handle_upload('iw_logo', 0);
            if (!is_wp_error($attachment_id)) {
                update_option('iw_logo_url', wp_get_attachment_url($attachment_id));
            }
        }

        echo '<div class="notice notice-success"><p>' . __('تم حفظ الإعدادات بنجاح', 'institute-warehouse') . '</p></div>';
    }
}

$current_lang = get_option('iw_language', 'ar');
?>
<div class="wrap iw-wrap" dir="<?php echo $current_lang === 'ar' ? 'rtl' : 'ltr'; ?>">
    <h1><?php echo $current_lang === 'ar' ? 'إعدادات النظام' : 'System Settings'; ?></h1>
    <form method="post" action="" id="iw-settings-form" enctype="multipart/form-data">
        <?php wp_nonce_field('iw_save_settings', 'iw_settings_nonce'); ?>
        <table class="form-table">
            <tr>
                <th><?php echo $current_lang === 'ar' ? 'لغة النظام' : 'System Language'; ?></th>
                <td>
                    <select name="iw_language" class="regular-text">
                        <option value="ar" <?php selected($current_lang, 'ar'); ?>>العربية</option>
                        <option value="en" <?php selected($current_lang, 'en'); ?>>English</option>
                    </select>
                    <p class="description"><?php echo $current_lang === 'ar' ? 'اختر لغة واجهة النظام' : 'Select system interface language'; ?></p>
                </td>
            </tr>
            <tr>
                <th><?php echo $current_lang === 'ar' ? 'اسم المعهد / المؤسسة' : 'Institute / Organization Name'; ?></th>
                <td><input type="text" name="iw_institute_name" class="regular-text" value="<?php echo esc_attr(get_option('iw_institute_name', '')); ?>"></td>
            </tr>
            <tr>
                <th><?php echo $current_lang === 'ar' ? 'اللوجو' : 'Logo'; ?></th>
                <td>
                    <?php $logo = get_option('iw_logo_url', ''); ?>
                    <?php if ($logo): ?>
                        <div style="margin-bottom:10px;"><img src="<?php echo esc_url($logo); ?>" style="max-height:80px;" /></div>
                    <?php endif; ?>
                    <input type="file" name="iw_logo" accept="image/*">
                    <p class="description"><?php echo $current_lang === 'ar' ? 'سيظهر اللوجو في جميع الأذون والأوراق المطبوعة' : 'Logo will appear on all permits and printed documents'; ?></p>
                </td>
            </tr>
            <tr>
                <th><?php echo $current_lang === 'ar' ? 'العنوان' : 'Address'; ?></th>
                <td><textarea name="iw_address" class="large-text" rows="2"><?php echo esc_textarea(get_option('iw_address', '')); ?></textarea></td>
            </tr>
            <tr>
                <th><?php echo $current_lang === 'ar' ? 'الهاتف' : 'Phone'; ?></th>
                <td><input type="text" name="iw_phone" class="regular-text" value="<?php echo esc_attr(get_option('iw_phone', '')); ?>"></td>
            </tr>
        </table>
        <button type="submit" class="button button-primary button-large">
            <?php echo $current_lang === 'ar' ? 'حفظ الإعدادات' : 'Save Settings'; ?>
        </button>
    </form>
</div>
