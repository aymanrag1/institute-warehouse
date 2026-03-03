<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>إدارة الأصناف <button class="button button-primary" onclick="iwShowProductForm()">إضافة صنف جديد</button></h1>

    <!-- Auto stock diagnostic notice -->
    <div id="iw-auto-diag" style="background:#fff;border:2px solid #2271b1;padding:15px;margin:10px 0;border-radius:4px;display:none;">
        <strong>🔍 تشخيص تلقائي للرصيد:</strong>
        <div id="iw-auto-diag-body" style="margin-top:8px;font-family:monospace;font-size:13px;"></div>
        <button onclick="document.getElementById('iw-auto-diag').style.display='none'" class="button" style="margin-top:8px;">إخفاء</button>
    </div>

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
                    <tr><th>التصنيف</th><td>
                        <div style="position:relative;">
                            <select id="product_category" name="product_category" style="width:100%;height:40px;padding:8px;border:1px solid #8c8f94;border-radius:4px;background:#fff;font-size:14px;cursor:pointer;">
                                <option value="">-- اختر التصنيف --</option>
                            </select>
                        </div>
                    </td></tr>
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

    <!-- Bulk Actions -->
    <div style="margin-bottom:10px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input type="text" id="iw-product-search" placeholder="بحث باسم الصنف..." style="width:200px;padding:5px 8px;" oninput="iwFilterProducts()">
        <select id="iw-category-filter" onchange="iwFilterProducts()" style="padding:5px 8px;">
            <option value="">-- جميع التصنيفات --</option>
        </select>
        <button class="button" onclick="iwSelectAll()">تحديد الكل</button>
        <button class="button iw-btn-danger" onclick="iwBulkDeleteProducts()">حذف المحدد</button>
        <button class="button button-primary" onclick="iwPrintProducts()">طباعة المحدد</button>
        <select id="iw-bulk-category" style="padding:5px 8px;">
            <option value="">-- تغيير التصنيف إلى --</option>
        </select>
        <button class="button" onclick="iwBulkChangeCategory()">تطبيق التصنيف</button>
        <button class="button" onclick="iwStockDebugAll()" style="background:#f0f0f0;border-color:#999;" title="يعرض تفاصيل حساب الرصيد لكل صنف">تشخيص الرصيد</button>
    </div>

    <!-- Stock Debug Modal -->
    <div id="iw-stock-debug-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:99999;overflow:auto;">
        <div style="background:#fff;margin:40px auto;max-width:900px;border-radius:6px;padding:20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                <h3 style="margin:0;">تشخيص رصيد الأصناف</h3>
                <button onclick="document.getElementById('iw-stock-debug-modal').style.display='none'" class="button">✕ إغلاق</button>
            </div>
            <div id="iw-stock-debug-content">جاري التحميل...</div>
        </div>
    </div>

    <!-- Products Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:30px;"><input type="checkbox" id="iw-select-all" onchange="iwToggleAll(this)"></th>
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
    var allProducts = [];
    loadProducts();
    runAutoStockDiag();

    function runAutoStockDiag() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_stock_debug', nonce: iwAdmin.nonce}, function(res) {
            if (!res.success || !res.data) return;
            var lines = [];
            res.data.forEach(function(p) {
                lines.push('<b>' + p.name + '</b>: DB=' + p.current_stock_db
                    + ' | حقيقي(approved+completed)=' + p.real_stock
                    + (p.current_stock_db != p.real_stock ? ' <span style="color:red">⚠ فرق: ' + (p.current_stock_db - p.real_stock) + '</span>' : ' ✓'));
            });
            if (lines.length) {
                document.getElementById('iw-auto-diag-body').innerHTML = lines.join('<br>');
                document.getElementById('iw-auto-diag').style.display = 'block';
            }
        });
    }

    function loadProducts() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_products_list', nonce: iwAdmin.nonce}, function(res) {
            if (!res.success) return;
            allProducts = res.data;
            loadFilterCategories();
            renderProducts(allProducts);
        });
    }

    function loadFilterCategories() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_categories', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) return;
            var h = '<option value="">-- جميع التصنيفات --</option>';
            var h2 = '<option value="">-- تغيير التصنيف إلى --</option>';
            r.data.forEach(function(c) {
                h += '<option value="'+c.name+'">'+c.name+'</option>';
                h2 += '<option value="'+c.name+'">'+c.name+'</option>';
            });
            $('#iw-category-filter').html(h);
            $('#iw-bulk-category').html(h2);
        });
    }

    window.iwFilterProducts = function() {
        var search = $('#iw-product-search').val().toLowerCase();
        var cat = $('#iw-category-filter').val();
        var filtered = allProducts.filter(function(p) {
            var matchName = !search || p.name.toLowerCase().indexOf(search) !== -1;
            var matchCat = !cat || (p.category || '') === cat;
            return matchName && matchCat;
        });
        renderProducts(filtered);
    };

    function renderProducts(data) {
        var html = '';
        data.forEach(function(p, i) {
            var status = '';
            if (p.min_stock > 0 && p.current_stock <= p.min_stock) {
                status = '<span class="iw-badge iw-badge-danger">تحت الحد الأدنى</span>';
            } else if (p.max_stock > 0 && p.current_stock >= p.max_stock) {
                status = '<span class="iw-badge iw-badge-warning">وصل الحد الأقصى</span>';
            } else {
                status = '<span class="iw-badge iw-badge-success">طبيعي</span>';
            }
            html += '<tr><td><input type="checkbox" class="iw-row-check" value="'+p.id+'"></td><td>'+(i+1)+'</td><td>'+p.name+'</td><td>'+(p.sku||'-')+'</td><td>'+(p.category||'-')+'</td>';
            html += '<td>'+(p.unit||'-')+'</td><td>'+p.current_stock+'</td><td>'+p.min_stock+'</td><td>'+p.max_stock+'</td>';
            html += '<td>'+parseFloat(p.price).toFixed(2)+'</td><td>'+status+'</td>';
            html += '<td><button class="button" onclick="iwEditProduct('+p.id+')">تعديل</button> ';
            html += '<button class="button iw-btn-danger" onclick="iwDeleteProduct('+p.id+')">حذف</button></td></tr>';
        });
        $('#iw-products-table').html(html || '<tr><td colspan="12">لا توجد أصناف</td></tr>');
    }

    window.iwBulkChangeCategory = function() {
        var newCat = $('#iw-bulk-category').val();
        if (!newCat) { alert('اختر التصنيف أولاً'); return; }
        var ids = [];
        $('.iw-row-check:checked').each(function() { ids.push($(this).val()); });
        if (!ids.length) { alert('اختر أصناف أولاً'); return; }
        if (!confirm('هل تريد تغيير تصنيف ' + ids.length + ' أصناف إلى "' + newCat + '"؟')) return;
        var done = 0;
        ids.forEach(function(id) {
            $.post(iwAdmin.ajaxurl, {action: 'iw_save_product', nonce: iwAdmin.nonce, product_id: id, category: newCat, bulk_category: 1}, function() {
                done++;
                if (done === ids.length) { alert('تم تغيير تصنيف ' + ids.length + ' أصناف'); loadProducts(); }
            });
        });
    };

    // Load categories into select
    function loadCategories(selectedValue) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_categories', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) return;
            var h = '<option value="">-- اختر التصنيف --</option>';
            r.data.forEach(function(c) {
                var sel = (selectedValue && selectedValue === c.name) ? ' selected' : '';
                h += '<option value="'+c.name+'"'+sel+'>'+c.name+'</option>';
            });
            $('#product_category').html(h);
        });
    }

    window.iwShowProductForm = function(id) {
        $('#product_id').val(0);
        $('#iw-product-form')[0].reset();
        $('#product-form-title').text('إضافة صنف جديد');
        loadCategories('');
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
            loadCategories(p.category);
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

    // Bulk actions
    window.iwToggleAll = function(el) {
        $('.iw-row-check').prop('checked', el.checked);
    };
    window.iwSelectAll = function() {
        var all = $('#iw-select-all').prop('checked');
        $('#iw-select-all').prop('checked', !all).trigger('change');
        $('.iw-row-check').prop('checked', !all);
    };
    window.iwBulkDeleteProducts = function() {
        var ids = [];
        $('.iw-row-check:checked').each(function() { ids.push($(this).val()); });
        if (!ids.length) { alert('اختر أصناف أولاً'); return; }
        if (!confirm('هل أنت متأكد من حذف ' + ids.length + ' أصناف؟')) return;
        var done = 0;
        ids.forEach(function(id) {
            $.post(iwAdmin.ajaxurl, {action: 'iw_delete_product', nonce: iwAdmin.nonce, product_id: id}, function() {
                done++;
                if (done === ids.length) { alert('تم حذف ' + ids.length + ' أصناف'); loadProducts(); }
            });
        });
    };
    window.iwPrintProducts = function() {
        var rows = [];
        $('.iw-row-check:checked').each(function() {
            var tr = $(this).closest('tr');
            rows.push(tr.clone());
        });
        if (!rows.length) { alert('اختر أصناف أولاً'); return; }
        var printContent = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
        printContent += '<h2 style="text-align:center;">قائمة الأصناف</h2>';
        printContent += '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;">';
        printContent += '<tr style="background:#f0f0f0;"><th>#</th><th>الصنف</th><th>الكود</th><th>التصنيف</th><th>الوحدة</th><th>المخزون</th><th>الحد الأدنى</th><th>الحد الأقصى</th><th>السعر</th></tr>';
        rows.forEach(function(tr, i) {
            var tds = tr.find('td');
            printContent += '<tr><td>'+(i+1)+'</td>';
            for (var j = 2; j <= 9; j++) { printContent += '<td>'+tds.eq(j).text()+'</td>'; }
            printContent += '</tr>';
        });
        printContent += '</table>';
        var w = window.open('','','width=900,height=600');
        w.document.write('<html dir="rtl"><head><title>قائمة الأصناف</title><style>body{font-family:Arial,sans-serif;padding:20px;}</style></head><body>'+printContent+'</body></html>');
        w.document.close();
        w.print();
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

function iwStockDebugAll() {
    document.getElementById('iw-stock-debug-modal').style.display = 'block';
    document.getElementById('iw-stock-debug-content').innerHTML = '<p>جاري التحميل...</p>';
    jQuery.post(iwAdmin.ajaxurl, {action: 'iw_stock_debug', nonce: iwAdmin.nonce}, function(res) {
        if (!res.success) { document.getElementById('iw-stock-debug-content').innerHTML = 'خطأ: ' + (res.data ? res.data.message : ''); return; }
        var rows = '';
        res.data.forEach(function(p) {
            var ok = p.real_stock == p.available_stock;
            var diff = p.real_stock - p.available_stock;
            rows += '<tr style="background:' + (diff > 0 ? '#fff3cd' : '#fff') + '">'
                + '<td><b>' + p.name + '</b></td>'
                + '<td>' + p.current_stock_db + '</td>'
                + '<td>' + p.real_stock + ' (مضاف - مكتمل)</td>'
                + '<td>' + p.available_stock + ' (مطروح منه المعتمد)</td>'
                + '<td style="color:' + (diff > 0 ? '#d63638' : '#008a20') + '">'
                    + (diff > 0 ? '⚠ ' + diff + ' كمية محجوزة بأذونات معتمدة' : '✓') + '</td>'
                + '<td><button class="button button-small" onclick="iwStockDebugProduct(' + p.id + ')">تفاصيل</button></td>'
                + '</tr>';
        });
        document.getElementById('iw-stock-debug-content').innerHTML =
            '<p style="color:#666;font-size:12px;">الرصيد الحقيقي = إجمالي الإضافات - الأذونات المكتملة | الرصيد المتاح = الرصيد الحقيقي - الأذونات المعتمدة</p>'
            + '<table class="wp-list-table widefat fixed striped" style="margin-top:10px"><thead><tr>'
            + '<th>الصنف</th><th>الرصيد في DB</th><th>الرصيد الحقيقي</th><th>الرصيد المتاح</th><th>الحالة</th><th></th>'
            + '</tr></thead><tbody>' + rows + '</tbody></table>';
    });
}

function iwStockDebugProduct(productId) {
    document.getElementById('iw-stock-debug-content').innerHTML = '<p>جاري التحميل...</p>';
    jQuery.post(iwAdmin.ajaxurl, {action: 'iw_stock_debug', nonce: iwAdmin.nonce, product_id: productId}, function(res) {
        if (!res.success) return;
        var d = res.data;
        var addRows = (d.add_items || []).map(function(i) {
            return '<tr><td>' + i.order_number + '</td><td>' + i.quantity + '</td><td>' + i.created_at + '</td></tr>';
        }).join('');
        var wdRows = (d.withdrawals || []).map(function(w) {
            var color = w.status === 'completed' ? '#008a20' : (w.status === 'approved' ? '#d63638' : '#666');
            return '<tr><td>' + w.order_number + '</td><td style="color:' + color + '">' + w.status + '</td><td>' + (w.order_type || 'normal') + '</td><td>' + w.qty + '</td></tr>';
        }).join('');
        // Orders in withdrawal_orders table (with or without items)
        var owRows = (d.orders_without_items || []).map(function(o) {
            var itemsLabel = o.items_count > 0
                ? '<span style="color:green">' + o.items_count + ' بند</span>'
                : '<span style="color:red;font-weight:bold">0 بنود ← مشكلة!</span>';
            return '<tr><td>' + o.order_number + '</td><td>' + o.status + '</td><td>' + (o.order_type || 'normal') + '</td><td>' + itemsLabel + '</td><td>' + o.created_at + '</td></tr>';
        }).join('');

        document.getElementById('iw-stock-debug-content').innerHTML =
            '<button onclick="iwStockDebugAll()" class="button" style="margin-bottom:10px">← رجوع للكل</button>'
            + '<h4>' + d.product.name + '</h4>'
            + '<p><b>الرصيد الحقيقي:</b> ' + d.real_stock + ' | <b>الرصيد المتاح:</b> ' + d.available_stock + '</p>'
            + '<h5>أذونات الإضافة (add_order_items):</h5>'
            + '<table class="wp-list-table widefat" style="margin-bottom:15px"><thead><tr><th>رقم الإذن</th><th>الكمية</th><th>التاريخ</th></tr></thead><tbody>' + (addRows || '<tr><td colspan=3>لا يوجد</td></tr>') + '</tbody></table>'
            + '<h5>الأرصدة الافتتاحية:</h5><p>' + (d.opening_balances.length ? d.opening_balances.map(function(b){return b.quantity + ' (' + b.balance_date + ')';}).join(', ') : 'لا يوجد') + '</p>'
            + '<h5>أذونات الصرف (في withdrawal_order_items):</h5>'
            + '<table class="wp-list-table widefat" style="margin-bottom:15px"><thead><tr><th>رقم الإذن</th><th>الحالة</th><th>النوع</th><th>الكمية</th></tr></thead><tbody>' + (wdRows || '<tr><td colspan=4 style="color:red">لا يوجد بنود في جدول withdrawal_order_items لهذا الصنف</td></tr>') + '</tbody></table>'
            + '<h5>جميع أذونات الصرف في النظام (آخر 20) - لكشف أذونات بدون بنود:</h5>'
            + '<table class="wp-list-table widefat"><thead><tr><th>رقم الإذن</th><th>الحالة</th><th>النوع</th><th>عدد البنود</th><th>التاريخ</th></tr></thead><tbody>' + (owRows || '<tr><td colspan=5 style="color:red">لا يوجد أذونات صرف في النظام نهائياً!</td></tr>') + '</tbody></table>';
    });
}
</script>
