<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="<?php echo iw_dir(); ?>">
    <h1><?php echo iw_t('لوحة تحكم المخازن', 'Warehouse Dashboard'); ?></h1>

    <div class="iw-dashboard-cards" id="iw-dashboard">
        <div class="iw-card">
            <h3><?php echo iw_t('إجمالي الأصناف', 'Total Products'); ?></h3>
            <span id="total-products">-</span>
        </div>
        <div class="iw-card iw-card-warning">
            <h3><?php echo iw_t('أصناف تحت الحد الأدنى', 'Low-Stock Items'); ?></h3>
            <span id="low-stock-count">-</span>
        </div>
        <div class="iw-card">
            <h3><?php echo iw_t('أوامر صرف معلقة', 'Pending Withdrawals'); ?></h3>
            <span id="pending-withdrawals">-</span>
        </div>
        <div class="iw-card">
            <h3><?php echo iw_t('طلبات شراء معلقة', 'Pending Purchase Requests'); ?></h3>
            <span id="pending-purchases">-</span>
        </div>
    </div>

    <div class="iw-section" id="low-stock-section" style="margin-top:20px;">
        <h2><?php echo iw_t('أصناف وصلت للحد الأدنى', 'Items at Minimum Stock Level'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo iw_t('الصنف', 'Product'); ?></th>
                    <th><?php echo iw_t('المخزون الحالي', 'Current Stock'); ?></th>
                    <th><?php echo iw_t('الحد الأدنى', 'Min Stock'); ?></th>
                    <th><?php echo iw_t('الحد الأقصى', 'Max Stock'); ?></th>
                    <th><?php echo iw_t('الكمية المطلوبة', 'Required Qty'); ?></th>
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
                $('#low-stock-table').html(html || '<tr><td colspan="5"><?php echo iw_t('لا توجد أصناف تحت الحد الأدنى', 'No items at minimum stock level.'); ?></td></tr>');
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
