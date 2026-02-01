<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>إذن إضافة مخزون</h1>
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
                <td><select id="as_supplier_id" class="regular-text"><option value="">اختر المورد</option></select></td>
            </tr>
            <tr><th>ملاحظات</th><td><textarea id="as_notes" class="large-text" rows="3"></textarea></td></tr>
        </table>
        <button type="submit" class="button button-primary button-large">إضافة للمخزون</button>
    </form>

    <h2 style="margin-top:30px;">آخر عمليات الإضافة</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr><th>الصنف</th><th>الكمية</th><th>السعر</th><th>التاريخ</th><th>ملاحظات</th></tr></thead>
        <tbody id="add-stock-history"></tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    $.post(iwAdmin.ajaxurl, {action: 'iw_get_products_list', nonce: iwAdmin.nonce}, function(r) {
        if (!r.success) { console.log('Products load error:', r); return; }
        var h = '<option value="">اختر الصنف</option>';
        r.data.forEach(function(p) { h += '<option value="'+p.id+'">'+p.name+' (المخزون: '+(p.current_stock||0)+')</option>'; });
        $('#as_product_id').html(h);
        if (typeof iwRefreshSelect2 === 'function') iwRefreshSelect2('#as_product_id');
    });
    $.post(iwAdmin.ajaxurl, {action: 'iw_get_suppliers', nonce: iwAdmin.nonce}, function(r) {
        if (!r.success) { console.log('Suppliers load error:', r); return; }
        var h = '<option value="">اختر المورد</option>';
        r.data.forEach(function(s) { h += '<option value="'+s.id+'">'+s.name+'</option>'; });
        $('#as_supplier_id').html(h);
        if (typeof iwRefreshSelect2 === 'function') iwRefreshSelect2('#as_supplier_id');
    });

    function loadHistory() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_transactions', nonce: iwAdmin.nonce, type: 'add'}, function(r) {
            if (!r.success) return;
            var h = '';
            r.data.slice(0,50).forEach(function(t) {
                h += '<tr><td>'+t.product_name+'</td><td>'+t.quantity+'</td><td>'+parseFloat(t.unit_price).toFixed(2)+'</td><td>'+t.created_at+'</td><td>'+(t.notes||'-')+'</td></tr>';
            });
            $('#add-stock-history').html(h || '<tr><td colspan="5">لا توجد عمليات</td></tr>');
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
            if (r.success) { $('#iw-add-stock-form')[0].reset(); loadHistory(); }
        });
    });
});
</script>
