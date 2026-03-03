<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>طلبات الشراء</h1>

    <div class="iw-tabs">
        <button class="iw-tab active" onclick="iwPrSwitchTab('create')">إنشاء طلب شراء</button>
        <button class="iw-tab" onclick="iwPrSwitchTab('auto')">توليد طلب (حد أدنى)</button>
        <button class="iw-tab" onclick="iwPrSwitchTab('pending')">طلبات معلقة</button>
        <button class="iw-tab" onclick="iwPrSwitchTab('approved')">طلبات معتمدة</button>
        <button class="iw-tab" onclick="iwPrSwitchTab('all')">جميع الطلبات</button>
    </div>

    <!-- Create Purchase Request -->
    <div id="pr-tab-create" class="iw-tab-content">
        <h2>إنشاء طلب شراء يدوي</h2>
        <form id="iw-pr-form">
            <table class="form-table">
                <tr><th>ملاحظات</th><td><textarea id="pr_notes" class="large-text" rows="2"></textarea></td></tr>
            </table>
            <h3>الأصناف المطلوبة</h3>
            <table class="wp-list-table widefat fixed" id="pr-items-table">
                <thead><tr><th>الصنف</th><th>المخزون الحالي</th><th>الحد الأدنى</th><th>الحد الأقصى</th><th>الكمية المطلوبة</th><th>آخر سعر شراء</th><th>السعر التقديري</th><th>إجراء</th></tr></thead>
                <tbody id="pr-items-body"></tbody>
            </table>
            <button type="button" class="button" onclick="iwAddPrItem()" style="margin-top:10px;">+ إضافة صنف</button>
            <br><br>
            <button type="submit" class="button button-primary button-large">إرسال للاعتماد</button>
        </form>
    </div>

    <!-- Auto Generate -->
    <div id="pr-tab-auto" class="iw-tab-content" style="display:none;">
        <h2>توليد طلب شراء للأصناف تحت الحد الأدنى</h2>
        <p>اختر تصنيف أو أكثر ثم اضغط "توليد" لإنشاء طلب شراء للأصناف التي وصلت للحد الأدنى.</p>
        <table class="form-table" style="max-width:600px;">
            <tr>
                <th>التصنيفات</th>
                <td>
                    <select id="auto-gen-category" class="regular-text" multiple size="6" style="min-width:300px;min-height:120px;">
                        <?php
                        $categories = IW_Categories::get_all();
                        foreach ($categories as $cat) {
                            echo '<option value="' . esc_attr($cat->name) . '">' . esc_html($cat->name) . '</option>';
                        }
                        ?>
                    </select>
                    <p class="description">اضغط Ctrl للاختيار المتعدد. عدم الاختيار = جميع التصنيفات.</p>
                </td>
            </tr>
        </table>
        <button class="button button-primary button-large" onclick="iwAutoGenerate()">توليد طلب الشراء</button>
        <div id="pr-auto-result" style="margin-top:15px;"></div>
    </div>

    <!-- Pending -->
    <div id="pr-tab-pending" class="iw-tab-content" style="display:none;">
        <h2>طلبات الشراء المعلقة (في انتظار اعتماد العميد)</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>رقم الطلب</th><th>التاريخ</th><th>ملاحظات</th><th>إجراءات</th></tr></thead>
            <tbody id="pr-pending-table"></tbody>
        </table>
    </div>

    <!-- Approved -->
    <div id="pr-tab-approved" class="iw-tab-content" style="display:none;">
        <h2>طلبات الشراء المعتمدة (جاهزة للطباعة)</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>رقم الطلب</th><th>التاريخ</th><th>المعتمد</th><th>إجراءات</th></tr></thead>
            <tbody id="pr-approved-table"></tbody>
        </table>
    </div>

    <!-- All -->
    <div id="pr-tab-all" class="iw-tab-content" style="display:none;">
        <h2>جميع طلبات الشراء</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>رقم الطلب</th><th>الحالة</th><th>التاريخ</th><th>إجراءات</th></tr></thead>
            <tbody id="pr-all-table"></tbody>
        </table>
    </div>

    <!-- Modal -->
    <div id="iw-pr-modal" class="iw-modal" style="display:none;">
        <div class="iw-modal-content iw-modal-large">
            <span class="iw-modal-close" onclick="jQuery('#iw-pr-modal').hide()">&times;</span>
            <div id="iw-pr-modal-body"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var products = [];
    $.post(iwAdmin.ajaxurl, {action: 'iw_get_products_list', nonce: iwAdmin.nonce}, function(r) {
        if(r.success) {
            products = r.data;
            console.log('PR Products loaded:', r.data.length);
            iwAddPrItem();
        }
        else console.log('PR Products error:', r);
    });

    window.iwPrSwitchTab = function(tab) {
        $('.iw-tab-content').hide();
        $('.iw-tab').removeClass('active');
        $('#pr-tab-'+tab).show();
        $('[onclick="iwPrSwitchTab(\''+tab+'\')"]').addClass('active');
        if (tab === 'pending') loadPrOrders('pending');
        if (tab === 'approved') loadPrOrders('approved');
        if (tab === 'all') loadPrOrders('');
    };

    window.iwAddPrItem = function() {
        var opts = '<option value="">اختر الصنف</option>';
        products.forEach(function(p) { opts += '<option value="'+p.id+'" data-stock="'+p.current_stock+'" data-min="'+p.min_stock+'" data-max="'+p.max_stock+'" data-price="'+p.price+'">'+p.name+'</option>'; });
        var row = '<tr><td><select class="pr-product regular-text" onchange="iwUpdatePrRow(this)">'+opts+'</select></td>';
        row += '<td class="pr-stock">-</td><td class="pr-min">-</td><td class="pr-max">-</td>';
        row += '<td><input type="number" class="pr-qty" min="1" value="1"></td>';
        row += '<td class="pr-last-price">-</td>';
        row += '<td><input type="number" class="pr-price" min="0" step="0.01" value="0"></td>';
        row += '<td><button type="button" class="button iw-btn-danger" onclick="$(this).closest(\'tr\').remove()">حذف</button></td></tr>';
        var $row = $(row);
        $('#pr-items-body').append($row);
        if (typeof iwInitSelect2 === 'function') iwInitSelect2($row);
    };

    window.iwUpdatePrRow = function(sel) {
        var opt = $(sel).find(':selected');
        var tr = $(sel).closest('tr');
        tr.find('.pr-stock').text(opt.data('stock') || 0);
        tr.find('.pr-min').text(opt.data('min') || 0);
        tr.find('.pr-max').text(opt.data('max') || 0);
        var needed = (opt.data('max') || 0) - (opt.data('stock') || 0);
        if (needed < 1) needed = 1;
        tr.find('.pr-qty').val(needed);
        var lastPrice = opt.data('price') || 0;
        tr.find('.pr-last-price').text(parseFloat(lastPrice).toFixed(2));
        tr.find('.pr-price').val(lastPrice);
    };

    $('#iw-pr-form').on('submit', function(e) {
        e.preventDefault();
        var items = [];
        $('#pr-items-body tr').each(function() {
            var pid = $(this).find('.pr-product').val();
            var qty = $(this).find('.pr-qty').val();
            var price = $(this).find('.pr-price').val();
            if (pid && qty > 0) items.push({product_id: pid, quantity: qty, estimated_price: price});
        });
        if (!items.length) { alert('يجب إضافة أصناف'); return; }
        $.post(iwAdmin.ajaxurl, {action: 'iw_create_purchase_request', nonce: iwAdmin.nonce, items: JSON.stringify(items), notes: $('#pr_notes').val()}, function(r) {
            alert(r.data.message);
            if (r.success) { $('#iw-pr-form')[0].reset(); $('#pr-items-body').html(''); iwAddPrItem(); }
        });
    });

    window.iwAutoGenerate = function() {
        var categories = $('#auto-gen-category').val() || [];
        var category = Array.isArray(categories) ? categories.join(',') : categories;
        $.post(iwAdmin.ajaxurl, {action: 'iw_auto_generate_purchase_requests', nonce: iwAdmin.nonce, category: category}, function(r) {
            if (!r.success) { alert('حدث خطأ'); return; }
            var d = r.data;
            var html = '';
            if (d.created > 0) {
                html += '<div class="notice notice-success"><p><strong>' + d.message + '</strong></p></div>';
                if (d.items && d.items.length) {
                    html += '<table class="wp-list-table widefat fixed striped"><thead><tr><th>الصنف</th><th>الكمية المطلوبة</th><th>آخر سعر شراء</th></tr></thead><tbody>';
                    d.items.forEach(function(it) {
                        html += '<tr><td>'+it.product_name+'</td><td>'+it.quantity+'</td><td>'+parseFloat(it.last_purchase_price || it.estimated_price).toFixed(2)+'</td></tr>';
                    });
                    html += '</tbody></table>';
                    html += '<p style="margin-top:10px;"><button class="button button-primary" onclick="iwViewPr('+d.request_id+')">عرض وتعديل الطلب</button></p>';
                }
            } else {
                html += '<div class="notice notice-warning"><p>' + d.message + '</p></div>';
            }
            if (d.skipped > 0) {
                html += '<p>تم تخطي ' + d.skipped + ' أصناف لوجود طلبات شراء معلقة لها بالفعل</p>';
            }
            $('#pr-auto-result').html(html);
        });
    };

    function loadPrOrders(status) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_purchase_requests', nonce: iwAdmin.nonce, status: status}, function(r) {
            if (!r.success) return;
            var target = status ? '#pr-'+status+'-table' : '#pr-all-table';
            var html = '';
            r.data.forEach(function(o) {
                var statusBadge = '<span class="iw-badge iw-badge-'+(o.status==='approved'?'success':o.status==='pending'?'warning':o.status==='rejected'?'danger':'info')+'">'+({pending:'معلق',approved:'معتمد',rejected:'مرفوض',completed:'منفذ'}[o.status]||o.status)+'</span>';
                html += '<tr><td>'+o.request_number+'</td>';
                if (!status) html += '<td>'+statusBadge+'</td>';
                html += '<td>'+o.created_at+'</td>';
                if (status === 'approved') html += '<td>'+(o.created_by_name||'-')+'</td>';
                if (status !== 'approved' && status) html += '<td>'+(o.notes||'-')+'</td>';
                html += '<td><button class="button" onclick="iwViewPr('+o.id+')">عرض</button>';
                if (o.status === 'pending') html += ' <button class="button iw-btn-danger" onclick="iwDeletePr('+o.id+')">حذف</button>';
                if (o.status === 'approved') html += ' <button class="button button-primary" onclick="iwPrintPr('+o.id+')">طباعة</button>';
                if (o.status === 'approved') html += ' <button class="button" style="background:#46b450;color:#fff;" onclick="iwCompletePr('+o.id+')">استلام البضاعة</button>';
                html += '</td></tr>';
            });
            $(target).html(html || '<tr><td colspan="5">لا توجد طلبات</td></tr>');
        });
    }

    window.iwDeletePr = function(id) {
        if (!confirm('هل أنت متأكد من حذف هذا الطلب؟')) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_delete_purchase_request', nonce: iwAdmin.nonce, request_id: id}, function(r) {
            alert(r.data.message);
            if (r.success) loadPrOrders('pending');
        });
    };

    window.iwViewPr = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_purchase_request', nonce: iwAdmin.nonce, request_id: id}, function(r) {
            if (!r.success) return;
            var o = r.data.request, items = r.data.items, sig = r.data.signature_url;
            var html = '<h2>طلب شراء رقم: '+o.request_number+'</h2>';
            html += '<table class="wp-list-table widefat fixed striped"><thead><tr><th>الصنف</th><th>المخزون الحالي</th><th>الحد الأدنى</th><th>الحد الأقصى</th><th>الكمية المطلوبة</th>';
            if (o.status === 'pending') html += '<th>الكمية المعتمدة</th>';
            html += '<th>آخر سعر شراء</th><th>السعر التقديري</th>';
            if (o.status === 'pending') html += '<th>حذف</th>';
            html += '</tr></thead><tbody>';
            items.forEach(function(it) {
                var lastPrice = parseFloat(it.last_purchase_price || it.estimated_price).toFixed(2);
                html += '<tr data-item-id="'+it.id+'"><td>'+it.product_name+'</td><td>'+(it.current_stock||0)+'</td><td>'+(it.min_stock||0)+'</td><td>'+(it.max_stock||0)+'</td><td>'+it.quantity+'</td>';
                if (o.status === 'pending') {
                    html += '<td><input type="number" class="pr-approve-qty" data-product="'+it.product_id+'" value="'+it.quantity+'" min="0" data-price="'+it.estimated_price+'" style="width:80px;"></td>';
                }
                html += '<td>'+lastPrice+'</td>';
                if (o.status === 'pending') {
                    html += '<td><input type="number" class="pr-edit-price" value="'+parseFloat(it.estimated_price).toFixed(2)+'" min="0" step="0.01" style="width:100px;"></td>';
                    html += '<td><button class="button iw-btn-danger" onclick="iwDeletePrItem('+it.id+','+o.id+')">X</button></td>';
                } else {
                    html += '<td>'+parseFloat(it.estimated_price).toFixed(2)+'</td>';
                }
                html += '</tr>';
            });
            html += '</tbody></table>';

            if (sig && o.status !== 'pending') {
                html += '<div style="margin-top:15px;text-align:center;"><p><strong>توقيع المعتمد:</strong></p><img src="'+sig+'" style="max-height:100px;" /></div>';
            }

            if (o.status === 'pending') {
                html += '<div style="margin-top:15px;">';
                html += '<button class="button button-primary button-large" onclick="iwApprovePr('+o.id+')">اعتماد</button> ';
                html += '<button class="button iw-btn-danger button-large" onclick="iwRejectPr('+o.id+')">رفض</button> ';
                html += '<button class="button button-large" onclick="iwSavePrEdit('+o.id+')">حفظ التعديلات</button>';
                html += '</div>';
            }
            if (o.status === 'approved') {
                html += '<div style="margin-top:15px;">';
                html += '<button class="button button-primary button-large" onclick="iwPrintPr('+o.id+')">طباعة</button> ';
                html += '<button class="button button-large" style="background:#46b450;color:#fff;" onclick="iwCompletePr('+o.id+')">استلام البضاعة</button>';
                html += '</div>';
            }

            $('#iw-pr-modal-body').html(html);
            $('#iw-pr-modal').show();
        });
    };

    window.iwDeletePrItem = function(itemId, requestId) {
        if (!confirm('هل أنت متأكد من حذف هذا الصنف من الطلب؟')) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_delete_purchase_request_item', nonce: iwAdmin.nonce, item_id: itemId}, function(r) {
            alert(r.data.message);
            if (r.success) {
                if (r.data.request_deleted) {
                    $('#iw-pr-modal').hide();
                    loadPrOrders('pending');
                } else {
                    iwViewPr(requestId);
                }
            }
        });
    };

    window.iwSavePrEdit = function(id) {
        var items = [];
        $('#iw-pr-modal-body tbody tr').each(function() {
            var $row = $(this);
            var $qtyInput = $row.find('.pr-approve-qty');
            var $priceInput = $row.find('.pr-edit-price');
            if ($qtyInput.length) {
                items.push({
                    product_id: $qtyInput.data('product'),
                    quantity: $qtyInput.val(),
                    approved_quantity: $qtyInput.val(),
                    estimated_price: $priceInput.val() || $qtyInput.data('price')
                });
            }
        });
        $.post(iwAdmin.ajaxurl, {action: 'iw_update_purchase_request', nonce: iwAdmin.nonce, request_id: id, items: JSON.stringify(items)}, function(r) {
            alert(r.data.message);
            if (r.success) iwViewPr(id);
        });
    };

    window.iwApprovePr = function(id) {
        if (!confirm('هل أنت متأكد من اعتماد هذا الطلب؟')) return;
        var items = [];
        $('#iw-pr-modal-body tbody tr').each(function() {
            var $row = $(this);
            var $qtyInput = $row.find('.pr-approve-qty');
            var $priceInput = $row.find('.pr-edit-price');
            if ($qtyInput.length) {
                items.push({
                    product_id: $qtyInput.data('product'),
                    quantity: $qtyInput.val(),
                    approved_quantity: $qtyInput.val(),
                    estimated_price: $priceInput.val() || $qtyInput.data('price')
                });
            }
        });
        $.post(iwAdmin.ajaxurl, {action: 'iw_update_purchase_request', nonce: iwAdmin.nonce, request_id: id, items: JSON.stringify(items)}, function() {
            $.post(iwAdmin.ajaxurl, {action: 'iw_approve_purchase_request', nonce: iwAdmin.nonce, request_id: id}, function(r) {
                alert(r.data.message);
                if (r.success) { $('#iw-pr-modal').hide(); loadPrOrders('pending'); }
            });
        });
    };

    window.iwRejectPr = function(id) {
        var reason = prompt('سبب الرفض:');
        if (reason === null) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_reject_purchase_request', nonce: iwAdmin.nonce, request_id: id, rejection_reason: reason}, function(r) {
            alert(r.data.message);
            if (r.success) $('#iw-pr-modal').hide();
        });
    };

    window.iwCompletePr = function(id) {
        if (!confirm('هل تم استلام البضاعة؟ سيتم إضافة الكميات للمخزون.')) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_complete_purchase_request', nonce: iwAdmin.nonce, request_id: id}, function(r) {
            alert(r.data.message);
            if (r.success) { $('#iw-pr-modal').hide(); loadPrOrders('approved'); }
        });
    };

    window.iwPrintPr = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_purchase_request', nonce: iwAdmin.nonce, request_id: id}, function(r) {
            if (!r.success) return;
            var o = r.data.request, items = r.data.items, sig = r.data.signature_url;
            var printContent = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
            printContent += '<h2 style="text-align:center;">طلب شراء رقم: '+o.request_number+'</h2>';
            printContent += '<p><strong>التاريخ:</strong> '+o.created_at+'</p>';
            printContent += '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;">';
            printContent += '<tr style="background:#f0f0f0;"><th>الصنف</th><th>الكمية المطلوبة</th><th>آخر سعر شراء</th><th>السعر التقديري</th><th>الإجمالي</th></tr>';
            var grandTotal = 0;
            items.forEach(function(it) {
                var qty = it.approved_quantity !== null ? it.approved_quantity : it.quantity;
                var total = qty * parseFloat(it.estimated_price);
                grandTotal += total;
                var lastPrice = parseFloat(it.last_purchase_price || it.estimated_price).toFixed(2);
                printContent += '<tr><td>'+it.product_name+'</td><td>'+qty+'</td><td>'+lastPrice+'</td><td>'+parseFloat(it.estimated_price).toFixed(2)+'</td><td>'+total.toFixed(2)+'</td></tr>';
            });
            printContent += '<tr style="font-weight:bold;"><td colspan="4">الإجمالي</td><td>'+grandTotal.toFixed(2)+'</td></tr>';
            printContent += '</table>';

            // Signature
            if (sig) {
                printContent += '<div style="margin-top:40px;text-align:left;">';
                printContent += '<p><strong>توقيع عميد المعهد / المدير:</strong></p>';
                printContent += '<img src="'+sig+'" style="max-height:80px;" />';
                printContent += '</div>';
            }

            var w = window.open('','','width=800,height=600');
            w.document.write('<html dir="rtl"><head><title>طلب شراء</title><style>body{font-family:Arial,sans-serif;padding:20px;}</style></head><body>'+printContent+'</body></html>');
            w.document.close();
            w.print();
        });
    };
});
</script>
