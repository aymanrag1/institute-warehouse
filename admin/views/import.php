<?php
/**
 * Import View
 * صفحة استيراد البيانات من Excel
 */

if (!defined('ABSPATH')) {
    exit;
}

$warehouses = IW_Admin::get_active_warehouses();
?>

<div class="wrap iw-wrap" dir="rtl">
    <h1>
        <span class="dashicons dashicons-upload"></span>
        استيراد البيانات من Excel
    </h1>

    <div class="iw-import-container">
        <div class="iw-card">
            <div class="iw-card-header">
                <h3>اختر نوع البيانات للاستيراد</h3>
            </div>
            <div class="iw-card-body">
                <div class="iw-import-types">
                    <label class="iw-import-type">
                        <input type="radio" name="import_type" value="products" checked>
                        <span class="iw-import-type-content">
                            <span class="dashicons dashicons-archive"></span>
                            <strong>الأصناف</strong>
                            <small>استيراد المنتجات والأصناف</small>
                        </span>
                    </label>
                    <label class="iw-import-type">
                        <input type="radio" name="import_type" value="employees">
                        <span class="iw-import-type-content">
                            <span class="dashicons dashicons-groups"></span>
                            <strong>الموظفين</strong>
                            <small>استيراد بيانات الموظفين</small>
                        </span>
                    </label>
                    <label class="iw-import-type">
                        <input type="radio" name="import_type" value="suppliers">
                        <span class="iw-import-type-content">
                            <span class="dashicons dashicons-businessman"></span>
                            <strong>الموردين</strong>
                            <small>استيراد بيانات الموردين</small>
                        </span>
                    </label>
                    <label class="iw-import-type">
                        <input type="radio" name="import_type" value="stock">
                        <span class="iw-import-type-content">
                            <span class="dashicons dashicons-database"></span>
                            <strong>المخزون الابتدائي</strong>
                            <small>استيراد أرصدة المخزون</small>
                        </span>
                    </label>
                </div>

                <div id="stock-warehouse-select" style="display:none; margin-top: 15px;">
                    <label><strong>اختر المخزن:</strong></label>
                    <select id="import-warehouse">
                        <?php foreach ($warehouses as $wh): ?>
                            <option value="<?php echo esc_attr($wh->id); ?>"><?php echo esc_html($wh->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="iw-import-file">
                    <h4>رفع ملف Excel</h4>
                    <p class="description">يجب أن يكون الملف بصيغة .xlsx أو .xls</p>
                    <input type="file" id="excel-file" accept=".xlsx,.xls">
                    <button type="button" class="button" id="btn-download-template">
                        <span class="dashicons dashicons-download"></span> تحميل قالب فارغ
                    </button>
                </div>
            </div>
        </div>

        <div class="iw-card" id="preview-card" style="display:none;">
            <div class="iw-card-header">
                <h3>معاينة البيانات</h3>
            </div>
            <div class="iw-card-body">
                <div id="preview-table"></div>
                <div class="iw-import-actions">
                    <button type="button" class="button button-primary" id="btn-import">
                        <span class="dashicons dashicons-upload"></span> استيراد البيانات
                    </button>
                    <button type="button" class="button" id="btn-cancel">إلغاء</button>
                </div>
            </div>
        </div>

        <div class="iw-card" id="result-card" style="display:none;">
            <div class="iw-card-header">
                <h3>نتيجة الاستيراد</h3>
            </div>
            <div class="iw-card-body">
                <div id="import-result"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
jQuery(document).ready(function($) {
    let parsedData = [];
    let currentType = 'products';

    $('input[name="import_type"]').on('change', function() {
        currentType = $(this).val();
        $('#stock-warehouse-select').toggle(currentType === 'stock');
        $('#preview-card, #result-card').hide();
        $('#excel-file').val('');
        parsedData = [];
    });

    // تحميل القالب
    $('#btn-download-template').on('click', function() {
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'iw_get_import_template',
                nonce: iwAdmin.nonce,
                type: currentType
            },
            success: function(response) {
                if (response.success) {
                    const template = response.data;
                    const ws_data = [Object.values(template.headers)];
                    template.sample.forEach(row => ws_data.push(row));

                    const wb = XLSX.utils.book_new();
                    const ws = XLSX.utils.aoa_to_sheet(ws_data);
                    XLSX.utils.book_append_sheet(wb, ws, 'Template');
                    XLSX.writeFile(wb, 'template_' + currentType + '.xlsx');
                }
            }
        });
    });

    // قراءة ملف Excel
    $('#excel-file').on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            const jsonData = XLSX.utils.sheet_to_json(firstSheet, { defval: '' });

            if (jsonData.length === 0) {
                alert('الملف فارغ أو لا يحتوي على بيانات صحيحة');
                return;
            }

            // تحويل أسماء الأعمدة العربية إلى إنجليزية
            parsedData = jsonData.map(row => {
                const newRow = {};
                Object.keys(row).forEach(key => {
                    const mappedKey = mapColumnName(key, currentType);
                    newRow[mappedKey] = row[key];
                });
                return newRow;
            });

            renderPreview();
        };
        reader.readAsArrayBuffer(file);
    });

    function mapColumnName(arabicName, type) {
        const maps = {
            products: {
                'كود المنتج *': 'sku', 'كود المنتج': 'sku',
                'اسم المنتج *': 'name', 'اسم المنتج': 'name',
                'الفئة': 'category', 'الوحدة': 'unit',
                'الوصف': 'description', 'الحد الأدنى': 'min_quantity',
                'حد إعادة الطلب': 'reorder_level', 'مكان التخزين': 'storage_location',
                'له صلاحية (نعم/لا)': 'has_expiry', 'له صلاحية': 'has_expiry'
            },
            employees: {
                'الرقم الوظيفي': 'employee_number', 'اسم الموظف *': 'name', 'اسم الموظف': 'name',
                'القسم': 'department', 'المسمى الوظيفي': 'position',
                'رقم الهاتف': 'phone', 'البريد الإلكتروني': 'email'
            },
            suppliers: {
                'اسم المورد *': 'name', 'اسم المورد': 'name',
                'جهة الاتصال': 'contact_person', 'رقم الهاتف': 'phone',
                'البريد الإلكتروني': 'email', 'العنوان': 'address', 'الرقم الضريبي': 'tax_number'
            },
            stock: {
                'كود المنتج *': 'sku', 'كود المنتج': 'sku',
                'الكمية *': 'quantity', 'الكمية': 'quantity',
                'رقم الدفعة': 'batch_number', 'سعر الشراء': 'purchase_price',
                'تاريخ الصلاحية (YYYY-MM-DD)': 'expiry_date', 'تاريخ الصلاحية': 'expiry_date',
                'مكان التخزين': 'storage_location'
            }
        };

        return maps[type][arabicName] || arabicName.toLowerCase().replace(/\s+/g, '_').replace(/[*]/g, '');
    }

    function renderPreview() {
        if (parsedData.length === 0) return;

        const headers = Object.keys(parsedData[0]);
        let html = '<table class="widefat striped"><thead><tr>';
        headers.forEach(h => html += `<th>${h}</th>`);
        html += '</tr></thead><tbody>';

        const previewRows = parsedData.slice(0, 10);
        previewRows.forEach(row => {
            html += '<tr>';
            headers.forEach(h => html += `<td>${row[h] || ''}</td>`);
            html += '</tr>';
        });

        if (parsedData.length > 10) {
            html += `<tr><td colspan="${headers.length}" style="text-align:center;">... و ${parsedData.length - 10} صف آخر</td></tr>`;
        }

        html += '</tbody></table>';
        html += `<p><strong>إجمالي الصفوف: ${parsedData.length}</strong></p>`;

        $('#preview-table').html(html);
        $('#preview-card').show();
        $('#result-card').hide();
    }

    $('#btn-cancel').on('click', function() {
        $('#preview-card').hide();
        $('#excel-file').val('');
        parsedData = [];
    });

    $('#btn-import').on('click', function() {
        if (parsedData.length === 0) {
            alert('لا توجد بيانات للاستيراد');
            return;
        }

        const data = {
            action: 'iw_import_' + currentType,
            nonce: iwAdmin.nonce,
            data: parsedData
        };

        if (currentType === 'stock') {
            data.warehouse_id = $('#import-warehouse').val();
        }

        $(this).prop('disabled', true).text('جاري الاستيراد...');

        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                let html = '';
                if (response.success) {
                    html = `<div class="notice notice-success"><p>${response.data.message}</p></div>`;

                    if (response.data.result.errors && response.data.result.errors.length > 0) {
                        html += '<div class="notice notice-warning"><p><strong>أخطاء:</strong></p><ul>';
                        response.data.result.errors.forEach(err => html += `<li>${err}</li>`);
                        html += '</ul></div>';
                    }
                } else {
                    html = `<div class="notice notice-error"><p>${response.data.message}</p></div>`;
                }

                $('#import-result').html(html);
                $('#result-card').show();
                $('#preview-card').hide();
            },
            complete: function() {
                $('#btn-import').prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> استيراد البيانات');
            }
        });
    });
});
</script>

<style>
.iw-import-types {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.iw-import-type {
    cursor: pointer;
}
.iw-import-type input {
    display: none;
}
.iw-import-type-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    border: 2px solid #ddd;
    border-radius: 8px;
    text-align: center;
    transition: all 0.3s;
}
.iw-import-type input:checked + .iw-import-type-content {
    border-color: #0073aa;
    background: #f0f6fc;
}
.iw-import-type-content .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    margin-bottom: 10px;
    color: #0073aa;
}
.iw-import-file {
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}
.iw-import-file input[type="file"] {
    margin: 10px 0;
}
.iw-import-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}
</style>
