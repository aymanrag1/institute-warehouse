<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>طباعة إذن صرف</h1>
    <div id="iw-print-withdraw-permit">
        <table class="form-table">
            <tr>
                <th>الحالة</th>
                <td>
                    <select id="pw_status">
                        <option value="">الكل</option>
                        <option value="approved" selected>معتمد</option>
                        <option value="completed">منفذ</option>
                    </select>
                </td>
                <td><button class="button button-primary" onclick="loadWithdrawPermits()">بحث</button></td>
            </tr>
        </table>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>رقم الإذن</th><th>القسم</th><th>الموظف</th><th>الحالة</th><th>التاريخ</th><th>طباعة</th></tr></thead>
            <tbody id="withdraw-permits-body"></tbody>
        </table>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    window.loadWithdrawPermits = function() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_withdrawal_orders', nonce: iwAdmin.nonce, status: $('#pw_status').val()}, function(r) {
            if (!r.success) return;
            var statusMap = {pending:'معلق',approved:'معتمد',rejected:'مرفوض',completed:'منفذ'};
            var h = '';
            r.data.forEach(function(o) {
                h += '<tr><td>'+o.order_number+'</td><td>'+(o.department_name||'-')+'</td><td>'+(o.employee_name||'-')+'</td>';
                h += '<td>'+( statusMap[o.status]||o.status)+'</td><td>'+o.created_at+'</td>';
                h += '<td><button class="button button-primary" onclick="iwPrintWdPermit('+o.id+')">طباعة</button></td></tr>';
            });
            $('#withdraw-permits-body').html(h || '<tr><td colspan="6">لا توجد أذونات</td></tr>');
        });
    };
    loadWithdrawPermits();

    window.iwPrintWdPermit = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_withdrawal_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            if (!r.success) return;
            var o = r.data.order, items = r.data.items, sig = r.data.signature_url;
            var header = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
            var content = header;
            content += '<h2 style="text-align:center;">إذن صرف رقم: '+o.order_number+'</h2>';
            content += '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;margin-bottom:10px;">';
            content += '<tr><th>القسم</th><td>'+(o.department_name||'-')+'</td><th>الموظف</th><td>'+(o.employee_name||'-')+'</td></tr>';
            content += '<tr><th>التاريخ</th><td>'+o.created_at+'</td><th>الحالة</th><td>'+({pending:'معلق',approved:'معتمد',rejected:'مرفوض',completed:'منفذ'}[o.status]||o.status)+'</td></tr>';
            content += '</table>';
            content += '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;">';
            content += '<tr style="background:#f0f0f0;"><th>#</th><th>الصنف</th><th>الوحدة</th><th>الكمية المطلوبة</th><th>الكمية المعتمدة</th></tr>';
            items.forEach(function(it, i) {
                var aq = it.approved_quantity !== null ? it.approved_quantity : it.quantity;
                content += '<tr><td>'+(i+1)+'</td><td>'+it.product_name+'</td><td>'+(it.product_unit||'-')+'</td><td>'+it.quantity+'</td><td>'+aq+'</td></tr>';
            });
            content += '</table>';
            if (sig) {
                content += '<div style="margin-top:30px;text-align:left;"><p><strong>توقيع المعتمد (عميد المعهد / المدير):</strong></p>';
                content += '<img src="'+sig+'" style="max-height:80px;" /></div>';
            }
            content += '<div style="margin-top:40px;display:flex;justify-content:space-between;"><div><strong>مشرف المخزن</strong><br>التوقيع: ____________</div><div><strong>المستلم</strong><br>التوقيع: ____________</div></div>';
            var w = window.open('','','width=800,height=600');
            w.document.write('<html dir="rtl"><head><title>إذن صرف</title><style>body{font-family:Arial,sans-serif;padding:20px;}th{background:#f0f0f0;}</style></head><body>'+content+'</body></html>');
            w.document.close(); w.print();
        });
    };
});
</script>
