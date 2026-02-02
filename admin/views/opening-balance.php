<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>الرصيد الافتتاحي</h1>

    <div class="iw-tabs">
        <button class="iw-tab active" onclick="iwObTab('manual')">إدخال يدوي</button>
        <button class="iw-tab" onclick="iwObTab('excel')">استيراد من Excel</button>
        <button class="iw-tab" onclick="iwObTab('history')">الأرصدة السابقة</button>
    </div>

    <!-- Manual Entry -->
    <div id="ob-tab-manual" class="iw-tab-content">
        <h2>إدخال الرصيد الافتتاحي يدوياً</h2>
        <form id="iw-ob-form">
            <table class="form-table">
                <tr><th>تاريخ الرصيد *</th><td><input type="date" id="ob_date" class="regular-text" required></td></tr>
                <tr><th>ملاحظات</th><td><textarea id="ob_notes" class="large-text" rows="2"></textarea></td></tr>
            </table>
            <h3>الأصناف</h3>
            <table class="wp-list-table widefat fixed">
                <thead><tr><th>الصنف</th><th>الكمية</th><th>سعر الوحدة</th><th>إجراء</th></tr></thead>
                <tbody id="ob-items-body"></tbody>
            </table>
            <button type="button" class="button" onclick="iwAddObItem()" style="margin-top:10px;">+ إضافة صنف</button>
            <br><br>
            <button type="submit" class="button button-primary button-large">حفظ الرصيد الافتتاحي</button>
        </form>
    </div>

    <!-- Excel Import -->
    <div id="ob-tab-excel" class="iw-tab-content" style="display:none;">
        <h2>استيراد الرصيد الافتتاحي من Excel</h2>

        <div style="background:#f9f9f9;padding:15px;border:1px solid #ddd;border-radius:5px;margin-bottom:20px;">
            <h3>1. تحميل النموذج</h3>
            <p>قم بتحميل نموذج Excel وتعبئته بالأرصدة الافتتاحية:</p>
            <button class="button button-primary" onclick="iwDownloadObTemplate()">تحميل نموذج الرصيد الافتتاحي</button>
        </div>

        <div style="background:#f9f9f9;padding:15px;border:1px solid #ddd;border-radius:5px;">
            <h3>2. رفع الملف</h3>
            <p>يجب أن يحتوي الملف على الأعمدة التالية:</p>
            <table class="wp-list-table widefat fixed" style="max-width:500px;margin-bottom:15px;">
                <thead><tr><th>العمود</th><th>الوصف</th><th>مطلوب</th></tr></thead>
                <tbody>
                    <tr><td>A</td><td>اسم الصنف (يجب أن يكون موجود مسبقاً)</td><td><strong>نعم</strong></td></tr>
                    <tr><td>B</td><td>الكمية</td><td><strong>نعم</strong></td></tr>
                    <tr><td>C</td><td>سعر الوحدة</td><td><strong>نعم</strong></td></tr>
                </tbody>
            </table>
            <table class="form-table">
                <tr><th>تاريخ الرصيد *</th><td><input type="date" id="ob_excel_date" class="regular-text" required></td></tr>
                <tr><th>ملاحظات</th><td><textarea id="ob_excel_notes" class="large-text" rows="2"></textarea></td></tr>
            </table>
            <input type="file" id="ob_excel_file" accept=".xlsx,.xls,.csv">
            <button class="button button-primary" onclick="iwImportObExcel()">استيراد</button>
            <div id="ob-import-preview" style="margin-top:20px;"></div>
        </div>
    </div>

    <!-- History -->
    <div id="ob-tab-history" class="iw-tab-content" style="display:none;">
        <h2>الأرصدة الافتتاحية السابقة</h2>
        <p><button class="button iw-btn-danger" onclick="iwResetAllOb()">حذف جميع الأرصدة الافتتاحية وإعادة الضبط</button></p>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>الصنف</th><th>الكمية</th><th>سعر الوحدة</th><th>التاريخ</th><th>ملاحظات</th><th>إجراء</th></tr></thead>
            <tbody id="ob-history"></tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var products = [];
    $.post(iwAdmin.ajaxurl, {action: 'iw_get_products_list', nonce: iwAdmin.nonce}, function(r) {
        if(r.success) {
            products = r.data;
            console.log('OB Products loaded:', r.data.length);
            iwAddObItem();
        }
        else console.log('OB Products error:', r);
    });

    // Tabs
    window.iwObTab = function(tab) {
        $('.iw-tab-content').hide(); $('.iw-tab').removeClass('active');
        $('#ob-tab-'+tab).show();
        $('[onclick="iwObTab(\''+tab+'\')"]').addClass('active');
        if (tab === 'history') loadHistory();
    };

    // Manual entry
    window.iwAddObItem = function() {
        var opts = '<option value="">اختر الصنف</option>';
        products.forEach(function(p) { opts += '<option value="'+p.id+'" data-price="'+(p.price||0)+'">'+p.name+'</option>'; });
        var row = '<tr><td><select class="ob-product regular-text" onchange="var pr=$(this).find(\':selected\').data(\'price\')||0;$(this).closest(\'tr\').find(\'.ob-price\').val(pr);">'+opts+'</select></td>';
        row += '<td><input type="number" class="ob-qty" min="1" value="1"></td>';
        row += '<td><input type="number" class="ob-price" min="0" step="0.01" value="0"></td>';
        row += '<td><button type="button" class="button iw-btn-danger" onclick="$(this).closest(\'tr\').remove()">حذف</button></td></tr>';
        var $row = $(row);
        $('#ob-items-body').append($row);
        if (typeof iwInitSelect2 === 'function') iwInitSelect2($row);
    };

    function loadHistory() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_opening_balances', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) return;
            var h = '';
            r.data.forEach(function(b) {
                h += '<tr><td>'+(b.product_name||'-')+'</td><td>'+b.quantity+'</td><td>'+parseFloat(b.unit_price).toFixed(2)+'</td><td>'+b.balance_date+'</td><td>'+(b.notes||'-')+'</td>';
                h += '<td><button class="button iw-btn-danger" onclick="iwDeleteOb('+b.id+')">حذف</button></td></tr>';
            });
            $('#ob-history').html(h || '<tr><td colspan="6">لا توجد أرصدة افتتاحية</td></tr>');
        });
    }

    window.iwDeleteOb = function(id) {
        if (!confirm('هل أنت متأكد؟ سيتم حذف الرصيد وتعديل المخزون.')) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_delete_opening_balance', nonce: iwAdmin.nonce, balance_id: id}, function(r) {
            alert(r.data.message);
            if (r.success) loadHistory();
        });
    };

    window.iwResetAllOb = function() {
        if (!confirm('هل أنت متأكد من حذف جميع الأرصدة الافتتاحية؟ سيتم إعادة ضبط المخزون.')) return;
        if (!confirm('تأكيد نهائي: سيتم حذف كل الأرصدة الافتتاحية وتعديل المخزون!')) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_reset_opening_balance', nonce: iwAdmin.nonce}, function(r) {
            alert(r.data.message);
            if (r.success) loadHistory();
        });
    };

    $('#iw-ob-form').on('submit', function(e) {
        e.preventDefault();
        var items = [];
        $('#ob-items-body tr').each(function() {
            var pid = $(this).find('.ob-product').val();
            var qty = $(this).find('.ob-qty').val();
            var price = $(this).find('.ob-price').val();
            if (pid && qty > 0) items.push({product_id: pid, quantity: qty, unit_price: price});
        });
        if (!items.length) { alert('يجب إضافة أصناف'); return; }
        $.post(iwAdmin.ajaxurl, {
            action: 'iw_save_opening_balance', nonce: iwAdmin.nonce,
            items: JSON.stringify(items), balance_date: $('#ob_date').val(), notes: $('#ob_notes').val()
        }, function(r) {
            alert(r.data.message);
            if (r.success) { $('#iw-ob-form')[0].reset(); $('#ob-items-body').html(''); iwAddObItem(); }
        });
    });

    // Excel template download
    window.iwDownloadObTemplate = function() {
        var wb = XLSX.utils.book_new();
        var wsData = [
            ['اسم الصنف', 'الكمية', 'سعر الوحدة'],
            ['مثال: ورق A4', 100, 25.00],
            ['مثال: حبر طابعة', 50, 150.00],
            ['مثال: كرسي مكتب', 10, 500.00],
        ];
        var ws = XLSX.utils.aoa_to_sheet(wsData);
        ws['!cols'] = [{wch: 30}, {wch: 15}, {wch: 15}];
        XLSX.utils.book_append_sheet(wb, ws, 'الرصيد الافتتاحي');
        XLSX.writeFile(wb, 'نموذج_الرصيد_الافتتاحي.xlsx');
    };

    // Excel import
    window.iwImportObExcel = function() {
        var file = $('#ob_excel_file')[0].files[0];
        var balanceDate = $('#ob_excel_date').val();
        if (!file) { alert('اختر ملف'); return; }
        if (!balanceDate) { alert('يجب تحديد تاريخ الرصيد'); return; }

        var reader = new FileReader();
        reader.onload = function(e) {
            var data = new Uint8Array(e.target.result);
            var workbook = XLSX.read(data, {type: 'array'});
            var sheet = workbook.Sheets[workbook.SheetNames[0]];
            var json = XLSX.utils.sheet_to_json(sheet, {header: 1});

            // Remove header row
            if (json.length > 0 && isNaN(json[0][1])) json.shift();

            if (!json.length) { alert('الملف فارغ'); return; }

            // Match product names to IDs
            var matchedItems = [];
            var notFound = [];
            json.forEach(function(row) {
                var pname = (row[0] || '').toString().trim();
                var qty = parseInt(row[1]) || 0;
                var price = parseFloat(row[2]) || 0;
                if (!pname || qty <= 0) return;

                var found = products.find(function(p) {
                    return p.name.trim() === pname || (p.sku && p.sku.trim() === pname);
                });

                if (found) {
                    matchedItems.push({product_id: found.id, product_name: found.name, quantity: qty, unit_price: price});
                } else {
                    notFound.push(pname);
                }
            });

            // Show preview
            var preview = '<h3>معاينة البيانات</h3>';
            if (notFound.length) {
                preview += '<div class="notice notice-warning"><p><strong>أصناف غير موجودة في النظام ('+notFound.length+'):</strong> '+notFound.join('، ')+'</p><p>يجب إضافة هذه الأصناف أولاً من شاشة الأصناف أو استيراد Excel</p></div>';
            }
            if (matchedItems.length) {
                preview += '<p><strong>أصناف تم التعرف عليها: '+matchedItems.length+'</strong></p>';
                preview += '<table class="wp-list-table widefat fixed striped"><thead><tr><th>الصنف</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th></tr></thead><tbody>';
                var grandTotal = 0;
                matchedItems.forEach(function(item) {
                    var total = item.quantity * item.unit_price;
                    grandTotal += total;
                    preview += '<tr><td>'+item.product_name+'</td><td>'+item.quantity+'</td><td>'+item.unit_price.toFixed(2)+'</td><td>'+total.toFixed(2)+'</td></tr>';
                });
                preview += '<tr style="font-weight:bold;"><td colspan="3">الإجمالي</td><td>'+grandTotal.toFixed(2)+'</td></tr>';
                preview += '</tbody></table>';
                preview += '<br><button class="button button-primary button-large" id="confirm-ob-import">تأكيد حفظ الرصيد الافتتاحي</button>';
            } else {
                preview += '<div class="notice notice-error"><p>لم يتم التعرف على أي أصناف. تأكد من أن أسماء الأصناف في الملف مطابقة للأسماء في النظام.</p></div>';
            }
            $('#ob-import-preview').html(preview);

            // Confirm import
            $('#confirm-ob-import').on('click', function() {
                $(this).prop('disabled', true).text('جاري الحفظ...');
                var items = matchedItems.map(function(m) { return {product_id: m.product_id, quantity: m.quantity, unit_price: m.unit_price}; });
                $.post(iwAdmin.ajaxurl, {
                    action: 'iw_save_opening_balance', nonce: iwAdmin.nonce,
                    items: JSON.stringify(items), balance_date: balanceDate, notes: $('#ob_excel_notes').val() || 'استيراد من Excel'
                }, function(r) {
                    alert(r.data.message);
                    if (r.success) {
                        $('#ob-import-preview').html('<div class="notice notice-success"><p>'+r.data.message+'</p></div>');
                    }
                });
            });
        };
        reader.readAsArrayBuffer(file);
    };

    // First item row is added after products load (see products AJAX callback above)
});
</script>
