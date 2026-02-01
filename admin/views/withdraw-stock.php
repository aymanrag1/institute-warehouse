<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>إذن صرف</h1>

    <div class="iw-tabs">
        <button class="iw-tab active" onclick="iwSwitchTab('create')">إنشاء إذن صرف</button>
        <button class="iw-tab" onclick="iwSwitchTab('pending')">أوامر معلقة</button>
        <button class="iw-tab" onclick="iwSwitchTab('approved')">أوامر معتمدة (جاهزة للطباعة)</button>
        <button class="iw-tab" onclick="iwSwitchTab('all')">جميع الأوامر</button>
    </div>

    <!-- Create Withdrawal Order -->
    <div id="tab-create" class="iw-tab-content">
        <h2>إنشاء إذن صرف جديد</h2>
        <form id="iw-withdrawal-form">
            <table class="form-table">
                <tr>
                    <th>القسم *</th>
                    <td><select id="wd_department_id" class="regular-text" required><option value="">اختر القسم</option></select></td>
                </tr>
                <tr>
                    <th>الموظف *</th>
                    <td><select id="wd_employee_id" class="regular-text" required><option value="">اختر الموظف</option></select></td>
                </tr>
                <tr><th>ملاحظات</th><td><textarea id="wd_notes" class="large-text" rows="2"></textarea></td></tr>
            </table>

            <h3>الأصناف المراد صرفها</h3>
            <table class="wp-list-table widefat fixed" id="wd-items-table">
                <thead>
                    <tr><th>الصنف</th><th>المخزون المتاح</th><th>الكمية المطلوبة</th><th>إجراء</th></tr>
                </thead>
                <tbody id="wd-items-body"></tbody>
            </table>
            <button type="button" class="button" onclick="iwAddWdItem()" style="margin-top:10px;">+ إضافة صنف</button>
            <br><br>
            <button type="submit" class="button button-primary button-large">إرسال للاعتماد</button>
        </form>
    </div>

    <!-- Pending Orders (for Dean) -->
    <div id="tab-pending" class="iw-tab-content" style="display:none;">
        <h2>أوامر الصرف المعلقة (في انتظار الاعتماد)</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>رقم الإذن</th><th>القسم</th><th>الموظف</th><th>التاريخ</th><th>إجراءات</th></tr></thead>
            <tbody id="wd-pending-table"></tbody>
        </table>
    </div>

    <!-- Approved Orders (for printing) -->
    <div id="tab-approved" class="iw-tab-content" style="display:none;">
        <h2>أوامر الصرف المعتمدة (جاهزة للطباعة والتنفيذ)</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>رقم الإذن</th><th>القسم</th><th>الموظف</th><th>المعتمد</th><th>التاريخ</th><th>إجراءات</th></tr></thead>
            <tbody id="wd-approved-table"></tbody>
        </table>
    </div>

    <!-- All Orders -->
    <div id="tab-all" class="iw-tab-content" style="display:none;">
        <h2>جميع أوامر الصرف</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>رقم الإذن</th><th>القسم</th><th>الموظف</th><th>الحالة</th><th>التاريخ</th><th>إجراءات</th></tr></thead>
            <tbody id="wd-all-table"></tbody>
        </table>
    </div>

    <!-- Order Detail / Approval Modal -->
    <div id="iw-wd-modal" class="iw-modal" style="display:none;">
        <div class="iw-modal-content iw-modal-large">
            <span class="iw-modal-close" onclick="$('#iw-wd-modal').hide()">&times;</span>
            <div id="iw-wd-modal-body"></div>
        </div>
    </div>

    <!-- Print Container -->
    <div id="iw-print-container" style="display:none;"></div>
</div>

<script>
jQuery(document).ready(function($) {
    var products = [], departments = [], employees = [];

    // Load dropdowns
    $.post(iwAdmin.ajaxurl, {action: 'iw_get_products_list', nonce: iwAdmin.nonce}, function(r) {
        if(r.success) {
            products = r.data;
            console.log('Products loaded:', r.data.length);
            // Now add the first item row since products are ready
            iwAddWdItem();
        }
        else console.log('Products error:', r);
    });
    $.post(iwAdmin.ajaxurl, {action: 'iw_get_departments', nonce: iwAdmin.nonce}, function(r) {
        if(r.success) {
            departments = r.data;
            var html = '<option value="">اختر القسم</option>';
            r.data.forEach(function(d) { html += '<option value="'+d.id+'">'+d.name+'</option>'; });
            $('#wd_department_id').html(html);
            if (typeof iwRefreshSelect2 === 'function') iwRefreshSelect2('#wd_department_id');
        }
    });

    // Load employees when department changes
    $(document).on('change', '#wd_department_id', function() {
        var deptId = $(this).val();
        if (!deptId) {
            $('#wd_employee_id').html('<option value="">اختر الموظف</option>');
            if (typeof iwRefreshSelect2 === 'function') iwRefreshSelect2('#wd_employee_id');
            return;
        }
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_employees_by_dept', nonce: iwAdmin.nonce, department_id: deptId}, function(r) {
            if(r.success) {
                var html = '<option value="">اختر الموظف</option>';
                r.data.forEach(function(e) { html += '<option value="'+e.id+'">'+e.name+'</option>'; });
                $('#wd_employee_id').html(html);
                if (typeof iwRefreshSelect2 === 'function') iwRefreshSelect2('#wd_employee_id');
            }
        });
    });

    window.iwAddWdItem = function() {
        var opts = '<option value="">اختر الصنف</option>';
        products.forEach(function(p) { opts += '<option value="'+p.id+'" data-stock="'+(p.current_stock||0)+'">'+p.name+' ('+(p.current_stock||0)+' '+(p.unit||'')+')</option>'; });
        var row = '<tr><td><select class="wd-product regular-text" onchange="iwUpdateStock(this)">'+opts+'</select></td>';
        row += '<td class="wd-available">-</td>';
        row += '<td><input type="number" class="wd-qty" min="1" value="1"></td>';
        row += '<td><button type="button" class="button iw-btn-danger" onclick="$(this).closest(\'tr\').remove()">حذف</button></td></tr>';
        var $row = $(row);
        $('#wd-items-body').append($row);
        if (typeof iwInitSelect2 === 'function') iwInitSelect2($row);
    };

    window.iwUpdateStock = function(sel) {
        var stock = $(sel).find(':selected').data('stock') || 0;
        $(sel).closest('tr').find('.wd-available').text(stock);
    };

    // Submit withdrawal order
    $('#iw-withdrawal-form').on('submit', function(e) {
        e.preventDefault();
        var items = [];
        $('#wd-items-body tr').each(function() {
            var pid = $(this).find('.wd-product').val();
            var qty = $(this).find('.wd-qty').val();
            if (pid && qty > 0) items.push({product_id: pid, quantity: qty});
        });
        if (!items.length) { alert('يجب إضافة أصناف'); return; }

        $.post(iwAdmin.ajaxurl, {
            action: 'iw_create_withdrawal_order', nonce: iwAdmin.nonce,
            department_id: $('#wd_department_id').val(),
            employee_id: $('#wd_employee_id').val(),
            notes: $('#wd_notes').val(),
            items: JSON.stringify(items)
        }, function(res) {
            alert(res.data.message);
            if (res.success) { $('#iw-withdrawal-form')[0].reset(); $('#wd-items-body').html(''); }
        });
    });

    // Tabs
    window.iwSwitchTab = function(tab) {
        $('.iw-tab-content').hide();
        $('.iw-tab').removeClass('active');
        $('#tab-'+tab).show();
        $('[onclick="iwSwitchTab(\''+tab+'\')"]').addClass('active');
        if (tab === 'pending') loadOrders('pending');
        if (tab === 'approved') loadOrders('approved');
        if (tab === 'all') loadOrders('');
    };

    function loadOrders(status) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_withdrawal_orders', nonce: iwAdmin.nonce, status: status}, function(r) {
            if (!r.success) return;
            var target = status ? '#wd-'+status+'-table' : '#wd-all-table';
            var html = '';
            r.data.forEach(function(o) {
                var statusBadge = getStatusBadge(o.status);
                html += '<tr><td>'+o.order_number+'</td><td>'+(o.department_name||'-')+'</td><td>'+(o.employee_name||'-')+'</td>';
                if (!status) html += '<td>'+statusBadge+'</td>';
                if (status === 'approved') html += '<td>'+(o.approved_by ? 'معتمد' : '-')+'</td>';
                html += '<td>'+o.created_at+'</td>';
                html += '<td><button class="button" onclick="iwViewOrder('+o.id+')">عرض</button>';
                if (o.status === 'approved') html += ' <button class="button button-primary" onclick="iwPrintOrder('+o.id+')">طباعة</button>';
                if (o.status === 'approved') html += ' <button class="button" style="background:#46b450;color:#fff;" onclick="iwCompleteOrder('+o.id+')">تنفيذ الصرف</button>';
                html += '</td></tr>';
            });
            $(target).html(html || '<tr><td colspan="6">لا توجد أوامر</td></tr>');
        });
    }

    function getStatusBadge(s) {
        var map = {pending:'معلق',approved:'معتمد',rejected:'مرفوض',completed:'منفذ'};
        var cls = {pending:'warning',approved:'success',rejected:'danger',completed:'info'};
        return '<span class="iw-badge iw-badge-'+(cls[s]||'')+'">'+( map[s]||s)+'</span>';
    }

    // View order detail
    window.iwViewOrder = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_withdrawal_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            if (!r.success) return;
            var o = r.data.order, items = r.data.items, sig = r.data.signature_url;
            var html = '<h2>إذن صرف رقم: '+o.order_number+'</h2>';
            html += '<p><strong>القسم:</strong> '+(o.department_name||'-')+' | <strong>الموظف:</strong> '+(o.employee_name||'-')+' | <strong>الحالة:</strong> '+getStatusBadge(o.status)+'</p>';

            html += '<table class="wp-list-table widefat fixed striped"><thead><tr><th>الصنف</th><th>الوحدة</th><th>الكمية المطلوبة</th>';
            if (o.status === 'pending') html += '<th>الكمية المعتمدة</th>';
            else if (o.status !== 'pending' && items[0] && items[0].approved_quantity !== null) html += '<th>الكمية المعتمدة</th>';
            html += '</tr></thead><tbody>';

            items.forEach(function(it) {
                html += '<tr><td>'+it.product_name+'</td><td>'+(it.product_unit||'-')+'</td>';
                html += '<td>'+it.quantity+'</td>';
                if (o.status === 'pending') {
                    html += '<td><input type="number" class="wd-approve-qty" data-product="'+it.product_id+'" value="'+it.quantity+'" min="0"></td>';
                } else if (it.approved_quantity !== null) {
                    html += '<td>'+it.approved_quantity+'</td>';
                }
                html += '</tr>';
            });
            html += '</tbody></table>';

            if (sig && o.status !== 'pending') {
                html += '<div style="margin-top:15px;text-align:center;"><p><strong>توقيع المعتمد:</strong></p>';
                html += '<img src="'+sig+'" style="max-height:100px;" /></div>';
            }

            if (o.status === 'pending') {
                html += '<div style="margin-top:15px;">';
                html += '<button class="button button-primary button-large" onclick="iwApproveOrder('+o.id+')">اعتماد</button> ';
                html += '<button class="button iw-btn-danger button-large" onclick="iwRejectOrder('+o.id+')">رفض</button> ';
                html += '<button class="button button-large" onclick="iwSaveOrderEdit('+o.id+')">حفظ التعديلات</button>';
                html += '</div>';
            }

            if (o.status === 'approved') {
                html += '<div style="margin-top:15px;">';
                html += '<button class="button button-primary button-large" onclick="iwPrintOrder('+o.id+')">طباعة</button> ';
                html += '<button class="button button-large" style="background:#46b450;color:#fff;" onclick="iwCompleteOrder('+o.id+')">تنفيذ الصرف</button>';
                html += '</div>';
            }

            $('#iw-wd-modal-body').html(html);
            $('#iw-wd-modal').show();
        });
    };

    // Save edits (Dean)
    window.iwSaveOrderEdit = function(id) {
        var items = [];
        $('.wd-approve-qty').each(function() {
            items.push({product_id: $(this).data('product'), quantity: $(this).val(), approved_quantity: $(this).val()});
        });
        $.post(iwAdmin.ajaxurl, {action: 'iw_update_withdrawal_order', nonce: iwAdmin.nonce, order_id: id, items: JSON.stringify(items)}, function(r) {
            alert(r.data.message);
        });
    };

    // Approve
    window.iwApproveOrder = function(id) {
        if (!confirm('هل أنت متأكد من اعتماد هذا الإذن؟')) return;
        // Save edits first then approve
        var items = [];
        $('.wd-approve-qty').each(function() {
            items.push({product_id: $(this).data('product'), quantity: $(this).val(), approved_quantity: $(this).val()});
        });
        $.post(iwAdmin.ajaxurl, {action: 'iw_update_withdrawal_order', nonce: iwAdmin.nonce, order_id: id, items: JSON.stringify(items)}, function() {
            $.post(iwAdmin.ajaxurl, {action: 'iw_approve_withdrawal_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
                alert(r.data.message);
                if (r.success) { $('#iw-wd-modal').hide(); loadOrders('pending'); }
            });
        });
    };

    // Reject
    window.iwRejectOrder = function(id) {
        var reason = prompt('سبب الرفض:');
        if (reason === null) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_reject_withdrawal_order', nonce: iwAdmin.nonce, order_id: id, rejection_reason: reason}, function(r) {
            alert(r.data.message);
            if (r.success) { $('#iw-wd-modal').hide(); loadOrders('pending'); }
        });
    };

    // Complete withdrawal
    window.iwCompleteOrder = function(id) {
        if (!confirm('هل أنت متأكد من تنفيذ الصرف؟ سيتم خصم الكميات من المخزون.')) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_complete_withdrawal_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            alert(r.data.message);
            if (r.success) { $('#iw-wd-modal').hide(); loadOrders('approved'); }
        });
    };

    // Print
    window.iwPrintOrder = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_withdrawal_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            if (!r.success) return;
            var o = r.data.order, items = r.data.items, sig = r.data.signature_url;
            var printContent = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
            printContent += '<h2 style="text-align:center;">إذن صرف رقم: '+o.order_number+'</h2>';
            printContent += '<p><strong>القسم:</strong> '+(o.department_name||'-')+' | <strong>الموظف:</strong> '+(o.employee_name||'-')+'</p>';
            printContent += '<p><strong>التاريخ:</strong> '+o.created_at+'</p>';
            printContent += '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;">';
            printContent += '<tr style="background:#f0f0f0;"><th>الصنف</th><th>الوحدة</th><th>الكمية</th></tr>';
            items.forEach(function(it) {
                var qty = it.approved_quantity !== null ? it.approved_quantity : it.quantity;
                printContent += '<tr><td>'+it.product_name+'</td><td>'+(it.product_unit||'-')+'</td><td>'+qty+'</td></tr>';
            });
            printContent += '</table>';
            if (sig) {
                printContent += '<div style="margin-top:30px;text-align:left;"><p><strong>توقيع المعتمد:</strong></p>';
                printContent += '<img src="'+sig+'" style="max-height:80px;" /></div>';
            }
            var w = window.open('','','width=800,height=600');
            w.document.write('<html dir="rtl"><head><title>إذن صرف</title><style>body{font-family:Arial,sans-serif;padding:20px;}</style></head><body>'+printContent+'</body></html>');
            w.document.close();
            w.print();
        });
    };

    // First item row is added after products load (see products AJAX callback above)
});
</script>
