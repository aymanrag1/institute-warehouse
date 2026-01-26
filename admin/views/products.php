<?php
/**
 * Products View
 * صفحة إدارة الأصناف
 */

if (!defined('ABSPATH')) {
    exit;
}

$categories = IW_Admin::get_categories();
?>

<div class="wrap iw-wrap" dir="rtl">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-archive"></span>
        إدارة الأصناف
    </h1>

    <?php if (current_user_can('iw_manage_products')): ?>
        <a href="#" class="page-title-action" id="btn-add-product">
            <span class="dashicons dashicons-plus-alt2"></span> إضافة صنف جديد
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <!-- فلاتر البحث -->
    <div class="iw-filters-bar">
        <div class="iw-filter-group">
            <input type="text" id="filter-search" placeholder="بحث بالاسم أو الكود..." class="iw-filter-input">
        </div>
        <div class="iw-filter-group">
            <select id="filter-category" class="iw-filter-select">
                <option value="">جميع الفئات</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo esc_attr($cat->id); ?>"><?php echo esc_html($cat->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="iw-filter-group">
            <label>
                <input type="checkbox" id="filter-discontinued">
                إظهار المنتجات المتوقفة
            </label>
        </div>
        <button type="button" class="button" id="btn-filter">
            <span class="dashicons dashicons-filter"></span> تصفية
        </button>
    </div>

    <!-- جدول الأصناف -->
    <div class="iw-table-container">
        <table class="wp-list-table widefat fixed striped" id="products-table">
            <thead>
                <tr>
                    <th class="column-sku">الكود</th>
                    <th class="column-name">اسم الصنف</th>
                    <th class="column-category">الفئة</th>
                    <th class="column-unit">الوحدة</th>
                    <th class="column-stock">المخزون</th>
                    <th class="column-reorder">حد الطلب</th>
                    <th class="column-location">مكان التخزين</th>
                    <th class="column-actions">الإجراءات</th>
                </tr>
            </thead>
            <tbody id="products-tbody">
                <tr class="iw-loading">
                    <td colspan="8">
                        <span class="spinner is-active"></span>
                        جاري تحميل البيانات...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ترقيم الصفحات -->
    <div class="iw-pagination" id="products-pagination"></div>
</div>

<!-- نموذج إضافة/تعديل الصنف -->
<div id="product-modal" class="iw-modal" style="display: none;">
    <div class="iw-modal-content">
        <div class="iw-modal-header">
            <h2 id="modal-title">إضافة صنف جديد</h2>
            <button type="button" class="iw-modal-close">&times;</button>
        </div>
        <form id="product-form">
            <input type="hidden" name="id" id="product-id">

            <div class="iw-modal-body">
                <div class="iw-form-row">
                    <div class="iw-form-group">
                        <label for="product-sku">كود الصنف <span class="required">*</span></label>
                        <input type="text" name="sku" id="product-sku" required>
                    </div>
                    <div class="iw-form-group">
                        <label for="product-name">اسم الصنف <span class="required">*</span></label>
                        <input type="text" name="name" id="product-name" required>
                    </div>
                </div>

                <div class="iw-form-row">
                    <div class="iw-form-group">
                        <label for="product-category">الفئة</label>
                        <select name="category_id" id="product-category">
                            <option value="">اختر الفئة</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo esc_attr($cat->id); ?>"><?php echo esc_html($cat->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="iw-form-group">
                        <label for="product-unit">الوحدة</label>
                        <input type="text" name="unit" id="product-unit" placeholder="مثال: قطعة، علبة، كرتون">
                    </div>
                </div>

                <div class="iw-form-row">
                    <div class="iw-form-group">
                        <label for="product-min-quantity">الحد الأدنى</label>
                        <input type="number" name="min_quantity" id="product-min-quantity" min="0" value="0">
                    </div>
                    <div class="iw-form-group">
                        <label for="product-reorder-level">حد إعادة الطلب</label>
                        <input type="number" name="reorder_level" id="product-reorder-level" min="0" value="0">
                        <p class="description">سيتم إنشاء تنبيه عند وصول المخزون لهذا الحد</p>
                    </div>
                </div>

                <div class="iw-form-row">
                    <div class="iw-form-group">
                        <label for="product-storage">مكان التخزين</label>
                        <input type="text" name="storage_location" id="product-storage" placeholder="مثال: رف أ-1">
                    </div>
                    <div class="iw-form-group">
                        <label>
                            <input type="checkbox" name="has_expiry" id="product-has-expiry">
                            له تاريخ صلاحية
                        </label>
                    </div>
                </div>

                <div class="iw-form-group">
                    <label for="product-description">الوصف</label>
                    <textarea name="description" id="product-description" rows="3"></textarea>
                </div>

                <div class="iw-form-group">
                    <label for="product-notes">ملاحظات</label>
                    <textarea name="notes" id="product-notes" rows="2"></textarea>
                </div>

                <div class="iw-form-group" id="discontinued-group" style="display: none;">
                    <label class="iw-checkbox-danger">
                        <input type="checkbox" name="is_discontinued" id="product-discontinued">
                        إيقاف المنتج (لن يظهر في التقارير ولن يمكن طلبه)
                    </label>
                </div>
            </div>

            <div class="iw-modal-footer">
                <button type="button" class="button" onclick="closeProductModal()">إلغاء</button>
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-saved"></span>
                    حفظ
                </button>
            </div>
        </form>
    </div>
</div>

<!-- نموذج عرض المخزون -->
<div id="stock-modal" class="iw-modal" style="display: none;">
    <div class="iw-modal-content iw-modal-lg">
        <div class="iw-modal-header">
            <h2 id="stock-modal-title">تفاصيل المخزون</h2>
            <button type="button" class="iw-modal-close">&times;</button>
        </div>
        <div class="iw-modal-body">
            <div id="stock-details"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let currentPage = 1;
    const perPage = 20;

    // تحميل الأصناف
    function loadProducts() {
        const search = $('#filter-search').val();
        const categoryId = $('#filter-category').val();
        const includeDiscontinued = $('#filter-discontinued').is(':checked');

        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'iw_get_products',
                nonce: iwAdmin.nonce,
                search: search,
                category_id: categoryId,
                include_discontinued: includeDiscontinued,
                page: currentPage,
                per_page: perPage
            },
            beforeSend: function() {
                $('#products-tbody').html('<tr class="iw-loading"><td colspan="8"><span class="spinner is-active"></span> جاري التحميل...</td></tr>');
            },
            success: function(response) {
                if (response.success) {
                    renderProducts(response.data.products);
                    renderPagination(response.data.total, response.data.pages);
                }
            }
        });
    }

    // عرض الأصناف
    function renderProducts(products) {
        if (products.length === 0) {
            $('#products-tbody').html('<tr><td colspan="8" class="iw-empty">لا توجد أصناف</td></tr>');
            return;
        }

        let html = '';
        products.forEach(function(product) {
            const stockClass = product.total_stock <= 0 ? 'danger' :
                              (product.reorder_level > 0 && product.total_stock <= product.reorder_level) ? 'warning' : 'success';

            html += `<tr data-id="${product.id}" class="${product.is_discontinued ? 'discontinued' : ''}">
                <td class="column-sku"><strong>${escapeHtml(product.sku)}</strong></td>
                <td class="column-name">
                    ${escapeHtml(product.name)}
                    ${product.is_discontinued ? '<span class="iw-badge danger">متوقف</span>' : ''}
                </td>
                <td class="column-category">${escapeHtml(product.category_name || '-')}</td>
                <td class="column-unit">${escapeHtml(product.unit || '-')}</td>
                <td class="column-stock">
                    <span class="iw-badge ${stockClass}" onclick="viewStock(${product.id})" style="cursor:pointer">
                        ${parseInt(product.total_stock).toLocaleString()}
                    </span>
                </td>
                <td class="column-reorder">${product.reorder_level || '-'}</td>
                <td class="column-location">${escapeHtml(product.storage_location || '-')}</td>
                <td class="column-actions">
                    <button type="button" class="button button-small" onclick="editProduct(${product.id})" title="تعديل">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                    ${!product.is_discontinued ? `
                    <button type="button" class="button button-small" onclick="discontinueProduct(${product.id})" title="إيقاف">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                    ` : `
                    <button type="button" class="button button-small" onclick="reactivateProduct(${product.id})" title="إعادة تفعيل">
                        <span class="dashicons dashicons-yes"></span>
                    </button>
                    `}
                    <button type="button" class="button button-small button-link-delete" onclick="deleteProduct(${product.id})" title="حذف">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            </tr>`;
        });
        $('#products-tbody').html(html);
    }

    // ترقيم الصفحات
    function renderPagination(total, pages) {
        if (pages <= 1) {
            $('#products-pagination').html('');
            return;
        }

        let html = `<span class="displaying-num">${total} عنصر</span>`;
        html += '<span class="pagination-links">';

        if (currentPage > 1) {
            html += `<a class="first-page button" href="#" data-page="1"><span>&laquo;</span></a>`;
            html += `<a class="prev-page button" href="#" data-page="${currentPage - 1}"><span>&lsaquo;</span></a>`;
        }

        html += `<span class="paging-input">${currentPage} من ${pages}</span>`;

        if (currentPage < pages) {
            html += `<a class="next-page button" href="#" data-page="${currentPage + 1}"><span>&rsaquo;</span></a>`;
            html += `<a class="last-page button" href="#" data-page="${pages}"><span>&raquo;</span></a>`;
        }

        html += '</span>';
        $('#products-pagination').html(html);
    }

    // الأحداث
    $('#btn-filter').on('click', function() {
        currentPage = 1;
        loadProducts();
    });

    $('#filter-search').on('keypress', function(e) {
        if (e.which === 13) {
            currentPage = 1;
            loadProducts();
        }
    });

    $(document).on('click', '.pagination-links a', function(e) {
        e.preventDefault();
        currentPage = parseInt($(this).data('page'));
        loadProducts();
    });

    $('#btn-add-product').on('click', function(e) {
        e.preventDefault();
        openProductModal();
    });

    // إغلاق النموذج
    $('.iw-modal-close').on('click', function() {
        $(this).closest('.iw-modal').hide();
    });

    // حفظ الصنف
    $('#product-form').on('submit', function(e) {
        e.preventDefault();
        saveProduct();
    });

    // تحميل البيانات عند التحميل
    loadProducts();
});

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function openProductModal(id = null) {
    jQuery('#product-form')[0].reset();
    jQuery('#product-id').val('');
    jQuery('#discontinued-group').hide();

    if (id) {
        jQuery('#modal-title').text('تعديل الصنف');
        jQuery('#discontinued-group').show();
        // سيتم تحميل البيانات في editProduct
    } else {
        jQuery('#modal-title').text('إضافة صنف جديد');
    }

    jQuery('#product-modal').show();
}

function closeProductModal() {
    jQuery('#product-modal').hide();
}

function editProduct(id) {
    jQuery.ajax({
        url: iwAdmin.ajaxurl,
        type: 'POST',
        data: {
            action: 'iw_get_product',
            nonce: iwAdmin.nonce,
            id: id
        },
        success: function(response) {
            if (response.success) {
                const p = response.data;
                jQuery('#product-id').val(p.id);
                jQuery('#product-sku').val(p.sku);
                jQuery('#product-name').val(p.name);
                jQuery('#product-category').val(p.category_id || '');
                jQuery('#product-unit').val(p.unit || '');
                jQuery('#product-min-quantity').val(p.min_quantity || 0);
                jQuery('#product-reorder-level').val(p.reorder_level || 0);
                jQuery('#product-storage').val(p.storage_location || '');
                jQuery('#product-has-expiry').prop('checked', p.has_expiry == 1);
                jQuery('#product-description').val(p.description || '');
                jQuery('#product-notes').val(p.notes || '');
                jQuery('#product-discontinued').prop('checked', p.is_discontinued == 1);

                jQuery('#modal-title').text('تعديل الصنف');
                jQuery('#discontinued-group').show();
                jQuery('#product-modal').show();
            }
        }
    });
}

function saveProduct() {
    const formData = {
        action: 'iw_save_product',
        nonce: iwAdmin.nonce,
        id: jQuery('#product-id').val(),
        sku: jQuery('#product-sku').val(),
        name: jQuery('#product-name').val(),
        category_id: jQuery('#product-category').val(),
        unit: jQuery('#product-unit').val(),
        min_quantity: jQuery('#product-min-quantity').val(),
        reorder_level: jQuery('#product-reorder-level').val(),
        storage_location: jQuery('#product-storage').val(),
        has_expiry: jQuery('#product-has-expiry').is(':checked'),
        description: jQuery('#product-description').val(),
        notes: jQuery('#product-notes').val(),
        is_discontinued: jQuery('#product-discontinued').is(':checked')
    };

    jQuery.ajax({
        url: iwAdmin.ajaxurl,
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                alert(response.data.message);
                closeProductModal();
                jQuery('#btn-filter').trigger('click');
            } else {
                alert(response.data.message || 'حدث خطأ');
            }
        }
    });
}

function deleteProduct(id) {
    if (!confirm(iwAdmin.strings.confirm_delete)) return;

    jQuery.ajax({
        url: iwAdmin.ajaxurl,
        type: 'POST',
        data: {
            action: 'iw_delete_product',
            nonce: iwAdmin.nonce,
            id: id
        },
        success: function(response) {
            if (response.success) {
                alert(response.data.message);
                jQuery('#btn-filter').trigger('click');
            } else {
                alert(response.data.message || 'حدث خطأ');
            }
        }
    });
}

function discontinueProduct(id) {
    if (!confirm('هل تريد إيقاف هذا المنتج؟')) return;

    jQuery.ajax({
        url: iwAdmin.ajaxurl,
        type: 'POST',
        data: {
            action: 'iw_discontinue_product',
            nonce: iwAdmin.nonce,
            id: id,
            discontinue: true
        },
        success: function(response) {
            if (response.success) {
                alert(response.data.message);
                jQuery('#btn-filter').trigger('click');
            }
        }
    });
}

function reactivateProduct(id) {
    jQuery.ajax({
        url: iwAdmin.ajaxurl,
        type: 'POST',
        data: {
            action: 'iw_discontinue_product',
            nonce: iwAdmin.nonce,
            id: id,
            discontinue: false
        },
        success: function(response) {
            if (response.success) {
                alert(response.data.message);
                jQuery('#btn-filter').trigger('click');
            }
        }
    });
}

function viewStock(productId) {
    jQuery.ajax({
        url: iwAdmin.ajaxurl,
        type: 'POST',
        data: {
            action: 'iw_get_product_stock',
            nonce: iwAdmin.nonce,
            product_id: productId
        },
        success: function(response) {
            if (response.success) {
                let html = '<h4>إجمالي المخزون: ' + response.data.total + '</h4>';

                if (response.data.stock.length > 0) {
                    html += '<table class="widefat striped"><thead><tr><th>المخزن</th><th>الدفعة</th><th>الكمية</th><th>تاريخ الصلاحية</th><th>مكان التخزين</th></tr></thead><tbody>';
                    response.data.stock.forEach(function(s) {
                        html += '<tr>';
                        html += '<td>' + (s.warehouse_name || '-') + '</td>';
                        html += '<td>' + (s.batch_number || '-') + '</td>';
                        html += '<td>' + s.remaining_quantity + '</td>';
                        html += '<td>' + (s.expiry_date || '-') + '</td>';
                        html += '<td>' + (s.storage_location || '-') + '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p>لا يوجد مخزون حالياً</p>';
                }

                jQuery('#stock-details').html(html);
                jQuery('#stock-modal').show();
            }
        }
    });
}
</script>
