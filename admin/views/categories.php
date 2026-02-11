<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>تصنيفات الأصناف <button class="button button-primary" onclick="iwShowAddCategory()">+ إضافة تصنيف</button></h1>

    <p class="description">التصنيفات المضافة هنا ستظهر في القوائم المنسدلة في جميع الشاشات (الأصناف، طلبات الشراء، إلخ).</p>

    <table class="wp-list-table widefat fixed striped" style="max-width:800px;">
        <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th>اسم التصنيف</th>
                <th>الوصف</th>
                <th style="width:200px;">إجراءات</th>
            </tr>
        </thead>
        <tbody id="categories-list"></tbody>
    </table>
</div>

<!-- Category Modal -->
<div id="iw-category-modal" class="iw-modal" style="display:none;">
    <div class="iw-modal-content" style="max-width:500px;">
        <span class="iw-modal-close" onclick="iwHideCategoryModal()">&times;</span>
        <h2 id="category-modal-title">إضافة تصنيف جديد</h2>
        <form id="iw-category-form">
            <input type="hidden" id="cat_id" value="0">
            <table class="form-table">
                <tr>
                    <th>اسم التصنيف *</th>
                    <td><input type="text" id="cat_name" class="regular-text" required placeholder="مثال: منظفات، أدوات مكتبية..."></td>
                </tr>
                <tr>
                    <th>الوصف</th>
                    <td><textarea id="cat_description" class="large-text" rows="3" placeholder="وصف اختياري للتصنيف"></textarea></td>
                </tr>
            </table>
            <p><button type="submit" class="button button-primary button-large">حفظ</button></p>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    function loadCategories() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_categories', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) return;
            var h = '';
            r.data.forEach(function(c, idx) {
                h += '<tr>';
                h += '<td>'+(idx+1)+'</td>';
                h += '<td><strong>'+c.name+'</strong></td>';
                h += '<td>'+(c.description||'-')+'</td>';
                h += '<td>';
                h += '<button class="button" onclick="iwEditCategory('+c.id+')">تعديل</button> ';
                h += '<button class="button iw-btn-danger" onclick="iwDeleteCategory('+c.id+')">حذف</button>';
                h += '</td></tr>';
            });
            $('#categories-list').html(h || '<tr><td colspan="4">لا يوجد تصنيفات. قم بإضافة تصنيفات جديدة.</td></tr>');
        });
    }
    loadCategories();

    window.iwResetCatForm = function() {
        $('#iw-category-form')[0].reset();
        $('#cat_id').val(0);
        $('#category-modal-title').text('إضافة تصنيف جديد');
    };

    window.iwShowAddCategory = function() {
        iwResetCatForm();
        $('#iw-category-modal').show();
    };

    window.iwHideCategoryModal = function() {
        $('#iw-category-modal').hide();
    };

    $('#iw-category-form').on('submit', function(e) {
        e.preventDefault();
        $.post(iwAdmin.ajaxurl, {
            action: 'iw_save_category',
            nonce: iwAdmin.nonce,
            category_id: $('#cat_id').val(),
            name: $('#cat_name').val(),
            description: $('#cat_description').val()
        }, function(r) {
            alert(r.data.message);
            if (r.success) {
                $('#iw-category-modal').hide();
                loadCategories();
            }
        });
    });

    window.iwEditCategory = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_category', nonce: iwAdmin.nonce, category_id: id}, function(r) {
            if (!r.success) return;
            var c = r.data;
            $('#cat_id').val(c.id);
            $('#cat_name').val(c.name);
            $('#cat_description').val(c.description||'');
            $('#category-modal-title').text('تعديل التصنيف');
            $('#iw-category-modal').show();
        });
    };

    window.iwDeleteCategory = function(id) {
        if (!confirm('هل أنت متأكد من حذف هذا التصنيف؟')) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_delete_category', nonce: iwAdmin.nonce, category_id: id}, function(r) {
            alert(r.data.message);
            if (r.success) loadCategories();
        });
    };
});
</script>
