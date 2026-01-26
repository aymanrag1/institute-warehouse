<?php
/**
 * Add Stock View
 * صفحة إذن الإضافة
 */

if (!defined('ABSPATH')) {
    exit;
}

$warehouses = IW_Admin::get_active_warehouses();
$view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
$permit_id = isset($_GET['permit_id']) ? absint($_GET['permit_id']) : 0;
?>

<div class="wrap iw-wrap" dir="rtl">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-plus-alt"></span>
        إذن إضافة
    </h1>

    <?php if ($view !== 'new'): ?>
        <a href="<?php echo admin_url('admin.php?page=iw-add-stock&view=new'); ?>" class="page-title-action">
            <span class="dashicons dashicons-plus"></span> إذن إضافة جديد
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php if ($view === 'new'): ?>
        <!-- نموذج إذن إضافة جديد -->
        <div class="iw-permit-form">
            <form id="add-permit-form">
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
                                <label for="supplier_search">المورد</label>
                                <input type="text" id="supplier_search" placeholder="ابحث عن مورد...">
                                <input type="hidden" name="supplier_id" id="supplier_id">
                                <div id="supplier_results" class="iw-autocomplete-results"></div>
                            </div>
                        </div>

                        <div class="iw-form-row">
                            <div class="iw-form-group">
                                <label for="supplier_name">اسم جهة الشراء</label>
                                <input type="text" name="supplier_name" id="supplier_name" placeholder="أو اكتب اسم الجهة مباشرة">
                            </div>
                            <div class="iw-form-group">
                                <label for="invoice_number">رقم الفاتورة</label>
                                <input type="text" name="invoice_number" id="invoice_number">
                            </div>
                            <div class="iw-form-group">
                                <label for="invoice_date">تاريخ الفاتورة</label>
                                <input type="date" name="invoice_date" id="invoice_date">
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
                        <h3>الأصناف</h3>
                        <button type="button" class="button" id="btn-add-item">
                            <span class="dashicons dashicons-plus"></span> إضافة صنف
                        </button>
                    </div>
                    <div class="iw-card-body">
                        <table class="widefat" id="items-table">
                            <thead>
                                <tr>
                                    <th style="width:30%">الصنف</th>
                                    <th style="width:10%">الكمية</th>
                                    <th style="width:12%">سعر الوحدة</th>
                                    <th style="width:12%">الإجمالي</th>
                                    <th style="width:12%">رقم الدفعة</th>
                                    <th style="width:12%">تاريخ الصلاحية</th>
                                    <th style="width:10%">مكان التخزين</th>
                                    <th style="width:5%"></th>
                                </tr>
                            </thead>
                            <tbody id="items-tbody">
                                <tr class="iw-empty-row">
                                    <td colspan="8">لم يتم إضافة أصناف بعد</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-left"><strong>الإجمالي:</strong></td>
                                    <td id="total-amount"><strong>0.00</strong></td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="iw-form-actions">
                    <a href="<?php echo admin_url('admin.php?page=iw-add-stock'); ?>" class="button">إلغاء</a>
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-saved"></span> حفظ الإذن
                    </button>
                </div>
            </form>
        </div>

    <?php elseif ($permit_id > 0): ?>
        <!-- عرض تفاصيل الإذن -->
        <?php
        $permit = IW_Transactions::get_add_permit($permit_id);
        if ($permit):
            $status_info = IW_Admin::get_permit_status_label($permit->status);
        ?>
            <div class="iw-permit-details">
                <div class="iw-card">
                    <div class="iw-card-header">
                        <h3>إذن إضافة رقم: <?php echo esc_html($permit->permit_number); ?></h3>
                        <span class="iw-badge <?php echo esc_attr($status_info['class']); ?>"><?php echo esc_html($status_info['label']); ?></span>
                    </div>
                    <div class="iw-card-body">
                        <div class="iw-details-grid">
                            <div class="iw-detail-item">
                                <label>المخزن:</label>
                                <span><?php echo esc_html($permit->warehouse_name); ?></span>
                            </div>
                            <div class="iw-detail-item">
                                <label>المورد:</label>
                                <span><?php echo esc_html($permit->supplier_full_name ?: $permit->supplier_name ?: '-'); ?></span>
                            </div>
                            <div class="iw-detail-item">
                                <label>رقم الفاتورة:</label>
                                <span><?php echo esc_html($permit->invoice_number ?: '-'); ?></span>
                            </div>
                            <div class="iw-detail-item">
                                <label>تاريخ الفاتورة:</label>
                                <span><?php echo IW_Admin::format_date($permit->invoice_date); ?></span>
                            </div>
                            <div class="iw-detail-item">
                                <label>تاريخ الإنشاء:</label>
                                <span><?php echo IW_Admin::format_date($permit->created_at, 'd/m/Y H:i'); ?></span>
                            </div>
                            <div class="iw-detail-item">
                                <label>بواسطة:</label>
                                <span><?php echo esc_html($permit->created_by_name); ?></span>
                            </div>
                            <?php if ($permit->approved_at): ?>
                                <div class="iw-detail-item">
                                    <label>تاريخ الاعتماد:</label>
                                    <span><?php echo IW_Admin::format_date($permit->approved_at, 'd/m/Y H:i'); ?></span>
                                </div>
                                <div class="iw-detail-item">
                                    <label>اعتمد بواسطة:</label>
                                    <span><?php echo esc_html($permit->approved_by_name); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h4>الأصناف:</h4>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>الكود</th>
                                    <th>الصنف</th>
                                    <th>الكمية</th>
                                    <th>سعر الوحدة</th>
                                    <th>الإجمالي</th>
                                    <th>رقم الدفعة</th>
                                    <th>تاريخ الصلاحية</th>
                                    <th>مكان التخزين</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($permit->items as $item): ?>
                                    <tr>
                                        <td><?php echo esc_html($item->product_sku); ?></td>
                                        <td><?php echo esc_html($item->product_name); ?></td>
                                        <td><?php echo number_format($item->quantity); ?> <?php echo esc_html($item->unit); ?></td>
                                        <td><?php echo IW_Admin::format_currency($item->unit_price); ?></td>
                                        <td><?php echo IW_Admin::format_currency($item->total_price); ?></td>
                                        <td><?php echo esc_html($item->batch_number ?: '-'); ?></td>
                                        <td><?php echo IW_Admin::format_date($item->expiry_date); ?></td>
                                        <td><?php echo esc_html($item->storage_location ?: '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4"><strong>الإجمالي:</strong></td>
                                    <td colspan="4"><strong><?php echo IW_Admin::format_currency($permit->total_amount); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>

                        <?php if ($permit->notes): ?>
                            <h4>ملاحظات:</h4>
                            <p><?php echo nl2br(esc_html($permit->notes)); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="iw-card-footer">
                        <a href="<?php echo admin_url('admin.php?page=iw-add-stock'); ?>" class="button">رجوع</a>

                        <?php if ($permit->status === 'pending' && current_user_can('iw_approve_permits')): ?>
                            <button type="button" class="button button-primary" onclick="approvePermit(<?php echo $permit->id; ?>)">
                                <span class="dashicons dashicons-yes"></span> اعتماد وإضافة للمخزون
                            </button>
                            <button type="button" class="button" onclick="cancelPermit(<?php echo $permit->id; ?>, 'add')">
                                <span class="dashicons dashicons-no"></span> إلغاء
                            </button>
                        <?php endif; ?>

                        <?php if ($permit->status === 'approved'): ?>
                            <a href="<?php echo admin_url('admin.php?page=iw-print-add-permit&permit_id=' . $permit->id); ?>" class="button" target="_blank">
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
                <select id="filter-status">
                    <option value="">جميع الحالات</option>
                    <option value="pending">معلق</option>
                    <option value="approved">معتمد</option>
                    <option value="cancelled">ملغي</option>
                </select>
            </div>
            <div class="iw-filter-group">
                <input type="date" id="filter-date-from" placeholder="من تاريخ">
                <input type="date" id="filter-date-to" placeholder="إلى تاريخ">
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
                        <th>المورد</th>
                        <th>المبلغ</th>
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

    // البحث عن الموردين
    $('#supplier_search').on('input', function() {
        const term = $(this).val();
        if (term.length < 2) {
            $('#supplier_results').hide();
            return;
        }

        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'iw_search_suppliers',
                nonce: iwAdmin.nonce,
                term: term
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(function(s) {
                        html += `<div class="iw-autocomplete-item" data-id="${s.id}" data-name="${s.name}">${s.name}</div>`;
                    });
                    $('#supplier_results').html(html).show();
                } else {
                    $('#supplier_results').hide();
                }
            }
        });
    });

    $(document).on('click', '#supplier_results .iw-autocomplete-item', function() {
        $('#supplier_id').val($(this).data('id'));
        $('#supplier_search').val($(this).data('name'));
        $('#supplier_name').val($(this).data('name'));
        $('#supplier_results').hide();
    });

    // إضافة صنف
    $('#btn-add-item').on('click', function() {
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
                <td><input type="number" name="items[${itemIndex}][quantity]" class="item-quantity" min="1" value="1"></td>
                <td><input type="number" name="items[${itemIndex}][unit_price]" class="item-price" min="0" step="0.01" value="0"></td>
                <td class="item-total">0.00</td>
                <td><input type="text" name="items[${itemIndex}][batch_number]"></td>
                <td><input type="date" name="items[${itemIndex}][expiry_date]"></td>
                <td><input type="text" name="items[${itemIndex}][storage_location]"></td>
                <td><button type="button" class="button button-small btn-remove-item"><span class="dashicons dashicons-trash"></span></button></td>
            </tr>
        `;
        $('#items-tbody').append(html);
        itemIndex++;
    }

    // حذف صنف
    $(document).on('click', '.btn-remove-item', function() {
        $(this).closest('tr').remove();
        calculateTotal();
        if ($('#items-tbody tr').length === 0) {
            $('#items-tbody').html('<tr class="iw-empty-row"><td colspan="8">لم يتم إضافة أصناف بعد</td></tr>');
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
                        html += `<div class="iw-autocomplete-item" data-id="${p.id}" data-name="${p.name}" data-sku="${p.sku}">${p.sku} - ${p.name}</div>`;
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
        $row.find('.product-results').hide();
    });

    // حساب الإجمالي
    $(document).on('input', '.item-quantity, .item-price', function() {
        const $row = $(this).closest('tr');
        const qty = parseFloat($row.find('.item-quantity').val()) || 0;
        const price = parseFloat($row.find('.item-price').val()) || 0;
        $row.find('.item-total').text((qty * price).toFixed(2));
        calculateTotal();
    });

    function calculateTotal() {
        let total = 0;
        $('.item-total').each(function() {
            total += parseFloat($(this).text()) || 0;
        });
        $('#total-amount strong').text(total.toFixed(2));
    }

    // حفظ الإذن
    $('#add-permit-form').on('submit', function(e) {
        e.preventDefault();

        const items = [];
        $('.item-row').each(function() {
            const $row = $(this);
            const productId = $row.find('.product-id').val();
            if (productId) {
                items.push({
                    product_id: productId,
                    quantity: $row.find('.item-quantity').val(),
                    unit_price: $row.find('.item-price').val(),
                    batch_number: $row.find('[name*="batch_number"]').val(),
                    expiry_date: $row.find('[name*="expiry_date"]').val(),
                    storage_location: $row.find('[name*="storage_location"]').val()
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
                action: 'iw_create_add_permit',
                nonce: iwAdmin.nonce,
                warehouse_id: $('#warehouse_id').val(),
                supplier_id: $('#supplier_id').val(),
                supplier_name: $('#supplier_name').val(),
                invoice_number: $('#invoice_number').val(),
                invoice_date: $('#invoice_date').val(),
                notes: $('#notes').val(),
                items: items
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.href = '<?php echo admin_url('admin.php?page=iw-add-stock&permit_id='); ?>' + response.data.permit_id;
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
                action: 'iw_get_add_permits',
                nonce: iwAdmin.nonce,
                warehouse_id: $('#filter-warehouse').val(),
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
            const statusClass = p.status === 'approved' ? 'success' : (p.status === 'pending' ? 'warning' : 'danger');
            const statusLabel = p.status === 'approved' ? 'معتمد' : (p.status === 'pending' ? 'معلق' : 'ملغي');

            html += `<tr>
                <td><a href="?page=iw-add-stock&permit_id=${p.id}">${p.permit_number}</a></td>
                <td>${p.warehouse_name || '-'}</td>
                <td>${p.supplier_name || '-'}</td>
                <td>${parseFloat(p.total_amount).toLocaleString()}</td>
                <td>${p.created_at}</td>
                <td><span class="iw-badge ${statusClass}">${statusLabel}</span></td>
                <td>
                    <a href="?page=iw-add-stock&permit_id=${p.id}" class="button button-small">عرض</a>
                </td>
            </tr>`;
        });
        $('#permits-tbody').html(html);
    }

    $('#btn-filter').on('click', loadPermits);
    loadPermits();
});

function approvePermit(id) {
    if (!confirm('هل تريد اعتماد هذا الإذن وإضافة الأصناف للمخزون؟')) return;

    jQuery.ajax({
        url: iwAdmin.ajaxurl,
        type: 'POST',
        data: {
            action: 'iw_approve_add_permit',
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
