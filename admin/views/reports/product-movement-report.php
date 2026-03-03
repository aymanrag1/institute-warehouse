<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>تقرير حركة صنف</h1>
    <table class="form-table">
        <tr>
            <th>الصنف</th>
            <td><select id="pm_product" class="regular-text"><option value="">اختر الصنف</option></select></td>
            <th>من تاريخ</th><td><input type="date" id="pm_from"></td>
            <th>إلى تاريخ</th><td><input type="date" id="pm_to"></td>
            <td><button class="button button-primary" onclick="loadProductMovement()">بحث</button> <button class="button" onclick="iwPrintThis()">طباعة</button></td>
        </tr>
    </table>
    <div id="iw-print-area">
        <div id="pm-summary" style="margin:15px 0;"></div>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>#</th><th>النوع</th><th>الكمية</th><th>السعر</th><th>القسم/المورد</th><th>التاريخ</th><th>ملاحظات</th></tr></thead>
            <tbody id="pm-body"></tbody>
        </table>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    $.post(iwAdmin.ajaxurl, {action: 'iw_get_products_list', nonce: iwAdmin.nonce}, function(r) {
        if (!r.success) return;
        var h = '<option value="">اختر الصنف</option>';
        r.data.forEach(function(p) { h += '<option value="'+p.id+'">'+p.name+'</option>'; });
        $('#pm_product').html(h);
    });

    window.loadProductMovement = function() {
        var productId = $('#pm_product').val();
        if (!productId) { alert('اختر صنف'); return; }
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_transactions_report', nonce: iwAdmin.nonce, from_date: $('#pm_from').val(), to_date: $('#pm_to').val()}, function(r) {
            if (!r.success) return;
            var filtered = r.data.filter(function(t) { return t.product_id == productId; });
            var h = '', totalIn = 0, totalOut = 0;
            filtered.forEach(function(t, i) {
                var type = t.transaction_type === 'add' ? '<span style="color:green;">إضافة</span>' : '<span style="color:red;">صرف</span>';
                var source = t.transaction_type === 'add' ? (t.supplier_name||'-') : (t.department_name||'-');
                if (t.transaction_type === 'add') totalIn += parseInt(t.quantity); else totalOut += parseInt(t.quantity);
                h += '<tr><td>'+(i+1)+'</td><td>'+type+'</td><td>'+t.quantity+'</td><td>'+parseFloat(t.unit_price).toFixed(2)+'</td>';
                h += '<td>'+source+'</td><td>'+t.created_at+'</td><td>'+(t.notes||'-')+'</td></tr>';
            });
            $('#pm-body').html(h || '<tr><td colspan="7">لا توجد حركات</td></tr>');
            $('#pm-summary').html('<strong>إجمالي الوارد:</strong> '+totalIn+' | <strong>إجمالي المنصرف:</strong> '+totalOut+' | <strong>الصافي:</strong> '+(totalIn-totalOut));
        });
    };

    window.iwPrintThis = function() {
        var header = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
        var productName = $('#pm_product option:selected').text();
        var w = window.open('','','width=800,height=600');
        w.document.write('<html dir="rtl"><head><title>حركة صنف</title><style>body{font-family:Arial,sans-serif;padding:20px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #333;padding:6px;text-align:right;}th{background:#f0f0f0;}</style></head><body>'+header+'<h2 style="text-align:center;">تقرير حركة صنف: '+productName+'</h2>'+$('#iw-print-area').html()+'</body></html>');
        w.document.close(); w.print();
    };
});
</script>
