<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>طباعة إذن إضافة</h1>
    <div id="iw-print-add-permit">
        <p>اختر إذن الإضافة للطباعة:</p>
        <table class="form-table">
            <tr>
                <th>من تاريخ</th><td><input type="date" id="pa_from"></td>
                <th>إلى تاريخ</th><td><input type="date" id="pa_to"></td>
                <td><button class="button button-primary" onclick="loadAddPermits()">بحث</button></td>
            </tr>
        </table>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>الصنف</th><th>الكمية</th><th>سعر الوحدة</th><th>المورد</th><th>التاريخ</th><th>ملاحظات</th><th>طباعة</th></tr></thead>
            <tbody id="add-permits-body"></tbody>
        </table>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    window.loadAddPermits = function() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_transactions_report', nonce: iwAdmin.nonce, type: 'add', from_date: $('#pa_from').val(), to_date: $('#pa_to').val()}, function(r) {
            if (!r.success) return;
            var h = '';
            r.data.forEach(function(t) {
                h += '<tr><td>'+t.product_name+'</td><td>'+t.quantity+'</td><td>'+parseFloat(t.unit_price).toFixed(2)+'</td>';
                h += '<td>'+(t.supplier_name||'-')+'</td><td>'+t.created_at+'</td><td>'+(t.notes||'-')+'</td>';
                h += '<td><button class="button" onclick="iwPrintAddPermit('+JSON.stringify(t).replace(/"/g,'&quot;')+')">طباعة</button></td></tr>';
            });
            $('#add-permits-body').html(h || '<tr><td colspan="7">لا توجد أذونات</td></tr>');
        });
    };
    loadAddPermits();

    window.iwPrintAddPermit = function(t) {
        var header = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
        var content = header;
        content += '<h2 style="text-align:center;">إذن إضافة مخزون</h2>';
        content += '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;">';
        content += '<tr><th>الصنف</th><td>'+t.product_name+'</td><th>التاريخ</th><td>'+t.created_at+'</td></tr>';
        content += '<tr><th>الكمية</th><td>'+t.quantity+'</td><th>سعر الوحدة</th><td>'+parseFloat(t.unit_price).toFixed(2)+'</td></tr>';
        content += '<tr><th>المورد</th><td>'+(t.supplier_name||'-')+'</td><th>الإجمالي</th><td>'+(t.quantity*parseFloat(t.unit_price)).toFixed(2)+'</td></tr>';
        content += '<tr><th>ملاحظات</th><td colspan="3">'+(t.notes||'-')+'</td></tr>';
        content += '</table>';
        content += '<div style="margin-top:40px;display:flex;justify-content:space-between;"><div><strong>مسؤول المخزن</strong><br>التوقيع: ____________</div><div><strong>المستلم</strong><br>التوقيع: ____________</div></div>';
        var w = window.open('','','width=800,height=600');
        w.document.write('<html dir="rtl"><head><title>إذن إضافة</title><style>body{font-family:Arial,sans-serif;padding:20px;}th{background:#f0f0f0;}</style></head><body>'+content+'</body></html>');
        w.document.close(); w.print();
    };
});
</script>
