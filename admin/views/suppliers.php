<?php if (!defined('ABSPATH')) exit; wp_enqueue_media(); ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>الموردين <button class="button button-primary" onclick="$('#iw-supplier-modal').show();iwResetForm();">+ إضافة مورد</button> <button class="button" onclick="iwPrintSuppliers()">طباعة السجل</button></h1>

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
        <span class="iw-modal-close" onclick="$('#iw-supplier-modal').hide()">&times;</span>
        <h2 id="supplier-modal-title">إضافة مورد جديد</h2>
        <form id="iw-supplier-form">
            <input type="hidden" id="sup_id" value="0">
            <table class="form-table">
                <tr><th>رقم المورد</th><td><input type="text" id="sup_number" class="regular-text" readonly disabled placeholder="(يتم توليده تلقائياً)"></td></tr>
                <tr><th>اسم المورد *</th><td><input type="text" id="sup_name" class="regular-text" required></td></tr>
                <tr><th>العنوان</th><td><textarea id="sup_address" class="large-text" rows="2"></textarea></td></tr>
                <tr><th>تليفون أرضي</th><td><input type="text" id="sup_phone_landline" class="regular-text"></td></tr>
                <tr><th>تليفون محمول</th><td><input type="text" id="sup_phone_mobile" class="regular-text"></td></tr>
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
        <span class="iw-modal-close" onclick="$('#iw-view-supplier-modal').hide()">&times;</span>
        <div id="iw-view-supplier-body"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    function loadSuppliers() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_suppliers', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) return;
            var h = '';
            r.data.forEach(function(s) {
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
        $.post(iwAdmin.ajaxurl, {
            action: 'iw_save_supplier', nonce: iwAdmin.nonce,
            supplier_id: $('#sup_id').val(),
            name: $('#sup_name').val(),
            address: $('#sup_address').val(),
            phone_landline: $('#sup_phone_landline').val(),
            phone_mobile: $('#sup_phone_mobile').val(),
            email: $('#sup_email').val(),
            contact_person: $('#sup_contact_person').val(),
            tax_card_number: $('#sup_tax_card_number').val(),
            tax_card_file: $('#sup_tax_card_file').val(),
            commercial_reg_number: $('#sup_commercial_reg_number').val(),
            commercial_reg_file: $('#sup_commercial_reg_file').val(),
            specialty: $('#sup_specialty').val()
        }, function(r) {
            alert(r.data.message);
            if (r.success) { $('#iw-supplier-modal').hide(); loadSuppliers(); }
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
            html += '<tr><th>تليفون أرضي</th><td>'+(s.phone_landline||'-')+'</td></tr>';
            html += '<tr><th>تليفون محمول</th><td>'+(s.phone_mobile||s.phone||'-')+'</td></tr>';
            html += '<tr><th>البريد الإلكتروني</th><td>'+(s.email||'-')+'</td></tr>';
            html += '<tr><th>اسم المسؤول</th><td>'+(s.contact_person||'-')+'</td></tr>';
            html += '<tr><th>رقم البطاقة الضريبية</th><td>'+(s.tax_card_number||'-')+'</td></tr>';
            if (s.tax_card_file) html += '<tr><th>ملف البطاقة الضريبية</th><td><a href="'+s.tax_card_file+'" target="_blank">عرض الملف</a></td></tr>';
            html += '<tr><th>رقم السجل التجاري</th><td>'+(s.commercial_reg_number||'-')+'</td></tr>';
            if (s.commercial_reg_file) html += '<tr><th>ملف السجل التجاري</th><td><a href="'+s.commercial_reg_file+'" target="_blank">عرض الملف</a></td></tr>';
            html += '<tr><th>التخصص</th><td>'+(s.specialty||'-')+'</td></tr>';
            html += '</table>';
            $('#iw-view-supplier-body').html(html);
            $('#iw-view-supplier-modal').show();
        });
    };

    window.iwEditSupplier = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_supplier', nonce: iwAdmin.nonce, supplier_id: id}, function(r) {
            if (!r.success) return;
            var s = r.data;
            $('#sup_id').val(s.id);
            $('#sup_number').val(s.supplier_number||'');
            $('#sup_name').val(s.name);
            $('#sup_address').val(s.address||'');
            $('#sup_phone_landline').val(s.phone_landline||'');
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
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_suppliers', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) return;
            var header = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
            var content = header;
            content += '<h2 style="text-align:center;">سجل الموردين</h2>';
            content += '<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;font-size:11px;">';
            content += '<tr style="background:#f0f0f0;"><th>#</th><th>رقم المورد</th><th>اسم المورد</th><th>العنوان</th>';
            content += '<th>أرضي</th><th>محمول</th><th>إيميل</th><th>المسؤول</th>';
            content += '<th>الضريبية</th><th>التجاري</th><th>التخصص</th></tr>';

            r.data.forEach(function(s, idx) {
                content += '<tr>';
                content += '<td>'+(idx+1)+'</td>';
                content += '<td>'+(s.supplier_number||'-')+'</td>';
                content += '<td>'+s.name+'</td>';
                content += '<td>'+(s.address||'-')+'</td>';
                content += '<td>'+(s.phone_landline||'-')+'</td>';
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
        });
    };
});
</script>
