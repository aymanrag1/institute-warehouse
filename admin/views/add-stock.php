<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>إذن إضافة مشتريات</h1>
    <form id="iw-add-stock-form">
        <table class="form-table">
            <tr>
                <th>الصنف *</th>
                <td><select id="as_product_id" class="regular-text" required><option value="">اختر الصنف</option></select></td>
            </tr>
            <tr><th>الكمية *</th><td><input type="number" id="as_quantity" class="regular-text" min="1" required></td></tr>
            <tr><th>سعر الوحدة</th><td><input type="number" id="as_unit_price" class="regular-text" min="0" step="0.01" value="0"></td></tr>
            <tr>
                <th>المورد</th>
                <td>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <select id="as_supplier_id" class="regular-text" style="flex:1;"><option value="">اختر المورد</option></select>
                        <button type="button" class="button" onclick="$('#iw-new-supplier-modal').show()">+ إضافة مورد جديد</button>
                    </div>
                </td>
            </tr>
            <tr><th>ملاحظات</th><td><textarea id="as_notes" class="large-text" rows="3"></textarea></td></tr>
        </table>
        <button type="submit" class="button button-primary button-large">إضافة للمخزون</button>
    </form>

    <h2 style="margin-top:30px;">آخر عمليات الإضافة <button class="button" onclick="iwPrintFullOrder()">طباعة إذن الإضافة</button></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr><th><input type="checkbox" onchange="$('.as-check').prop('checked',this.checked)"></th><th>الصنف</th><th>الكمية</th><th>السعر</th><th>المورد</th><th>التاريخ</th><th>ملاحظات</th></tr></thead>
        <tbody id="add-stock-history"></tbody>
    </table>
</div>

<!-- New Supplier Modal -->
<div id="iw-new-supplier-modal" class="iw-modal" style="display:none;">
    <div class="iw-modal-content">
        <span class="iw-modal-close" onclick="$('#iw-new-supplier-modal').hide()">&times;</span>
        <h2>إضافة مورد جديد</h2>
        <form id="iw-quick-supplier-form">
            <table class="form-table">
                <tr><th>اسم المورد *</th><td><input type="text" id="qs_name" class="regular-text" required></td></tr>
                <tr><th>رقم الهاتف</th><td><input type="text" id="qs_phone" class="regular-text"></td></tr>
                <tr><th>البريد الإلكتروني</th><td><input type="email" id="qs_email" class="regular-text"></td></tr>
                <tr><th>العنوان</th><td><textarea id="qs_address" class="regular-text" rows="2"></textarea></td></tr>
            </table>
            <button type="submit" class="button button-primary">حفظ المورد</button>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    function loadProducts() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_products_list', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) { console.log('Products load error:', r); return; }
            var h = '<option value="">اختر الصنف</option>';
            r.data.forEach(function(p) { h += '<option value="'+p.id+'">'+p.name+' (المخزون: '+(p.current_stock||0)+')</option>'; });
            $('#as_product_id').html(h);
            if (typeof iwRefreshSelect2 === 'function') iwRefreshSelect2('#as_product_id');
        });
    }
    loadProducts();

    function loadSuppliers() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_suppliers', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) { console.log('Suppliers load error:', r); return; }
            var h = '<option value="">اختر المورد</option>';
            r.data.forEach(function(s) { h += '<option value="'+s.id+'">'+s.name+'</option>'; });
            $('#as_supplier_id').html(h);
            if (typeof iwRefreshSelect2 === 'function') iwRefreshSelect2('#as_supplier_id');
        });
    }
    loadSuppliers();

    function loadHistory() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_transactions', nonce: iwAdmin.nonce, type: 'add'}, function(r) {
            if (!r.success) return;
            var h = '';
            r.data.slice(0,50).forEach(function(t) {
                h += '<tr><td><input type="checkbox" class="as-check" value="'+JSON.stringify(t).replace(/"/g,'&quot;')+'"></td>';
                h += '<td>'+t.product_name+'</td><td>'+t.quantity+'</td><td>'+parseFloat(t.unit_price).toFixed(2)+'</td>';
                h += '<td>'+(t.supplier_name||'-')+'</td><td>'+t.created_at+'</td><td>'+(t.notes||'-')+'</td></tr>';
            });
            $('#add-stock-history').html(h || '<tr><td colspan="7">لا توجد عمليات</td></tr>');
        });
    }
    loadHistory();

    $('#iw-add-stock-form').on('submit', function(e) {
        e.preventDefault();
        $.post(iwAdmin.ajaxurl, {
            action: 'iw_add_stock', nonce: iwAdmin.nonce,
            product_id: $('#as_product_id').val(),
            quantity: $('#as_quantity').val(),
            unit_price: $('#as_unit_price').val(),
            supplier_id: $('#as_supplier_id').val(),
            notes: $('#as_notes').val()
        }, function(r) {
            alert(r.data.message);
            if (r.success) { $('#iw-add-stock-form')[0].reset(); loadHistory(); loadProducts(); }
        });
    });

    // Quick add supplier
    $('#iw-quick-supplier-form').on('submit', function(e) {
        e.preventDefault();
        $.post(iwAdmin.ajaxurl, {
            action: 'iw_create_supplier', nonce: iwAdmin.nonce,
            name: $('#qs_name').val(),
            phone: $('#qs_phone').val(),
            email: $('#qs_email').val(),
            address: $('#qs_address').val()
        }, function(r) {
            if (r.success) {
                alert('تم إضافة المورد بنجاح');
                $('#iw-new-supplier-modal').hide();
                $('#iw-quick-supplier-form')[0].reset();
                loadSuppliers();
                // Select the new supplier
                setTimeout(function() {
                    $('#as_supplier_id').val(r.data.id);
                    if (typeof iwRefreshSelect2 === 'function') iwRefreshSelect2('#as_supplier_id');
                }, 500);
            } else {
                alert(r.data.message || 'حدث خطأ');
            }
        });
    });

    // Print full order (multiple items)
    window.iwPrintFullOrder = function() {
        var items = [];
        $('.as-check:checked').each(function() {
            items.push(JSON.parse($(this).val()));
        });
        if (!items.length) { alert('اختر عناصر للطباعة'); return; }

        var header = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
        var content = header;
        content += '<h2 style="text-align:center;">إذن إضافة مشتريات</h2>';
        content += '<p style="text-align:center;"><strong>التاريخ:</strong> '+items[0].created_at.split(' ')[0]+'</p>';
        content += '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;">';
        content += '<tr style="background:#f0f0f0;"><th>#</th><th>الصنف</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th><th>المورد</th></tr>';

        var totalQty = 0, totalValue = 0;
        items.forEach(function(t, idx) {
            var lineTotal = parseInt(t.quantity) * parseFloat(t.unit_price);
            totalQty += parseInt(t.quantity);
            totalValue += lineTotal;
            content += '<tr><td>'+(idx+1)+'</td><td>'+t.product_name+'</td><td>'+t.quantity+'</td>';
            content += '<td>'+parseFloat(t.unit_price).toFixed(2)+'</td><td>'+lineTotal.toFixed(2)+'</td>';
            content += '<td>'+(t.supplier_name||'-')+'</td></tr>';
        });
        content += '<tr style="background:#f0f0f0;font-weight:bold;"><td colspan="2">الإجمالي</td><td>'+totalQty+'</td><td>-</td><td>'+totalValue.toFixed(2)+'</td><td></td></tr>';
        content += '</table>';
        content += '<div style="margin-top:40px;display:flex;justify-content:space-between;"><div style="text-align:center;"><strong>مسؤول المخزن</strong><br><br>التوقيع: ____________</div><div style="text-align:center;"><strong>المدير / عميد المعهد</strong><br><br>التوقيع: ____________</div></div>';

        var w = window.open('','','width=800,height=600');
        w.document.write('<html dir="rtl"><head><title>إذن إضافة مشتريات</title><style>body{font-family:Arial,sans-serif;padding:20px;}th{background:#f0f0f0;}</style></head><body>'+content+'</body></html>');
        w.document.close(); w.print();
    };
});
</script>
