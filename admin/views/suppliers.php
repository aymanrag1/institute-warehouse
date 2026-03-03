<?php if (!defined('ABSPATH')) exit; wp_enqueue_media(); ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>الموردين
        <button class="button button-primary" onclick="iwShowSupplierModal()">+ إضافة مورد</button>
        <button class="button" onclick="iwPrintSuppliers()">طباعة السجل</button>
        <button class="button" onclick="iwExportToExcel()">تصدير Excel</button>
    </h1>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>رقم المورد</th>
                <th>اسم المورد</th>
                <th>المسؤول</th>
                <th>تليفون محمول</th>
                <th>التخصص</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody id="suppliers-list"></tbody>
    </table>
</div>

<!-- Supplier Modal -->
<div id="iw-supplier-modal" class="iw-modal" style="display:none;">
    <div class="iw-modal-content" style="max-width:700px;">
        <span class="iw-modal-close" onclick="iwHideSupplierModal()">&times;</span>
        <h2 id="supplier-modal-title">إضافة مورد جديد</h2>
        <form id="iw-supplier-form">
            <input type="hidden" id="sup_id" value="0">
            <table class="form-table">
                <tr><th>رقم المورد</th><td><input type="text" id="sup_number" class="regular-text" readonly disabled placeholder="(يتم توليده تلقائياً)"></td></tr>
                <tr><th>اسم المورد *</th><td><input type="text" id="sup_name" class="regular-text" required></td></tr>
                <tr><th>العنوان</th><td><textarea id="sup_address" class="large-text" rows="2"></textarea></td></tr>
                <tr><th>رقم التليفون</th><td><input type="text" id="sup_phone_mobile" class="regular-text"></td></tr>
                <tr><th>البريد الإلكتروني</th><td><input type="email" id="sup_email" class="regular-text"></td></tr>
                <tr><th>اسم المسؤول</th><td><input type="text" id="sup_contact_person" class="regular-text"></td></tr>
                <tr><th>رقم البطاقة الضريبية</th><td><input type="text" id="sup_tax_card_number" class="regular-text"></td></tr>
                <tr>
                    <th>ملف البطاقة الضريبية</th>
                    <td>
                        <input type="hidden" id="sup_tax_card_file">
                        <button type="button" class="button" onclick="iwUploadFile('sup_tax_card_file')">رفع ملف</button>
                        <span id="sup_tax_card_file_name" style="margin-right:10px;"></span>
                    </td>
                </tr>
                <tr><th>رقم السجل التجاري</th><td><input type="text" id="sup_commercial_reg_number" class="regular-text"></td></tr>
                <tr>
                    <th>ملف السجل التجاري</th>
                    <td>
                        <input type="hidden" id="sup_commercial_reg_file">
                        <button type="button" class="button" onclick="iwUploadFile('sup_commercial_reg_file')">رفع ملف</button>
                        <span id="sup_commercial_reg_file_name" style="margin-right:10px;"></span>
                    </td>
                </tr>
                <tr><th>تخصص المورد</th><td><input type="text" id="sup_specialty" class="regular-text" placeholder="مثال: أدوات مكتبية، أجهزة كمبيوتر..."></td></tr>
            </table>
            <p><button type="submit" class="button button-primary button-large">حفظ</button></p>
        </form>
    </div>
</div>

<!-- View Supplier Modal -->
<div id="iw-view-supplier-modal" class="iw-modal" style="display:none;">
    <div class="iw-modal-content" style="max-width:600px;">
        <span class="iw-modal-close" onclick="iwHideViewSupplierModal()">&times;</span>
        <div id="iw-view-supplier-body"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var suppliersData = [];

    function loadSuppliers() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_suppliers', nonce: iwAdmin.nonce}, function(r) {
            if (!r || !r.success) {
                console.error('Failed to load suppliers:', r);
                return;
            }
            suppliersData = r.data || [];
            var h = '';
            suppliersData.forEach(function(s) {
                h += '<tr>';
                h += '<td>'+(s.supplier_number||'-')+'</td>';
                h += '<td>'+s.name+'</td>';
                h += '<td>'+(s.contact_person||'-')+'</td>';
                h += '<td>'+(s.phone_mobile||s.phone||'-')+'</td>';
                h += '<td>'+(s.specialty||'-')+'</td>';
                h += '<td>';
                h += '<button class="button" onclick="iwViewSupplier('+s.id+')">عرض</button> ';
                h += '<button class="button" onclick="iwEditSupplier('+s.id+')">تعديل</button> ';
                h += '<button class="button iw-btn-danger" onclick="iwDeleteSupplier('+s.id+')">حذف</button>';
                h += '</td></tr>';
            });
            $('#suppliers-list').html(h || '<tr><td colspan="6">لا يوجد موردين</td></tr>');
        }).fail(function(xhr, status, error) {
            console.error('AJAX Error loading suppliers:', error);
        });
    }
    loadSuppliers();

    window.iwResetForm = function() {
        $('#iw-supplier-form')[0].reset();
        $('#sup_id').val(0);
        $('#sup_number').val('');
        $('#supplier-modal-title').text('إضافة مورد جديد');
        $('#sup_tax_card_file, #sup_commercial_reg_file').val('');
        $('#sup_tax_card_file_name, #sup_commercial_reg_file_name').text('');
    };

    window.iwShowSupplierModal = function() {
        iwResetForm();
        $('#iw-supplier-modal').show();
    };

    window.iwHideSupplierModal = function() {
        $('#iw-supplier-modal').hide();
    };

    window.iwHideViewSupplierModal = function() {
        $('#iw-view-supplier-modal').hide();
    };

    // Upload file using WordPress media uploader
    window.iwUploadFile = function(fieldId) {
        var frame = wp.media({
            title: 'اختر ملف',
            button: { text: 'اختيار' },
            multiple: false
        });
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#'+fieldId).val(attachment.url);
            $('#'+fieldId+'_name').text(attachment.filename);
        });
        frame.open();
    };

    $('#iw-supplier-form').on('submit', function(e) {
        e.preventDefault();
        var formData = {
            action: 'iw_save_supplier', nonce: iwAdmin.nonce,
            supplier_id: $('#sup_id').val(),
            name: $('#sup_name').val(),
            address: $('#sup_address').val(),
            phone_mobile: $('#sup_phone_mobile').val(),
            email: $('#sup_email').val(),
            contact_person: $('#sup_contact_person').val(),
            tax_card_number: $('#sup_tax_card_number').val(),
            tax_card_file: $('#sup_tax_card_file').val(),
            commercial_reg_number: $('#sup_commercial_reg_number').val(),
            commercial_reg_file: $('#sup_commercial_reg_file').val(),
            specialty: $('#sup_specialty').val()
        };

        $.post(iwAdmin.ajaxurl, formData, function(r) {
            if (r && r.data && r.data.message) {
                alert(r.data.message);
            }
            if (r && r.success) {
                // Reload page to ensure data is refreshed
                window.location.reload();
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('حدث خطأ في الاتصال بالخادم');
        });
    });

    window.iwViewSupplier = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_supplier', nonce: iwAdmin.nonce, supplier_id: id}, function(r) {
            if (!r.success) return;
            var s = r.data;
            var html = '<h2>بيانات المورد: '+s.name+'</h2>';
            html += '<table class="form-table">';
            html += '<tr><th>رقم المورد</th><td>'+(s.supplier_number||'-')+'</td></tr>';
            html += '<tr><th>اسم المورد</th><td>'+s.name+'</td></tr>';
            html += '<tr><th>العنوان</th><td>'+(s.address||'-')+'</td></tr>';
            html += '<tr><th>رقم التليفون</th><td>'+(s.phone_mobile||s.phone||'-')+'</td></tr>';
            html += '<tr><th>البريد الإلكتروني</th><td>'+(s.email||'-')+'</td></tr>';
            html += '<tr><th>اسم المسؤول</th><td>'+(s.contact_person||'-')+'</td></tr>';
            html += '<tr><th>رقم البطاقة الضريبية</th><td>'+(s.tax_card_number||'-')+'</td></tr>';
            if (s.tax_card_file) {
                html += '<tr><th>ملف البطاقة الضريبية</th><td>';
                html += '<a href="'+s.tax_card_file+'" target="_blank" class="button">عرض الملف</a> ';
                html += '<button class="button button-primary" onclick="iwPrintFile(\''+s.tax_card_file+'\',\'البطاقة الضريبية - '+s.name+'\')">طباعة</button>';
                html += '</td></tr>';
            }
            html += '<tr><th>رقم السجل التجاري</th><td>'+(s.commercial_reg_number||'-')+'</td></tr>';
            if (s.commercial_reg_file) {
                html += '<tr><th>ملف السجل التجاري</th><td>';
                html += '<a href="'+s.commercial_reg_file+'" target="_blank" class="button">عرض الملف</a> ';
                html += '<button class="button button-primary" onclick="iwPrintFile(\''+s.commercial_reg_file+'\',\'السجل التجاري - '+s.name+'\')">طباعة</button>';
                html += '</td></tr>';
            }
            html += '<tr><th>التخصص</th><td>'+(s.specialty||'-')+'</td></tr>';
            html += '</table>';

            // Print all files button
            if (s.tax_card_file || s.commercial_reg_file) {
                html += '<div style="margin-top:15px;">';
                html += '<button class="button button-primary button-large" onclick="iwPrintAllSupplierFiles('+s.id+')">طباعة جميع الملفات</button>';
                html += '</div>';
            }

            $('#iw-view-supplier-body').html(html);
            $('#iw-view-supplier-modal').show();
        });
    };

    // Print a single file
    window.iwPrintFile = function(fileUrl, title) {
        var w = window.open('','','width=800,height=600');
        var isImage = /\.(jpg|jpeg|png|gif|bmp|webp)$/i.test(fileUrl);
        var isPdf = /\.pdf$/i.test(fileUrl);

        if (isImage) {
            w.document.write('<html dir="rtl"><head><title>'+title+'</title></head><body style="text-align:center;padding:20px;">');
            w.document.write('<h2>'+title+'</h2>');
            w.document.write('<img src="'+fileUrl+'" style="max-width:100%;max-height:90vh;" onload="window.print();" />');
            w.document.write('</body></html>');
        } else if (isPdf) {
            w.document.write('<html dir="rtl"><head><title>'+title+'</title></head><body style="margin:0;padding:0;">');
            w.document.write('<iframe src="'+fileUrl+'" style="width:100%;height:100vh;border:none;" onload="setTimeout(function(){window.print();},500);"></iframe>');
            w.document.write('</body></html>');
        } else {
            w.location.href = fileUrl;
        }
        w.document.close();
    };

    // Print all supplier files
    window.iwPrintAllSupplierFiles = function(id) {
        var supplier = suppliersData.find(function(s) { return s.id == id; });
        if (!supplier) return;

        var w = window.open('','','width=800,height=600');
        var content = '<html dir="rtl"><head><title>ملفات المورد - '+supplier.name+'</title>';
        content += '<style>body{font-family:Arial,sans-serif;padding:20px;} .file-section{margin-bottom:30px;page-break-inside:avoid;} img{max-width:100%;}</style>';
        content += '</head><body>';
        content += '<h1 style="text-align:center;">ملفات المورد: '+supplier.name+'</h1>';

        if (supplier.tax_card_file) {
            content += '<div class="file-section">';
            content += '<h2>البطاقة الضريبية - رقم: '+(supplier.tax_card_number||'-')+'</h2>';
            if (/\.(jpg|jpeg|png|gif|bmp|webp)$/i.test(supplier.tax_card_file)) {
                content += '<img src="'+supplier.tax_card_file+'" />';
            } else {
                content += '<p><a href="'+supplier.tax_card_file+'" target="_blank">فتح الملف</a></p>';
            }
            content += '</div>';
        }

        if (supplier.commercial_reg_file) {
            content += '<div class="file-section">';
            content += '<h2>السجل التجاري - رقم: '+(supplier.commercial_reg_number||'-')+'</h2>';
            if (/\.(jpg|jpeg|png|gif|bmp|webp)$/i.test(supplier.commercial_reg_file)) {
                content += '<img src="'+supplier.commercial_reg_file+'" />';
            } else {
                content += '<p><a href="'+supplier.commercial_reg_file+'" target="_blank">فتح الملف</a></p>';
            }
            content += '</div>';
        }

        content += '</body></html>';
        w.document.write(content);
        w.document.close();
        setTimeout(function() { w.print(); }, 500);
    };

    window.iwEditSupplier = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_supplier', nonce: iwAdmin.nonce, supplier_id: id}, function(r) {
            if (!r.success) return;
            var s = r.data;
            $('#sup_id').val(s.id);
            $('#sup_number').val(s.supplier_number||'');
            $('#sup_name').val(s.name);
            $('#sup_address').val(s.address||'');
            $('#sup_phone_mobile').val(s.phone_mobile||s.phone||'');
            $('#sup_email').val(s.email||'');
            $('#sup_contact_person').val(s.contact_person||'');
            $('#sup_tax_card_number').val(s.tax_card_number||'');
            $('#sup_tax_card_file').val(s.tax_card_file||'');
            $('#sup_tax_card_file_name').text(s.tax_card_file ? 'ملف مرفق' : '');
            $('#sup_commercial_reg_number').val(s.commercial_reg_number||'');
            $('#sup_commercial_reg_file').val(s.commercial_reg_file||'');
            $('#sup_commercial_reg_file_name').text(s.commercial_reg_file ? 'ملف مرفق' : '');
            $('#sup_specialty').val(s.specialty||'');
            $('#supplier-modal-title').text('تعديل بيانات المورد');
            $('#iw-supplier-modal').show();
        });
    };

    window.iwDeleteSupplier = function(id) {
        if (!confirm('هل أنت متأكد من حذف هذا المورد؟')) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_delete_supplier', nonce: iwAdmin.nonce, supplier_id: id}, function(r) {
            alert(r.data.message);
            if (r.success) loadSuppliers();
        });
    };

    // Print suppliers registry
    window.iwPrintSuppliers = function() {
        var header = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
        var content = header;
        content += '<h2 style="text-align:center;">سجل الموردين</h2>';
        content += '<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;font-size:11px;">';
        content += '<tr style="background:#f0f0f0;"><th>#</th><th>رقم المورد</th><th>اسم المورد</th><th>العنوان</th>';
        content += '<th>التليفون</th><th>إيميل</th><th>المسؤول</th>';
        content += '<th>الضريبية</th><th>التجاري</th><th>التخصص</th></tr>';

        suppliersData.forEach(function(s, idx) {
            content += '<tr>';
            content += '<td>'+(idx+1)+'</td>';
            content += '<td>'+(s.supplier_number||'-')+'</td>';
            content += '<td>'+s.name+'</td>';
            content += '<td>'+(s.address||'-')+'</td>';
            content += '<td>'+(s.phone_mobile||s.phone||'-')+'</td>';
            content += '<td>'+(s.email||'-')+'</td>';
            content += '<td>'+(s.contact_person||'-')+'</td>';
            content += '<td>'+(s.tax_card_number||'-')+'</td>';
            content += '<td>'+(s.commercial_reg_number||'-')+'</td>';
            content += '<td>'+(s.specialty||'-')+'</td>';
            content += '</tr>';
        });
        content += '</table>';
        content += '<p style="margin-top:20px;text-align:center;font-size:11px;">تاريخ الطباعة: '+new Date().toLocaleDateString('ar-EG')+'</p>';

        var w = window.open('','','width=1000,height=700');
        w.document.write('<html dir="rtl"><head><title>سجل الموردين</title><style>body{font-family:Arial,sans-serif;padding:15px;}th{background:#f0f0f0;}</style></head><body>'+content+'</body></html>');
        w.document.close(); w.print();
    };

    // Export to Excel
    window.iwExportToExcel = function() {
        if (!suppliersData.length) {
            alert('لا يوجد موردين للتصدير');
            return;
        }

        // Build CSV content
        var headers = ['رقم المورد', 'اسم المورد', 'العنوان', 'رقم التليفون', 'البريد الإلكتروني', 'المسؤول', 'رقم البطاقة الضريبية', 'رقم السجل التجاري', 'التخصص'];
        var csvContent = '\uFEFF'; // BOM for UTF-8
        csvContent += headers.join(',') + '\n';

        suppliersData.forEach(function(s) {
            var row = [
                s.supplier_number || '',
                '"' + (s.name || '').replace(/"/g, '""') + '"',
                '"' + (s.address || '').replace(/"/g, '""').replace(/\n/g, ' ') + '"',
                s.phone_mobile || s.phone || '',
                s.email || '',
                '"' + (s.contact_person || '').replace(/"/g, '""') + '"',
                s.tax_card_number || '',
                s.commercial_reg_number || '',
                '"' + (s.specialty || '').replace(/"/g, '""') + '"'
            ];
            csvContent += row.join(',') + '\n';
        });

        // Create download link
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'suppliers_' + new Date().toISOString().slice(0,10) + '.csv';
        link.click();
    };
});
</script>
