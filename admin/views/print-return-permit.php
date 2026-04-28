<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="<?php echo iw_dir(); ?>">
    <h1>طباعة أذون الارتجاع</h1>
    <div>
        <table class="form-table">
            <tr>
                <th>النوع</th>
                <td>
                    <select id="rp_type">
                        <option value="">الكل</option>
                        <option value="normal">ارتجاع عادي</option>
                        <option value="custody">رد عهدة</option>
                    </select>
                </td>
                <th>الحالة</th>
                <td>
                    <select id="rp_status">
                        <option value="">الكل</option>
                        <option value="approved" selected>معتمد</option>
                        <option value="completed">منفذ</option>
                    </select>
                </td>
                <td><button class="button button-primary" onclick="loadReturnPermits()">بحث</button></td>
            </tr>
        </table>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>رقم الإذن</th><th>النوع</th><th>القسم</th><th>الموظف</th><th>الحالة</th><th>التاريخ</th><th>طباعة</th>
                </tr>
            </thead>
            <tbody id="return-permits-body"></tbody>
        </table>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    window.loadReturnPermits = function() {
        var type   = $('#rp_type').val();
        var status = $('#rp_status').val();
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_return_orders', nonce: iwAdmin.nonce, order_type: type, status: status}, function(r) {
            if (!r.success) return;
            var statusMap = {pending:'معلق', approved:'معتمد', completed:'منفذ', rejected:'مرفوض'};
            var typeMap   = {normal:'ارتجاع عادي', custody:'رد عهدة'};
            var h = '';
            (r.data || []).forEach(function(o) {
                h += '<tr>';
                h += '<td>'+o.order_number+'</td>';
                h += '<td>'+(typeMap[o.order_type]||o.order_type)+'</td>';
                h += '<td>'+(o.department_name||'-')+'</td>';
                h += '<td>'+(o.employee_name||'-')+'</td>';
                h += '<td>'+(statusMap[o.status]||o.status)+'</td>';
                h += '<td>'+o.created_at+'</td>';
                h += '<td><button class="button button-primary" onclick="iwPrintRtPermit('+o.id+')">طباعة</button></td>';
                h += '</tr>';
            });
            $('#return-permits-body').html(h || '<tr><td colspan="7">لا توجد أذونات</td></tr>');
        });
    };
    loadReturnPermits();

    window.iwPrintRtPermit = function(id) {
        jQuery.post(iwAdmin.ajaxurl, {action: 'iw_get_return_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            if (!r.success) return;
            var o = r.data.order, items = r.data.items || [], sig = r.data.signature_url;
            var isCustody = o.order_type === 'custody';
            var header = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
            var title  = isCustody ? 'إذن رد عهدة' : 'إذن ارتجاع';
            var content = header;
            content += '<h2 style="text-align:center;">'+title+' رقم: '+o.order_number+'</h2>';
            content += '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;margin-bottom:10px;">';
            content += '<tr><th>القسم</th><td>'+(o.department_name||'-')+'</td><th>الموظف</th><td>'+(o.employee_name||'-')+'</td></tr>';
            content += '<tr><th>التاريخ</th><td>'+o.created_at+'</td><th>النوع</th><td>'+(isCustody?'رد عهدة':'ارتجاع عادي')+'</td></tr>';
            if (o.original_order_id) content += '<tr><th>الإذن الأصلي</th><td colspan="3">#'+o.original_order_id+'</td></tr>';
            content += '</table>';
            content += '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;">';
            content += '<tr style="background:#f0f0f0;"><th>#</th><th>الصنف</th><th>الوحدة</th><th>الكمية المطلوبة</th><th>الكمية المعتمدة</th></tr>';
            items.forEach(function(it, i) {
                var aq = it.approved_quantity !== null ? it.approved_quantity : it.quantity;
                content += '<tr><td>'+(i+1)+'</td><td>'+it.product_name+'</td><td>'+(it.product_unit||'-')+'</td><td>'+it.quantity+'</td><td>'+aq+'</td></tr>';
            });
            content += '</table>';
            if (sig) {
                content += '<div style="margin-top:30px;text-align:left;"><p><strong>توقيع المعتمد:</strong></p><img src="'+sig+'" style="max-width:'+(iwAdmin.sigWidth||150)+'px;height:auto;"/></div>';
            }
            content += '<div style="margin-top:40px;display:flex;justify-content:space-between;">';
            content += '<div><strong>مشرف المخزن</strong><br>التوقيع: ____________</div>';
            content += '<div><strong>مُسلِّم البضاعة</strong><br>التوقيع: ____________</div>';
            content += '</div>';
            var w = window.open('','','width=800,height=600');
            w.document.write('<html dir="<?php echo iw_dir(); ?>"><head><title>'+title+'</title><style>body{font-family:Arial,sans-serif;padding:20px;}th{background:#f0f0f0;}</style></head><body>'+content+'</body></html>');
            w.document.close(); w.print();
        });
    };
});
</script>
