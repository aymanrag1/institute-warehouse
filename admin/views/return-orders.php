<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="<?php echo iw_dir(); ?>">
    <h1><?php echo iw_t('أذون الارتجاع ورد العهدة', 'Return Orders & Custody Returns'); ?></h1>

    <!-- Tabs -->
    <div style="margin-bottom:15px;">
        <button class="iw-tab active" onclick="iwRtSwitchTab('normal')"><?php echo iw_t('إذن ارتجاع عادي', 'Normal Return'); ?></button>
        <button class="iw-tab" onclick="iwRtSwitchTab('custody')"><?php echo iw_t('رد عهدة', 'Custody Return'); ?></button>
    </div>

    <!-- Tab: ارتجاع عادي -->
    <div id="tab-rt-normal" class="iw-tab-content">
        <div style="margin-bottom:10px;">
            <button class="button button-primary" onclick="iwShowReturnForm('normal')">+ <?php echo iw_t('إنشاء إذن ارتجاع', 'Create Return Order'); ?></button>
        </div>
        <h3><?php echo iw_t('أذون الارتجاع', 'Return Orders'); ?></h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo iw_t('رقم الإذن', 'Order No.'); ?></th><th><?php echo iw_t('القسم', 'Department'); ?></th><th><?php echo iw_t('الموظف', 'Employee'); ?></th><th><?php echo iw_t('الحالة', 'Status'); ?></th>
                    <th>الإذن الأصلي</th><th>التاريخ</th><th>إجراءات</th>
                </tr>
            </thead>
            <tbody id="rt-normal-table"><tr><td colspan="7" style="text-align:center">جاري التحميل...</td></tr></tbody>
        </table>
    </div>

    <!-- Tab: رد عهدة -->
    <div id="tab-rt-custody" class="iw-tab-content" style="display:none;">
        <div style="margin-bottom:10px;">
            <button class="button button-primary" onclick="iwShowReturnForm('custody')">+ إنشاء إذن رد عهدة</button>
        </div>
        <div class="notice notice-info inline" style="margin:0 0 10px;padding:8px 12px;">
            <p style="margin:0;">رد العهدة يُسجَّل كإرجاع بدون تغيير الرصيد (العهدة لم تُخصم أصلاً).</p>
        </div>
        <h3>أذون رد العهدة</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>رقم الإذن</th><th>القسم</th><th>الموظف</th><th>الحالة</th>
                    <th>الإذن الأصلي</th><th>التاريخ</th><th>إجراءات</th>
                </tr>
            </thead>
            <tbody id="rt-custody-table"><tr><td colspan="7" style="text-align:center">جاري التحميل...</td></tr></tbody>
        </table>
    </div>

    <!-- Create/Edit Modal -->
    <div id="iw-rt-form-modal" class="iw-modal" style="display:none;">
        <div class="iw-modal-content" style="max-width:750px;">
            <span class="iw-modal-close" onclick="jQuery('#iw-rt-form-modal').hide()">&times;</span>
            <h2 id="rt-form-title">إنشاء إذن ارتجاع</h2>
            <input type="hidden" id="rt_order_type" value="normal">
            <table class="form-table">
                <tr>
                    <th>القسم</th>
                    <td>
                        <select id="rt_department_id" style="width:250px;" onchange="iwRtLoadEmployees(this.value)">
                            <option value="">-- اختر القسم --</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>الموظف</th>
                    <td><select id="rt_employee_id" style="width:250px;"><option value="">-- اختر الموظف --</option></select></td>
                </tr>
                <tr>
                    <th>الإذن الأصلي (اختياري)</th>
                    <td><input type="text" id="rt_original_order_id_label" class="regular-text" placeholder="رقم إذن الصرف الأصلي (اختياري)" readonly>
                        <input type="hidden" id="rt_original_order_id" value="0">
                    </td>
                </tr>
                <tr>
                    <th>ملاحظات</th>
                    <td><textarea id="rt_notes" class="large-text" rows="2"></textarea></td>
                </tr>
            </table>

            <h4 style="margin:10px 0 5px;">الأصناف</h4>
            <table class="wp-list-table widefat fixed" id="rt-items-table">
                <thead><tr><th>الصنف</th><th>الكمية</th><th>سعر الوحدة</th><th></th></tr></thead>
                <tbody id="rt-items-body"></tbody>
            </table>
            <button class="button" style="margin-top:8px;" onclick="iwAddRtItem()">+ إضافة صنف</button>

            <div style="margin-top:15px;">
                <button class="button button-primary" onclick="iwSaveReturnOrder()">حفظ الإذن</button>
                <button class="button" style="margin-right:5px;" onclick="jQuery('#iw-rt-form-modal').hide()">إلغاء</button>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div id="iw-rt-view-modal" class="iw-modal" style="display:none;">
        <div class="iw-modal-content" style="max-width:750px;">
            <span class="iw-modal-close" onclick="jQuery('#iw-rt-view-modal').hide()">&times;</span>
            <div id="iw-rt-view-body"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var rtProducts    = [];
    var rtDepts       = [];
    var fromOrderId   = <?php echo intval($_GET['from_order'] ?? 0); ?>;
    var fromOrderType = '<?php echo sanitize_key($_GET['type'] ?? 'normal'); ?>';

    // Load initial data
    loadReturnOrders('normal');
    loadReturnOrders('custody');
    loadRtDepts();
    $.post(iwAdmin.ajaxurl, {action: 'iw_get_products_list', nonce: iwAdmin.nonce}, function(r) {
        if (r.success) {
            rtProducts = r.data;
            // Auto-open form if coming from a withdrawal order
            if (fromOrderId) {
                iwRtSwitchTab(fromOrderType);
                iwShowReturnForm(fromOrderType);
                $('#rt_original_order_id').val(fromOrderId);
                $('#rt_original_order_id_label').val('إذن صرف #' + fromOrderId);
                // Pre-fill items from original order
                $.post(iwAdmin.ajaxurl, {action: 'iw_get_withdrawal_order', nonce: iwAdmin.nonce, order_id: fromOrderId}, function(wr) {
                    if (!wr.success) return;
                    var dept = wr.data.order.department_name || '';
                    var emp  = wr.data.order.employee_name  || '';
                    // Try to find dept/emp by name in loaded depts (best effort)
                    // Just set the department text manually
                    $('#rt-items-body').html('');
                    (wr.data.items || []).forEach(function() { iwAddRtItem(); });
                    var rows = $('#rt-items-body tr');
                    (wr.data.items || []).forEach(function(it, idx) {
                        var aq = it.approved_quantity !== null ? it.approved_quantity : it.quantity;
                        rows.eq(idx).find('.rt-product').val(it.product_id);
                        rows.eq(idx).find('.rt-qty').val(aq);
                    });
                });
            }
        }
    });

    function loadRtDepts() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_departments', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) return;
            rtDepts = r.data || [];
            var h = '<option value="">-- اختر القسم --</option>';
            rtDepts.forEach(function(d) { h += '<option value="'+d.id+'">'+d.name+'</option>'; });
            $('#rt_department_id').html(h);
        });
    }

    function loadReturnOrders(type) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_return_orders', nonce: iwAdmin.nonce, order_type: type}, function(r) {
            var target = '#rt-' + type + '-table';
            if (!r.success) { $(target).html('<tr><td colspan="7">خطأ في التحميل</td></tr>'); return; }
            var html = '';
            (r.data || []).forEach(function(o) {
                var statusBadge = getRtStatusBadge(o.status);
                html += '<tr>';
                html += '<td>'+o.order_number+'</td>';
                html += '<td>'+(o.department_name||'-')+'</td>';
                html += '<td>'+(o.employee_name||'-')+'</td>';
                html += '<td>'+statusBadge+'</td>';
                html += '<td>'+(o.original_order_id ? '#'+o.original_order_id : '-')+'</td>';
                html += '<td>'+o.created_at+'</td>';
                html += '<td><button class="button" onclick="iwViewReturnOrder('+o.id+')">عرض</button>';
                if (o.status === 'pending') {
                    html += ' <button class="button iw-btn-danger" onclick="iwDeleteReturnOrder('+o.id+',\''+type+'\')">حذف</button>';
                }
                html += '</td></tr>';
            });
            $(target).html(html || '<tr><td colspan="7">لا توجد أذونات</td></tr>');
        });
    }

    function getRtStatusBadge(s) {
        var map = {pending:'معلق', approved:'معتمد', completed:'منفذ', rejected:'مرفوض'};
        var cls = {pending:'warning', approved:'success', completed:'info', rejected:'danger'};
        return '<span class="iw-badge iw-badge-'+(cls[s]||'')+'">'+( map[s]||s)+'</span>';
    }

    window.iwRtSwitchTab = function(tab) {
        $('.iw-tab-content').hide();
        $('.iw-tab').removeClass('active');
        $('#tab-rt-'+tab).show();
        $('[onclick="iwRtSwitchTab(\''+tab+'\')"]').addClass('active');
    };

    window.iwShowReturnForm = function(type) {
        $('#rt_order_type').val(type);
        $('#rt-form-title').text(type === 'custody' ? 'إنشاء إذن رد عهدة' : 'إنشاء إذن ارتجاع');
        $('#rt_department_id, #rt_employee_id').val('');
        $('#rt_original_order_id').val(0);
        $('#rt_original_order_id_label').val('');
        $('#rt_notes').val('');
        $('#rt-items-body').html('');
        iwAddRtItem();
        $('#iw-rt-form-modal').show();
    };

    window.iwRtLoadEmployees = function(deptId) {
        if (!deptId) { $('#rt_employee_id').html('<option value="">-- اختر الموظف --</option>'); return; }
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_employees_by_department', nonce: iwAdmin.nonce, department_id: deptId}, function(r) {
            var h = '<option value="">-- اختر الموظف --</option>';
            if (r.success) r.data.forEach(function(e) { h += '<option value="'+e.id+'">'+e.name+'</option>'; });
            $('#rt_employee_id').html(h);
        });
    };

    window.iwAddRtItem = function() {
        var opts = '<option value="">-- اختر الصنف --</option>';
        rtProducts.forEach(function(p) { opts += '<option value="'+p.id+'" data-price="'+p.price+'">'+p.name+'</option>'; });
        var row = '<tr>'
            + '<td><select class="rt-product regular-text" style="min-width:200px;" onchange="this.closest(\'tr\').querySelector(\'.rt-price\').value=this.selectedOptions[0].dataset.price||0">'+opts+'</select></td>'
            + '<td><input type="number" class="rt-qty" min="1" value="1" style="width:70px;"></td>'
            + '<td><input type="number" class="rt-price" min="0" step="0.01" value="0" style="width:90px;"></td>'
            + '<td><button type="button" class="button iw-btn-danger" onclick="jQuery(this).closest(\'tr\').remove()">X</button></td>'
            + '</tr>';
        $('#rt-items-body').append(row);
    };

    window.iwSaveReturnOrder = function() {
        var type      = $('#rt_order_type').val();
        var deptId    = $('#rt_department_id').val();
        var empId     = $('#rt_employee_id').val();
        var deptName  = $('#rt_department_id option:selected').text().replace('-- اختر القسم --','').trim();
        var empName   = $('#rt_employee_id option:selected').text().replace('-- اختر الموظف --','').trim();
        var origId    = parseInt($('#rt_original_order_id').val()) || 0;
        var notes     = $('#rt_notes').val();
        var items     = [];

        $('#rt-items-body tr').each(function() {
            var pid = $(this).find('.rt-product').val();
            var qty = parseInt($(this).find('.rt-qty').val());
            var price = parseFloat($(this).find('.rt-price').val()) || 0;
            if (pid && qty > 0) items.push({product_id: pid, quantity: qty, unit_price: price});
        });

        if (!items.length) { alert('أضف صنفاً واحداً على الأقل'); return; }

        $.post(iwAdmin.ajaxurl, {
            action: 'iw_create_return_order',
            nonce: iwAdmin.nonce,
            order_type: type,
            original_order_id: origId,
            department_id: deptId || 0,
            employee_id: empId || 0,
            department_name: deptName,
            employee_name: empName,
            notes: notes,
            items: JSON.stringify(items)
        }, function(r) {
            alert(r.data.message);
            if (r.success) {
                $('#iw-rt-form-modal').hide();
                loadReturnOrders(type);
            }
        });
    };

    window.iwViewReturnOrder = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_return_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            if (!r.success) { alert(r.data.message); return; }
            var o = r.data.order, items = r.data.items || [], sig = r.data.signature_url;
            var isCustody = o.order_type === 'custody';
            var title = isCustody ? 'رد عهدة' : 'إذن ارتجاع';

            var html = '<h2>'+title+' رقم: '+o.order_number+'</h2>';
            html += '<p><strong>القسم:</strong> '+(o.department_name||'-')+
                    ' | <strong>الموظف:</strong> '+(o.employee_name||'-')+
                    ' | <strong>الحالة:</strong> '+getRtStatusBadge(o.status)+'</p>';
            if (o.original_order_id) html += '<p><strong>الإذن الأصلي:</strong> #'+o.original_order_id+'</p>';
            if (o.notes) html += '<p><strong>ملاحظات:</strong> '+o.notes+'</p>';

            html += '<table class="wp-list-table widefat fixed striped"><thead><tr><th>الصنف</th><th>الوحدة</th><th>الكمية</th><th>الكمية المعتمدة</th><th>سعر الوحدة</th></tr></thead><tbody>';
            items.forEach(function(it) {
                var aq = it.approved_quantity !== null ? it.approved_quantity : it.quantity;
                html += '<tr><td>'+it.product_name+'</td><td>'+(it.product_unit||'-')+'</td><td>'+it.quantity+'</td><td>'+aq+'</td><td>'+parseFloat(it.unit_price).toFixed(2)+'</td></tr>';
            });
            html += '</tbody></table>';

            if (sig) {
                html += '<div style="margin-top:15px;text-align:center;"><p><strong>توقيع المعتمد:</strong></p><img src="'+sig+'" style="max-width:'+(iwAdmin.sigWidth||150)+'px;height:auto;"/></div>';
            }

            // Action buttons
            html += '<div style="margin-top:15px;">';
            if (o.status === 'pending') {
                html += '<button class="button button-primary button-large" onclick="iwApproveReturnOrder('+o.id+')">اعتماد</button> ';
                html += '<button class="button iw-btn-danger button-large" onclick="iwRejectReturnOrder('+o.id+')">رفض</button> ';
            }
            if (o.status === 'approved') {
                html += '<button class="button button-primary button-large" onclick="iwCompleteReturnOrder('+o.id+')">تنفيذ '+(isCustody?'رد العهدة':'الارتجاع')+'</button> ';
                html += '<button class="button button-large" onclick="iwPrintReturnOrder('+o.id+')">طباعة</button> ';
            }
            if (o.status === 'completed') {
                html += '<button class="button button-primary button-large" onclick="iwPrintReturnOrder('+o.id+')">طباعة</button> ';
            }
            html += '</div>';

            $('#iw-rt-view-body').html(html);
            $('#iw-rt-view-modal').show();
        });
    };

    window.iwApproveReturnOrder = function(id) {
        var sig = '';
        <?php
        $user_id = get_current_user_id();
        $sig_url = get_user_meta($user_id, 'iw_signature_url', true);
        if ($sig_url) echo "sig = " . json_encode($sig_url) . ";";
        ?>
        $.post(iwAdmin.ajaxurl, {action: 'iw_approve_return_order', nonce: iwAdmin.nonce, order_id: id, signature_url: sig}, function(r) {
            alert(r.data.message);
            if (r.success) { $('#iw-rt-view-modal').hide(); loadReturnOrders('normal'); loadReturnOrders('custody'); }
        });
    };

    window.iwCompleteReturnOrder = function(id) {
        if (!confirm('هل تريد تنفيذ هذا الإذن؟')) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_complete_return_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            alert(r.data.message);
            if (r.success) { $('#iw-rt-view-modal').hide(); loadReturnOrders('normal'); loadReturnOrders('custody'); }
        });
    };

    window.iwRejectReturnOrder = function(id) {
        var reason = prompt('سبب الرفض:');
        if (reason === null) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_reject_return_order', nonce: iwAdmin.nonce, order_id: id, rejection_reason: reason}, function(r) {
            alert(r.data.message);
            if (r.success) { $('#iw-rt-view-modal').hide(); loadReturnOrders('normal'); loadReturnOrders('custody'); }
        });
    };

    window.iwDeleteReturnOrder = function(id, type) {
        if (!confirm('هل أنت متأكد من حذف هذا الإذن؟')) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_delete_return_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            alert(r.data.message);
            if (r.success) loadReturnOrders(type);
        });
    };

    window.iwPrintReturnOrder = function(id) {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_return_order', nonce: iwAdmin.nonce, order_id: id}, function(r) {
            if (!r.success) return;
            var o = r.data.order, items = r.data.items || [], sig = r.data.signature_url;
            var isCustody = o.order_type === 'custody';
            var header = '<?php echo addslashes(IW_Admin::get_print_header()); ?>';
            var title  = isCustody ? 'إذن رد عهدة' : 'إذن ارتجاع';
            var content = header;
            content += '<h2 style="text-align:center;">'+title+' رقم: '+o.order_number+'</h2>';
            content += '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;margin-bottom:10px;">';
            content += '<tr><th>القسم</th><td>'+(o.department_name||'-')+'</td><th>الموظف</th><td>'+(o.employee_name||'-')+'</td></tr>';
            content += '<tr><th>التاريخ</th><td>'+o.created_at+'</td><th>النوع</th><td>'+(isCustody?'رد عهدة':'ارتجاع عادي')+'</td></tr>';
            if (o.original_order_id) content += '<tr><th>الإذن الأصلي</th><td colspan="3">#'+o.original_order_id+'</td></tr>';
            content += '</table>';
            content += '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;text-align:right;">';
            content += '<tr style="background:#f0f0f0;"><th>#</th><th>الصنف</th><th>الوحدة</th><th>الكمية المطلوبة</th><th>الكمية المعتمدة</th></tr>';
            items.forEach(function(it, i) {
                var aq = it.approved_quantity !== null ? it.approved_quantity : it.quantity;
                content += '<tr><td>'+(i+1)+'</td><td>'+it.product_name+'</td><td>'+(it.product_unit||'-')+'</td><td>'+it.quantity+'</td><td>'+aq+'</td></tr>';
            });
            content += '</table>';
            if (sig) {
                content += '<div style="margin-top:30px;text-align:left;"><p><strong>توقيع المعتمد:</strong></p><img src="'+sig+'" style="max-width:'+(iwAdmin.sigWidth||150)+'px;height:auto;"/></div>';
            }
            content += '<div style="margin-top:40px;display:flex;justify-content:space-between;">';
            content += '<div><strong>مشرف المخزن</strong><br>التوقيع: ____________</div>';
            content += '<div><strong>مُسلِّم البضاعة</strong><br>التوقيع: ____________</div>';
            content += '</div>';
            var w = window.open('','','width=800,height=600');
            w.document.write('<html dir="<?php echo iw_dir(); ?>"><head><title>'+title+'</title><style>body{font-family:Arial,sans-serif;padding:20px;}th{background:#f0f0f0;}</style></head><body>'+content+'</body></html>');
            w.document.close(); w.print();
        });
    };
});
</script>
