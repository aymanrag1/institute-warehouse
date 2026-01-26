<?php
/**
 * Reports View
 * صفحة التقارير
 */

if (!defined('ABSPATH')) {
    exit;
}

$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'stock';
$warehouses = IW_Admin::get_active_warehouses();
$categories = IW_Admin::get_categories();
$departments = IW_Admin::get_active_departments();
?>

<div class="wrap iw-wrap" dir="rtl">
    <h1>
        <span class="dashicons dashicons-chart-bar"></span>
        التقارير
    </h1>

    <nav class="nav-tab-wrapper">
        <a href="?page=iw-reports&tab=stock" class="nav-tab <?php echo $tab === 'stock' ? 'nav-tab-active' : ''; ?>">تقرير المخزون</a>
        <a href="?page=iw-reports&tab=low-stock" class="nav-tab <?php echo $tab === 'low-stock' ? 'nav-tab-active' : ''; ?>">أصناف تحتاج إعادة طلب</a>
        <a href="?page=iw-reports&tab=transactions" class="nav-tab <?php echo $tab === 'transactions' ? 'nav-tab-active' : ''; ?>">حركة المخزون</a>
        <a href="?page=iw-reports&tab=consumption" class="nav-tab <?php echo $tab === 'consumption' ? 'nav-tab-active' : ''; ?>">استهلاك الأقسام</a>
        <a href="?page=iw-reports&tab=expiry" class="nav-tab <?php echo $tab === 'expiry' ? 'nav-tab-active' : ''; ?>">انتهاء الصلاحية</a>
        <a href="?page=iw-reports&tab=alerts" class="nav-tab <?php echo $tab === 'alerts' ? 'nav-tab-active' : ''; ?>">التنبيهات</a>
    </nav>

    <div class="iw-report-content">
        <?php if ($tab === 'stock'): ?>
            <!-- تقرير المخزون -->
            <div class="iw-filters-bar">
                <select id="filter-warehouse">
                    <option value="">جميع المخازن</option>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?php echo esc_attr($wh->id); ?>"><?php echo esc_html($wh->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filter-category">
                    <option value="">جميع الفئات</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo esc_attr($cat->id); ?>"><?php echo esc_html($cat->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <label><input type="checkbox" id="exclude-zero"> إخفاء الأصناف النافذة</label>
                <button type="button" class="button" id="btn-load-report">عرض التقرير</button>
                <button type="button" class="button" id="btn-print-report">
                    <span class="dashicons dashicons-printer"></span> طباعة
                </button>
            </div>
            <div id="report-container"></div>

        <?php elseif ($tab === 'low-stock'): ?>
            <!-- أصناف تحتاج إعادة طلب -->
            <div class="iw-filters-bar">
                <select id="filter-warehouse">
                    <option value="">جميع المخازن</option>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?php echo esc_attr($wh->id); ?>"><?php echo esc_html($wh->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button" id="btn-load-report">عرض التقرير</button>
                <button type="button" class="button" id="btn-print-report">
                    <span class="dashicons dashicons-printer"></span> طباعة
                </button>
            </div>
            <div id="report-container"></div>

        <?php elseif ($tab === 'transactions'): ?>
            <!-- حركة المخزون -->
            <div class="iw-filters-bar">
                <select id="filter-warehouse">
                    <option value="">جميع المخازن</option>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?php echo esc_attr($wh->id); ?>"><?php echo esc_html($wh->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filter-type">
                    <option value="">جميع الأنواع</option>
                    <option value="add">إضافة</option>
                    <option value="withdraw">صرف</option>
                </select>
                <input type="date" id="filter-date-from">
                <input type="date" id="filter-date-to">
                <button type="button" class="button" id="btn-load-report">عرض التقرير</button>
            </div>
            <div id="report-container"></div>

        <?php elseif ($tab === 'consumption'): ?>
            <!-- استهلاك الأقسام -->
            <div class="iw-filters-bar">
                <select id="filter-department">
                    <option value="">جميع الأقسام</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo esc_attr($dept->id); ?>"><?php echo esc_html($dept->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" id="filter-date-from" value="<?php echo date('Y-m-01'); ?>">
                <input type="date" id="filter-date-to" value="<?php echo date('Y-m-t'); ?>">
                <button type="button" class="button" id="btn-load-report">عرض التقرير</button>
            </div>
            <div id="report-container"></div>

        <?php elseif ($tab === 'expiry'): ?>
            <!-- انتهاء الصلاحية -->
            <div class="iw-filters-bar">
                <select id="filter-days">
                    <option value="7">خلال أسبوع</option>
                    <option value="30" selected>خلال شهر</option>
                    <option value="90">خلال 3 أشهر</option>
                    <option value="180">خلال 6 أشهر</option>
                </select>
                <select id="filter-warehouse">
                    <option value="">جميع المخازن</option>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?php echo esc_attr($wh->id); ?>"><?php echo esc_html($wh->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button" id="btn-load-report">عرض التقرير</button>
            </div>
            <div id="report-container"></div>

        <?php elseif ($tab === 'alerts'): ?>
            <!-- التنبيهات -->
            <div class="iw-filters-bar">
                <select id="filter-type">
                    <option value="">جميع الأنواع</option>
                    <option value="low_stock">مخزون منخفض</option>
                    <option value="out_of_stock">نفاذ المخزون</option>
                    <option value="expiry_warning">قرب انتهاء الصلاحية</option>
                    <option value="expiry">انتهاء الصلاحية</option>
                </select>
                <select id="filter-resolved">
                    <option value="false">غير محلولة</option>
                    <option value="true">محلولة</option>
                    <option value="">الكل</option>
                </select>
                <button type="button" class="button" id="btn-load-report">عرض</button>
            </div>
            <div id="report-container"></div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const tab = '<?php echo $tab; ?>';

    function loadReport() {
        let action, data = { nonce: iwAdmin.nonce };

        switch (tab) {
            case 'stock':
                action = 'iw_get_stock_report';
                data.warehouse_id = $('#filter-warehouse').val();
                data.category_id = $('#filter-category').val();
                data.exclude_zero_stock = $('#exclude-zero').is(':checked');
                break;

            case 'low-stock':
                action = 'iw_get_low_stock_report';
                data.warehouse_id = $('#filter-warehouse').val();
                break;

            case 'transactions':
                action = 'iw_get_transactions_report';
                data.warehouse_id = $('#filter-warehouse').val();
                data.transaction_type = $('#filter-type').val();
                data.date_from = $('#filter-date-from').val();
                data.date_to = $('#filter-date-to').val();
                break;

            case 'consumption':
                action = 'iw_get_department_consumption';
                data.department_id = $('#filter-department').val();
                data.date_from = $('#filter-date-from').val();
                data.date_to = $('#filter-date-to').val();
                break;

            case 'expiry':
                action = 'iw_get_expiry_report';
                data.days = $('#filter-days').val();
                data.warehouse_id = $('#filter-warehouse').val();
                break;

            case 'alerts':
                action = 'iw_get_alerts';
                data.alert_type = $('#filter-type').val();
                const resolved = $('#filter-resolved').val();
                if (resolved !== '') data.is_resolved = resolved;
                break;
        }

        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: { action: action, ...data },
            beforeSend: function() {
                $('#report-container').html('<p class="iw-loading"><span class="spinner is-active"></span> جاري التحميل...</p>');
            },
            success: function(response) {
                if (response.success) {
                    renderReport(response.data);
                }
            }
        });
    }

    function renderReport(data) {
        let html = '';

        switch (tab) {
            case 'stock':
                if (data.length === 0) {
                    html = '<p class="iw-empty">لا توجد بيانات</p>';
                } else {
                    html = '<table class="widefat striped" id="printable-report"><thead><tr><th>الكود</th><th>الصنف</th><th>الفئة</th><th>الكمية</th><th>القيمة</th><th>حد الطلب</th><th>مكان التخزين</th></tr></thead><tbody>';
                    let totalQty = 0, totalValue = 0;
                    data.forEach(function(row) {
                        const stockClass = row.total_quantity <= 0 ? 'danger' : (row.reorder_level > 0 && row.total_quantity <= row.reorder_level ? 'warning' : '');
                        totalQty += parseInt(row.total_quantity);
                        totalValue += parseFloat(row.total_value);
                        html += `<tr class="${stockClass}"><td>${row.sku}</td><td>${row.name}</td><td>${row.category_name || '-'}</td><td>${parseInt(row.total_quantity).toLocaleString()}</td><td>${parseFloat(row.total_value).toLocaleString()}</td><td>${row.reorder_level || '-'}</td><td>${row.storage_location || '-'}</td></tr>`;
                    });
                    html += `</tbody><tfoot><tr><td colspan="3"><strong>الإجمالي</strong></td><td><strong>${totalQty.toLocaleString()}</strong></td><td><strong>${totalValue.toLocaleString()}</strong></td><td colspan="2"></td></tr></tfoot></table>`;
                }
                break;

            case 'low-stock':
                if (data.length === 0) {
                    html = '<p class="iw-empty">جميع الأصناف متوفرة بكميات كافية</p>';
                } else {
                    html = '<table class="widefat striped" id="printable-report"><thead><tr><th>الكود</th><th>الصنف</th><th>الكمية الحالية</th><th>حد الطلب</th><th>النقص</th><th>مكان التخزين</th></tr></thead><tbody>';
                    data.forEach(function(row) {
                        const stockClass = row.current_stock == 0 ? 'danger' : 'warning';
                        html += `<tr class="${stockClass}"><td>${row.sku}</td><td>${row.name}</td><td>${parseInt(row.current_stock).toLocaleString()}</td><td>${row.reorder_level}</td><td>${row.shortage}</td><td>${row.storage_location || '-'}</td></tr>`;
                    });
                    html += '</tbody></table>';
                }
                break;

            case 'transactions':
                if (data.length === 0) {
                    html = '<p class="iw-empty">لا توجد حركات</p>';
                } else {
                    html = '<table class="widefat striped"><thead><tr><th>التاريخ</th><th>النوع</th><th>الصنف</th><th>الكمية</th><th>المرجع</th><th>بواسطة</th></tr></thead><tbody>';
                    data.forEach(function(row) {
                        const typeLabel = row.transaction_type === 'add' ? 'إضافة' : (row.transaction_type === 'withdraw' ? 'صرف' : row.transaction_type);
                        const typeClass = row.transaction_type === 'add' ? 'success' : 'warning';
                        html += `<tr><td>${row.created_at}</td><td><span class="iw-badge ${typeClass}">${typeLabel}</span></td><td>${row.product_sku} - ${row.product_name}</td><td>${row.quantity}</td><td>${row.reference_number || '-'}</td><td>${row.created_by_name || '-'}</td></tr>`;
                    });
                    html += '</tbody></table>';
                }
                break;

            case 'consumption':
                html = '<div class="iw-report-grid">';

                // حسب القسم
                html += '<div class="iw-report-section"><h3>الاستهلاك حسب القسم</h3>';
                if (data.by_department && data.by_department.length > 0) {
                    html += '<table class="widefat striped"><thead><tr><th>القسم</th><th>عدد الإذونات</th><th>إجمالي الكميات</th></tr></thead><tbody>';
                    data.by_department.forEach(function(row) {
                        html += `<tr><td>${row.department_name}</td><td>${row.permit_count}</td><td>${parseInt(row.total_quantity).toLocaleString()}</td></tr>`;
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p class="iw-empty">لا توجد بيانات</p>';
                }
                html += '</div>';

                // حسب المنتج
                html += '<div class="iw-report-section"><h3>أكثر الأصناف استهلاكاً</h3>';
                if (data.by_product && data.by_product.length > 0) {
                    html += '<table class="widefat striped"><thead><tr><th>الصنف</th><th>الكمية</th></tr></thead><tbody>';
                    data.by_product.forEach(function(row) {
                        html += `<tr><td>${row.sku} - ${row.product_name}</td><td>${parseInt(row.total_quantity).toLocaleString()}</td></tr>`;
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p class="iw-empty">لا توجد بيانات</p>';
                }
                html += '</div></div>';
                break;

            case 'expiry':
                if (data.length === 0) {
                    html = '<p class="iw-empty">لا توجد أصناف قريبة من انتهاء الصلاحية</p>';
                } else {
                    html = '<table class="widefat striped"><thead><tr><th>الصنف</th><th>الدفعة</th><th>الكمية</th><th>تاريخ الصلاحية</th><th>الأيام المتبقية</th><th>المخزن</th></tr></thead><tbody>';
                    data.forEach(function(row) {
                        const daysClass = row.days_remaining <= 7 ? 'danger' : (row.days_remaining <= 30 ? 'warning' : '');
                        html += `<tr class="${daysClass}"><td>${row.sku} - ${row.name}</td><td>${row.batch_number || '-'}</td><td>${row.remaining_quantity}</td><td>${row.expiry_date}</td><td>${row.days_remaining} يوم</td><td>${row.warehouse_name || '-'}</td></tr>`;
                    });
                    html += '</tbody></table>';
                }
                break;

            case 'alerts':
                if (data.length === 0) {
                    html = '<p class="iw-empty">لا توجد تنبيهات</p>';
                } else {
                    html = '<table class="widefat striped"><thead><tr><th>النوع</th><th>الرسالة</th><th>التاريخ</th><th>الحالة</th><th>الإجراءات</th></tr></thead><tbody>';
                    data.forEach(function(row) {
                        const resolved = row.is_resolved == 1;
                        html += `<tr class="${row.is_read == 0 ? 'unread' : ''}">
                            <td>${getAlertTypeLabel(row.alert_type)}</td>
                            <td>${row.message}</td>
                            <td>${row.created_at}</td>
                            <td>${resolved ? '<span class="iw-badge success">محلول</span>' : '<span class="iw-badge warning">نشط</span>'}</td>
                            <td>${!resolved ? `<button class="button button-small" onclick="resolveAlert(${row.id})">تم الحل</button>` : ''}</td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                }
                break;
        }

        $('#report-container').html(html);
    }

    function getAlertTypeLabel(type) {
        const labels = {
            'low_stock': 'مخزون منخفض',
            'out_of_stock': 'نفاذ المخزون',
            'expiry_warning': 'قرب انتهاء الصلاحية',
            'expiry': 'انتهاء الصلاحية',
            'reorder': 'حد إعادة الطلب'
        };
        return labels[type] || type;
    }

    window.resolveAlert = function(id) {
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'iw_resolve_alert',
                nonce: iwAdmin.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    loadReport();
                }
            }
        });
    };

    $('#btn-load-report').on('click', loadReport);
    $('#btn-print-report').on('click', function() {
        window.print();
    });

    loadReport();
});
</script>
