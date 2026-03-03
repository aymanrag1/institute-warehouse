<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>تقرير استهلاك الأقسام</h1>
    <table class="form-table">
        <tr>
            <th>القسم</th>
            <td><select id="dc_department" class="regular-text"><option value="">جميع الأقسام</option></select></td>
            <th>من تاريخ</th><td><input type="date" id="dc_from"></td>
            <th>إلى تاريخ</th><td><input type="date" id="dc_to"></td>
            <td><button class="button button-primary" onclick="loadDeptConsumption()">بحث</button> <button class="button" onclick="iwPrintThis()">طباعة</button></td>
        </tr>
    </table>

    <div id="iw-print-area">
        <!-- Summary Section -->
        <div id="dc-summary" style="margin-bottom:20px;"></div>

        <!-- Detailed Table -->
        <table class="wp-list-table widefat fixed striped" id="dc-detail-table">
            <thead><tr><th>#</th><th>التاريخ</th><th>القسم</th><th>الصنف</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th><th>المستلم</th></tr></thead>
            <tbody id="dept-consumption-body"></tbody>
            <tfoot id="dept-consumption-footer"></tfoot>
        </table>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    // Load departments dropdown
    $.post(iwAdmin.ajaxurl, {action: 'iw_get_departments', nonce: iwAdmin.nonce}, function(r) {
        if(r.success) {
            var html = '<option value="">جميع الأقسام</option>';
            r.data.forEach(function(d) { html += '<option value="'+d.id+'">'+d.name+'</option>'; });
            $('#dc_department').html(html);
            if (typeof iwRefreshSelect2 === 'function') iwRefreshSelect2('#dc_department');
        }
    });

    window.loadDeptConsumption = function() {
        var deptId = $('#dc_department').val();
        $.post(iwAdmin.ajaxurl, {
            action: 'iw_get_dept_consumption_detail',
            nonce: iwAdmin.nonce,
            department_id: deptId,
            from_date: $('#dc_from').val(),
            to_date: $('#dc_to').val()
        }, function(r) {
            if (!r.success) return;

            var data = r.data.transactions || [];
            var summary = r.data.summary || {};

            // Build summary
            var sumHtml = '<div style="background:#f9f9f9;padding:15px;border-radius:5px;margin-bottom:15px;">';
            sumHtml += '<h3 style="margin-top:0;">ملخص الاستهلاك</h3>';
            sumHtml += '<table style="width:100%;"><tr>';
            sumHtml += '<td><strong>عدد عمليات الصرف:</strong> '+summary.total_transactions+'</td>';
            sumHtml += '<td><strong>إجمالي الكميات:</strong> '+summary.total_qty+'</td>';
            sumHtml += '<td><strong>إجمالي القيمة:</strong> '+parseFloat(summary.total_value||0).toFixed(2)+' جنيه</td>';
            sumHtml += '</tr></table></div>';
            $('#dc-summary').html(sumHtml);

            // Build detailed table
            var h = '', i = 0, totalQty = 0, totalValue = 0;
            data.forEach(function(t) {
                i++;
                var lineTotal = parseInt(t.quantity) * parseFloat(t.unit_price);
                totalQty += parseInt(t.quantity);
                totalValue += lineTotal;
                h += '<tr>';
                h += '<td>'+i+'</td>';
                h += '<td>'+t.created_at+'</td>';
                h += '<td>'+(t.department_name||'-')+'</td>';
                h += '<td>'+t.product_name+'</td>';
                h += '<td>'+t.quantity+'</td>';
                h += '<td>'+parseFloat(t.unit_price).toFixed(2)+'</td>';
                h += '<td>'+lineTotal.toFixed(2)+'</td>';
                h += '<td>'+(t.employee_name||'-')+'</td>';
                h += '</tr>';
            });
            $('#dept-consumption-body').html(h || '<tr><td colspan="8">لا توجد بيانات</td></tr>');

            // Footer totals
            if (data.length > 0) {
                var fHtml = '<tr style="background:#f0f0f0;font-weight:bold;">';
                fHtml += '<td colspan="4">الإجمالي</td>';
                fHtml += '<td>'+totalQty+'</td>';
                fHtml += '<td>-</td>';
                fHtml += '<td>'+totalValue.toFixed(2)+'</td>';
                fHtml += '<td></td></tr>';
                $('#dept-consumption-footer').html(fHtml);
            } else {
                $('#dept-consumption-footer').html('');
            }
        });
    };
    loadDeptConsumption();

    window.iwPrintThis = function() {
        var header = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
        var deptName = $('#dc_department option:selected').text() || 'جميع الأقسام';
        var fromDate = $('#dc_from').val() || '-';
        var toDate = $('#dc_to').val() || '-';
        var w = window.open('','','width=900,height=700');
        w.document.write('<html dir="rtl"><head><title>استهلاك الأقسام</title><style>body{font-family:Arial,sans-serif;padding:20px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #333;padding:6px;text-align:right;}th{background:#f0f0f0;}.no-border{border:none;}</style></head><body>'+header+'<h2 style="text-align:center;">تقرير استهلاك الأقسام</h2><p style="text-align:center;"><strong>القسم:</strong> '+deptName+' | <strong>من:</strong> '+fromDate+' | <strong>إلى:</strong> '+toDate+'</p>'+$('#iw-print-area').html()+'</body></html>');
        w.document.close(); w.print();
    };
});
</script>
