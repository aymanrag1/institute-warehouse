<?php
if (!defined('ABSPATH')) exit;
$tax_rate   = floatval(get_option('iw_tax_rate', 14));
$from_pr_id = isset($_GET['from_pr']) ? intval($_GET['from_pr']) : 0;
?>
<div class="wrap iw-wrap" dir="<?php echo iw_dir(); ?>">
    <h1><?php echo iw_t('إذن إضافة', 'Stock-In Order'); ?></h1>

    <?php if ($from_pr_id): ?>
    <div class="notice notice-info" style="margin:10px 0;padding:10px 15px;">
        <p style="margin:0;">
            <strong><?php echo iw_t('إنشاء إذن إضافة من طلب شراء', 'Creating Stock-In from Purchase Request'); ?></strong>
            — <?php echo iw_t('الأصناف ستُحمَّل تلقائياً. أدخل سعر الوحدة لكل صنف.', 'Items will be loaded automatically. Enter unit price for each item.'); ?>
        </p>
    </div>
    <input type="hidden" id="ao_from_pr_id" value="<?php echo esc_attr($from_pr_id); ?>">
    <?php endif; ?>

    <form id="iw-add-order-form">
        <table class="form-table">
            <tr>
                <th><?php echo iw_t('المورد', 'Supplier'); ?></th>
                <td>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <select id="ao_supplier_id" class="regular-text" style="flex:1;"><option value=""><?php echo iw_t('اختر المورد', 'Select Supplier'); ?></option></select>
                        <button type="button" class="button" onclick="iwShowNewSupplierModal()">+ <?php echo iw_t('إضافة مورد جديد', 'Add New Supplier'); ?></button>
                    </div>
                </td>
            </tr>
            <tr><th><?php echo iw_t('ملاحظات', 'Notes'); ?></th><td><textarea id="ao_notes" class="large-text" rows="2"></textarea></td></tr>
            <tr>
                <th><?php echo iw_t('الضريبة', 'Tax'); ?></th>
                <td>
                    <label style="margin-<?php echo iw_dir() === 'rtl' ? 'left' : 'right'; ?>:20px;">
                        <input type="radio" name="ao_tax_mode" value="incl" checked>
                        &nbsp;<?php echo iw_t('السعر شامل الضريبة', 'Price includes tax'); ?>
                    </label>
                    <label>
                        <input type="radio" name="ao_tax_mode" value="excl">
                        &nbsp;<?php echo iw_t('السعر بدون الضريبة', 'Price excludes tax'); ?>
                        <span style="color:#888;">(<?php echo iw_t('سيُضاف', 'tax added:'); ?> <?php echo $tax_rate; ?>%)</span>
                    </label>
                </td>
            </tr>
        </table>

        <h3><?php echo iw_t('الأصناف', 'Items'); ?></h3>
        <table class="wp-list-table widefat fixed striped" id="ao-items-table">
            <thead><tr>
                <th style="width:36%"><?php echo iw_t('الصنف', 'Product'); ?></th>
                <th style="width:9%"><?php echo iw_t('الكمية', 'Qty'); ?></th>
                <th style="width:13%"><?php echo iw_t('سعر الوحدة', 'Unit Price'); ?></th>
                <th style="width:22%"><?php echo iw_t('الإجمالي', 'Total'); ?></th>
                <th style="width:10%"><?php echo iw_t('حذف', 'Del'); ?></th>
            </tr></thead>
            <tbody id="ao-items-body"></tbody>
            <tfoot>
                <tr><td id="ao-add-btn-td" colspan="5"><button type="button" class="button" onclick="iwAddOrderItem()">+ <?php echo iw_t('إضافة صنف', 'Add Item'); ?></button></td></tr>
                <tr id="ao-totals-row" style="background:#f9f9f9;font-weight:bold;">
                    <td colspan="2"><?php echo iw_t('الإجمالي', 'Total'); ?></td>
                    <td id="ao-total-qty">0</td>
                    <td id="ao-total-value">0.00</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <p style="margin-top:15px;">
            <button type="submit" class="button button-primary button-large"><?php echo iw_t('حفظ إذن الإضافة', 'Save Stock-In Order'); ?></button>
        </p>
    </form>

    <hr style="margin:30px 0;">

    <h2><?php echo iw_t('أذونات الإضافة السابقة', 'Previous Stock-In Orders'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr>
            <th><?php echo iw_t('رقم الإذن', 'Order No.'); ?></th>
            <th><?php echo iw_t('المورد', 'Supplier'); ?></th>
            <th><?php echo iw_t('عدد الأصناف', 'Items'); ?></th>
            <th><?php echo iw_t('إجمالي القيمة', 'Total Value'); ?></th>
            <th><?php echo iw_t('التاريخ', 'Date'); ?></th>
            <th><?php echo iw_t('إجراءات', 'Actions'); ?></th>
        </tr></thead>
        <tbody id="add-orders-list"></tbody>
    </table>
</div>

<!-- New Supplier Modal -->
<div id="iw-new-supplier-modal" class="iw-modal" style="display:none;">
    <div class="iw-modal-content">
        <span class="iw-modal-close" onclick="iwHideNewSupplierModal()">&times;</span>
        <h2>إضافة مورد جديد</h2>
        <form id="iw-quick-supplier-form">
            <table class="form-table">
                <tr><th>اسم المورد *</th><td><input type="text" id="qs_name" class="regular-text" required></td></tr>
                <tr><th>تليفون محمول</th><td><input type="text" id="qs_phone" class="regular-text"></td></tr>
                <tr><th>البريد الإلكتروني</th><td><input type="email" id="qs_email" class="regular-text"></td></tr>
            </table>
            <button type="submit" class="button button-primary">حفظ المورد</button>
        </form>
    </div>
</div>

<!-- View/Edit Order Modal -->
<div id="iw-order-modal" class="iw-modal" style="display:none;">
    <div class="iw-modal-content" style="max-width:800px;">
        <span class="iw-modal-close" onclick="iwHideOrderModal()">&times;</span>
        <div id="iw-order-modal-body"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var products = [];
    var TAX_ENABLED = false;
    var TAX_RATE    = <?php echo $tax_rate; ?>;
    var FROM_PR_ID  = <?php echo intval($from_pr_id); ?>;

    // Per-order tax toggle
    $(document).on('change', 'input[name="ao_tax_mode"]', function() {
        TAX_ENABLED = $(this).val() === 'excl';

        // Rebuild table header
        var th = '';
        th += '<th style="width:36%"><?php echo iw_t('الصنف', 'Product'); ?></th>';
        th += '<th style="width:9%"><?php echo iw_t('الكمية', 'Qty'); ?></th>';
        th += '<th style="width:13%"><?php echo iw_t('سعر الوحدة', 'Unit Price'); ?></th>';
        if (TAX_ENABLED) {
            th += '<th style="width:11%"><?php echo iw_t('قبل الضريبة', 'Subtotal'); ?></th>';
            th += '<th style="width:9%"><?php echo iw_t('الضريبة', 'Tax'); ?> (' + TAX_RATE + '%)</th>';
            th += '<th style="width:12%"><?php echo iw_t('الإجمالي شامل الضريبة', 'Total incl. Tax'); ?></th>';
        } else {
            th += '<th style="width:22%"><?php echo iw_t('الإجمالي', 'Total'); ?></th>';
        }
        th += '<th style="width:10%"><?php echo iw_t('حذف', 'Del'); ?></th>';
        $('#ao-items-table thead tr').html(th);

        // Update add-button colspan
        $('#ao-add-btn-td').attr('colspan', TAX_ENABLED ? 7 : 5);

        // Rebuild totals row
        var tf = '';
        tf += '<td colspan="2"><?php echo iw_t('الإجمالي', 'Total'); ?></td>';
        tf += '<td id="ao-total-qty">0</td>';
        tf += '<td id="ao-total-value">0.00</td>';
        if (TAX_ENABLED) {
            tf += '<td id="ao-total-tax">0.00</td>';
            tf += '<td id="ao-total-grand">0.00</td>';
        }
        tf += '<td></td>';
        $('#ao-totals-row').html(tf);

        // Preserve item values and rebuild rows with correct structure
        var saved = [];
        $('.ao-item-row').each(function() {
            saved.push({
                product_id: $(this).find('.ao-product').val(),
                quantity:   $(this).find('.ao-qty').val(),
                unit_price: $(this).find('.ao-price').val()
            });
        });
        $('#ao-items-body').html('');
        if (saved.length) {
            saved.forEach(function(it) { iwAddOrderItem(it); });
        } else {
            iwAddOrderItem();
        }
    });

    function loadProducts() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_products_list', nonce: iwAdmin.nonce}, function(r) {
            if (r.success) {
                products = r.data;
                if (FROM_PR_ID) {
                    iwLoadFromPr();
                } else {
                    iwAddOrderItem();
                }
            }
        });
    }
    loadProducts();

    window.iwLoadFromPr = function() {
        var raw = null;
        try { raw = sessionStorage.getItem('iw_pr_to_addorder'); } catch(e) {}
        if (!raw) { iwAddOrderItem(); return; }
        var data = null;
        try { data = JSON.parse(raw); } catch(e) {}
        if (!data || !data.items || !data.items.length) { iwAddOrderItem(); return; }
        data.items.forEach(function(it) {
            iwAddOrderItem({ product_id: it.product_id, quantity: it.quantity, unit_price: 0 });
        });
        try { sessionStorage.removeItem('iw_pr_to_addorder'); } catch(e) {}
    };

    // Modal functions
    window.iwShowNewSupplierModal = function() {
        $('#iw-quick-supplier-form')[0].reset();
        $('#iw-new-supplier-modal').show();
    };

    window.iwHideNewSupplierModal = function() {
        $('#iw-new-supplier-modal').hide();
    };

    window.iwHideOrderModal = function() {
        $('#iw-order-modal').hide();
    };

    function loadSuppliers() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_suppliers', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) return;
            var h = '<option value="">اختر المورد</option>';
            r.data.forEach(function(s) { h += '<option value="'+s.id+'">'+s.name+'</option>'; });
            $('#ao_supplier_id').html(h);
            if (typeof iwRefreshSelect2 === 'function') iwRefreshSelect2('#ao_supplier_id');
        });
    }
    loadSuppliers();

    function loadOrders() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_add_orders', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) return;
            var h = '';
            r.data.forEach(function(o) {
                h += '<tr>';
                h += '<td>'+o.order_number+'</td>';
                h += '<td>'+(o.supplier_name||'-')+'</td>';
                h += '<td>'+o.total_quantity+'</td>';
                h += '<td>'+parseFloat(o.total_value).toFixed(2)+'</td>';
                h += '<td>'+o.created_at+'</td>';
                h += '<td>';
                h += '<button class="button" onclick="iwViewOrder('+o.id+')">عرض</button> ';
                h += '<button class="button" onclick="iwPrintOrder('+o.id+')">طباعة</button>';
                <?php if (current_user_can('manage_options')): ?>
                h += ' <button class="button" onclick="iwEditOrder('+o.id+')">تعديل</button>';
                h += ' <button class="button iw-btn-danger" onclick="iwDeleteOrder('+o.id+')">حذف</button>';
                <?php endif; ?>
                h += '</td></tr>';
            });
            $('#add-orders-list').html(h || '<tr><td colspan="6">لا توجد أذونات</td></tr>');
        });
    }
    loadOrders();

    // Add item row
    window.iwAddOrderItem = function(prefill) {
        prefill = prefill || {};
        var pid  = prefill.product_id || '';
        var qty  = prefill.quantity   || 1;
        var pr   = prefill.unit_price || 0;
        var h = '<tr class="ao-item-row">';
        h += '<td><select class="ao-product regular-text" onchange="iwCalcRowTotal(this)"><option value="">اختر الصنف</option>';
        products.forEach(function(p) {
            var sel = (String(p.id) === String(pid)) ? ' selected' : '';
            h += '<option value="'+p.id+'" data-price="'+p.price+'"'+sel+'>'+p.name+' (المخزون: '+(p.current_stock||0)+')</option>';
        });
        h += '</select></td>';
        h += '<td><input type="number" class="ao-qty" min="1" value="'+qty+'" oninput="iwCalcRowTotal(this)" style="width:90%;max-width:90px;"></td>';
        h += '<td><input type="number" class="ao-price" min="0" step="0.01" value="'+pr+'" oninput="iwCalcRowTotal(this)" style="width:90%;max-width:110px;"></td>';
        h += '<td class="ao-row-subtotal">0.00</td>';
        if (TAX_ENABLED) {
            h += '<td class="ao-row-tax">0.00</td>';
            h += '<td class="ao-row-total">0.00</td>';
        }
        h += '<td><button type="button" class="button iw-btn-danger" onclick="jQuery(this).closest(\'tr\').remove();iwCalcTotals();">حذف</button></td>';
        h += '</tr>';
        $('#ao-items-body').append(h);

        var $row = $('#ao-items-body tr:last');
        if (typeof iwInitSelect2 === 'function') iwInitSelect2($row);
        iwCalcRowTotal($row.find('.ao-qty')[0]);
    };

    // Calculate row total
    window.iwCalcRowTotal = function(el) {
        var $row = $(el).closest('tr');
        // Only auto-fill price when product changes AND not coming from purchase request
        // (PR flow leaves price blank for the user to enter)
        if ($(el).hasClass('ao-product') && !FROM_PR_ID) {
            var price = $(el).find(':selected').data('price') || 0;
            $row.find('.ao-price').val(parseFloat(price).toFixed(2));
        }

        var qty = parseFloat($row.find('.ao-qty').val()) || 0;
        var unitPrice = parseFloat($row.find('.ao-price').val()) || 0;
        var subtotal  = qty * unitPrice;
        var tax       = TAX_ENABLED ? subtotal * (TAX_RATE / 100) : 0;
        var total     = subtotal + tax;

        $row.find('.ao-row-subtotal').text(TAX_ENABLED ? subtotal.toFixed(2) : total.toFixed(2));
        if (TAX_ENABLED) {
            $row.find('.ao-row-tax').text(tax.toFixed(2));
            $row.find('.ao-row-total').text(total.toFixed(2));
        }
        iwCalcTotals();
    };

    // Calculate totals
    window.iwCalcTotals = function() {
        var totalQty = 0, subTotal = 0, taxTotal = 0;
        $('.ao-item-row').each(function() {
            var qty   = parseFloat($(this).find('.ao-qty').val()) || 0;
            var price = parseFloat($(this).find('.ao-price').val()) || 0;
            totalQty += qty;
            var sub   = qty * price;
            subTotal += sub;
            taxTotal += TAX_ENABLED ? sub * (TAX_RATE / 100) : 0;
        });
        $('#ao-total-qty').text(totalQty);
        if (TAX_ENABLED) {
            $('#ao-total-value').text(subTotal.toFixed(2));
            $('#ao-total-tax').text(taxTotal.toFixed(2));
            $('#ao-total-grand').text((subTotal + taxTotal).toFixed(2));
        } else {
            $('#ao-total-value').text(subTotal.toFixed(2));
        }
    };

    // Submit order
    $('#iw-add-order-form').on('submit', function(e) {
        e.preventDefault();
        var items = [];
        $('.ao-item-row').each(function() {
            var pid = $(this).find('.ao-product').val();
            var qty = $(this).find('.ao-qty').val();
            var price = $(this).find('.ao-price').val();
            if (pid && qty > 0) {
                items.push({product_id: pid, quantity: qty, unit_price: price});
            }
        });

        if (!items.length) { alert('يجب إضافة صنف واحد على الأقل'); return; }

        $.post(iwAdmin.ajaxurl, {
            action: 'iw_create_add_order', nonce: iwAdmin.nonce,
            supplier_id: $('#ao_supplier_id').val(),
            notes: $('#ao_notes').val(),
            items: JSON.stringify(items),
            from_pr_id: ($('#ao_from_pr_id').val() || 0),
            tax_enabled: TAX_ENABLED ? 1 : 0,
            tax_rate: TAX_RATE
        }, function(r) {
            alert(r.data.message);
            if (r.success) {
                $('#iw-add-order-form')[0].reset();
                TAX_ENABLED = false;
                // Reset header back to 5-col mode
                $('#ao-items-table thead tr').html(
                    '<th style="width:36%"><?php echo iw_t('الصنف', 'Product'); ?></th>' +
                    '<th style="width:9%"><?php echo iw_t('الكمية', 'Qty'); ?></th>' +
                    '<th style="width:13%"><?php echo iw_t('سعر الوحدة', 'Unit Price'); ?></th>' +
                    '<th style="width:22%"><?php echo iw_t('الإجمالي', 'Total'); ?></th>' +
                    '<th style="width:10%"><?php echo iw_t('حذف', 'Del'); ?></th>'
                );
                $('#ao-add-btn-td').attr('colspan', 5);
                $('#ao-totals-row').html(
                    '<td colspan="2"><?php echo iw_t('الإجمالي', 'Total'); ?></td>' +
                    '<td id="ao-total-qty">0</td>' +
                    '<td id="ao-total-value">0.00</td>' +
                    '<td></td>'
                );
                $('#ao-items-body').html('');
                iwAddOrderItem();
                loadOrders();
                loadProducts();
                if (FROM_PR_ID) {
                    window.location.href = iwAdmin.adminurl + 'admin.php?page=iw-add-stock';
                }
            }
        });
    });

    // Quick add supplier
    $('#iw-quick-supplier-form').on('submit', function(e) {
        e.preventDefault();
        $.post(iwAdmin.ajaxurl, {
            action: 'iw_create_supplier', nonce: iwAdmin.nonce,
            name: $('#qs_name').val(),
            phone_mobile: $('#qs_phone').val(),
            email: $('#qs_email').val()
        }, function(r) {
            if (r.success) {
                alert('تم إضافة المورد بنجاح');
                $('#iw-new-supplier-modal').hide();
                $('#iw-quick-supplier-form')[0].reset();
                loadSuppliers();
            } else {
                alert(r.data.message || 'حدث خطأ');
            }
        });
    });

    // View order
    window.iwViewOrder = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_add_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            if (!r.success) return;
            var o = r.data.order, items = r.data.items;
            var html = '<h2>إذن إضافة رقم: '+o.order_number+'</h2>';
            html += '<p><strong>المورد:</strong> '+(o.supplier_name||'-')+' | <strong>التاريخ:</strong> '+o.created_at+'</p>';
            html += '<table class="wp-list-table widefat fixed striped"><thead><tr><th>الصنف</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody>';
            items.forEach(function(i) {
                html += '<tr><td>'+i.product_name+'</td><td>'+i.quantity+'</td><td>'+parseFloat(i.unit_price).toFixed(2)+'</td><td>'+(i.quantity*i.unit_price).toFixed(2)+'</td></tr>';
            });
            html += '</tbody></table>';
            $('#iw-order-modal-body').html(html);
            $('#iw-order-modal').show();
        });
    };

    // Print order
    window.iwPrintOrder = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_add_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            if (!r.success) return;
            var o = r.data.order, items = r.data.items;
            var header = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
            var content = header;
            content += '<h2 style="text-align:center;">إذن إضافة رقم: '+o.order_number+'</h2>';
            content += '<p style="text-align:center;"><strong>المورد:</strong> '+(o.supplier_name||'-')+' | <strong>التاريخ:</strong> '+o.created_at+'</p>';
            content += '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;">';
            content += '<tr style="background:#f0f0f0;"><th>#</th><th>الصنف</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th></tr>';

            var totalQty = 0, totalValue = 0;
            items.forEach(function(i, idx) {
                var lineTotal = i.quantity * i.unit_price;
                totalQty += parseInt(i.quantity);
                totalValue += lineTotal;
                content += '<tr><td>'+(idx+1)+'</td><td>'+i.product_name+'</td><td>'+i.quantity+'</td>';
                content += '<td>'+parseFloat(i.unit_price).toFixed(2)+'</td><td>'+lineTotal.toFixed(2)+'</td></tr>';
            });
            content += '<tr style="background:#f0f0f0;font-weight:bold;"><td colspan="2">الإجمالي</td><td>'+totalQty+'</td><td>-</td><td>'+totalValue.toFixed(2)+'</td></tr>';
            content += '</table>';

            // Signatures: warehouse manager - accountant - approver
            content += '<table width="100%" style="margin-top:50px;border:none;"><tr>';
            content += '<td style="text-align:center;border:none;width:33%;"><strong>مسؤول المخازن</strong><br><br><br>التوقيع: ____________</td>';
            content += '<td style="text-align:center;border:none;width:33%;"><strong>مدير الحسابات</strong><br><br><br>التوقيع: ____________</td>';
            content += '<td style="text-align:center;border:none;width:33%;"><strong>يعتمد</strong><br><br><br>التوقيع: ____________</td>';
            content += '</tr></table>';

            var w = window.open('','','width=800,height=600');
            w.document.write('<html dir="<?php echo iw_dir(); ?>"><head><title>إذن إضافة</title><style>body{font-family:Arial,sans-serif;padding:20px;direction:rtl;text-align:right;}table{direction:rtl;text-align:right;}th{background:#f0f0f0;text-align:right;}td{text-align:right;}</style></head><body>'+content+'</body></html>');
            w.document.close(); w.print();
        });
    };

    // Edit order (admin only)
    window.iwEditOrder = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_add_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            if (!r.success) return;
            var o = r.data.order, items = r.data.items;
            var html = '<h2>تعديل إذن إضافة رقم: '+o.order_number+'</h2>';
            html += '<form id="iw-edit-order-form">';
            html += '<input type="hidden" id="edit_order_id" value="'+o.id+'">';
            html += '<table class="form-table"><tr><th>المورد</th><td><select id="edit_supplier_id" class="regular-text"><option value="">اختر المورد</option></select></td></tr>';
            html += '<tr><th>ملاحظات</th><td><textarea id="edit_notes" class="large-text">'+( o.notes||'')+'</textarea></td></tr></table>';
            html += '<h3>الأصناف</h3><table class="wp-list-table widefat fixed striped"><thead><tr><th>الصنف</th><th>الكمية</th><th>السعر</th><th>حذف</th></tr></thead><tbody id="edit-items-body">';
            items.forEach(function(i) {
                html += '<tr class="edit-item-row" data-product="'+i.product_id+'">';
                html += '<td>'+i.product_name+'</td>';
                html += '<td><input type="number" class="edit-qty" value="'+i.quantity+'" min="1"></td>';
                html += '<td><input type="number" class="edit-price" value="'+i.unit_price+'" min="0" step="0.01"></td>';
                html += '<td><button type="button" class="button iw-btn-danger" onclick="jQuery(this).closest(\'tr\').remove()">حذف</button></td></tr>';
            });
            html += '</tbody></table>';
            html += '<p style="margin-top:10px;"><button type="button" class="button" onclick="iwAddEditItem()">+ إضافة صنف</button></p>';
            html += '<p><button type="submit" class="button button-primary">حفظ التعديلات</button></p></form>';
            $('#iw-order-modal-body').html(html);

            // Load suppliers into edit form
            $.post(iwAdmin.ajaxurl, {action: 'iw_get_suppliers', nonce: iwAdmin.nonce}, function(sr) {
                if (sr.success) {
                    var sh = '<option value="">اختر المورد</option>';
                    sr.data.forEach(function(s) { sh += '<option value="'+s.id+'" '+(s.id==o.supplier_id?'selected':'')+'>'+s.name+'</option>'; });
                    $('#edit_supplier_id').html(sh);
                }
            });

            $('#iw-order-modal').show();
        });
    };

    // Add new item to edit form
    window.iwAddEditItem = function() {
        var opts = '<option value="">اختر الصنف</option>';
        products.forEach(function(p) { opts += '<option value="'+p.id+'" data-price="'+p.price+'">'+p.name+'</option>'; });
        var row = '<tr class="edit-item-row">';
        row += '<td><select class="edit-new-product regular-text" onchange="var p=$(this).find(\':selected\');$(this).closest(\'tr\').data(\'product\',$(this).val());$(this).closest(\'tr\').find(\'.edit-price\').val(p.data(\'price\')||0);">'+opts+'</select></td>';
        row += '<td><input type="number" class="edit-qty" value="1" min="1"></td>';
        row += '<td><input type="number" class="edit-price" value="0" min="0" step="0.01"></td>';
        row += '<td><button type="button" class="button iw-btn-danger" onclick="jQuery(this).closest(\'tr\').remove()">حذف</button></td></tr>';
        $('#edit-items-body').append(row);
    };

    // Submit edit
    $(document).on('submit', '#iw-edit-order-form', function(e) {
        e.preventDefault();
        var items = [];
        $('.edit-item-row').each(function() {
            var pid = $(this).data('product') || $(this).find('.edit-new-product').val();
            if (!pid) return;
            items.push({
                product_id: pid,
                quantity: $(this).find('.edit-qty').val(),
                unit_price: $(this).find('.edit-price').val()
            });
        });

        $.post(iwAdmin.ajaxurl, {
            action: 'iw_update_add_order', nonce: iwAdmin.nonce,
            order_id: $('#edit_order_id').val(),
            supplier_id: $('#edit_supplier_id').val(),
            notes: $('#edit_notes').val(),
            items: JSON.stringify(items)
        }, function(r) {
            alert(r.data.message);
            if (r.success) { $('#iw-order-modal').hide(); loadOrders(); loadProducts(); }
        });
    });

    // Delete order (admin only)
    window.iwDeleteOrder = function(id) {
        if (!confirm('هل أنت متأكد من حذف هذا الإذن؟ سيتم إلغاء الكميات المضافة.')) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_delete_add_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            alert(r.data.message);
            if (r.success) { loadOrders(); loadProducts(); }
        });
    };
});
</script>
