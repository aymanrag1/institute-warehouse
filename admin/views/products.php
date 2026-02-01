<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>إدارة الأصناف <button class="button button-primary" onclick="iwShowProductForm()">إضافة صنف جديد</button></h1>

    <!-- Product Form Modal -->
    <div id="iw-product-modal" class="iw-modal" style="display:none;">
        <div class="iw-modal-content">
            <span class="iw-modal-close" onclick="iwCloseProductForm()">&times;</span>
            <h2 id="product-form-title">إضافة صنف جديد</h2>
            <form id="iw-product-form">
                <input type="hidden" id="product_id" value="0">
                <table class="form-table">
                    <tr><th>اسم الصنف *</th><td><input type="text" id="product_name" class="regular-text" required></td></tr>
                    <tr><th>الكود (SKU)</th><td><input type="text" id="product_sku" class="regular-text"></td></tr>
                    <tr><th>التصنيف</th><td><input type="text" id="product_category" class="regular-text"></td></tr>
                    <tr><th>وحدة القياس</th><td><input type="text" id="product_unit" class="regular-text" placeholder="مثال: قطعة، كرتونة، متر"></td></tr>
                    <tr><th>الحد الأدنى للمخزون *</th><td><input type="number" id="product_min_stock" class="regular-text" min="0" value="0"></td></tr>
                    <tr><th>الحد الأقصى للمخزون *</th><td><input type="number" id="product_max_stock" class="regular-text" min="0" value="0"></td></tr>
                    <tr><th>السعر</th><td><input type="number" id="product_price" class="regular-text" min="0" step="0.01" value="0"></td></tr>
                    <tr><th>الوصف</th><td><textarea id="product_description" class="large-text" rows="3"></textarea></td></tr>
                </table>
                <button type="submit" class="button button-primary">حفظ</button>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>#</th>
                <th>اسم الصنف</th>
                <th>الكود</th>
                <th>التصنيف</th>
                <th>الوحدة</th>
                <th>المخزون الحالي</th>
                <th>الحد الأدنى</th>
                <th>الحد الأقصى</th>
                <th>السعر</th>
                <th>الحالة</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody id="iw-products-table"></tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    loadProducts();

    function loadProducts() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_products_list', nonce: iwAdmin.nonce}, function(res) {
            if (!res.success) return;
            var html = '';
            res.data.forEach(function(p, i) {
                var status = '';
                if (p.min_stock > 0 && p.current_stock <= p.min_stock) {
                    status = '<span class="iw-badge iw-badge-danger">تحت الحد الأدنى</span>';
                } else if (p.max_stock > 0 && p.current_stock >= p.max_stock) {
                    status = '<span class="iw-badge iw-badge-warning">وصل الحد الأقصى</span>';
                } else {
                    status = '<span class="iw-badge iw-badge-success">طبيعي</span>';
                }
                html += '<tr><td>'+(i+1)+'</td><td>'+p.name+'</td><td>'+(p.sku||'-')+'</td><td>'+(p.category||'-')+'</td>';
                html += '<td>'+(p.unit||'-')+'</td><td>'+p.current_stock+'</td><td>'+p.min_stock+'</td><td>'+p.max_stock+'</td>';
                html += '<td>'+parseFloat(p.price).toFixed(2)+'</td><td>'+status+'</td>';
                html += '<td><button class="button" onclick="iwEditProduct('+p.id+')">تعديل</button> ';
                html += '<button class="button iw-btn-danger" onclick="iwDeleteProduct('+p.id+')">حذف</button></td></tr>';
            });
            $('#iw-products-table').html(html || '<tr><td colspan="11">لا توجد أصناف</td></tr>');
        });
    }

    window.iwShowProductForm = function(id) {
        $('#product_id').val(0);
        $('#iw-product-form')[0].reset();
        $('#product-form-title').text('إضافة صنف جديد');
        $('#iw-product-modal').show();
    };

    window.iwCloseProductForm = function() {
        $('#iw-product-modal').hide();
    };

    window.iwEditProduct = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_product', nonce: iwAdmin.nonce, product_id: id}, function(res) {
            if (!res.success) return;
            var p = res.data;
            $('#product_id').val(p.id);
            $('#product_name').val(p.name);
            $('#product_sku').val(p.sku);
            $('#product_category').val(p.category);
            $('#product_unit').val(p.unit);
            $('#product_min_stock').val(p.min_stock);
            $('#product_max_stock').val(p.max_stock);
            $('#product_price').val(p.price);
            $('#product_description').val(p.description);
            $('#product-form-title').text('تعديل الصنف');
            $('#iw-product-modal').show();
        });
    };

    window.iwDeleteProduct = function(id) {
        if (!confirm(iwAdmin.strings.confirm_delete)) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_delete_product', nonce: iwAdmin.nonce, product_id: id}, function(res) {
            alert(res.data.message);
            loadProducts();
        });
    };

    $('#iw-product-form').on('submit', function(e) {
        e.preventDefault();
        $.post(iwAdmin.ajaxurl, {
            action: 'iw_save_product', nonce: iwAdmin.nonce,
            product_id: $('#product_id').val(),
            name: $('#product_name').val(),
            sku: $('#product_sku').val(),
            category: $('#product_category').val(),
            unit: $('#product_unit').val(),
            min_stock: $('#product_min_stock').val(),
            max_stock: $('#product_max_stock').val(),
            price: $('#product_price').val(),
            description: $('#product_description').val()
        }, function(res) {
            alert(res.data.message);
            if (res.success) { iwCloseProductForm(); loadProducts(); }
        });
    });
});
</script>
