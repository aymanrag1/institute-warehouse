<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>تقرير استهلاك الأقسام <button class="button" onclick="iwPrintThis()">طباعة</button></h1>
    <table class="form-table">
        <tr>
            <th>من تاريخ</th><td><input type="date" id="dc_from"></td>
            <th>إلى تاريخ</th><td><input type="date" id="dc_to"></td>
            <td><button class="button button-primary" onclick="loadDeptConsumption()">بحث</button></td>
        </tr>
    </table>
    <div id="iw-print-area">
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>#</th><th>القسم</th><th>عدد عمليات الصرف</th><th>إجمالي الأصناف المصروفة</th><th>إجمالي القيمة</th></tr></thead>
            <tbody id="dept-consumption-body"></tbody>
        </table>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    window.loadDeptConsumption = function() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_transactions_report', nonce: iwAdmin.nonce, type: 'withdraw', from_date: $('#dc_from').val(), to_date: $('#dc_to').val()}, function(r) {
            if (!r.success) return;
            var depts = {};
            r.data.forEach(function(t) {
                var dname = t.department_name || 'غير محدد';
                if (!depts[dname]) depts[dname] = {count: 0, qty: 0, value: 0};
                depts[dname].count++;
                depts[dname].qty += parseInt(t.quantity);
                depts[dname].value += parseInt(t.quantity) * parseFloat(t.unit_price);
            });
            var h = '', i = 0;
            for (var d in depts) {
                i++;
                h += '<tr><td>'+i+'</td><td>'+d+'</td><td>'+depts[d].count+'</td><td>'+depts[d].qty+'</td><td>'+depts[d].value.toFixed(2)+'</td></tr>';
            }
            $('#dept-consumption-body').html(h || '<tr><td colspan="5">لا توجد بيانات</td></tr>');
        });
    };
    loadDeptConsumption();
    window.iwPrintThis = function() {
        var header = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
        var w = window.open('','','width=800,height=600');
        w.document.write('<html dir="rtl"><head><title>استهلاك الأقسام</title><style>body{font-family:Arial,sans-serif;padding:20px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #333;padding:6px;text-align:right;}th{background:#f0f0f0;}</style></head><body>'+header+'<h2 style="text-align:center;">تقرير استهلاك الأقسام</h2>'+$('#iw-print-area').html()+'</body></html>');
        w.document.close(); w.print();
    };
});
</script>
