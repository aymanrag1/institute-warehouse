<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>إدارة الموردين <button class="button button-primary" onclick="iwShowSupForm()">إضافة مورد</button></h1>
    <div id="iw-sup-form-wrap" style="display:none;background:#f9f9f9;padding:15px;margin-bottom:15px;border:1px solid #ddd;">
        <form id="iw-sup-form">
            <input type="hidden" id="sup_id" value="0">
            <table class="form-table">
                <tr><th>اسم المورد *</th><td><input type="text" id="sup_name" class="regular-text" required></td></tr>
                <tr><th>الهاتف</th><td><input type="text" id="sup_phone" class="regular-text"></td></tr>
                <tr><th>البريد الإلكتروني</th><td><input type="email" id="sup_email" class="regular-text"></td></tr>
                <tr><th>العنوان</th><td><textarea id="sup_address" class="large-text" rows="2"></textarea></td></tr>
            </table>
            <button type="submit" class="button button-primary">حفظ</button>
            <button type="button" class="button" onclick="$('#iw-sup-form-wrap').hide()">إلغاء</button>
        </form>
    </div>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr><th>#</th><th>الاسم</th><th>الهاتف</th><th>البريد</th><th>العنوان</th><th>إجراءات</th></tr></thead>
        <tbody id="iw-sups-table"></tbody>
    </table>
</div>
<script>
jQuery(document).ready(function($) {
    function loadSups() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_suppliers', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) return;
            var h = '';
            r.data.forEach(function(s, i) {
                h += '<tr><td>'+(i+1)+'</td><td>'+s.name+'</td><td>'+(s.phone||'-')+'</td><td>'+(s.email||'-')+'</td><td>'+(s.address||'-')+'</td>';
                h += '<td><button class="button" onclick="iwEditSup('+s.id+')">تعديل</button> ';
                h += '<button class="button iw-btn-danger" onclick="iwDeleteSup('+s.id+')">حذف</button></td></tr>';
            });
            $('#iw-sups-table').html(h || '<tr><td colspan="6">لا يوجد موردين</td></tr>');
        });
    }
    loadSups();
    window.iwShowSupForm = function() { $('#sup_id').val(0); $('#iw-sup-form')[0].reset(); $('#iw-sup-form-wrap').show(); };
    window.iwEditSup = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_suppliers', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) return;
            var s = r.data.find(function(x){return x.id==id;});
            if (!s) return;
            $('#sup_id').val(s.id); $('#sup_name').val(s.name); $('#sup_phone').val(s.phone); $('#sup_email').val(s.email); $('#sup_address').val(s.address);
            $('#iw-sup-form-wrap').show();
        });
    };
    window.iwDeleteSup = function(id) {
        if (!confirm(iwAdmin.strings.confirm_delete)) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_delete_supplier', nonce: iwAdmin.nonce, supplier_id: id}, function(r) { alert(r.data.message); loadSups(); });
    };
    $('#iw-sup-form').on('submit', function(e) {
        e.preventDefault();
        $.post(iwAdmin.ajaxurl, {action: 'iw_save_supplier', nonce: iwAdmin.nonce, supplier_id: $('#sup_id').val(), name: $('#sup_name').val(), phone: $('#sup_phone').val(), email: $('#sup_email').val(), address: $('#sup_address').val()}, function(r) {
            alert(r.data.message); if (r.success) { $('#iw-sup-form-wrap').hide(); loadSups(); }
        });
    });
});
</script>
