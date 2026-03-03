<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>إذن صرف</h1>

    <div class="iw-tabs">
        <button class="iw-tab active" onclick="iwSwitchTab('create')">إنشاء إذن صرف</button>
        <button class="iw-tab" onclick="iwSwitchTab('custody')">إذن صرف عهدة</button>
        <button class="iw-tab" onclick="iwSwitchTab('pending')">أوامر معلقة</button>
        <button class="iw-tab" onclick="iwSwitchTab('approved')">أوامر معتمدة</button>
        <button class="iw-tab" onclick="iwSwitchTab('all')">جميع الأوامر</button>
    </div>

    <!-- Create Withdrawal Order -->
    <div id="tab-create" class="iw-tab-content">
        <h2>إنشاء إذن صرف جديد</h2>
        <form id="iw-withdrawal-form">
            <table class="form-table">
                <tr>
                    <th>القسم</th>
                    <td><select id="wd_department_id" class="regular-text"><option value="">— اختياري —</option></select></td>
                </tr>
                <tr>
                    <th>الموظف</th>
                    <td><select id="wd_employee_id" class="regular-text"><option value="">— اختياري —</option></select></td>
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

    <!-- Create Custody Withdrawal Order -->
    <div id="tab-custody" class="iw-tab-content" style="display:none;">
        <h2>إنشاء إذن صرف عهدة</h2>
        <div class="notice notice-info"><p><strong>ملاحظة:</strong> إذن صرف العهدة يستخدم لصرف العهدة المستردة مرة أخرى ولا يتم خصم الكميات من الرصيد.</p></div>
        <form id="iw-custody-form">
            <table class="form-table">
                <tr>
                    <th>القسم</th>
                    <td><select id="cust_department_id" class="regular-text"><option value="">— اختياري —</option></select></td>
                </tr>
                <tr>
                    <th>الموظف</th>
                    <td><select id="cust_employee_id" class="regular-text"><option value="">— اختياري —</option></select></td>
                </tr>
                <tr><th>ملاحظات</th><td><textarea id="cust_notes" class="large-text" rows="2"></textarea></td></tr>
            </table>

            <h3>أصناف العهدة</h3>
            <table class="wp-list-table widefat fixed" id="cust-items-table">
                <thead>
                    <tr><th>الصنف</th><th>الكمية</th><th>اسم الموظف المصروف له</th><th>إجراء</th></tr>
                </thead>
                <tbody id="cust-items-body"></tbody>
            </table>
            <button type="button" class="button" onclick="iwAddCustItem()" style="margin-top:10px;">+ إضافة صنف</button>
            <br><br>
            <button type="submit" class="button button-primary button-large">إرسال للاعتماد</button>
        </form>
    </div>

    <!-- Pending Orders (for Dean) -->
    <div id="tab-pending" class="iw-tab-content" style="display:none;">
        <h2>أوامر الصرف المعلقة (في انتظار الاعتماد)</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>رقم الإذن</th><th>النوع</th><th>القسم</th><th>الموظف</th><th>التاريخ</th><th>إجراءات</th></tr></thead>
            <tbody id="wd-pending-table"></tbody>
        </table>
    </div>

    <!-- Approved Orders (for printing) -->
    <div id="tab-approved" class="iw-tab-content" style="display:none;">
        <h2>أوامر الصرف المعتمدة (جاهزة للطباعة والتنفيذ)</h2>
        <div style="margin-bottom:10px;">
            <button class="button" onclick="iwWdSelectAll('approved')">تحديد الكل</button>
            <button class="button button-primary" onclick="iwBulkPrintOrders()">طباعة المحدد</button>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th style="width:30px;"><input type="checkbox" onchange="$('.wd-approved-check').prop('checked',this.checked)"></th><th>رقم الإذن</th><th>النوع</th><th>القسم</th><th>الموظف</th><th>التاريخ</th><th>إجراءات</th></tr></thead>
            <tbody id="wd-approved-table"></tbody>
        </table>
    </div>

    <!-- All Orders -->
    <div id="tab-all" class="iw-tab-content" style="display:none;">
        <h2>جميع أوامر الصرف</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>رقم الإذن</th><th>النوع</th><th>القسم</th><th>الموظف</th><th>الحالة</th><th>التاريخ</th><th>إجراءات</th></tr></thead>
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
            iwAddWdItem();
            iwAddCustItem();
        }
        else console.log('Products error:', r);
    });
    $.post(iwAdmin.ajaxurl, {action: 'iw_get_departments', nonce: iwAdmin.nonce}, function(r) {
        if(r.success) {
            departments = r.data;
            var html = '<option value="">اختر القسم</option>';
            r.data.forEach(function(d) { html += '<option value="'+d.id+'">'+d.name+'</option>'; });
            $('#wd_department_id, #cust_department_id').html(html);
            if (typeof iwRefreshSelect2 === 'function') {
                iwRefreshSelect2('#wd_department_id');
                iwRefreshSelect2('#cust_department_id');
            }
        }
    });

    // Load employees when department changes
    $(document).on('change', '#wd_department_id, #cust_department_id', function() {
        var deptId = $(this).val();
        var targetId = $(this).attr('id') === 'wd_department_id' ? '#wd_employee_id' : '#cust_employee_id';
        if (!deptId) {
            $(targetId).html('<option value="">اختر الموظف</option>');
            if (typeof iwRefreshSelect2 === 'function') iwRefreshSelect2(targetId);
            return;
        }
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_employees_by_dept', nonce: iwAdmin.nonce, department_id: deptId}, function(r) {
            if(r.success) {
                var html = '<option value="">اختر الموظف</option>';
                r.data.forEach(function(e) { html += '<option value="'+e.id+'">'+e.name+'</option>'; });
                $(targetId).html(html);
                if (typeof iwRefreshSelect2 === 'function') iwRefreshSelect2(targetId);
            }
        });
    });

    window.iwAddWdItem = function() {
        var opts = '<option value="">اختر الصنف</option>';
        products.forEach(function(p) { opts += '<option value="'+p.id+'" data-stock="'+(p.current_stock||0)+'">'+p.name+' ('+(p.current_stock||0)+' '+(p.unit||'')+')</option>'; });
        var row = '<tr><td><select class="wd-product regular-text" onchange="iwUpdateStock(this)">'+opts+'</select></td>';
        row += '<td class="wd-available">-</td>';
        row += '<td><input type="number" class="wd-qty" min="1" value="1" onchange="iwValidateQty(this)"></td>';
        row += '<td><button type="button" class="button iw-btn-danger" onclick="$(this).closest(\'tr\').remove()">حذف</button></td></tr>';
        var $row = $(row);
        $('#wd-items-body').append($row);
        if (typeof iwInitSelect2 === 'function') iwInitSelect2($row);
    };

    window.iwAddCustItem = function() {
        var opts = '<option value="">اختر الصنف</option>';
        products.forEach(function(p) { opts += '<option value="'+p.id+'">'+p.name+'</option>'; });
        var row = '<tr><td><select class="cust-product regular-text">'+opts+'</select></td>';
        row += '<td><input type="number" class="cust-qty" min="1" value="1"></td>';
        row += '<td><input type="text" class="cust-emp-name regular-text" placeholder="اسم الموظف المصروف له"></td>';
        row += '<td><button type="button" class="button iw-btn-danger" onclick="$(this).closest(\'tr\').remove()">حذف</button></td></tr>';
        var $row = $(row);
        $('#cust-items-body').append($row);
        if (typeof iwInitSelect2 === 'function') iwInitSelect2($row);
    };

    window.iwUpdateStock = function(sel) {
        var stock = parseInt($(sel).find(':selected').data('stock')) || 0;
        var $row = $(sel).closest('tr');
        var $available = $row.find('.wd-available');
        var $qty = $row.find('.wd-qty');

        if (!$(sel).val()) {
            $available.text('-').css('color', '');
            $qty.prop('disabled', false).removeAttr('max');
            return;
        }

        if (stock <= 0) {
            $available.html('<span style="color:red;font-weight:bold;">0 - لا يوجد رصيد!</span>');
            $qty.prop('disabled', true).val(0);
        } else {
            $available.html('<span style="color:green;font-weight:bold;">' + stock + '</span>');
            $qty.prop('disabled', false).attr('max', stock).val(1);
        }
    };

    // Validate quantity against available stock
    window.iwValidateQty = function(input) {
        var $row = $(input).closest('tr');
        var stock = parseInt($row.find('.wd-product :selected').data('stock')) || 0;
        var qty = parseInt($(input).val()) || 0;
        if (stock > 0 && qty > stock) {
            alert('الكمية المطلوبة (' + qty + ') أكبر من الرصيد المتاح (' + stock + ')');
            $(input).val(stock);
        }
    };

    // Submit withdrawal order
    $('#iw-withdrawal-form').on('submit', function(e) {
        e.preventDefault();
        var items = [];
        var errors = [];
        $('#wd-items-body tr').each(function() {
            var pid = $(this).find('.wd-product').val();
            var qty = parseInt($(this).find('.wd-qty').val()) || 0;
            var stock = parseInt($(this).find('.wd-product :selected').data('stock')) || 0;
            var name = $(this).find('.wd-product :selected').text();
            if (pid && qty > 0) {
                if (stock <= 0) {
                    errors.push('الصنف "' + name + '" لا يوجد به رصيد متاح (الرصيد: 0) - لا يمكن صرفه');
                } else if (qty > stock) {
                    errors.push('الكمية المطلوبة من "' + name + '" (' + qty + ') أكبر من الرصيد المتاح (' + stock + ')');
                }
                items.push({product_id: pid, quantity: qty});
            }
        });
        if (errors.length) { alert('لا يمكن إنشاء إذن الصرف:\n\n' + errors.join('\n')); return; }
        if (!items.length) { alert('يجب إضافة أصناف'); return; }

        $.post(iwAdmin.ajaxurl, {
            action: 'iw_create_withdrawal_order', nonce: iwAdmin.nonce,
            department_id: $('#wd_department_id').val(),
            employee_id: $('#wd_employee_id').val(),
            notes: $('#wd_notes').val(),
            items: JSON.stringify(items)
        }, function(res) {
            alert(res.data.message);
            if (res.success) { $('#iw-withdrawal-form')[0].reset(); $('#wd-items-body').html(''); iwAddWdItem(); }
        });
    });

    // Submit custody order
    $('#iw-custody-form').on('submit', function(e) {
        e.preventDefault();
        var items = [];
        $('#cust-items-body tr').each(function() {
            var pid = $(this).find('.cust-product').val();
            var qty = $(this).find('.cust-qty').val();
            var empName = $(this).find('.cust-emp-name').val();
            if (pid && qty > 0) items.push({product_id: pid, quantity: qty, custody_employee_name: empName});
        });
        if (!items.length) { alert('يجب إضافة أصناف'); return; }

        $.post(iwAdmin.ajaxurl, {
            action: 'iw_create_custody_order', nonce: iwAdmin.nonce,
            department_id: $('#cust_department_id').val(),
            employee_id: $('#cust_employee_id').val(),
            notes: $('#cust_notes').val(),
            items: JSON.stringify(items)
        }, function(res) {
            alert(res.data.message);
            if (res.success) { $('#iw-custody-form')[0].reset(); $('#cust-items-body').html(''); iwAddCustItem(); }
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

    function getOrderTypeBadge(type) {
        if (type === 'custody') return '<span class="iw-badge iw-badge-info">عهدة</span>';
        return '<span class="iw-badge">عادي</span>';
    }

    function loadOrders(status) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_withdrawal_orders', nonce: iwAdmin.nonce, status: status}, function(r) {
            if (!r.success) return;
            var target = status ? '#wd-'+status+'-table' : '#wd-all-table';
            var html = '';
            r.data.forEach(function(o) {
                var statusBadge = getStatusBadge(o.status);
                var typeBadge = getOrderTypeBadge(o.order_type);
                html += '<tr>';
                if (status === 'approved') {
                    html += '<td><input type="checkbox" class="wd-approved-check" value="'+o.id+'"></td>';
                }
                html += '<td>'+o.order_number+'</td><td>'+typeBadge+'</td><td>'+(o.department_name||'-')+'</td><td>'+(o.employee_name||'-')+'</td>';
                if (!status) html += '<td>'+statusBadge+'</td>';
                html += '<td>'+o.created_at+'</td>';
                html += '<td><button class="button" onclick="iwViewOrder('+o.id+')">عرض</button>';
                if (o.status === 'pending') {
                    html += ' <button class="button" onclick="iwViewOrder('+o.id+')">تعديل</button>';
                    html += ' <button class="button iw-btn-danger" onclick="iwDeleteOrder('+o.id+')">حذف</button>';
                }
                if (o.status === 'approved') {
                    html += ' <button class="button button-primary" onclick="iwPrintAndExecute('+o.id+')">طباعة وتنفيذ</button>';
                    if (iwAdmin.isAdmin) {
                        html += ' <button class="button" onclick="iwViewOrder('+o.id+')">تعديل</button>';
                    }
                    html += ' <button class="button iw-btn-danger" onclick="iwCancelOrder('+o.id+')">إلغاء</button>';
                }
                if (o.status === 'completed') html += ' <button class="button button-primary" onclick="iwPrintOrder('+o.id+')">طباعة</button>';
                html += '</td></tr>';
            });
            $(target).html(html || '<tr><td colspan="7">لا توجد أوامر</td></tr>');
        });
    }

    function getStatusBadge(s) {
        var map = {pending:'معلق',approved:'معتمد',rejected:'مرفوض',completed:'منفذ',cancelled:'ملغي'};
        var cls = {pending:'warning',approved:'success',rejected:'danger',completed:'info',cancelled:'danger'};
        return '<span class="iw-badge iw-badge-'+(cls[s]||'')+'">'+( map[s]||s)+'</span>';
    }

    // View order detail
    window.iwViewOrder = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_withdrawal_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            if (!r.success) return;
            var o = r.data.order, items = r.data.items, sig = r.data.signature_url;
            var isCustody = o.order_type === 'custody';
            var orderTypeLabel = isCustody ? 'إذن صرف عهدة' : 'إذن صرف';

            // Check for zero stock items (only for non-custody orders)
            var hasZeroStock = false;
            var zeroStockItems = [];
            if (!isCustody) {
                items.forEach(function(it) {
                    var qty = it.approved_quantity !== null ? parseInt(it.approved_quantity) : parseInt(it.quantity);
                    if (qty > 0 && parseInt(it.current_stock || 0) <= 0) {
                        hasZeroStock = true;
                        zeroStockItems.push(it.product_name);
                    }
                });
            }

            var html = '<h2>'+orderTypeLabel+' رقم: '+o.order_number+'</h2>';
            if (isCustody) html += '<div class="notice notice-info inline"><p>هذا إذن عهدة - لا يتم خصم من الرصيد</p></div>';

            // Show warning if there are zero stock items
            if (hasZeroStock) {
                html += '<div class="notice notice-error inline" style="border-right-color:#dc3232;background:#fff;padding:10px;margin:10px 0;">';
                html += '<p style="margin:0;color:#dc3232;font-weight:bold;">⚠️ تحذير: الأصناف التالية رصيدها صفر ولا يمكن صرفها:</p>';
                html += '<ul style="margin:5px 20px;color:#dc3232;">';
                zeroStockItems.forEach(function(name) { html += '<li>' + name + '</li>'; });
                html += '</ul>';
                html += '<p style="margin:5px 0 0 0;color:#666;">يجب إضافة رصيد لهذه الأصناف أو حذفها من الإذن قبل الاعتماد/التنفيذ.</p>';
                html += '</div>';
            }

            html += '<p><strong>القسم:</strong> '+(o.department_name||'-')+' | <strong>الموظف:</strong> '+(o.employee_name||'-')+' | <strong>الحالة:</strong> '+getStatusBadge(o.status)+'</p>';

            html += '<table class="wp-list-table widefat fixed striped"><thead><tr><th>الصنف</th><th>الوحدة</th>';
            if (!isCustody) html += '<th>الرصيد الحالي</th>';
            html += '<th>الكمية المطلوبة</th>';
            if (isCustody) html += '<th>الموظف المصروف له</th>';
            if (o.status === 'pending') html += '<th>الكمية المعتمدة</th><th>حذف</th>';
            else if (o.status !== 'pending' && items[0] && items[0].approved_quantity !== null) html += '<th>الكمية المعتمدة</th>';
            html += '</tr></thead><tbody>';

            items.forEach(function(it) {
                var stock = parseInt(it.current_stock || 0);
                var qty = parseInt(it.quantity);
                var isZeroStock = stock <= 0 && qty > 0;
                var rowStyle = isZeroStock ? ' style="background-color:#ffe6e6;"' : '';

                html += '<tr data-item-product="'+it.product_id+'" data-stock="'+stock+'"'+rowStyle+'><td>'+it.product_name+'</td><td>'+(it.product_unit||'-')+'</td>';
                if (!isCustody) {
                    if (isZeroStock) {
                        html += '<td><strong style="color:red;">0 ❌ لا يوجد رصيد!</strong></td>';
                    } else if (stock < qty) {
                        html += '<td><strong style="color:orange;">'+stock+' ⚠️</strong></td>';
                    } else {
                        html += '<td><strong style="color:green;">'+stock+' ✓</strong></td>';
                    }
                }
                html += '<td>'+it.quantity+'</td>';
                if (isCustody) {
                    html += '<td>'+(it.custody_employee_name||'-')+'</td>';
                }
                if (o.status === 'pending') {
                    html += '<td><input type="number" class="wd-approve-qty" data-product="'+it.product_id+'" value="'+it.quantity+'" min="0"'+(isZeroStock ? ' style="background:#ffe6e6;"' : '')+'></td>';
                    html += '<td><button type="button" class="button iw-btn-danger" onclick="$(this).closest(\'tr\').remove()">حذف</button></td>';
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

            // Admin: edit employee/department name on any order regardless of status
            if (iwAdmin.isAdmin) {
                html += '<div style="margin-top:15px;padding:12px;background:#f0f6fc;border:1px solid #c3d4e4;border-radius:4px;">';
                html += '<strong>تعديل بيانات الموظف (أدمن)</strong>';
                html += '<table style="margin-top:8px;width:100%"><tr>';
                html += '<td style="width:50%;padding:4px;">القسم: <input type="text" id="emp-edit-dept" class="regular-text" value="'+((o.department_name||''))+'"></td>';
                html += '<td style="padding:4px;">الموظف: <input type="text" id="emp-edit-name" class="regular-text" value="'+((o.employee_name||''))+'"></td>';
                html += '</tr></table>';
                html += '<button class="button" style="margin-top:8px;" onclick="iwSaveEmployeeInfo('+o.id+')">حفظ البيانات</button>';
                html += '</div>';
            }

            if (o.status === 'pending') {
                html += '<div style="margin-top:15px;">';
                if (hasZeroStock) {
                    html += '<button class="button button-large" disabled title="يوجد أصناف رصيدها صفر">اعتماد (غير متاح)</button> ';
                } else {
                    html += '<button class="button button-primary button-large" onclick="iwApproveOrder('+o.id+')">اعتماد</button> ';
                }
                html += '<button class="button iw-btn-danger button-large" onclick="iwRejectOrder('+o.id+')">رفض</button> ';
                html += '<button class="button button-large" onclick="iwSaveOrderEdit('+o.id+')">حفظ التعديلات</button> ';
                html += '<button class="button iw-btn-danger button-large" onclick="iwDeleteOrder('+o.id+')">حذف الإذن</button>';
                html += '</div>';
            }

            if (o.status === 'approved') {
                html += '<div style="margin-top:15px;">';
                if (hasZeroStock) {
                    html += '<button class="button button-large" disabled title="يوجد أصناف رصيدها صفر">طباعة وتنفيذ (غير متاح)</button> ';
                } else {
                    html += '<button class="button button-primary button-large" onclick="iwPrintAndExecute('+o.id+')">طباعة وتنفيذ</button> ';
                }
                if (iwAdmin.isAdmin) {
                    html += '<button class="button button-large" onclick="iwSaveOrderEdit('+o.id+')">حفظ التعديلات</button> ';
                }
                html += '<button class="button iw-btn-danger button-large" onclick="iwCancelOrder('+o.id+')">إلغاء الإذن</button>';
                html += '</div>';
            }

            if (o.status === 'completed') {
                html += '<div style="margin-top:15px;">';
                html += '<button class="button button-primary button-large" onclick="iwPrintOrder('+o.id+')">طباعة</button>';
                html += '</div>';
            }

            $('#iw-wd-modal-body').html(html);
            $('#iw-wd-modal').show();
        });
    };

    // Save edits (Dean) - handles deleted items too
    window.iwSaveOrderEdit = function(id) {
        var items = [];
        $('#iw-wd-modal-body tr[data-item-product]').each(function() {
            var productId = $(this).data('item-product');
            var qty = $(this).find('.wd-approve-qty').val();
            if (productId && qty) {
                items.push({product_id: productId, quantity: qty, approved_quantity: qty});
            }
        });
        if (!items.length) { alert('يجب أن يحتوي الإذن على صنف واحد على الأقل'); return; }
        $.post(iwAdmin.ajaxurl, {action: 'iw_update_withdrawal_order', nonce: iwAdmin.nonce, order_id: id, items: JSON.stringify(items)}, function(r) {
            alert(r.data.message);
            if (r.success) iwViewOrder(id);
        });
    };

    // Admin: save employee/department name only (any order status)
    window.iwSaveEmployeeInfo = function(id) {
        var deptName = $('#emp-edit-dept').val();
        var empName  = $('#emp-edit-name').val();
        $.post(iwAdmin.ajaxurl, {
            action: 'iw_update_order_employee', nonce: iwAdmin.nonce,
            order_id: id, department_name: deptName, employee_name: empName
        }, function(r) {
            alert(r.data.message);
            if (r.success) iwViewOrder(id);
        });
    };

    // Delete order
    window.iwDeleteOrder = function(id) {
        if (!confirm('هل أنت متأكد من حذف هذا الإذن؟')) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_delete_withdrawal_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            alert(r.data.message);
            if (r.success) { $('#iw-wd-modal').hide(); loadOrders('pending'); }
        });
    };

    // Cancel approved order
    window.iwCancelOrder = function(id) {
        if (!confirm('هل أنت متأكد من إلغاء هذا الإذن؟ (الرصيد لم يخصم بعد لأنه لم ينفذ)')) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_cancel_withdrawal_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            alert(r.data.message);
            if (r.success) { $('#iw-wd-modal').hide(); loadOrders('approved'); }
        });
    };

    // Approve
    window.iwApproveOrder = function(id) {
        // Check for zero stock items before approval
        var errors = [];
        $('#iw-wd-modal-body tr[data-item-product]').each(function() {
            var stock = parseInt($(this).data('stock')) || 0;
            var qty = parseInt($(this).find('.wd-approve-qty').val()) || 0;
            var name = $(this).find('td:first').text();
            if (qty > 0 && stock <= 0) {
                errors.push('الصنف "' + name + '" رصيده صفر - لا يمكن اعتماده');
            } else if (qty > stock) {
                errors.push('الكمية المطلوبة من "' + name + '" (' + qty + ') أكبر من الرصيد (' + stock + ')');
            }
        });
        if (errors.length) {
            alert('لا يمكن اعتماد الإذن:\n\n' + errors.join('\n'));
            return;
        }
        if (!confirm('هل أنت متأكد من اعتماد هذا الإذن؟')) return;
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
    window.iwCompleteOrder = function(id, callback) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_complete_withdrawal_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            if (callback) callback(r);
            else {
                alert(r.data.message);
                if (r.success) { $('#iw-wd-modal').hide(); loadOrders('approved'); }
            }
        });
    };

    // Print and Execute in one action
    window.iwPrintAndExecute = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_withdrawal_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            if (!r.success) return;
            var o = r.data.order, items = r.data.items, sig = r.data.signature_url;

            if (o.status === 'approved') {
                iwCompleteOrder(id, function(execResult) {
                    if (!execResult.success) {
                        alert(execResult.data.message);
                        return;
                    }
                    doPrint(o, items, sig);
                    alert('تم تنفيذ الصرف والطباعة بنجاح');
                    $('#iw-wd-modal').hide();
                    loadOrders('approved');
                });
            } else {
                doPrint(o, items, sig);
            }
        });

        function doPrint(o, items, sig) {
            var isCustody = o.order_type === 'custody';
            var orderTypeLabel = isCustody ? 'إذن صرف عهدة' : 'إذن صرف';

            var printContent = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
            printContent += '<h2 style="text-align:center;">'+orderTypeLabel+' رقم: '+o.order_number+'</h2>';
            if (isCustody) printContent += '<p style="text-align:center;color:#666;">(إذن عهدة - بدون خصم من الرصيد)</p>';
            printContent += '<p><strong>القسم:</strong> '+(o.department_name||'-')+' | <strong>الموظف:</strong> '+(o.employee_name||'-')+'</p>';
            printContent += '<p><strong>التاريخ:</strong> '+o.created_at+'</p>';
            printContent += '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;">';
            printContent += '<tr style="background:#f0f0f0;"><th>الصنف</th><th>الوحدة</th><th>الكمية</th>';
            if (isCustody) printContent += '<th>الموظف المصروف له</th>';
            printContent += '</tr>';
            items.forEach(function(it) {
                var qty = it.approved_quantity !== null ? it.approved_quantity : it.quantity;
                printContent += '<tr><td>'+it.product_name+'</td><td>'+(it.product_unit||'-')+'</td><td>'+qty+'</td>';
                if (isCustody) printContent += '<td>'+(it.custody_employee_name||'-')+'</td>';
                printContent += '</tr>';
            });
            printContent += '</table>';
            printContent += '<table width="100%" style="margin-top:40px;border:none;"><tr>';
            printContent += '<td style="text-align:center;border:none;width:50%;"><p><strong>توقيع المستلم:</strong></p>';
            printContent += '<div style="border-bottom:1px solid #000;width:200px;margin:40px auto 5px;"></div>';
            printContent += '<p>الاسم: '+(o.employee_name||'.................')+'</p></td>';
            if (sig) {
                printContent += '<td style="text-align:center;border:none;width:50%;"><p><strong>توقيع عميد المعهد / المدير:</strong></p>';
                printContent += '<img src="'+sig+'" style="max-height:80px;" /></td>';
            } else {
                printContent += '<td style="text-align:center;border:none;width:50%;"><p><strong>توقيع عميد المعهد / المدير:</strong></p><div style="height:60px;"></div></td>';
            }
            printContent += '</tr></table>';
            var w = window.open('','','width=800,height=600');
            w.document.write('<html dir="rtl"><head><title>'+orderTypeLabel+'</title><style>body{font-family:Arial,sans-serif;padding:20px;}</style></head><body>'+printContent+'</body></html>');
            w.document.close();
            w.print();
        }
    };

    // Print
    window.iwPrintOrder = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_withdrawal_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            if (!r.success) return;
            var o = r.data.order, items = r.data.items, sig = r.data.signature_url;
            var isCustody = o.order_type === 'custody';
            var orderTypeLabel = isCustody ? 'إذن صرف عهدة' : 'إذن صرف';

            var printContent = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
            printContent += '<h2 style="text-align:center;">'+orderTypeLabel+' رقم: '+o.order_number+'</h2>';
            if (isCustody) printContent += '<p style="text-align:center;color:#666;">(إذن عهدة - بدون خصم من الرصيد)</p>';
            printContent += '<p><strong>القسم:</strong> '+(o.department_name||'-')+' | <strong>الموظف:</strong> '+(o.employee_name||'-')+'</p>';
            printContent += '<p><strong>التاريخ:</strong> '+o.created_at+'</p>';
            printContent += '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;">';
            printContent += '<tr style="background:#f0f0f0;"><th>الصنف</th><th>الوحدة</th><th>الكمية</th>';
            if (isCustody) printContent += '<th>الموظف المصروف له</th>';
            printContent += '</tr>';
            items.forEach(function(it) {
                var qty = it.approved_quantity !== null ? it.approved_quantity : it.quantity;
                printContent += '<tr><td>'+it.product_name+'</td><td>'+(it.product_unit||'-')+'</td><td>'+qty+'</td>';
                if (isCustody) printContent += '<td>'+(it.custody_employee_name||'-')+'</td>';
                printContent += '</tr>';
            });
            printContent += '</table>';
            printContent += '<table width="100%" style="margin-top:40px;border:none;"><tr>';
            printContent += '<td style="text-align:center;border:none;width:50%;"><p><strong>توقيع المستلم:</strong></p>';
            printContent += '<div style="border-bottom:1px solid #000;width:200px;margin:40px auto 5px;"></div>';
            printContent += '<p>الاسم: '+(o.employee_name||'.................')+'</p></td>';
            if (sig) {
                printContent += '<td style="text-align:center;border:none;width:50%;"><p><strong>توقيع عميد المعهد / المدير:</strong></p>';
                printContent += '<img src="'+sig+'" style="max-height:80px;" /></td>';
            } else {
                printContent += '<td style="text-align:center;border:none;width:50%;"><p><strong>توقيع عميد المعهد / المدير:</strong></p><div style="height:60px;"></div></td>';
            }
            printContent += '</tr></table>';
            var w = window.open('','','width=800,height=600');
            w.document.write('<html dir="rtl"><head><title>'+orderTypeLabel+'</title><style>body{font-family:Arial,sans-serif;padding:20px;}</style></head><body>'+printContent+'</body></html>');
            w.document.close();
            w.print();
        });
    };

    // Bulk actions
    window.iwWdSelectAll = function(type) {
        var cls = '.wd-'+type+'-check';
        var allChecked = $(cls).length === $(cls+':checked').length;
        $(cls).prop('checked', !allChecked);
    };

    window.iwBulkPrintOrders = function() {
        var ids = [];
        $('.wd-approved-check:checked').each(function() { ids.push($(this).val()); });
        if (!ids.length) { alert('اختر أوامر أولاً'); return; }
        var printContent = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
        var loaded = 0;
        ids.forEach(function(id) {
            $.post(iwAdmin.ajaxurl, {action: 'iw_get_withdrawal_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
                if (r.success) {
                    var o = r.data.order, items = r.data.items, sig = r.data.signature_url;
                    var isCustody = o.order_type === 'custody';
                    var orderTypeLabel = isCustody ? 'إذن صرف عهدة' : 'إذن صرف';

                    printContent += '<div style="page-break-after:always;">';
                    printContent += '<h2 style="text-align:center;">'+orderTypeLabel+' رقم: '+o.order_number+'</h2>';
                    if (isCustody) printContent += '<p style="text-align:center;color:#666;">(إذن عهدة - بدون خصم من الرصيد)</p>';
                    printContent += '<p><strong>القسم:</strong> '+(o.department_name||'-')+' | <strong>الموظف:</strong> '+(o.employee_name||'-')+'</p>';
                    printContent += '<p><strong>التاريخ:</strong> '+o.created_at+'</p>';
                    printContent += '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;">';
                    printContent += '<tr style="background:#f0f0f0;"><th>الصنف</th><th>الوحدة</th><th>الكمية</th>';
                    if (isCustody) printContent += '<th>الموظف المصروف له</th>';
                    printContent += '</tr>';
                    items.forEach(function(it) {
                        var qty = it.approved_quantity !== null ? it.approved_quantity : it.quantity;
                        printContent += '<tr><td>'+it.product_name+'</td><td>'+(it.product_unit||'-')+'</td><td>'+qty+'</td>';
                        if (isCustody) printContent += '<td>'+(it.custody_employee_name||'-')+'</td>';
                        printContent += '</tr>';
                    });
                    printContent += '</table>';
                    printContent += '<table width="100%" style="margin-top:40px;border:none;"><tr>';
                    printContent += '<td style="text-align:center;border:none;width:50%;"><p><strong>توقيع المستلم:</strong></p>';
                    printContent += '<div style="border-bottom:1px solid #000;width:200px;margin:40px auto 5px;"></div>';
                    printContent += '<p>الاسم: '+(o.employee_name||'.................')+'</p></td>';
                    if (sig) {
                        printContent += '<td style="text-align:center;border:none;width:50%;"><p><strong>توقيع عميد المعهد / المدير:</strong></p><img src="'+sig+'" style="max-height:80px;" /></td>';
                    } else {
                        printContent += '<td style="text-align:center;border:none;width:50%;"><p><strong>توقيع عميد المعهد / المدير:</strong></p><div style="height:60px;"></div></td>';
                    }
                    printContent += '</tr></table>';
                    printContent += '</div>';
                }
                loaded++;
                if (loaded === ids.length) {
                    var w = window.open('','','width=800,height=600');
                    w.document.write('<html dir="rtl"><head><title>أوامر صرف</title><style>body{font-family:Arial,sans-serif;padding:20px;}</style></head><body>'+printContent+'</body></html>');
                    w.document.close();
                    w.print();
                }
            });
        });
    };
});
</script>
