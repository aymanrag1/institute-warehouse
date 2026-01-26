<?php
/**
 * Setup Wizard View
 * معالج التنصيب
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = IW_Admin::get_settings();
?>

<div class="wrap iw-wrap" dir="rtl">
    <div class="iw-setup-wizard">
        <div class="iw-setup-header">
            <h1>
                <span class="dashicons dashicons-store"></span>
                مرحباً بك في نظام إدارة مخازن المعهد
            </h1>
            <p>يرجى إكمال البيانات التالية لإعداد النظام</p>
        </div>

        <form method="post" action="" enctype="multipart/form-data" class="iw-setup-form">
            <?php wp_nonce_field('iw_settings_nonce'); ?>

            <div class="iw-setup-section">
                <h2><span class="dashicons dashicons-admin-home"></span> بيانات المعهد</h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="institute_name">اسم المعهد <span class="required">*</span></label></th>
                        <td>
                            <input type="text" name="institute_name" id="institute_name" class="regular-text"
                                   value="<?php echo esc_attr($settings['institute_name'] ?? ''); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="institute_type">طبيعة العمل</label></th>
                        <td>
                            <select name="institute_type" id="institute_type" class="regular-text">
                                <option value="">اختر...</option>
                                <option value="educational" <?php selected($settings['institute_type'] ?? '', 'educational'); ?>>معهد تعليمي</option>
                                <option value="training" <?php selected($settings['institute_type'] ?? '', 'training'); ?>>مركز تدريب</option>
                                <option value="university" <?php selected($settings['institute_type'] ?? '', 'university'); ?>>جامعة</option>
                                <option value="school" <?php selected($settings['institute_type'] ?? '', 'school'); ?>>مدرسة</option>
                                <option value="other" <?php selected($settings['institute_type'] ?? '', 'other'); ?>>أخرى</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="institute_logo">شعار المعهد</label></th>
                        <td>
                            <input type="file" name="institute_logo" id="institute_logo" accept="image/*">
                            <?php if (!empty($settings['institute_logo'])): ?>
                                <p class="description">
                                    <img src="<?php echo esc_url($settings['institute_logo']); ?>" alt="" style="max-height: 60px;">
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="institute_address">العنوان</label></th>
                        <td>
                            <textarea name="institute_address" id="institute_address" class="large-text" rows="3"><?php echo esc_textarea($settings['institute_address'] ?? ''); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="institute_phone">رقم الهاتف</label></th>
                        <td>
                            <input type="text" name="institute_phone" id="institute_phone" class="regular-text"
                                   value="<?php echo esc_attr($settings['institute_phone'] ?? ''); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="institute_email">البريد الإلكتروني</label></th>
                        <td>
                            <input type="email" name="institute_email" id="institute_email" class="regular-text"
                                   value="<?php echo esc_attr($settings['institute_email'] ?? ''); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="dean_name">اسم عميد/مدير المعهد</label></th>
                        <td>
                            <input type="text" name="dean_name" id="dean_name" class="regular-text"
                                   value="<?php echo esc_attr($settings['dean_name'] ?? ''); ?>">
                            <p class="description">سيظهر في إذونات الصرف للتوقيع</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="iw-setup-section">
                <h2><span class="dashicons dashicons-admin-settings"></span> إعدادات النظام</h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="permit_prefix_add">بادئة إذن الإضافة</label></th>
                        <td>
                            <input type="text" name="permit_prefix_add" id="permit_prefix_add" class="small-text"
                                   value="<?php echo esc_attr($settings['permit_prefix_add'] ?? 'ADD'); ?>">
                            <p class="description">مثال: ADD-202501-0001</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="permit_prefix_withdraw">بادئة إذن الصرف</label></th>
                        <td>
                            <input type="text" name="permit_prefix_withdraw" id="permit_prefix_withdraw" class="small-text"
                                   value="<?php echo esc_attr($settings['permit_prefix_withdraw'] ?? 'WD'); ?>">
                            <p class="description">مثال: WD-202501-0001</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="low_stock_threshold">حد التنبيه للمخزون المنخفض</label></th>
                        <td>
                            <input type="number" name="low_stock_threshold" id="low_stock_threshold" class="small-text"
                                   value="<?php echo esc_attr($settings['low_stock_threshold'] ?? 10); ?>" min="0">
                            <p class="description">الكمية الافتراضية للتنبيه</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="expiry_warning_days">أيام التحذير قبل انتهاء الصلاحية</label></th>
                        <td>
                            <input type="number" name="expiry_warning_days" id="expiry_warning_days" class="small-text"
                                   value="<?php echo esc_attr($settings['expiry_warning_days'] ?? 30); ?>" min="1">
                            <p class="description">عدد الأيام قبل انتهاء الصلاحية للتنبيه</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="currency">العملة</label></th>
                        <td>
                            <select name="currency" id="currency">
                                <option value="SAR" <?php selected($settings['currency'] ?? 'SAR', 'SAR'); ?>>ريال سعودي (SAR)</option>
                                <option value="AED" <?php selected($settings['currency'] ?? '', 'AED'); ?>>درهم إماراتي (AED)</option>
                                <option value="EGP" <?php selected($settings['currency'] ?? '', 'EGP'); ?>>جنيه مصري (EGP)</option>
                                <option value="KWD" <?php selected($settings['currency'] ?? '', 'KWD'); ?>>دينار كويتي (KWD)</option>
                                <option value="QAR" <?php selected($settings['currency'] ?? '', 'QAR'); ?>>ريال قطري (QAR)</option>
                                <option value="BHD" <?php selected($settings['currency'] ?? '', 'BHD'); ?>>دينار بحريني (BHD)</option>
                                <option value="OMR" <?php selected($settings['currency'] ?? '', 'OMR'); ?>>ريال عماني (OMR)</option>
                                <option value="JOD" <?php selected($settings['currency'] ?? '', 'JOD'); ?>>دينار أردني (JOD)</option>
                                <option value="USD" <?php selected($settings['currency'] ?? '', 'USD'); ?>>دولار أمريكي (USD)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="date_format">تنسيق التاريخ</label></th>
                        <td>
                            <select name="date_format" id="date_format">
                                <option value="d/m/Y" <?php selected($settings['date_format'] ?? 'd/m/Y', 'd/m/Y'); ?>>25/01/2025</option>
                                <option value="Y-m-d" <?php selected($settings['date_format'] ?? '', 'Y-m-d'); ?>>2025-01-25</option>
                                <option value="d-m-Y" <?php selected($settings['date_format'] ?? '', 'd-m-Y'); ?>>25-01-2025</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="iw-setup-actions">
                <button type="submit" name="iw_save_settings" class="button button-primary button-hero">
                    <span class="dashicons dashicons-yes"></span>
                    حفظ وبدء استخدام النظام
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.iw-setup-wizard {
    max-width: 800px;
    margin: 40px auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}
.iw-setup-header {
    background: linear-gradient(135deg, #0073aa 0%, #005177 100%);
    color: #fff;
    padding: 40px;
    text-align: center;
}
.iw-setup-header h1 {
    margin: 0 0 10px;
    font-size: 28px;
    color: #fff;
}
.iw-setup-header h1 .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    margin-left: 10px;
}
.iw-setup-header p {
    margin: 0;
    opacity: 0.9;
}
.iw-setup-form {
    padding: 30px;
}
.iw-setup-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #eee;
}
.iw-setup-section:last-of-type {
    border-bottom: none;
}
.iw-setup-section h2 {
    margin: 0 0 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #0073aa;
    display: inline-block;
}
.iw-setup-section h2 .dashicons {
    color: #0073aa;
    margin-left: 5px;
}
.iw-setup-actions {
    text-align: center;
    padding-top: 20px;
}
.button-hero {
    padding: 15px 50px !important;
    height: auto !important;
    font-size: 16px !important;
}
.button-hero .dashicons {
    margin-left: 5px;
}
.required {
    color: #d63638;
}
</style>
