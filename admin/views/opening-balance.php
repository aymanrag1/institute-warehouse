<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>الرصيد الافتتاحي</h1>
    <form id="iw-ob-form">
        <table class="form-table">
            <tr><th>تاريخ الرصيد *</th><td><input type="date" id="ob_date" class="regular-text" required></td></tr>
            <tr><th>ملاحظات</th><td><textarea id="ob_notes" class="large-text" rows="2"></textarea></td></tr>
        </table>
        <h3>الأصناف</h3>
        <table class="wp-list-table widefat fixed">
            <thead><tr><th>الصنف</th><th>الكمية</th><th>سعر الوحدة</th><th>إجراء</th></tr></thead>
            <tbody id="ob-items-body"></tbody>
        </table>
        <button type="button" class="button" onclick="iwAddObItem()" style="margin-top:10px;">+ إضافة صنف</button>
        <br><br>
        <button type="submit" class="button button-primary button-large">حفظ الرصيد الافتتاحي</button>
    </form>

    <h2 style="margin-top:30px;">الأرصدة الافتتاحية السابقة</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr><th>الصنف</th><th>الكمية</th><th>سعر الوحدة</th><th>التاريخ</th><th>ملاحظات</th></tr></thead>
        <tbody id="ob-history"></tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    var products = [];
    $.post(iwAdmin.ajaxurl, {action: 'iw_get_products_list', nonce: iwAdmin.nonce}, function(r) { if(r.success) products = r.data; });

    window.iwAddObItem = function() {
        var opts = '<option value="">اختر الصنف</option>';
        products.forEach(function(p) { opts += '<option value="'+p.id+'" data-price="'+p.price+'">'+p.name+'</option>'; });
        var row = '<tr><td><select class="ob-product regular-text" onchange="var pr=$(this).find(\':selected\').data(\'price\')||0;$(this).closest(\'tr\').find(\'.ob-price\').val(pr);">'+opts+'</select></td>';
        row += '<td><input type="number" class="ob-qty" min="1" value="1"></td>';
        row += '<td><input type="number" class="ob-price" min="0" step="0.01" value="0"></td>';
        row += '<td><button type="button" class="button iw-btn-danger" onclick="$(this).closest(\'tr\').remove()">حذف</button></td></tr>';
        $('#ob-items-body').append(row);
    };

    function loadHistory() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_opening_balances', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) return;
            var h = '';
            r.data.forEach(function(b) {
                h += '<tr><td>'+b.product_name+'</td><td>'+b.quantity+'</td><td>'+parseFloat(b.unit_price).toFixed(2)+'</td><td>'+b.balance_date+'</td><td>'+(b.notes||'-')+'</td></tr>';
            });
            $('#ob-history').html(h || '<tr><td colspan="5">لا توجد أرصدة افتتاحية</td></tr>');
        });
    }
    loadHistory();

    $('#iw-ob-form').on('submit', function(e) {
        e.preventDefault();
        var items = [];
        $('#ob-items-body tr').each(function() {
            var pid = $(this).find('.ob-product').val();
            var qty = $(this).find('.ob-qty').val();
            var price = $(this).find('.ob-price').val();
            if (pid && qty > 0) items.push({product_id: pid, quantity: qty, unit_price: price});
        });
        if (!items.length) { alert('يجب إضافة أصناف'); return; }
        $.post(iwAdmin.ajaxurl, {
            action: 'iw_save_opening_balance', nonce: iwAdmin.nonce,
            items: JSON.stringify(items), balance_date: $('#ob_date').val(), notes: $('#ob_notes').val()
        }, function(r) {
            alert(r.data.message);
            if (r.success) { $('#iw-ob-form')[0].reset(); $('#ob-items-body').html(''); loadHistory(); }
        });
    });

    iwAddObItem();
});
</script>
