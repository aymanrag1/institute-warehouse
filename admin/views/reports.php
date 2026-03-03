<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>التقارير</h1>
    <div class="iw-tabs">
        <button class="iw-tab active" onclick="iwRepTab('stock')">تقرير المخزون</button>
        <button class="iw-tab" onclick="iwRepTab('transactions')">تقرير الحركات</button>
        <button class="iw-tab" onclick="iwRepTab('lowstock')">أصناف تحت الحد الأدنى</button>
    </div>

    <div id="rep-tab-stock" class="iw-tab-content">
        <h2>تقرير المخزون الحالي</h2>
        <div style="margin-bottom:15px;">
            <label style="margin-left:10px;"><strong>التصنيف:</strong></label>
            <select id="stock_category" style="min-width:200px;" onchange="loadStockReport()">
                <option value="">جميع التصنيفات</option>
                <?php echo IW_Categories::get_options_html(); ?>
            </select>
            <button class="button" onclick="iwPrintReport('stock')" style="margin-right:15px;">طباعة</button>
        </div>
        <table class="wp-list-table widefat fixed striped" id="stock-report-table">
            <thead><tr><th>الصنف</th><th>الكود</th><th>التصنيف</th><th>الوحدة</th><th>المخزون الحالي</th><th>الحد الأدنى</th><th>الحد الأقصى</th><th>السعر</th></tr></thead>
            <tbody id="stock-report-body"></tbody>
        </table>
    </div>

    <div id="rep-tab-transactions" class="iw-tab-content" style="display:none;">
        <h2>تقرير الحركات</h2>
        <table class="form-table">
            <tr>
                <th>من تاريخ</th><td><input type="date" id="rep_from"></td>
                <th>إلى تاريخ</th><td><input type="date" id="rep_to"></td>
                <th>النوع</th><td><select id="rep_type"><option value="">الكل</option><option value="add">إضافة</option><option value="withdraw">صرف</option></select></td>
                <td><button class="button button-primary" onclick="loadTransReport()">بحث</button> <button class="button" onclick="iwPrintReport('transactions')">طباعة</button></td>
            </tr>
        </table>
        <table class="wp-list-table widefat fixed striped" id="trans-report-table">
            <thead><tr><th>النوع</th><th>الصنف</th><th>الكمية</th><th>السعر</th><th>القسم</th><th>الموظف</th><th>المورد</th><th>التاريخ</th></tr></thead>
            <tbody id="trans-report-body"></tbody>
        </table>
    </div>

    <div id="rep-tab-lowstock" class="iw-tab-content" style="display:none;">
        <h2>أصناف تحت الحد الأدنى</h2>
        <div style="margin-bottom:15px;">
            <label style="margin-left:10px;"><strong>التصنيف:</strong></label>
            <select id="lowstock_category" style="min-width:200px;" onchange="loadLowReport()">
                <option value="">جميع التصنيفات</option>
                <?php echo IW_Categories::get_options_html(); ?>
            </select>
            <button class="button" onclick="iwPrintReport('lowstock')" style="margin-right:15px;">طباعة</button>
        </div>
        <table class="wp-list-table widefat fixed striped" id="low-report-table">
            <thead><tr><th>الصنف</th><th>التصنيف</th><th>المخزون الحالي</th><th>الحد الأدنى</th><th>الحد الأقصى</th><th>الكمية المطلوبة</th></tr></thead>
            <tbody id="low-report-body"></tbody>
        </table>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    window.iwRepTab = function(tab) {
        $('.iw-tab-content').hide(); $('.iw-tab').removeClass('active');
        $('#rep-tab-'+tab).show(); $('[onclick="iwRepTab(\''+tab+'\')"]').addClass('active');
        if (tab === 'stock') loadStockReport();
        if (tab === 'lowstock') loadLowReport();
    };

    window.loadStockReport = function() {
        var category = $('#stock_category').val() || '';
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_stock_report', nonce: iwAdmin.nonce, category: category}, function(r) {
            if (!r.success) return;
            var h = '';
            r.data.forEach(function(p) {
                h += '<tr><td>'+p.name+'</td><td>'+(p.sku||'-')+'</td><td>'+(p.category||'-')+'</td><td>'+(p.unit||'-')+'</td><td>'+p.current_stock+'</td><td>'+p.min_stock+'</td><td>'+p.max_stock+'</td><td>'+parseFloat(p.price).toFixed(2)+'</td></tr>';
            });
            $('#stock-report-body').html(h || '<tr><td colspan="8">لا توجد أصناف</td></tr>');
        });
    };
    loadStockReport();

    window.loadTransReport = function() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_transactions_report', nonce: iwAdmin.nonce, from_date: $('#rep_from').val(), to_date: $('#rep_to').val(), type: $('#rep_type').val()}, function(r) {
            if (!r.success) return;
            var h = '';
            r.data.forEach(function(t) {
                var type = t.transaction_type === 'add' ? '<span class="iw-badge iw-badge-success">إضافة</span>' : '<span class="iw-badge iw-badge-danger">صرف</span>';
                h += '<tr><td>'+type+'</td><td>'+t.product_name+'</td><td>'+t.quantity+'</td><td>'+parseFloat(t.unit_price).toFixed(2)+'</td>';
                h += '<td>'+(t.department_name||'-')+'</td><td>'+(t.employee_name||'-')+'</td><td>'+(t.supplier_name||'-')+'</td><td>'+t.created_at+'</td></tr>';
            });
            $('#trans-report-body').html(h || '<tr><td colspan="8">لا توجد حركات</td></tr>');
        });
    };

    window.loadLowReport = function() {
        var category = $('#lowstock_category').val() || '';
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_low_stock_report', nonce: iwAdmin.nonce, category: category}, function(r) {
            if (!r.success) return;
            var h = '';
            r.data.forEach(function(p) {
                h += '<tr><td>'+p.name+'</td><td>'+(p.category||'-')+'</td><td>'+p.current_stock+'</td><td>'+p.min_stock+'</td><td>'+p.max_stock+'</td><td>'+(p.max_stock-p.current_stock)+'</td></tr>';
            });
            $('#low-report-body').html(h || '<tr><td colspan="6">لا توجد أصناف تحت الحد الأدنى</td></tr>');
        });
    };

    window.iwPrintReport = function(type) {
        var tableId = type === 'stock' ? '#stock-report-table' : type === 'transactions' ? '#trans-report-table' : '#low-report-table';
        var title = type === 'stock' ? 'تقرير المخزون' : type === 'transactions' ? 'تقرير الحركات' : 'أصناف تحت الحد الأدنى';
        var header = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
        var w = window.open('','','width=800,height=600');
        w.document.write('<html dir="rtl"><head><title>'+title+'</title><style>body{font-family:Arial,sans-serif;padding:20px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #333;padding:8px;text-align:right;}th{background:#f0f0f0;}</style></head><body>'+header+'<h2 style="text-align:center;">'+title+'</h2>'+$(tableId).prop('outerHTML')+'</body></html>');
        w.document.close(); w.print();
    };
});
</script>
