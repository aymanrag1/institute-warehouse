<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>لوحة تحكم المخازن</h1>

    <div class="iw-dashboard-cards" id="iw-dashboard">
        <div class="iw-card">
            <h3>إجمالي الأصناف</h3>
            <span id="total-products">-</span>
        </div>
        <div class="iw-card iw-card-warning">
            <h3>أصناف تحت الحد الأدنى</h3>
            <span id="low-stock-count">-</span>
        </div>
        <div class="iw-card">
            <h3>أوامر صرف معلقة</h3>
            <span id="pending-withdrawals">-</span>
        </div>
        <div class="iw-card">
            <h3>طلبات شراء معلقة</h3>
            <span id="pending-purchases">-</span>
        </div>
    </div>

    <div class="iw-section" id="low-stock-section" style="margin-top:20px;">
        <h2>أصناف وصلت للحد الأدنى</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>الصنف</th>
                    <th>المخزون الحالي</th>
                    <th>الحد الأدنى</th>
                    <th>الحد الأقصى</th>
                    <th>الكمية المطلوبة</th>
                </tr>
            </thead>
            <tbody id="low-stock-table"></tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    function loadDashboard() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_stock_report', nonce: iwAdmin.nonce}, function(res) {
            if (res.success) {
                $('#total-products').text(res.data.length);
                var lowStock = res.data.filter(function(p) { return p.min_stock > 0 && p.current_stock <= p.min_stock; });
                $('#low-stock-count').text(lowStock.length);
                var html = '';
                lowStock.forEach(function(p) {
                    var needed = p.max_stock - p.current_stock;
                    html += '<tr><td>'+p.name+'</td><td>'+p.current_stock+'</td><td>'+p.min_stock+'</td><td>'+p.max_stock+'</td><td>'+needed+'</td></tr>';
                });
                $('#low-stock-table').html(html || '<tr><td colspan="5">لا توجد أصناف تحت الحد الأدنى</td></tr>');
            }
        });
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_withdrawal_orders', nonce: iwAdmin.nonce, status: 'pending'}, function(res) {
            if (res.success) $('#pending-withdrawals').text(res.data.length);
        });
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_purchase_requests', nonce: iwAdmin.nonce, status: 'pending'}, function(res) {
            if (res.success) $('#pending-purchases').text(res.data.length);
        });
    }
    loadDashboard();
});
</script>
