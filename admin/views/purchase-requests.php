<?php if (!defined('ABSPATH')) exit;
$is_ar = iw_is_ar();
$dir   = iw_dir();
?>
<div class="wrap iw-wrap" dir="<?php echo $dir; ?>">
    <h1><?php echo iw_t('طلبات الشراء', 'Purchase Requests'); ?></h1>

    <div class="iw-tabs">
        <button class="iw-tab active" onclick="iwPrSwitchTab('create')"><?php echo iw_t('إنشاء طلب شراء', 'Create Request'); ?></button>
        <button class="iw-tab" onclick="iwPrSwitchTab('auto')"><?php echo iw_t('توليد طلب (حد أدنى)', 'Auto-Generate'); ?></button>
        <button class="iw-tab" onclick="iwPrSwitchTab('pending')"><?php echo iw_t('طلبات معلقة', 'Pending'); ?></button>
        <button class="iw-tab" onclick="iwPrSwitchTab('approved')"><?php echo iw_t('طلبات معتمدة', 'Approved'); ?></button>
        <button class="iw-tab" onclick="iwPrSwitchTab('all')"><?php echo iw_t('جميع الطلبات', 'All Requests'); ?></button>
    </div>

    <!-- Create Purchase Request -->
    <div id="pr-tab-create" class="iw-tab-content">
        <h2><?php echo iw_t('إنشاء طلب شراء يدوي', 'Create Manual Purchase Request'); ?></h2>
        <form id="iw-pr-form">
            <table class="form-table">
                <tr>
                    <th><?php echo iw_t('ملاحظات', 'Notes'); ?></th>
                    <td><textarea id="pr_notes" class="large-text" rows="2"></textarea></td>
                </tr>
            </table>
            <h3><?php echo iw_t('الأصناف المطلوبة', 'Requested Items'); ?></h3>
            <table class="wp-list-table widefat fixed" id="pr-items-table">
                <thead>
                    <tr>
                        <th><?php echo iw_t('الصنف', 'Product'); ?></th>
                        <th><?php echo iw_t('المخزون الحالي', 'Stock'); ?></th>
                        <th><?php echo iw_t('الحد الأدنى', 'Min'); ?></th>
                        <th><?php echo iw_t('الحد الأقصى', 'Max'); ?></th>
                        <th><?php echo iw_t('الكمية المطلوبة', 'Qty'); ?></th>
                        <th><?php echo iw_t('آخر سعر شراء', 'Last Price'); ?></th>
                        <th><?php echo iw_t('السعر التقديري', 'Est. Price'); ?></th>
                        <th><?php echo iw_t('إجراء', 'Action'); ?></th>
                    </tr>
                </thead>
                <tbody id="pr-items-body"></tbody>
            </table>
            <button type="button" class="button" onclick="iwAddPrItem()" style="margin-top:10px;">
                + <?php echo iw_t('إضافة صنف', 'Add Item'); ?>
            </button>
            <br><br>
            <button type="submit" class="button button-primary button-large">
                <?php echo iw_t('إرسال للاعتماد', 'Submit for Approval'); ?>
            </button>
        </form>
    </div>

    <!-- Auto Generate -->
    <div id="pr-tab-auto" class="iw-tab-content" style="display:none;">
        <h2><?php echo iw_t('توليد طلب شراء للأصناف تحت الحد الأدنى', 'Auto-Generate for Low-Stock Items'); ?></h2>
        <p><?php echo iw_t(
            'اختر تصنيف أو أكثر ثم اضغط "توليد" لإنشاء طلب شراء للأصناف التي وصلت للحد الأدنى.',
            'Choose one or more categories then click "Generate" to create a purchase request for items at minimum stock level.'
        ); ?></p>
        <table class="form-table" style="max-width:600px;">
            <tr>
                <th><?php echo iw_t('التصنيفات', 'Categories'); ?></th>
                <td>
                    <select id="auto-gen-category" class="regular-text" multiple size="6" style="min-width:300px;min-height:120px;">
                        <?php
                        $categories = IW_Categories::get_all();
                        foreach ($categories as $cat) {
                            echo '<option value="' . esc_attr($cat->name) . '">' . esc_html($cat->name) . '</option>';
                        }
                        ?>
                    </select>
                    <p class="description">
                        <?php echo iw_t(
                            'اضغط Ctrl للاختيار المتعدد. عدم الاختيار = جميع التصنيفات.',
                            'Hold Ctrl to select multiple. No selection = all categories.'
                        ); ?>
                    </p>
                </td>
            </tr>
        </table>
        <button class="button button-primary button-large" onclick="iwAutoGenerate()">
            <?php echo iw_t('توليد طلب الشراء', 'Generate Purchase Request'); ?>
        </button>
        <div id="pr-auto-result" style="margin-top:15px;"></div>
    </div>

    <!-- Pending -->
    <div id="pr-tab-pending" class="iw-tab-content" style="display:none;">
        <h2><?php echo iw_t('طلبات الشراء المعلقة (في انتظار اعتماد العميد)', 'Pending Purchase Requests (Awaiting Approval)'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo iw_t('رقم الطلب', 'Request No.'); ?></th>
                    <th><?php echo iw_t('التاريخ', 'Date'); ?></th>
                    <th><?php echo iw_t('الإجمالي التقديري', 'Est. Total'); ?></th>
                    <th><?php echo iw_t('ملاحظات', 'Notes'); ?></th>
                    <th><?php echo iw_t('إجراءات', 'Actions'); ?></th>
                </tr>
            </thead>
            <tbody id="pr-pending-table"></tbody>
        </table>
    </div>

    <!-- Approved -->
    <div id="pr-tab-approved" class="iw-tab-content" style="display:none;">
        <h2><?php echo iw_t('طلبات الشراء المعتمدة (جاهزة للطباعة)', 'Approved Purchase Requests (Ready to Print)'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo iw_t('رقم الطلب', 'Request No.'); ?></th>
                    <th><?php echo iw_t('التاريخ', 'Date'); ?></th>
                    <th><?php echo iw_t('الإجمالي التقديري', 'Est. Total'); ?></th>
                    <th><?php echo iw_t('المعتمد', 'Approved By'); ?></th>
                    <th><?php echo iw_t('إجراءات', 'Actions'); ?></th>
                </tr>
            </thead>
            <tbody id="pr-approved-table"></tbody>
        </table>
    </div>

    <!-- All -->
    <div id="pr-tab-all" class="iw-tab-content" style="display:none;">
        <h2><?php echo iw_t('جميع طلبات الشراء', 'All Purchase Requests'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo iw_t('رقم الطلب', 'Request No.'); ?></th>
                    <th><?php echo iw_t('الحالة', 'Status'); ?></th>
                    <th><?php echo iw_t('التاريخ', 'Date'); ?></th>
                    <th><?php echo iw_t('الإجمالي التقديري', 'Est. Total'); ?></th>
                    <th><?php echo iw_t('إجراءات', 'Actions'); ?></th>
                </tr>
            </thead>
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
            iwAddPrItem();
        }
    });

    window.iwPrSwitchTab = function(tab) {
        $('.iw-tab-content').hide();
        $('.iw-tab').removeClass('active');
        $('#pr-tab-'+tab).show();
        $('[onclick="iwPrSwitchTab(\''+tab+'\')"]').addClass('active');
        if (tab === 'pending')  loadPrOrders('pending');
        if (tab === 'approved') loadPrOrders('approved');
        if (tab === 'all')      loadPrOrders('');
    };

    window.iwAddPrItem = function() {
        var opts = '<option value="">' + iwT('اختر الصنف', 'Select item') + '</option>';
        products.forEach(function(p) {
            opts += '<option value="'+p.id+'" data-stock="'+p.current_stock+'" data-min="'+p.min_stock+'" data-max="'+p.max_stock+'" data-price="'+p.price+'">'+p.name+'</option>';
        });
        var row = '<tr>'
            + '<td><select class="pr-product regular-text" onchange="iwUpdatePrRow(this)">'+opts+'</select></td>'
            + '<td class="pr-stock">-</td>'
            + '<td class="pr-min">-</td>'
            + '<td class="pr-max">-</td>'
            + '<td><input type="number" class="pr-qty" min="1" value="1" oninput="iwUpdatePrRowTotal(this)"></td>'
            + '<td class="pr-last-price">-</td>'
            + '<td><input type="number" class="pr-price" min="0" step="0.01" value="0" oninput="iwUpdatePrRowTotal(this)"></td>'
            + '<td><button type="button" class="button iw-btn-danger" onclick="jQuery(this).closest(\'tr\').remove();iwRecalcCreateTotal()">'
            + iwT('حذف', 'Delete') + '</button></td>'
            + '</tr>';
        var $row = $(row);
        $('#pr-items-body').append($row);
        if (typeof iwInitSelect2 === 'function') iwInitSelect2($row);
    };

    window.iwUpdatePrRow = function(sel) {
        var opt = $(sel).find(':selected');
        var tr  = $(sel).closest('tr');
        tr.find('.pr-stock').text(opt.data('stock') || 0);
        tr.find('.pr-min').text(opt.data('min') || 0);
        tr.find('.pr-max').text(opt.data('max') || 0);
        var needed = (opt.data('max') || 0) - (opt.data('stock') || 0);
        if (needed < 1) needed = 1;
        tr.find('.pr-qty').val(needed);
        var lastPrice = opt.data('price') || 0;
        tr.find('.pr-last-price').text(parseFloat(lastPrice).toFixed(2));
        tr.find('.pr-price').val(lastPrice);
        iwRecalcCreateTotal();
    };

    window.iwUpdatePrRowTotal = function() { iwRecalcCreateTotal(); };

    window.iwRecalcCreateTotal = function() {
        var total = 0;
        $('#pr-items-body tr').each(function() {
            var qty   = parseFloat($(this).find('.pr-qty').val())   || 0;
            var price = parseFloat($(this).find('.pr-price').val()) || 0;
            total += qty * price;
        });
        $('#pr-create-grand-total').text(total.toFixed(2));
    };

    $('#iw-pr-form').on('submit', function(e) {
        e.preventDefault();
        var items = [];
        $('#pr-items-body tr').each(function() {
            var pid   = $(this).find('.pr-product').val();
            var qty   = $(this).find('.pr-qty').val();
            var price = $(this).find('.pr-price').val();
            if (pid && qty > 0) items.push({product_id: pid, quantity: qty, estimated_price: price});
        });
        if (!items.length) { alert(iwT('يجب إضافة أصناف', 'Please add at least one item.')); return; }
        $.post(iwAdmin.ajaxurl, {action: 'iw_create_purchase_request', nonce: iwAdmin.nonce, items: JSON.stringify(items), notes: $('#pr_notes').val()}, function(r) {
            alert(r.data.message);
            if (r.success) { $('#iw-pr-form')[0].reset(); $('#pr-items-body').html(''); iwAddPrItem(); $('#pr-create-grand-total').text('0.00'); }
        });
    });

    // Grand total row under create form
    $('#iw-pr-form').after(
        '<p style="margin-top:8px;font-weight:bold;font-size:15px;">'
        + iwT('الإجمالي التقديري', 'Estimated Total') + ': '
        + '<span id="pr-create-grand-total">0.00</span>'
        + '</p>'
    );

    window.iwAutoGenerate = function() {
        var categories = $('#auto-gen-category').val() || [];
        var category   = Array.isArray(categories) ? categories.join(',') : categories;
        $.post(iwAdmin.ajaxurl, {action: 'iw_auto_generate_purchase_requests', nonce: iwAdmin.nonce, category: category}, function(r) {
            if (!r.success) { alert(iwT('حدث خطأ', 'An error occurred.')); return; }
            var d    = r.data;
            var html = '';
            if (d.created > 0) {
                html += '<div class="notice notice-success"><p><strong>' + d.message + '</strong></p></div>';
                if (d.items && d.items.length) {
                    html += '<table class="wp-list-table widefat fixed striped"><thead><tr>'
                        + '<th>' + iwT('الصنف', 'Product') + '</th>'
                        + '<th>' + iwT('الكمية المطلوبة', 'Qty') + '</th>'
                        + '<th>' + iwT('آخر سعر شراء', 'Last Price') + '</th>'
                        + '</tr></thead><tbody>';
                    var autoTotal = 0;
                    d.items.forEach(function(it) {
                        var lp = parseFloat(it.last_purchase_price || it.estimated_price);
                        autoTotal += it.quantity * lp;
                        html += '<tr><td>'+it.product_name+'</td><td>'+it.quantity+'</td><td>'+lp.toFixed(2)+'</td></tr>';
                    });
                    html += '<tr style="font-weight:bold;"><td colspan="2">' + iwT('الإجمالي التقديري', 'Estimated Total') + '</td><td>' + autoTotal.toFixed(2) + '</td></tr>';
                    html += '</tbody></table>';
                    html += '<p style="margin-top:10px;"><button class="button button-primary" onclick="iwViewPr('+d.request_id+')">'
                         + iwT('عرض وتعديل الطلب', 'View / Edit Request') + '</button></p>';
                }
            } else {
                html += '<div class="notice notice-warning"><p>' + d.message + '</p></div>';
            }
            if (d.skipped > 0) {
                html += '<p>' + iwT('تم تخطي ', 'Skipped ') + d.skipped + iwT(' أصناف لوجود طلبات شراء معلقة لها بالفعل', ' items — already have pending requests.') + '</p>';
            }
            $('#pr-auto-result').html(html);
        });
    };

    function loadPrOrders(status) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_purchase_requests', nonce: iwAdmin.nonce, status: status}, function(r) {
            if (!r.success) return;
            var target = status ? '#pr-'+status+'-table' : '#pr-all-table';
            var html   = '';
            var statusLabels = {
                pending:   iwT('معلق',   'Pending'),
                approved:  iwT('معتمد',  'Approved'),
                rejected:  iwT('مرفوض', 'Rejected'),
                completed: iwT('منفذ',   'Completed'),
            };
            r.data.forEach(function(o) {
                var badgeClass = o.status === 'approved' ? 'success' : o.status === 'pending' ? 'warning' : o.status === 'rejected' ? 'danger' : 'info';
                var statusBadge = '<span class="iw-badge iw-badge-' + badgeClass + '">' + (statusLabels[o.status] || o.status) + '</span>';
                var total = parseFloat(o.total_amount || 0).toFixed(2);
                html += '<tr>';
                html += '<td>' + o.request_number + '</td>';
                if (!status) html += '<td>' + statusBadge + '</td>';
                html += '<td>' + o.created_at + '</td>';
                html += '<td><strong>' + total + '</strong></td>';
                if (status === 'approved') html += '<td>' + (o.created_by_name || '-') + '</td>';
                if (status !== 'approved' && status) html += '<td>' + (o.notes || '-') + '</td>';
                html += '<td>';
                html += '<button class="button" onclick="iwViewPr('+o.id+')">' + iwT('عرض', 'View') + '</button>';
                if (o.status === 'pending')  html += ' <button class="button iw-btn-danger" onclick="iwDeletePr('+o.id+')">' + iwT('حذف', 'Delete') + '</button>';
                if (o.status === 'approved') html += ' <button class="button button-primary" onclick="iwPrintPr('+o.id+')">' + iwT('طباعة', 'Print') + '</button>';
                html += '</td></tr>';
            });
            $(target).html(html || '<tr><td colspan="6">' + iwT('لا توجد طلبات', 'No requests found.') + '</td></tr>');
        });
    }

    window.iwDeletePr = function(id) {
        if (!confirm(iwT('هل أنت متأكد من حذف هذا الطلب؟', 'Are you sure you want to delete this request?'))) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_delete_purchase_request', nonce: iwAdmin.nonce, request_id: id}, function(r) {
            alert(r.data.message);
            if (r.success) loadPrOrders('pending');
        });
    };

    window.iwViewPr = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_purchase_request', nonce: iwAdmin.nonce, request_id: id}, function(r) {
            if (!r.success) return;
            var o = r.data.request, items = r.data.items || [], sig = r.data.signature_url;
            var html = '<h2>' + iwT('طلب شراء رقم: ', 'Purchase Request No.: ') + o.request_number + '</h2>';

            // Items table
            html += '<table class="wp-list-table widefat fixed striped"><thead><tr>'
                  + '<th>' + iwT('الصنف', 'Product') + '</th>'
                  + '<th>' + iwT('المخزون الحالي', 'Stock') + '</th>'
                  + '<th>' + iwT('الحد الأدنى', 'Min') + '</th>'
                  + '<th>' + iwT('الحد الأقصى', 'Max') + '</th>'
                  + '<th>' + iwT('الكمية المطلوبة', 'Qty') + '</th>';
            if (o.status === 'pending') html += '<th>' + iwT('الكمية المعتمدة', 'Approved Qty') + '</th>';
            html += '<th>' + iwT('آخر سعر شراء', 'Last Price') + '</th>'
                  + '<th>' + iwT('السعر التقديري', 'Est. Price') + '</th>'
                  + '<th>' + iwT('الإجمالي', 'Total') + '</th>';
            if (o.status === 'pending') html += '<th>' + iwT('حذف', 'Del') + '</th>';
            html += '</tr></thead><tbody>';

            if (!items.length) {
                html += '<tr><td colspan="9" style="text-align:center;padding:20px;color:#999;">'
                      + iwT('لا توجد أصناف مسجلة في هذا الطلب', 'No items recorded in this request.')
                      + '</td></tr>';
            }

            var grandTotal = 0;
            items.forEach(function(it) {
                var lastPrice  = parseFloat(it.last_purchase_price || it.estimated_price);
                var estPrice   = parseFloat(it.estimated_price);
                var approvedQty = it.approved_quantity !== null ? parseInt(it.approved_quantity) : parseInt(it.quantity);
                var rowTotal   = (o.status === 'pending' ? parseInt(it.quantity) : approvedQty) * estPrice;
                grandTotal    += rowTotal;

                html += '<tr data-item-id="'+it.id+'">'
                      + '<td>'+it.product_name+'</td>'
                      + '<td>'+(it.current_stock||0)+'</td>'
                      + '<td>'+(it.min_stock||0)+'</td>'
                      + '<td>'+(it.max_stock||0)+'</td>'
                      + '<td>'+it.quantity+'</td>';

                if (o.status === 'pending') {
                    html += '<td><input type="number" class="pr-approve-qty" data-product="'+it.product_id+'"'
                         + ' value="'+it.quantity+'" min="0" data-price="'+estPrice+'" style="width:80px;"'
                         + ' oninput="iwRecalcModalTotal()"></td>';
                }
                html += '<td>' + lastPrice.toFixed(2) + '</td>';
                if (o.status === 'pending') {
                    html += '<td><input type="number" class="pr-edit-price" value="'+estPrice.toFixed(2)+'"'
                         + ' min="0" step="0.01" style="width:100px;" oninput="iwRecalcModalTotal()"></td>'
                         + '<td class="pr-row-total">'+rowTotal.toFixed(2)+'</td>'
                         + '<td><button class="button iw-btn-danger" onclick="iwDeletePrItem('+it.id+','+o.id+')">X</button></td>';
                } else {
                    html += '<td>'+estPrice.toFixed(2)+'</td>'
                         + '<td>'+rowTotal.toFixed(2)+'</td>';
                }
                html += '</tr>';
            });

            // Grand total row
            html += '<tr style="font-weight:bold;background:#f9f9f9;">'
                  + '<td colspan="' + (o.status === 'pending' ? '8' : '8') + '">'
                  + iwT('الإجمالي التقديري', 'Estimated Grand Total') + '</td>'
                  + '<td id="pr-modal-grand-total">' + grandTotal.toFixed(2) + '</td>';
            if (o.status === 'pending') html += '<td></td>';
            html += '</tr>';

            html += '</tbody></table>';

            // Prominent total display below table
            html += '<div style="margin:12px 0;padding:10px 16px;background:#eaf4fb;border-right:4px solid #2196F3;font-size:15px;font-weight:bold;">'
                  + iwT('الإجمالي التقديري الكلي: ', 'Estimated Grand Total: ')
                  + '<span id="pr-modal-total-display">' + grandTotal.toFixed(2) + '</span>'
                  + '</div>';

            if (sig && o.status !== 'pending') {
                html += '<div style="margin-top:15px;text-align:center;">'
                      + '<p><strong>' + iwT('توقيع المعتمد:', 'Approver Signature:') + '</strong></p>'
                      + '<img src="'+sig+'" style="max-height:100px;" /></div>';
            }

            if (o.status === 'pending') {
                html += '<div style="margin-top:15px;">'
                      + '<button class="button button-primary button-large" onclick="iwApprovePr('+o.id+')">' + iwT('اعتماد', 'Approve') + '</button> '
                      + '<button class="button iw-btn-danger button-large" onclick="iwRejectPr('+o.id+')">' + iwT('رفض', 'Reject') + '</button> '
                      + '<button class="button button-large" onclick="iwSavePrEdit('+o.id+')">' + iwT('حفظ التعديلات', 'Save Changes') + '</button>'
                      + '</div>';
            }
            if (o.status === 'approved') {
                html += '<div style="margin-top:15px;">'
                      + '<button class="button button-primary button-large" onclick="iwPrintPr('+o.id+')">' + iwT('طباعة', 'Print') + '</button>'
                      + '</div>';
            }

            // Admin: restore missing items
            <?php if (current_user_can('manage_options')): ?>
            if (iwAdmin.isAdmin && !items.length && o.status !== 'pending') {
                var adminOpts = '<option value="">' + iwT('اختر الصنف', 'Select') + '</option>';
                products.forEach(function(p) { adminOpts += '<option value="'+p.id+'" data-price="'+p.price+'">'+p.name+'</option>'; });
                html += '<div style="margin-top:20px;padding:15px;background:#fff3cd;border:1px solid #ffc107;border-radius:4px;">'
                      + '<p style="margin:0 0 10px;"><strong>⚠ ' + iwT('تحذير (للمدير فقط):', 'Warning (Admin only):') + '</strong> '
                      + iwT('هذا الطلب لا يحتوي على أصناف — أضف الأصناف لاستعادة البيانات.', 'This request has no items — add items to restore data.') + '</p>'
                      + '<table class="wp-list-table widefat fixed" id="pr-restore-body" style="margin-bottom:8px;"><thead><tr>'
                      + '<th>' + iwT('الصنف', 'Product') + '</th>'
                      + '<th>' + iwT('الكمية', 'Qty') + '</th>'
                      + '<th>' + iwT('السعر التقديري', 'Est. Price') + '</th>'
                      + '<th></th></tr></thead><tbody></tbody></table>'
                      + '<button class="button" onclick="iwAddRestoreItem()">+ ' + iwT('إضافة صنف', 'Add Item') + '</button> '
                      + '<button class="button button-primary" style="margin-top:5px;" onclick="iwSaveRestoreItems('+o.id+')">'
                      + iwT('حفظ الأصناف', 'Save Items') + '</button>'
                      + '</div>';
                html += '<script>window._prRestoreOpts = \''+adminOpts+'\';<\/script>';
            }
            <?php endif; ?>

            $('#iw-pr-modal-body').html(html);
            $('#iw-pr-modal').show();
        });
    };

    // Recalculate grand total while editing pending request
    window.iwRecalcModalTotal = function() {
        var total = 0;
        $('#iw-pr-modal-body tbody tr[data-item-id]').each(function() {
            var qtyInput   = $(this).find('.pr-approve-qty');
            var priceInput = $(this).find('.pr-edit-price');
            if (qtyInput.length) {
                var qty   = parseFloat(qtyInput.val())   || 0;
                var price = parseFloat(priceInput.val()) || 0;
                var rowT  = qty * price;
                $(this).find('.pr-row-total').text(rowT.toFixed(2));
                total += rowT;
            }
        });
        $('#pr-modal-grand-total, #pr-modal-total-display').text(total.toFixed(2));
    };

    window.iwAddRestoreItem = function() {
        var opts = window._prRestoreOpts || '<option value="">' + iwT('اختر', 'Select') + '</option>';
        var row  = '<tr>'
                 + '<td><select class="rpr-product regular-text" style="min-width:200px;">'+opts+'</select></td>'
                 + '<td><input type="number" class="rpr-qty" min="1" value="1" style="width:70px;"></td>'
                 + '<td><input type="number" class="rpr-price" min="0" step="0.01" value="0" style="width:90px;"></td>'
                 + '<td><button type="button" class="button iw-btn-danger" onclick="jQuery(this).closest(\'tr\').remove()">X</button></td>'
                 + '</tr>';
        jQuery('#pr-restore-body tbody').append(row);
    };

    window.iwSaveRestoreItems = function(id) {
        var items = [];
        jQuery('#pr-restore-body tbody tr').each(function() {
            var pid   = jQuery(this).find('.rpr-product').val();
            var qty   = parseInt(jQuery(this).find('.rpr-qty').val(), 10);
            var price = parseFloat(jQuery(this).find('.rpr-price').val());
            if (pid && qty > 0) items.push({product_id: pid, quantity: qty, approved_quantity: qty, estimated_price: price});
        });
        if (!items.length) { alert(iwT('أضف صنفاً واحداً على الأقل', 'Add at least one item.')); return; }
        jQuery.post(iwAdmin.ajaxurl, {action: 'iw_update_purchase_request', nonce: iwAdmin.nonce, request_id: id, items: JSON.stringify(items)}, function(r) {
            alert(r.data.message);
            if (r.success) iwViewPr(id);
        });
    };

    window.iwDeletePrItem = function(itemId, requestId) {
        if (!confirm(iwT('هل أنت متأكد من حذف هذا الصنف من الطلب؟', 'Delete this item from the request?'))) return;
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
        $('#iw-pr-modal-body tbody tr[data-item-id]').each(function() {
            var $qtyInput   = $(this).find('.pr-approve-qty');
            var $priceInput = $(this).find('.pr-edit-price');
            if ($qtyInput.length) {
                items.push({
                    product_id:       $qtyInput.data('product'),
                    quantity:         $qtyInput.val(),
                    approved_quantity: $qtyInput.val(),
                    estimated_price:  $priceInput.val() || $qtyInput.data('price'),
                });
            }
        });
        if (!items.length) { alert(iwT('لا توجد أصناف لحفظها — يجب إضافة صنف واحد على الأقل', 'No items to save. Add at least one item.')); return; }
        $.post(iwAdmin.ajaxurl, {action: 'iw_update_purchase_request', nonce: iwAdmin.nonce, request_id: id, items: JSON.stringify(items)}, function(r) {
            alert(r.data.message);
            if (r.success) iwViewPr(id);
        });
    };

    window.iwApprovePr = function(id) {
        if (!confirm(iwT('هل أنت متأكد من اعتماد هذا الطلب؟', 'Approve this purchase request?'))) return;
        var items = [];
        $('#iw-pr-modal-body tbody tr[data-item-id]').each(function() {
            var $qtyInput   = $(this).find('.pr-approve-qty');
            var $priceInput = $(this).find('.pr-edit-price');
            if ($qtyInput.length) {
                items.push({
                    product_id:       $qtyInput.data('product'),
                    quantity:         $qtyInput.val(),
                    approved_quantity: $qtyInput.val(),
                    estimated_price:  $priceInput.val() || $qtyInput.data('price'),
                });
            }
        });
        if (!items.length) { alert(iwT('لا توجد أصناف في الطلب — لا يمكن الاعتماد', 'No items in request — cannot approve.')); return; }
        $.post(iwAdmin.ajaxurl, {action: 'iw_update_purchase_request', nonce: iwAdmin.nonce, request_id: id, items: JSON.stringify(items)}, function() {
            $.post(iwAdmin.ajaxurl, {action: 'iw_approve_purchase_request', nonce: iwAdmin.nonce, request_id: id}, function(r) {
                alert(r.data.message);
                if (r.success) { $('#iw-pr-modal').hide(); loadPrOrders('pending'); }
            });
        });
    };

    window.iwRejectPr = function(id) {
        var reason = prompt(iwT('سبب الرفض:', 'Rejection reason:'));
        if (reason === null) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_reject_purchase_request', nonce: iwAdmin.nonce, request_id: id, rejection_reason: reason}, function(r) {
            alert(r.data.message);
            if (r.success) $('#iw-pr-modal').hide();
        });
    };

    window.iwPrintPr = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_purchase_request', nonce: iwAdmin.nonce, request_id: id}, function(r) {
            if (!r.success) return;
            var o = r.data.request, items = r.data.items || [], sig = r.data.signature_url;
            var printContent = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
            printContent += '<h2 style="text-align:center;">' + iwT('طلب شراء رقم: ', 'Purchase Request No.: ') + o.request_number + '</h2>';
            printContent += '<p><strong>' + iwT('التاريخ:', 'Date:') + '</strong> ' + o.created_at + '</p>';
            printContent += '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;">';
            printContent += '<tr style="background:#f0f0f0;">'
                          + '<th>' + iwT('الصنف', 'Product') + '</th>'
                          + '<th>' + iwT('الكمية المطلوبة', 'Qty') + '</th>'
                          + '<th>' + iwT('آخر سعر شراء', 'Last Price') + '</th>'
                          + '<th>' + iwT('السعر التقديري', 'Est. Price') + '</th>'
                          + '<th>' + iwT('الإجمالي', 'Total') + '</th>'
                          + '</tr>';
            var grandTotal = 0;
            items.forEach(function(it) {
                var qty       = it.approved_quantity !== null ? it.approved_quantity : it.quantity;
                var total     = qty * parseFloat(it.estimated_price);
                grandTotal   += total;
                var lastPrice = parseFloat(it.last_purchase_price || it.estimated_price).toFixed(2);
                printContent += '<tr><td>'+it.product_name+'</td><td>'+qty+'</td><td>'+lastPrice+'</td><td>'+parseFloat(it.estimated_price).toFixed(2)+'</td><td>'+total.toFixed(2)+'</td></tr>';
            });
            printContent += '<tr style="font-weight:bold;">'
                          + '<td colspan="4">' + iwT('الإجمالي التقديري', 'Estimated Total') + '</td>'
                          + '<td>' + grandTotal.toFixed(2) + '</td></tr>';
            printContent += '</table>';
            if (sig) {
                printContent += '<div style="margin-top:40px;text-align:left;">'
                              + '<p><strong>' + iwT('توقيع عميد المعهد / المدير:', 'Dean / Director Signature:') + '</strong></p>'
                              + '<img src="'+sig+'" style="max-height:80px;" />'
                              + '</div>';
            }
            var w = window.open('','','width=800,height=600');
            w.document.write('<html dir="<?php echo $dir; ?>"><head><title>' + iwT('طلب شراء', 'Purchase Request') + '</title><style>body{font-family:Arial,sans-serif;padding:20px;}</style></head><body>'+printContent+'</body></html>');
            w.document.close();
            w.print();
        });
    };
});
</script>
