<?php if (!defined('ABSPATH')) exit;

// Handle settings save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['iw_settings_nonce'])) {
    if (wp_verify_nonce($_POST['iw_settings_nonce'], 'iw_save_settings')) {
        update_option('iw_institute_name', sanitize_text_field($_POST['iw_institute_name']));
        update_option('iw_address', sanitize_textarea_field($_POST['iw_address']));
        update_option('iw_phone', sanitize_text_field($_POST['iw_phone']));
        update_option('iw_signature_width', max(50, intval($_POST['iw_signature_width'] ?? 150)));
        update_option('iw_tax_enabled', isset($_POST['iw_tax_enabled']) ? '1' : '0');
        update_option('iw_tax_rate', max(0, min(100, floatval($_POST['iw_tax_rate'] ?? 14))));

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

        echo '<div class="notice notice-success"><p>' . iw_t('تم حفظ الإعدادات بنجاح', 'Settings saved successfully.') . '</p></div>';
    }
}
?>
<div class="wrap iw-wrap" dir="<?php echo iw_dir(); ?>">
    <h1><?php echo iw_t('إعدادات النظام', 'System Settings'); ?></h1>
    <p class="description" style="margin-bottom:15px;">
        <?php echo iw_t(
            'لتغيير لغة النظام، غيّر لغة WordPress من إعدادات WordPress العامة.',
            'To change the system language, update the WordPress language in WordPress General Settings.'
        ); ?>
        <a href="<?php echo admin_url('options-general.php'); ?>" target="_blank">
            <?php echo iw_t('إعدادات WordPress', 'WordPress Settings'); ?> &rarr;
        </a>
    </p>
    <form method="post" action="" id="iw-settings-form" enctype="multipart/form-data">
        <?php wp_nonce_field('iw_save_settings', 'iw_settings_nonce'); ?>
        <table class="form-table">
            <tr>
                <th><?php echo iw_t('اسم المعهد / المؤسسة', 'Institute / Organization Name'); ?></th>
                <td><input type="text" name="iw_institute_name" class="regular-text" value="<?php echo esc_attr(get_option('iw_institute_name', '')); ?>"></td>
            </tr>
            <tr>
                <th><?php echo iw_t('اللوجو', 'Logo'); ?></th>
                <td>
                    <?php $logo = get_option('iw_logo_url', ''); ?>
                    <?php if ($logo): ?>
                        <div style="margin-bottom:10px;"><img src="<?php echo esc_url($logo); ?>" style="max-height:80px;" /></div>
                    <?php endif; ?>
                    <input type="file" name="iw_logo" accept="image/*">
                    <p class="description"><?php echo iw_t('سيظهر اللوجو في جميع الأذون والأوراق المطبوعة', 'Logo will appear on all permits and printed documents.'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php echo iw_t('العنوان', 'Address'); ?></th>
                <td><textarea name="iw_address" class="large-text" rows="2"><?php echo esc_textarea(get_option('iw_address', '')); ?></textarea></td>
            </tr>
            <tr>
                <th><?php echo iw_t('الهاتف', 'Phone'); ?></th>
                <td><input type="text" name="iw_phone" class="regular-text" value="<?php echo esc_attr(get_option('iw_phone', '')); ?>"></td>
            </tr>
            <tr>
                <th><?php echo iw_t('حجم التوقيع في المطبوعات', 'Signature Size on Printouts'); ?></th>
                <td>
                    <input type="number" name="iw_signature_width" min="50" max="400" value="<?php echo esc_attr(get_option('iw_signature_width', 150)); ?>" style="width:100px;"> px
                    <p class="description"><?php echo iw_t('العرض الأقصى للتوقيع الإلكتروني في المطبوعات (بالبكسل). الارتفاع يتناسب تلقائياً.', 'Max width of the electronic signature on printouts (px). Height scales automatically.'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php echo iw_t('تفعيل حساب الضريبة', 'Enable Tax Calculation'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="iw_tax_enabled" value="1" <?php checked(get_option('iw_tax_enabled', '0'), '1'); ?>>
                        <?php echo iw_t('تفعيل حساب الضريبة في أذون الإضافة', 'Enable tax calculation in stock-in orders'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php echo iw_t('نسبة الضريبة %', 'Tax Rate %'); ?></th>
                <td>
                    <input type="number" name="iw_tax_rate" min="0" max="100" step="0.01" value="<?php echo esc_attr(get_option('iw_tax_rate', 14)); ?>" style="width:100px;"> %
                    <p class="description"><?php echo iw_t('النسبة الافتراضية: 14%', 'Default rate: 14%'); ?></p>
                </td>
            </tr>
        </table>
        <button type="submit" class="button button-primary button-large">
            <?php echo iw_t('حفظ الإعدادات', 'Save Settings'); ?>
        </button>
    </form>
</div>
