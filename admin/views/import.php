<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>استيراد من Excel</h1>

    <div style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:5px;margin-bottom:20px;">
        <h2>1. تحميل النموذج</h2>
        <p>قم بتحميل نموذج Excel الجاهز وقم بتعبئته بالبيانات ثم ارفعه:</p>
        <button class="button button-primary" onclick="iwDownloadTemplate()">تحميل نموذج Excel</button>
    </div>

    <div style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:5px;">
        <h2>2. رفع الملف</h2>
        <p>يجب أن يحتوي ملف Excel على الأعمدة التالية بالترتيب:</p>
        <table class="wp-list-table widefat fixed" style="max-width:600px;">
            <thead>
                <tr><th>العمود</th><th>الوصف</th><th>مطلوب</th></tr>
            </thead>
            <tbody>
                <tr><td>A</td><td>اسم الصنف</td><td><strong>نعم</strong></td></tr>
                <tr><td>B</td><td>الكود (SKU)</td><td>لا</td></tr>
                <tr><td>C</td><td>التصنيف</td><td>لا</td></tr>
                <tr><td>D</td><td>وحدة القياس</td><td>لا</td></tr>
                <tr><td>E</td><td>الحد الأدنى للمخزون</td><td>لا</td></tr>
                <tr><td>F</td><td>الحد الأقصى للمخزون</td><td>لا</td></tr>
                <tr><td>G</td><td>السعر</td><td>لا</td></tr>
            </tbody>
        </table>
        <br>
        <input type="file" id="excel_file" accept=".xlsx,.xls,.csv">
        <button class="button button-primary" onclick="iwImportExcel()">استيراد</button>
        <div id="import-preview" style="margin-top:20px;"></div>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    window.iwDownloadTemplate = function() {
        var wb = XLSX.utils.book_new();
        var wsData = [
            ['اسم الصنف', 'الكود (SKU)', 'التصنيف', 'وحدة القياس', 'الحد الأدنى', 'الحد الأقصى', 'السعر'],
            ['مثال: ورق A4', 'P001', 'أدوات مكتبية', 'رزمة', 10, 100, 25.00],
            ['مثال: حبر طابعة', 'P002', 'أدوات مكتبية', 'قطعة', 5, 50, 150.00],
            ['مثال: كرسي مكتب', 'P003', 'أثاث', 'قطعة', 2, 20, 500.00],
        ];
        var ws = XLSX.utils.aoa_to_sheet(wsData);

        // Set column widths
        ws['!cols'] = [
            {wch: 25}, {wch: 15}, {wch: 20}, {wch: 15}, {wch: 12}, {wch: 12}, {wch: 12}
        ];

        XLSX.utils.book_append_sheet(wb, ws, 'الأصناف');
        XLSX.writeFile(wb, 'نموذج_استيراد_الأصناف.xlsx');
    };

    window.iwImportExcel = function() {
        var file = $('#excel_file')[0].files[0];
        if (!file) { alert('اختر ملف'); return; }
        var reader = new FileReader();
        reader.onload = function(e) {
            var data = new Uint8Array(e.target.result);
            var workbook = XLSX.read(data, {type: 'array'});
            var sheet = workbook.Sheets[workbook.SheetNames[0]];
            var json = XLSX.utils.sheet_to_json(sheet, {header: 1});
            // Remove header row if exists
            if (json.length > 0 && isNaN(json[0][4])) json.shift();

            if (!json.length) { alert('الملف فارغ'); return; }

            // Show preview
            var preview = '<h3>معاينة البيانات ('+json.length+' صنف)</h3>';
            preview += '<table class="wp-list-table widefat fixed striped"><thead><tr><th>الاسم</th><th>الكود</th><th>التصنيف</th><th>الوحدة</th><th>الحد الأدنى</th><th>الحد الأقصى</th><th>السعر</th></tr></thead><tbody>';
            json.forEach(function(row) {
                preview += '<tr><td>'+(row[0]||'-')+'</td><td>'+(row[1]||'-')+'</td><td>'+(row[2]||'-')+'</td><td>'+(row[3]||'-')+'</td><td>'+(row[4]||0)+'</td><td>'+(row[5]||0)+'</td><td>'+(row[6]||0)+'</td></tr>';
            });
            preview += '</tbody></table>';
            preview += '<br><button class="button button-primary" id="confirm-import">تأكيد الاستيراد</button>';
            $('#import-preview').html(preview);

            $('#confirm-import').on('click', function() {
                $(this).prop('disabled', true).text('جاري الاستيراد...');
                $.post(iwAdmin.ajaxurl, {action: 'iw_import_products', nonce: iwAdmin.nonce, data: JSON.stringify(json)}, function(r) {
                    alert(r.data.message);
                    if (r.success) $('#import-preview').html('<div class="notice notice-success"><p>'+r.data.message+'</p></div>');
                });
            });
        };
        reader.readAsArrayBuffer(file);
    };
});
</script>
