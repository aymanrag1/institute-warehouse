<?php
/**
 * Dashboard View
 * لوحة التحكم الرئيسية
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = IW_Admin::get_settings();
$setup_completed = isset($settings['setup_completed']) && $settings['setup_completed'] === '1';

// إذا لم يتم إكمال التنصيب، عرض معالج التنصيب
if (!$setup_completed) {
    include IW_PLUGIN_DIR . 'admin/views/setup-wizard.php';
    return;
}

$stats = IW_Reports::get_dashboard_stats();
$low_stock_products = IW_Products::get_low_stock_products();
$alerts = IW_Reports::get_alerts(array('is_resolved' => false, 'per_page' => 5));
?>

<div class="wrap iw-wrap" dir="rtl">
    <h1 class="wp-heading-inline">
        <?php if (!empty($settings['institute_logo'])): ?>
            <img src="<?php echo esc_url($settings['institute_logo']); ?>" alt="" style="height: 40px; vertical-align: middle; margin-left: 10px;">
        <?php endif; ?>
        <?php echo esc_html($settings['institute_name'] ?: 'نظام إدارة المخازن'); ?>
    </h1>

    <div class="iw-dashboard">
        <!-- إحصائيات سريعة -->
        <div class="iw-stats-grid">
            <div class="iw-stat-card">
                <div class="iw-stat-icon bg-primary">
                    <span class="dashicons dashicons-archive"></span>
                </div>
                <div class="iw-stat-content">
                    <h3><?php echo number_format($stats['total_products']); ?></h3>
                    <p>إجمالي الأصناف</p>
                </div>
            </div>

            <div class="iw-stat-card">
                <div class="iw-stat-icon bg-success">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div class="iw-stat-content">
                    <h3><?php echo IW_Admin::format_currency($stats['total_stock_value']); ?></h3>
                    <p>قيمة المخزون</p>
                </div>
            </div>

            <div class="iw-stat-card <?php echo $stats['low_stock_count'] > 0 ? 'warning' : ''; ?>">
                <div class="iw-stat-icon bg-warning">
                    <span class="dashicons dashicons-warning"></span>
                </div>
                <div class="iw-stat-content">
                    <h3><?php echo number_format($stats['low_stock_count']); ?></h3>
                    <p>أصناف منخفضة المخزون</p>
                </div>
            </div>

            <div class="iw-stat-card <?php echo $stats['out_of_stock_count'] > 0 ? 'danger' : ''; ?>">
                <div class="iw-stat-icon bg-danger">
                    <span class="dashicons dashicons-dismiss"></span>
                </div>
                <div class="iw-stat-content">
                    <h3><?php echo number_format($stats['out_of_stock_count']); ?></h3>
                    <p>أصناف نافذة</p>
                </div>
            </div>
        </div>

        <!-- إحصائيات الشهر -->
        <div class="iw-stats-grid iw-stats-secondary">
            <div class="iw-stat-card small">
                <div class="iw-stat-content">
                    <h4><?php echo IW_Admin::format_currency($stats['month_additions']); ?></h4>
                    <p>إضافات الشهر الحالي</p>
                </div>
            </div>

            <div class="iw-stat-card small">
                <div class="iw-stat-content">
                    <h4><?php echo number_format($stats['month_withdrawals']); ?></h4>
                    <p>إذونات الصرف هذا الشهر</p>
                </div>
            </div>

            <div class="iw-stat-card small">
                <div class="iw-stat-content">
                    <h4><?php echo number_format($stats['pending_add_permits']); ?></h4>
                    <p>إذونات إضافة معلقة</p>
                </div>
            </div>

            <div class="iw-stat-card small">
                <div class="iw-stat-content">
                    <h4><?php echo number_format($stats['pending_withdraw_permits']); ?></h4>
                    <p>إذونات صرف معلقة</p>
                </div>
            </div>
        </div>

        <div class="iw-dashboard-grid">
            <!-- الأصناف منخفضة المخزون -->
            <div class="iw-dashboard-card">
                <div class="iw-card-header">
                    <h3><span class="dashicons dashicons-warning"></span> أصناف تحتاج إعادة طلب</h3>
                    <a href="<?php echo admin_url('admin.php?page=iw-reports&tab=low-stock'); ?>" class="button button-small">عرض الكل</a>
                </div>
                <div class="iw-card-body">
                    <?php if (!empty($low_stock_products)): ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>الصنف</th>
                                    <th>الكمية الحالية</th>
                                    <th>حد الطلب</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($low_stock_products, 0, 5) as $product): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($product->name); ?></strong>
                                            <br><small><?php echo esc_html($product->sku); ?></small>
                                        </td>
                                        <td>
                                            <span class="iw-badge <?php echo $product->current_stock == 0 ? 'danger' : 'warning'; ?>">
                                                <?php echo number_format($product->current_stock); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($product->reorder_level); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="iw-empty-state">جميع الأصناف متوفرة بكميات كافية</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- التنبيهات الأخيرة -->
            <div class="iw-dashboard-card">
                <div class="iw-card-header">
                    <h3><span class="dashicons dashicons-bell"></span> التنبيهات
                        <?php if ($stats['unread_alerts'] > 0): ?>
                            <span class="iw-badge danger"><?php echo $stats['unread_alerts']; ?></span>
                        <?php endif; ?>
                    </h3>
                    <a href="<?php echo admin_url('admin.php?page=iw-reports&tab=alerts'); ?>" class="button button-small">عرض الكل</a>
                </div>
                <div class="iw-card-body">
                    <?php if (!empty($alerts)): ?>
                        <ul class="iw-alerts-list">
                            <?php foreach ($alerts as $alert):
                                $alert_info = IW_Admin::get_alert_type_label($alert->alert_type);
                            ?>
                                <li class="iw-alert-item <?php echo $alert->is_read ? '' : 'unread'; ?>">
                                    <span class="dashicons dashicons-<?php echo esc_attr($alert_info['icon']); ?> text-<?php echo esc_attr($alert_info['class']); ?>"></span>
                                    <div class="iw-alert-content">
                                        <p><?php echo esc_html($alert->message); ?></p>
                                        <small><?php echo IW_Admin::format_date($alert->created_at, 'd/m/Y H:i'); ?></small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="iw-empty-state">لا توجد تنبيهات جديدة</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- روابط سريعة -->
        <div class="iw-quick-actions">
            <h3>الإجراءات السريعة</h3>
            <div class="iw-actions-grid">
                <?php if (current_user_can('iw_add_stock')): ?>
                    <a href="<?php echo admin_url('admin.php?page=iw-add-stock'); ?>" class="iw-action-btn">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <span>إذن إضافة جديد</span>
                    </a>
                <?php endif; ?>

                <?php if (current_user_can('iw_withdraw_stock')): ?>
                    <a href="<?php echo admin_url('admin.php?page=iw-withdraw-stock'); ?>" class="iw-action-btn">
                        <span class="dashicons dashicons-minus"></span>
                        <span>إذن صرف جديد</span>
                    </a>
                <?php endif; ?>

                <?php if (current_user_can('iw_manage_products')): ?>
                    <a href="<?php echo admin_url('admin.php?page=iw-products'); ?>" class="iw-action-btn">
                        <span class="dashicons dashicons-archive"></span>
                        <span>إدارة الأصناف</span>
                    </a>
                <?php endif; ?>

                <?php if (current_user_can('iw_view_reports')): ?>
                    <a href="<?php echo admin_url('admin.php?page=iw-reports'); ?>" class="iw-action-btn">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <span>التقارير</span>
                    </a>
                <?php endif; ?>

                <?php if (current_user_can('iw_import_data')): ?>
                    <a href="<?php echo admin_url('admin.php?page=iw-import'); ?>" class="iw-action-btn">
                        <span class="dashicons dashicons-upload"></span>
                        <span>استيراد بيانات</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
