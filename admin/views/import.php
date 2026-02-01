<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>استيراد من Excel</h1>
    <p>قم برفع ملف Excel يحتوي على الأصناف. يجب أن يحتوي على الأعمدة التالية بالترتيب:</p>
    <ol>
        <li>اسم الصنف</li>
        <li>الكود (SKU)</li>
        <li>التصنيف</li>
        <li>وحدة القياس</li>
        <li>الحد الأدنى</li>
        <li>الحد الأقصى</li>
        <li>السعر</li>
    </ol>
    <input type="file" id="excel_file" accept=".xlsx,.xls,.csv">
    <button class="button button-primary" onclick="iwImportExcel()">استيراد</button>
    <div id="import-preview" style="margin-top:20px;"></div>
</div>
<script>
jQuery(document).ready(function($) {
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

            $.post(iwAdmin.ajaxurl, {action: 'iw_import_products', nonce: iwAdmin.nonce, data: JSON.stringify(json)}, function(r) {
                alert(r.data.message);
            });
        };
        reader.readAsArrayBuffer(file);
    };
});
</script>
