<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="<?php echo iw_dir(); ?>">
    <h1><?php echo iw_t('تصنيفات الأصناف', 'Product Categories'); ?> <button class="button button-primary" onclick="iwShowAddCategory()">+ <?php echo iw_t('إضافة تصنيف', 'Add Category'); ?></button></h1>

    <p class="description"><?php echo iw_t('التصنيفات المضافة هنا ستظهر في القوائم المنسدلة في جميع الشاشات (الأصناف، طلبات الشراء، إلخ).', 'Categories added here will appear in all dropdown menus across the system (Products, Purchase Requests, etc.).'); ?></p>

    <table class="wp-list-table widefat fixed striped" style="max-width:800px;">
        <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th><?php echo iw_t('اسم التصنيف', 'Category Name'); ?></th>
                <th><?php echo iw_t('الوصف', 'Description'); ?></th>
                <th style="width:200px;"><?php echo iw_t('إجراءات', 'Actions'); ?></th>
            </tr>
        </thead>
        <tbody id="categories-list"></tbody>
    </table>
</div>

<!-- Category Modal -->
<div id="iw-category-modal" class="iw-modal" style="display:none;">
    <div class="iw-modal-content" style="max-width:500px;">
        <span class="iw-modal-close" onclick="iwHideCategoryModal()">&times;</span>
        <h2 id="category-modal-title"><?php echo iw_t('إضافة تصنيف جديد', 'Add New Category'); ?></h2>
        <form id="iw-category-form">
            <input type="hidden" id="cat_id" value="0">
            <table class="form-table">
                <tr>
                    <th><?php echo iw_t('اسم التصنيف *', 'Category Name *'); ?></th>
                    <td><input type="text" id="cat_name" class="regular-text" required placeholder="<?php echo iw_t('مثال: منظفات، أدوات مكتبية...', 'e.g. Cleaning Supplies, Office Items...'); ?>"></td>
                </tr>
                <tr>
                    <th><?php echo iw_t('الوصف', 'Description'); ?></th>
                    <td><textarea id="cat_description" class="large-text" rows="3" placeholder="<?php echo iw_t('وصف اختياري للتصنيف', 'Optional description'); ?>"></textarea></td>
                </tr>
            </table>
            <p><button type="submit" class="button button-primary button-large"><?php echo iw_t('حفظ', 'Save'); ?></button></p>
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
                h += '<button class="button" onclick="iwEditCategory('+c.id+')">' + iwT('تعديل', 'Edit') + '</button> ';
                h += '<button class="button iw-btn-danger" onclick="iwDeleteCategory('+c.id+')">' + iwT('حذف', 'Delete') + '</button>';
                h += '</td></tr>';
            });
            $('#categories-list').html(h || '<tr><td colspan="4">' + iwT('لا يوجد تصنيفات. قم بإضافة تصنيفات جديدة.', 'No categories yet. Add a new category.') + '</td></tr>');
        });
    }
    loadCategories();

    window.iwResetCatForm = function() {
        $('#iw-category-form')[0].reset();
        $('#cat_id').val(0);
        $('#category-modal-title').text(iwT('إضافة تصنيف جديد', 'Add New Category'));
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
            $('#category-modal-title').text(iwT('تعديل التصنيف', 'Edit Category'));
            $('#iw-category-modal').show();
        });
    };

    window.iwDeleteCategory = function(id) {
        if (!confirm(iwT('هل أنت متأكد من حذف هذا التصنيف؟', 'Delete this category?'))) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_delete_category', nonce: iwAdmin.nonce, category_id: id}, function(r) {
            alert(r.data.message);
            if (r.success) loadCategories();
        });
    };
});
</script>
