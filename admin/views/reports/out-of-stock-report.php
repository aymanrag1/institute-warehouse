<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>تقرير الأصناف المنتهية <button class="button" onclick="iwPrintThis()">طباعة</button></h1>
    <div id="iw-print-area">
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>#</th><th>الصنف</th><th>الكود</th><th>التصنيف</th><th>الوحدة</th><th>الحد الأدنى</th><th>الحد الأقصى</th></tr></thead>
            <tbody id="out-stock-body"></tbody>
        </table>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    $.post(iwAdmin.ajaxurl, {action: 'iw_get_stock_report', nonce: iwAdmin.nonce}, function(r) {
        if (!r.success) return;
        var h = '', count = 0;
        r.data.forEach(function(p) {
            if (parseInt(p.current_stock) <= 0) {
                count++;
                h += '<tr><td>'+count+'</td><td>'+p.name+'</td><td>'+(p.sku||'-')+'</td><td>'+(p.category||'-')+'</td><td>'+(p.unit||'-')+'</td><td>'+p.min_stock+'</td><td>'+p.max_stock+'</td></tr>';
            }
        });
        $('#out-stock-body').html(h || '<tr><td colspan="7">لا توجد أصناف منتهية</td></tr>');
    });
    window.iwPrintThis = function() {
        var header = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
        var w = window.open('','','width=800,height=600');
        w.document.write('<html dir="rtl"><head><title>أصناف منتهية</title><style>body{font-family:Arial,sans-serif;padding:20px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #333;padding:6px;text-align:right;}th{background:#f0f0f0;}</style></head><body>'+header+'<h2 style="text-align:center;">تقرير الأصناف المنتهية من المخزون</h2>'+$('#iw-print-area').html()+'</body></html>');
        w.document.close(); w.print();
    };
});
</script>
