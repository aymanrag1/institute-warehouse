<?php
/**
 * Withdraw Stock View
 * صفحة إذن الصرف
 */

if (!defined('ABSPATH')) {
    exit;
}

$warehouses = IW_Admin::get_active_warehouses();
$departments = IW_Admin::get_active_departments();
$view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
$permit_id = isset($_GET['permit_id']) ? absint($_GET['permit_id']) : 0;
?>

<div class="wrap iw-wrap" dir="rtl">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-minus"></span>
        إذن صرف
    </h1>

    <?php if ($view !== 'new'): ?>
        <a href="<?php echo admin_url('admin.php?page=iw-withdraw-stock&view=new'); ?>" class="page-title-action">
            <span class="dashicons dashicons-plus"></span> إذن صرف جديد
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php if ($view === 'new'): ?>
        <!-- نموذج إذن صرف جديد -->
        <div class="iw-permit-form">
            <form id="withdraw-permit-form">
                <div class="iw-card">
                    <div class="iw-card-header">
                        <h3>بيانات الإذن</h3>
                    </div>
                    <div class="iw-card-body">
                        <div class="iw-form-row">
                            <div class="iw-form-group">
                                <label for="warehouse_id">المخزن <span class="required">*</span></label>
                                <select name="warehouse_id" id="warehouse_id" required>
                                    <option value="">اختر المخزن</option>
                                    <?php foreach ($warehouses as $wh): ?>
                                        <option value="<?php echo esc_attr($wh->id); ?>"><?php echo esc_html($wh->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="iw-form-group">
                                <label for="department_id">القسم <span class="required">*</span></label>
                                <select name="department_id" id="department_id" required>
                                    <option value="">اختر القسم</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo esc_attr($dept->id); ?>"><?php echo esc_html($dept->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="iw-form-row">
                            <div class="iw-form-group">
                                <label for="employee_search">الموظف المستلم</label>
                                <input type="text" id="employee_search" placeholder="ابحث عن موظف...">
                                <input type="hidden" name="employee_id" id="employee_id">
                                <div id="employee_results" class="iw-autocomplete-results"></div>
                            </div>
                            <div class="iw-form-group">
                                <label for="employee_name">اسم المستلم</label>
                                <input type="text" name="employee_name" id="employee_name" placeholder="أو اكتب الاسم مباشرة">
                            </div>
                        </div>

                        <div class="iw-form-row">
                            <div class="iw-form-group">
                                <label for="storage_location">مكان التخزين</label>
                                <input type="text" name="storage_location" id="storage_location">
                            </div>
                            <div class="iw-form-group">
                                <label for="purpose">الغرض من الصرف</label>
                                <input type="text" name="purpose" id="purpose">
                            </div>
                        </div>

                        <div class="iw-form-group">
                            <label for="notes">ملاحظات</label>
                            <textarea name="notes" id="notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <div class="iw-card">
                    <div class="iw-card-header">
                        <h3>الأصناف المطلوبة</h3>
                        <button type="button" class="button" id="btn-add-item">
                            <span class="dashicons dashicons-plus"></span> إضافة صنف
                        </button>
                    </div>
                    <div class="iw-card-body">
                        <table class="widefat" id="items-table">
                            <thead>
                                <tr>
                                    <th style="width:35%">الصنف</th>
                                    <th style="width:15%">المتاح</th>
                                    <th style="width:15%">الكمية المطلوبة</th>
                                    <th style="width:20%">مكان التخزين</th>
                                    <th style="width:10%">ملاحظات</th>
                                    <th style="width:5%"></th>
                                </tr>
                            </thead>
                            <tbody id="items-tbody">
                                <tr class="iw-empty-row">
                                    <td colspan="6">لم يتم إضافة أصناف بعد</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="iw-form-actions">
                    <a href="<?php echo admin_url('admin.php?page=iw-withdraw-stock'); ?>" class="button">إلغاء</a>
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-saved"></span> حفظ الإذن
                    </button>
                </div>
            </form>
        </div>

    <?php elseif ($permit_id > 0): ?>
        <!-- عرض تفاصيل الإذن -->
        <?php
        $permit = IW_Transactions::get_withdraw_permit($permit_id);
        if ($permit):
            $status_info = IW_Admin::get_permit_status_label($permit->status, 'withdraw');
            $settings = IW_Admin::get_settings();
        ?>
            <div class="iw-permit-details">
                <div class="iw-card">
                    <div class="iw-card-header">
                        <h3>إذن صرف رقم: <?php echo esc_html($permit->permit_number); ?></h3>
                        <span class="iw-badge <?php echo esc_attr($status_info['class']); ?>"><?php echo esc_html($status_info['label']); ?></span>
                    </div>
                    <div class="iw-card-body">
                        <div class="iw-details-grid">
                            <div class="iw-detail-item">
                                <label>المخزن:</label>
                                <span><?php echo esc_html($permit->warehouse_name); ?></span>
                            </div>
                            <div class="iw-detail-item">
                                <label>القسم:</label>
                                <span><?php echo esc_html($permit->department_name ?: '-'); ?></span>
                            </div>
                            <div class="iw-detail-item">
                                <label>الموظف المستلم:</label>
                                <span><?php echo esc_html($permit->employee_full_name ?: $permit->employee_name ?: '-'); ?></span>
                            </div>
                            <div class="iw-detail-item">
                                <label>مكان التخزين:</label>
                                <span><?php echo esc_html($permit->storage_location ?: '-'); ?></span>
                            </div>
                            <div class="iw-detail-item">
                                <label>تاريخ الإنشاء:</label>
                                <span><?php echo IW_Admin::format_date($permit->created_at, 'd/m/Y H:i'); ?></span>
                            </div>
                            <div class="iw-detail-item">
                                <label>بواسطة:</label>
                                <span><?php echo esc_html($permit->created_by_name); ?></span>
                            </div>
                            <?php if ($permit->purpose): ?>
                                <div class="iw-detail-item" style="grid-column: span 2;">
                                    <label>الغرض:</label>
                                    <span><?php echo esc_html($permit->purpose); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h4>الأصناف:</h4>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>الكود</th>
                                    <th>الصنف</th>
                                    <th>الكمية المطلوبة</th>
                                    <th>الكمية المعتمدة</th>
                                    <th>الكمية المسلمة</th>
                                    <th>مكان التخزين</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($permit->items as $item): ?>
                                    <tr>
                                        <td><?php echo esc_html($item->product_sku); ?></td>
                                        <td><?php echo esc_html($item->product_name); ?></td>
                                        <td><?php echo number_format($item->requested_quantity); ?> <?php echo esc_html($item->unit); ?></td>
                                        <td><?php echo $item->approved_quantity !== null ? number_format($item->approved_quantity) : '-'; ?></td>
                                        <td><?php echo $item->delivered_quantity !== null ? number_format($item->delivered_quantity) : '-'; ?></td>
                                        <td><?php echo esc_html($item->storage_location ?: $item->default_location ?: '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php if ($permit->notes): ?>
                            <h4>ملاحظات:</h4>
                            <p><?php echo nl2br(esc_html($permit->notes)); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="iw-card-footer">
                        <a href="<?php echo admin_url('admin.php?page=iw-withdraw-stock'); ?>" class="button">رجوع</a>

                        <?php if ($permit->status === 'pending' && current_user_can('iw_approve_permits')): ?>
                            <button type="button" class="button button-primary" onclick="approveWithdrawPermit(<?php echo $permit->id; ?>)">
                                <span class="dashicons dashicons-yes"></span> اعتماد
                            </button>
                            <button type="button" class="button" onclick="cancelPermit(<?php echo $permit->id; ?>, 'withdraw')">
                                <span class="dashicons dashicons-no"></span> إلغاء
                            </button>
                        <?php endif; ?>

                        <?php if ($permit->status === 'approved' && current_user_can('iw_withdraw_stock')): ?>
                            <button type="button" class="button button-primary" onclick="deliverWithdrawPermit(<?php echo $permit->id; ?>)">
                                <span class="dashicons dashicons-yes-alt"></span> تسليم وخصم من المخزون
                            </button>
                        <?php endif; ?>

                        <?php if ($permit->status === 'delivered'): ?>
                            <a href="<?php echo admin_url('admin.php?page=iw-print-withdraw-permit&permit_id=' . $permit->id); ?>" class="button" target="_blank">
                                <span class="dashicons dashicons-printer"></span> طباعة
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="notice notice-error"><p>الإذن غير موجود</p></div>
        <?php endif; ?>

    <?php else: ?>
        <!-- قائمة الإذونات -->
        <div class="iw-filters-bar">
            <div class="iw-filter-group">
                <select id="filter-warehouse">
                    <option value="">جميع المخازن</option>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?php echo esc_attr($wh->id); ?>"><?php echo esc_html($wh->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="iw-filter-group">
                <select id="filter-department">
                    <option value="">جميع الأقسام</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo esc_attr($dept->id); ?>"><?php echo esc_html($dept->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="iw-filter-group">
                <select id="filter-status">
                    <option value="">جميع الحالات</option>
                    <option value="pending">معلق</option>
                    <option value="approved">معتمد</option>
                    <option value="delivered">تم التسليم</option>
                    <option value="cancelled">ملغي</option>
                </select>
            </div>
            <div class="iw-filter-group">
                <input type="date" id="filter-date-from">
                <input type="date" id="filter-date-to">
            </div>
            <button type="button" class="button" id="btn-filter">
                <span class="dashicons dashicons-filter"></span> تصفية
            </button>
        </div>

        <div class="iw-table-container">
            <table class="wp-list-table widefat fixed striped" id="permits-table">
                <thead>
                    <tr>
                        <th>رقم الإذن</th>
                        <th>المخزن</th>
                        <th>القسم</th>
                        <th>المستلم</th>
                        <th>التاريخ</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="permits-tbody">
                    <tr class="iw-loading">
                        <td colspan="7"><span class="spinner is-active"></span> جاري التحميل...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($view === 'new'): ?>
<script>
jQuery(document).ready(function($) {
    let itemIndex = 0;

    // تحميل موظفي القسم عند اختياره
    $('#department_id').on('change', function() {
        const deptId = $(this).val();
        $('#employee_search').val('');
        $('#employee_id').val('');
        $('#employee_name').val('');
    });

    // البحث عن الموظفين
    $('#employee_search').on('input', function() {
        const term = $(this).val();
        const deptId = $('#department_id').val();

        if (term.length < 2) {
            $('#employee_results').hide();
            return;
        }

        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'iw_search_employees',
                nonce: iwAdmin.nonce,
                term: term,
                department_id: deptId
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(function(e) {
                        html += `<div class="iw-autocomplete-item" data-id="${e.id}" data-name="${e.name}">${e.name} - ${e.department_name || ''}</div>`;
                    });
                    $('#employee_results').html(html).show();
                } else {
                    $('#employee_results').hide();
                }
            }
        });
    });

    $(document).on('click', '#employee_results .iw-autocomplete-item', function() {
        $('#employee_id').val($(this).data('id'));
        $('#employee_search').val($(this).data('name'));
        $('#employee_name').val($(this).data('name'));
        $('#employee_results').hide();
    });

    // إضافة صنف
    $('#btn-add-item').on('click', function() {
        if (!$('#warehouse_id').val()) {
            alert('يرجى اختيار المخزن أولاً');
            return;
        }
        $('.iw-empty-row').remove();
        addItemRow();
    });

    function addItemRow() {
        const html = `
            <tr class="item-row" data-index="${itemIndex}">
                <td>
                    <input type="text" class="product-search" placeholder="ابحث عن صنف...">
                    <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id">
                    <div class="iw-autocomplete-results product-results"></div>
                </td>
                <td class="available-stock">-</td>
                <td><input type="number" name="items[${itemIndex}][quantity]" class="item-quantity" min="1" value="1"></td>
                <td><input type="text" name="items[${itemIndex}][storage_location]"></td>
                <td><input type="text" name="items[${itemIndex}][notes]"></td>
                <td><button type="button" class="button button-small btn-remove-item"><span class="dashicons dashicons-trash"></span></button></td>
            </tr>
        `;
        $('#items-tbody').append(html);
        itemIndex++;
    }

    // حذف صنف
    $(document).on('click', '.btn-remove-item', function() {
        $(this).closest('tr').remove();
        if ($('#items-tbody tr').length === 0) {
            $('#items-tbody').html('<tr class="iw-empty-row"><td colspan="6">لم يتم إضافة أصناف بعد</td></tr>');
        }
    });

    // البحث عن المنتجات
    $(document).on('input', '.product-search', function() {
        const $input = $(this);
        const $row = $input.closest('tr');
        const term = $input.val();

        if (term.length < 2) {
            $row.find('.product-results').hide();
            return;
        }

        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'iw_search_products',
                nonce: iwAdmin.nonce,
                term: term
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(function(p) {
                        html += `<div class="iw-autocomplete-item" data-id="${p.id}" data-name="${p.name}" data-sku="${p.sku}" data-stock="${p.total_stock}">${p.sku} - ${p.name} (متاح: ${p.total_stock})</div>`;
                    });
                    $row.find('.product-results').html(html).show();
                } else {
                    $row.find('.product-results').hide();
                }
            }
        });
    });

    $(document).on('click', '.product-results .iw-autocomplete-item', function() {
        const $row = $(this).closest('tr');
        $row.find('.product-id').val($(this).data('id'));
        $row.find('.product-search').val($(this).data('sku') + ' - ' + $(this).data('name'));
        $row.find('.available-stock').text($(this).data('stock'));
        $row.find('.product-results').hide();
    });

    // حفظ الإذن
    $('#withdraw-permit-form').on('submit', function(e) {
        e.preventDefault();

        const items = [];
        $('.item-row').each(function() {
            const $row = $(this);
            const productId = $row.find('.product-id').val();
            if (productId) {
                items.push({
                    product_id: productId,
                    quantity: $row.find('.item-quantity').val(),
                    storage_location: $row.find('[name*="storage_location"]').val(),
                    notes: $row.find('[name*="notes"]').val()
                });
            }
        });

        if (items.length === 0) {
            alert('يرجى إضافة صنف واحد على الأقل');
            return;
        }

        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'iw_create_withdraw_permit',
                nonce: iwAdmin.nonce,
                warehouse_id: $('#warehouse_id').val(),
                department_id: $('#department_id').val(),
                employee_id: $('#employee_id').val(),
                employee_name: $('#employee_name').val(),
                storage_location: $('#storage_location').val(),
                purpose: $('#purpose').val(),
                notes: $('#notes').val(),
                items: items
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.href = '<?php echo admin_url('admin.php?page=iw-withdraw-stock&permit_id='); ?>' + response.data.permit_id;
                } else {
                    alert(response.data.message || 'حدث خطأ');
                }
            }
        });
    });
});
</script>
<?php elseif (!$permit_id): ?>
<script>
jQuery(document).ready(function($) {
    function loadPermits() {
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'iw_get_withdraw_permits',
                nonce: iwAdmin.nonce,
                warehouse_id: $('#filter-warehouse').val(),
                department_id: $('#filter-department').val(),
                status: $('#filter-status').val(),
                date_from: $('#filter-date-from').val(),
                date_to: $('#filter-date-to').val()
            },
            beforeSend: function() {
                $('#permits-tbody').html('<tr class="iw-loading"><td colspan="7"><span class="spinner is-active"></span> جاري التحميل...</td></tr>');
            },
            success: function(response) {
                if (response.success) {
                    renderPermits(response.data);
                }
            }
        });
    }

    function renderPermits(permits) {
        if (permits.length === 0) {
            $('#permits-tbody').html('<tr><td colspan="7" class="iw-empty">لا توجد إذونات</td></tr>');
            return;
        }

        let html = '';
        permits.forEach(function(p) {
            let statusClass, statusLabel;
            switch (p.status) {
                case 'approved': statusClass = 'success'; statusLabel = 'معتمد'; break;
                case 'delivered': statusClass = 'info'; statusLabel = 'تم التسليم'; break;
                case 'cancelled': statusClass = 'danger'; statusLabel = 'ملغي'; break;
                default: statusClass = 'warning'; statusLabel = 'معلق';
            }

            html += `<tr>
                <td><a href="?page=iw-withdraw-stock&permit_id=${p.id}">${p.permit_number}</a></td>
                <td>${p.warehouse_name || '-'}</td>
                <td>${p.department_name || '-'}</td>
                <td>${p.employee_name || '-'}</td>
                <td>${p.created_at}</td>
                <td><span class="iw-badge ${statusClass}">${statusLabel}</span></td>
                <td>
                    <a href="?page=iw-withdraw-stock&permit_id=${p.id}" class="button button-small">عرض</a>
                </td>
            </tr>`;
        });
        $('#permits-tbody').html(html);
    }

    $('#btn-filter').on('click', loadPermits);
    loadPermits();
});

function approveWithdrawPermit(id) {
    if (!confirm('هل تريد اعتماد هذا الإذن؟')) return;

    jQuery.ajax({
        url: iwAdmin.ajaxurl,
        type: 'POST',
        data: {
            action: 'iw_approve_withdraw_permit',
            nonce: iwAdmin.nonce,
            id: id
        },
        success: function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message || 'حدث خطأ');
            }
        }
    });
}

function deliverWithdrawPermit(id) {
    if (!confirm('هل تريد تسليم الأصناف وخصمها من المخزون؟ سيتم تطبيق مبدأ FIFO (ما يرد أولاً يصرف أولاً)')) return;

    jQuery.ajax({
        url: iwAdmin.ajaxurl,
        type: 'POST',
        data: {
            action: 'iw_deliver_withdraw_permit',
            nonce: iwAdmin.nonce,
            id: id
        },
        success: function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message || 'حدث خطأ');
            }
        }
    });
}

function cancelPermit(id, type) {
    if (!confirm('هل تريد إلغاء هذا الإذن؟')) return;

    jQuery.ajax({
        url: iwAdmin.ajaxurl,
        type: 'POST',
        data: {
            action: 'iw_cancel_' + type + '_permit',
            nonce: iwAdmin.nonce,
            id: id
        },
        success: function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message || 'حدث خطأ');
            }
        }
    });
}
</script>
<?php endif; ?>
