<?php
/**
 * Settings View
 * صفحة الإعدادات
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = IW_Admin::get_settings();
settings_errors('iw_settings');
?>

<div class="wrap iw-wrap" dir="rtl">
    <h1>
        <span class="dashicons dashicons-admin-settings"></span>
        إعدادات النظام
    </h1>

    <form method="post" action="" enctype="multipart/form-data">
        <?php wp_nonce_field('iw_settings_nonce'); ?>

        <div class="iw-settings-container">
            <div class="iw-settings-tabs">
                <a href="#tab-institute" class="iw-tab active">بيانات المعهد</a>
                <a href="#tab-system" class="iw-tab">إعدادات النظام</a>
                <a href="#tab-warehouses" class="iw-tab">المخازن</a>
                <a href="#tab-permissions" class="iw-tab">الصلاحيات</a>
            </div>

            <div class="iw-settings-content">
                <!-- بيانات المعهد -->
                <div id="tab-institute" class="iw-tab-content active">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="institute_name">اسم المعهد</label></th>
                            <td>
                                <input type="text" name="institute_name" id="institute_name" class="regular-text"
                                       value="<?php echo esc_attr($settings['institute_name'] ?? ''); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="institute_type">طبيعة العمل</label></th>
                            <td>
                                <select name="institute_type" id="institute_type">
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
                                <?php if (!empty($settings['institute_logo'])): ?>
                                    <p><img src="<?php echo esc_url($settings['institute_logo']); ?>" alt="" style="max-height: 80px;"></p>
                                    <input type="hidden" name="institute_logo_url" value="<?php echo esc_url($settings['institute_logo']); ?>">
                                <?php endif; ?>
                                <input type="file" name="institute_logo" id="institute_logo" accept="image/*">
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
                                <p class="description">سيظهر في إذونات الصرف</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- إعدادات النظام -->
                <div id="tab-system" class="iw-tab-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="permit_prefix_add">بادئة إذن الإضافة</label></th>
                            <td>
                                <input type="text" name="permit_prefix_add" id="permit_prefix_add" class="small-text"
                                       value="<?php echo esc_attr($settings['permit_prefix_add'] ?? 'ADD'); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="permit_prefix_withdraw">بادئة إذن الصرف</label></th>
                            <td>
                                <input type="text" name="permit_prefix_withdraw" id="permit_prefix_withdraw" class="small-text"
                                       value="<?php echo esc_attr($settings['permit_prefix_withdraw'] ?? 'WD'); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="low_stock_threshold">حد التنبيه للمخزون المنخفض</label></th>
                            <td>
                                <input type="number" name="low_stock_threshold" id="low_stock_threshold" class="small-text"
                                       value="<?php echo esc_attr($settings['low_stock_threshold'] ?? 10); ?>" min="0">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="expiry_warning_days">أيام التحذير قبل انتهاء الصلاحية</label></th>
                            <td>
                                <input type="number" name="expiry_warning_days" id="expiry_warning_days" class="small-text"
                                       value="<?php echo esc_attr($settings['expiry_warning_days'] ?? 30); ?>" min="1">
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

                <!-- المخازن -->
                <div id="tab-warehouses" class="iw-tab-content">
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=iw-departments&tab=warehouses'); ?>" class="button">
                            إدارة المخازن
                        </a>
                    </p>
                    <?php
                    $warehouses = IW_Departments::get_warehouses();
                    if (!empty($warehouses)):
                    ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>اسم المخزن</th>
                                    <th>الموقع</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($warehouses as $wh): ?>
                                    <tr>
                                        <td><?php echo esc_html($wh->name); ?></td>
                                        <td><?php echo esc_html($wh->location ?: '-'); ?></td>
                                        <td>
                                            <span class="iw-badge <?php echo $wh->status === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo $wh->status === 'active' ? 'نشط' : 'غير نشط'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- الصلاحيات -->
                <div id="tab-permissions" class="iw-tab-content">
                    <h3>أدوار النظام</h3>
                    <?php
                    $roles = IW_Permissions::get_roles();
                    $capabilities_grouped = IW_Permissions::get_capabilities_grouped();
                    ?>

                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>الدور</th>
                                <th>الصلاحيات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role_key => $role): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($role['name']); ?></strong></td>
                                    <td>
                                        <?php
                                        $cap_labels = array();
                                        foreach ($role['capabilities'] as $cap) {
                                            $all_caps = IW_Permissions::get_capabilities();
                                            if (isset($all_caps[$cap])) {
                                                $cap_labels[] = $all_caps[$cap];
                                            }
                                        }
                                        echo esc_html(implode(' | ', $cap_labels));
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p class="description">يمكن تعيين الأدوار للمستخدمين من صفحة المستخدمين في WordPress</p>
                </div>
            </div>
        </div>

        <p class="submit">
            <button type="submit" name="iw_save_settings" class="button button-primary">
                <span class="dashicons dashicons-saved"></span>
                حفظ الإعدادات
            </button>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // التبويبات
    $('.iw-settings-tabs .iw-tab').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('href');

        $('.iw-tab').removeClass('active');
        $(this).addClass('active');

        $('.iw-tab-content').removeClass('active');
        $(target).addClass('active');
    });
});
</script>
