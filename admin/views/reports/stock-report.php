<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>تقرير المخزون الحالي <button class="button" onclick="iwPrintThis()">طباعة</button></h1>
    <div id="iw-print-area">
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>#</th><th>الصنف</th><th>الكود</th><th>التصنيف</th><th>الوحدة</th><th>المخزون الحالي</th><th>الحد الأدنى</th><th>الحد الأقصى</th><th>السعر</th><th>القيمة</th></tr></thead>
            <tbody id="stock-report-body"></tbody>
            <tfoot><tr><th colspan="9" style="text-align:left;">إجمالي القيمة</th><th id="total-value">0</th></tr></tfoot>
        </table>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    $.post(iwAdmin.ajaxurl, {action: 'iw_get_stock_report', nonce: iwAdmin.nonce}, function(r) {
        if (!r.success) return;
        var h = '', total = 0;
        r.data.forEach(function(p, i) {
            var val = p.current_stock * parseFloat(p.price);
            total += val;
            h += '<tr><td>'+(i+1)+'</td><td>'+p.name+'</td><td>'+(p.sku||'-')+'</td><td>'+(p.category||'-')+'</td><td>'+(p.unit||'-')+'</td>';
            h += '<td>'+p.current_stock+'</td><td>'+p.min_stock+'</td><td>'+p.max_stock+'</td><td>'+parseFloat(p.price).toFixed(2)+'</td><td>'+val.toFixed(2)+'</td></tr>';
        });
        $('#stock-report-body').html(h || '<tr><td colspan="10">لا توجد أصناف</td></tr>');
        $('#total-value').text(total.toFixed(2));
    });
    window.iwPrintThis = function() {
        var header = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
        var w = window.open('','','width=900,height=600');
        w.document.write('<html dir="rtl"><head><title>تقرير المخزون</title><style>body{font-family:Arial,sans-serif;padding:20px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #333;padding:6px;text-align:right;font-size:12px;}th{background:#f0f0f0;}</style></head><body>'+header+'<h2 style="text-align:center;">تقرير المخزون الحالي</h2>'+$('#iw-print-area').html()+'</body></html>');
        w.document.close(); w.print();
    };
});
</script>
