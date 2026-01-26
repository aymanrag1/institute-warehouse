<?php
/**
 * Suppliers View
 * صفحة إدارة الموردين
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap iw-wrap" dir="rtl">
    <h1>
        <span class="dashicons dashicons-businessman"></span>
        إدارة الموردين
    </h1>

    <div class="iw-filters-bar">
        <input type="text" id="filter-search" placeholder="بحث بالاسم أو رقم الهاتف...">
        <button type="button" class="button" id="btn-filter">بحث</button>
        <button type="button" class="button button-primary" id="btn-add">
            <span class="dashicons dashicons-plus"></span> إضافة مورد
        </button>
    </div>

    <table class="wp-list-table widefat fixed striped" id="suppliers-table">
        <thead>
            <tr>
                <th>اسم المورد</th>
                <th>جهة الاتصال</th>
                <th>الهاتف</th>
                <th>البريد الإلكتروني</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody id="suppliers-tbody"></tbody>
    </table>
</div>

<!-- نموذج المورد -->
<div id="supplier-modal" class="iw-modal" style="display:none;">
    <div class="iw-modal-content">
        <div class="iw-modal-header">
            <h2 id="modal-title">إضافة مورد</h2>
            <button type="button" class="iw-modal-close">&times;</button>
        </div>
        <form id="supplier-form">
            <input type="hidden" name="id" id="supplier-id">
            <div class="iw-modal-body">
                <div class="iw-form-row">
                    <div class="iw-form-group">
                        <label>اسم المورد <span class="required">*</span></label>
                        <input type="text" name="name" id="supplier-name" required>
                    </div>
                    <div class="iw-form-group">
                        <label>جهة الاتصال</label>
                        <input type="text" name="contact_person" id="supplier-contact">
                    </div>
                </div>
                <div class="iw-form-row">
                    <div class="iw-form-group">
                        <label>الهاتف</label>
                        <input type="text" name="phone" id="supplier-phone">
                    </div>
                    <div class="iw-form-group">
                        <label>البريد الإلكتروني</label>
                        <input type="email" name="email" id="supplier-email">
                    </div>
                </div>
                <div class="iw-form-group">
                    <label>العنوان</label>
                    <textarea name="address" id="supplier-address" rows="2"></textarea>
                </div>
                <div class="iw-form-row">
                    <div class="iw-form-group">
                        <label>الرقم الضريبي</label>
                        <input type="text" name="tax_number" id="supplier-tax">
                    </div>
                    <div class="iw-form-group">
                        <label>الحالة</label>
                        <select name="status" id="supplier-status">
                            <option value="active">نشط</option>
                            <option value="inactive">غير نشط</option>
                        </select>
                    </div>
                </div>
                <div class="iw-form-group">
                    <label>ملاحظات</label>
                    <textarea name="notes" id="supplier-notes" rows="2"></textarea>
                </div>
            </div>
            <div class="iw-modal-footer">
                <button type="button" class="button" onclick="jQuery('#supplier-modal').hide()">إلغاء</button>
                <button type="submit" class="button button-primary">حفظ</button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    function loadSuppliers() {
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'iw_get_suppliers',
                nonce: iwAdmin.nonce,
                search: $('#filter-search').val()
            },
            success: function(response) {
                if (response.success) {
                    let html = '';
                    response.data.forEach(function(s) {
                        html += `<tr>
                            <td><strong>${s.name}</strong></td>
                            <td>${s.contact_person || '-'}</td>
                            <td>${s.phone || '-'}</td>
                            <td>${s.email || '-'}</td>
                            <td><span class="iw-badge ${s.status === 'active' ? 'success' : 'danger'}">${s.status === 'active' ? 'نشط' : 'غير نشط'}</span></td>
                            <td>
                                <button class="button button-small" onclick="editSupplier(${s.id})">تعديل</button>
                                <button class="button button-small" onclick="deleteSupplier(${s.id})">حذف</button>
                            </td>
                        </tr>`;
                    });
                    $('#suppliers-tbody').html(html || '<tr><td colspan="6" class="iw-empty">لا توجد موردين</td></tr>');
                }
            }
        });
    }

    $('.iw-modal-close').on('click', function() {
        $(this).closest('.iw-modal').hide();
    });

    $('#btn-filter').on('click', loadSuppliers);
    $('#filter-search').on('keypress', function(e) {
        if (e.which === 13) loadSuppliers();
    });

    $('#btn-add').on('click', function() {
        $('#supplier-form')[0].reset();
        $('#supplier-id').val('');
        $('#modal-title').text('إضافة مورد');
        $('#supplier-modal').show();
    });

    window.editSupplier = function(id) {
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: { action: 'iw_get_supplier', nonce: iwAdmin.nonce, id: id },
            success: function(response) {
                if (response.success) {
                    const s = response.data;
                    $('#supplier-id').val(s.id);
                    $('#supplier-name').val(s.name);
                    $('#supplier-contact').val(s.contact_person);
                    $('#supplier-phone').val(s.phone);
                    $('#supplier-email').val(s.email);
                    $('#supplier-address').val(s.address);
                    $('#supplier-tax').val(s.tax_number);
                    $('#supplier-status').val(s.status);
                    $('#supplier-notes').val(s.notes);
                    $('#modal-title').text('تعديل المورد');
                    $('#supplier-modal').show();
                }
            }
        });
    };

    window.deleteSupplier = function(id) {
        if (!confirm('هل تريد حذف هذا المورد؟')) return;
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: { action: 'iw_delete_supplier', nonce: iwAdmin.nonce, id: id },
            success: function(response) {
                alert(response.data.message);
                if (response.success) loadSuppliers();
            }
        });
    };

    $('#supplier-form').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'iw_save_supplier',
                nonce: iwAdmin.nonce,
                id: $('#supplier-id').val(),
                name: $('#supplier-name').val(),
                contact_person: $('#supplier-contact').val(),
                phone: $('#supplier-phone').val(),
                email: $('#supplier-email').val(),
                address: $('#supplier-address').val(),
                tax_number: $('#supplier-tax').val(),
                status: $('#supplier-status').val(),
                notes: $('#supplier-notes').val()
            },
            success: function(response) {
                alert(response.data.message);
                if (response.success) {
                    $('#supplier-modal').hide();
                    loadSuppliers();
                }
            }
        });
    });

    loadSuppliers();
});
</script>
