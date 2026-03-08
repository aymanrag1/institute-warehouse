<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>طباعة إذن إضافة مشتريات</h1>
    <div id="iw-print-add-permit">
        <p>اختر إذن الإضافة للطباعة:</p>
        <table class="form-table">
            <tr>
                <th>من تاريخ</th><td><input type="date" id="pa_from"></td>
                <th>إلى تاريخ</th><td><input type="date" id="pa_to"></td>
                <td><button class="button button-primary" onclick="loadAddPermits()">بحث</button></td>
            </tr>
        </table>
        <div style="margin-bottom:10px;">
            <button class="button" onclick="$('.pa-check').prop('checked', !$('.pa-check:first').prop('checked'))">تحديد الكل</button>
            <button class="button button-primary" onclick="iwPrintSelected()">طباعة المحدد</button>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th style="width:30px;"><input type="checkbox" onchange="$('.pa-check').prop('checked',this.checked)"></th><th>الصنف</th><th>الكمية</th><th>سعر الوحدة</th><th>المورد</th><th>التاريخ</th><th>ملاحظات</th></tr></thead>
            <tbody id="add-permits-body"></tbody>
        </table>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    var allData = [];

    window.loadAddPermits = function() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_transactions_report', nonce: iwAdmin.nonce, type: 'add', from_date: $('#pa_from').val(), to_date: $('#pa_to').val()}, function(r) {
            if (!r.success) return;
            allData = r.data;
            var h = '';
            r.data.forEach(function(t, idx) {
                h += '<tr><td><input type="checkbox" class="pa-check" data-index="'+idx+'"></td>';
                h += '<td>'+t.product_name+'</td><td>'+t.quantity+'</td><td>'+parseFloat(t.unit_price).toFixed(2)+'</td>';
                h += '<td>'+(t.supplier_name||'-')+'</td><td>'+t.created_at+'</td><td>'+(t.notes||'-')+'</td></tr>';
            });
            $('#add-permits-body').html(h || '<tr><td colspan="7">لا توجد أذونات</td></tr>');
        });
    };
    loadAddPermits();

    // Print selected items as one order
    window.iwPrintSelected = function() {
        var items = [];
        $('.pa-check:checked').each(function() {
            var idx = $(this).data('index');
            items.push(allData[idx]);
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
        content += '<table width="100%" style="margin-top:50px;border:none;"><tr>';
        content += '<td style="text-align:center;border:none;width:33%;"><strong>مسؤول المخازن</strong><br><br><br>التوقيع: ____________</td>';
        content += '<td style="text-align:center;border:none;width:33%;"><strong>مدير الحسابات</strong><br><br><br>التوقيع: ____________</td>';
        content += '<td style="text-align:center;border:none;width:33%;"><strong>يعتمد</strong><br><br><br>التوقيع: ____________</td>';
        content += '</tr></table>';

        var w = window.open('','','width=800,height=600');
        w.document.write('<html dir="rtl"><head><title>إذن إضافة مشتريات</title><style>body{font-family:Arial,sans-serif;padding:20px;}th{background:#f0f0f0;}</style></head><body>'+content+'</body></html>');
        w.document.close(); w.print();
    };
});
</script>
