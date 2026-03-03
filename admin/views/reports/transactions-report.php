<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>تقرير الحركات</h1>
    <table class="form-table">
        <tr>
            <th>من تاريخ</th><td><input type="date" id="tr_from"></td>
            <th>إلى تاريخ</th><td><input type="date" id="tr_to"></td>
            <th>النوع</th>
            <td><select id="tr_type"><option value="">الكل</option><option value="add">إضافة</option><option value="withdraw">صرف</option></select></td>
            <td><button class="button button-primary" onclick="loadTransactions()">بحث</button> <button class="button" onclick="iwPrintThis()">طباعة</button></td>
        </tr>
    </table>
    <div id="iw-print-area">
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>#</th><th>النوع</th><th>الصنف</th><th>الكمية</th><th>السعر</th><th>القسم</th><th>الموظف</th><th>المورد</th><th>التاريخ</th><th>ملاحظات</th></tr></thead>
            <tbody id="trans-body"></tbody>
        </table>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    window.loadTransactions = function() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_transactions_report', nonce: iwAdmin.nonce, from_date: $('#tr_from').val(), to_date: $('#tr_to').val(), type: $('#tr_type').val()}, function(r) {
            if (!r.success) return;
            var h = '';
            r.data.forEach(function(t, i) {
                var type = t.transaction_type === 'add' ? '<span style="color:green;">إضافة</span>' : '<span style="color:red;">صرف</span>';
                h += '<tr><td>'+(i+1)+'</td><td>'+type+'</td><td>'+t.product_name+'</td><td>'+t.quantity+'</td><td>'+parseFloat(t.unit_price).toFixed(2)+'</td>';
                h += '<td>'+(t.department_name||'-')+'</td><td>'+(t.employee_name||'-')+'</td><td>'+(t.supplier_name||'-')+'</td><td>'+t.created_at+'</td><td>'+(t.notes||'-')+'</td></tr>';
            });
            $('#trans-body').html(h || '<tr><td colspan="10">لا توجد حركات</td></tr>');
        });
    };
    loadTransactions();
    window.iwPrintThis = function() {
        var header = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
        var w = window.open('','','width=900,height=600');
        w.document.write('<html dir="rtl"><head><title>تقرير الحركات</title><style>body{font-family:Arial,sans-serif;padding:20px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #333;padding:6px;text-align:right;font-size:11px;}th{background:#f0f0f0;}</style></head><body>'+header+'<h2 style="text-align:center;">تقرير الحركات</h2>'+$('#iw-print-area').html()+'</body></html>');
        w.document.close(); w.print();
    };
});
</script>
