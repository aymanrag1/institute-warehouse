<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>إعدادات النظام</h1>
    <form method="post" action="" id="iw-settings-form" enctype="multipart/form-data">
        <?php wp_nonce_field('iw_save_settings', 'iw_settings_nonce'); ?>
        <table class="form-table">
            <tr>
                <th>اسم المعهد / المؤسسة</th>
                <td><input type="text" name="iw_institute_name" class="regular-text" value="<?php echo esc_attr(get_option('iw_institute_name', '')); ?>"></td>
            </tr>
            <tr>
                <th>اللوجو</th>
                <td>
                    <?php $logo = get_option('iw_logo_url', ''); ?>
                    <?php if ($logo): ?>
                        <div style="margin-bottom:10px;"><img src="<?php echo esc_url($logo); ?>" style="max-height:80px;" /></div>
                    <?php endif; ?>
                    <input type="file" name="iw_logo" accept="image/*">
                    <p class="description">سيظهر اللوجو في جميع الأذون والأوراق المطبوعة</p>
                </td>
            </tr>
            <tr>
                <th>العنوان</th>
                <td><textarea name="iw_address" class="large-text" rows="2"><?php echo esc_textarea(get_option('iw_address', '')); ?></textarea></td>
            </tr>
            <tr>
                <th>الهاتف</th>
                <td><input type="text" name="iw_phone" class="regular-text" value="<?php echo esc_attr(get_option('iw_phone', '')); ?>"></td>
            </tr>
        </table>
        <button type="submit" class="button button-primary button-large">حفظ الإعدادات</button>
    </form>
</div>

<?php
// Handle settings save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['iw_settings_nonce'])) {
    if (wp_verify_nonce($_POST['iw_settings_nonce'], 'iw_save_settings')) {
        update_option('iw_institute_name', sanitize_text_field($_POST['iw_institute_name']));
        update_option('iw_address', sanitize_textarea_field($_POST['iw_address']));
        update_option('iw_phone', sanitize_text_field($_POST['iw_phone']));

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

        echo '<div class="notice notice-success"><p>تم حفظ الإعدادات بنجاح</p></div>';
    }
}
?>
