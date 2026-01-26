<?php
/**
 * Print Add Permit View
 * صفحة طباعة إذن الإضافة
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إذن إضافة رقم <?php echo esc_html($permit->permit_number); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; font-size: 14px; line-height: 1.6; direction: rtl; padding: 20px; }
        .permit-container { max-width: 800px; margin: 0 auto; border: 2px solid #333; padding: 20px; }
        .permit-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .permit-header img { max-height: 60px; margin-bottom: 10px; }
        .permit-header h1 { font-size: 18px; margin: 5px 0; }
        .permit-header h2 { font-size: 22px; margin: 10px 0; color: #0073aa; }
        .permit-info { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; }
        .permit-info-item { flex: 1; min-width: 200px; }
        .permit-info-item label { font-weight: bold; display: block; color: #666; }
        .permit-info-item span { font-size: 15px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #333; padding: 8px; text-align: center; }
        th { background: #f0f0f0; font-weight: bold; }
        .total-row { background: #f9f9f9; font-weight: bold; }
        .notes-section { margin: 20px 0; padding: 10px; border: 1px solid #ddd; background: #fafafa; }
        .notes-section h4 { margin-bottom: 5px; }
        .signatures { display: flex; justify-content: space-around; margin-top: 40px; padding-top: 20px; }
        .signature-box { text-align: center; width: 200px; }
        .signature-box p { margin-bottom: 50px; font-weight: bold; }
        .signature-line { border-top: 1px solid #333; padding-top: 5px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
        @media print {
            body { padding: 0; }
            .permit-container { border: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 30px; font-size: 16px; cursor: pointer;">
            طباعة
        </button>
        <button onclick="window.close()" style="padding: 10px 30px; font-size: 16px; cursor: pointer; margin-right: 10px;">
            إغلاق
        </button>
    </div>

    <div class="permit-container">
        <div class="permit-header">
            <?php if (!empty($settings['institute_logo'])): ?>
                <img src="<?php echo esc_url($settings['institute_logo']); ?>" alt="">
            <?php endif; ?>
            <h1><?php echo esc_html($settings['institute_name'] ?? 'المعهد'); ?></h1>
            <h2>إذن إضافة للمخزن</h2>
            <p><strong>رقم الإذن:</strong> <?php echo esc_html($permit->permit_number); ?></p>
        </div>

        <div class="permit-info">
            <div class="permit-info-item">
                <label>المخزن:</label>
                <span><?php echo esc_html($permit->warehouse_name); ?></span>
            </div>
            <div class="permit-info-item">
                <label>جهة الشراء:</label>
                <span><?php echo esc_html($permit->supplier_full_name ?: $permit->supplier_name ?: '-'); ?></span>
            </div>
            <div class="permit-info-item">
                <label>رقم الفاتورة:</label>
                <span><?php echo esc_html($permit->invoice_number ?: '-'); ?></span>
            </div>
            <div class="permit-info-item">
                <label>تاريخ الفاتورة:</label>
                <span><?php echo IW_Admin::format_date($permit->invoice_date); ?></span>
            </div>
            <div class="permit-info-item">
                <label>تاريخ الإذن:</label>
                <span><?php echo IW_Admin::format_date($permit->created_at); ?></span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:5%">م</th>
                    <th style="width:15%">الكود</th>
                    <th style="width:25%">اسم الصنف</th>
                    <th style="width:10%">الوحدة</th>
                    <th style="width:10%">الكمية</th>
                    <th style="width:12%">سعر الوحدة</th>
                    <th style="width:13%">الإجمالي</th>
                    <th style="width:10%">مكان التخزين</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($permit->items as $index => $item): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo esc_html($item->product_sku); ?></td>
                        <td style="text-align: right;"><?php echo esc_html($item->product_name); ?></td>
                        <td><?php echo esc_html($item->unit); ?></td>
                        <td><?php echo number_format($item->quantity); ?></td>
                        <td><?php echo number_format($item->unit_price, 2); ?></td>
                        <td><?php echo number_format($item->total_price, 2); ?></td>
                        <td><?php echo esc_html($item->storage_location ?: '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="6" style="text-align: left;">الإجمالي الكلي:</td>
                    <td colspan="2"><?php echo IW_Admin::format_currency($permit->total_amount); ?></td>
                </tr>
            </tfoot>
        </table>

        <?php if ($permit->notes): ?>
            <div class="notes-section">
                <h4>ملاحظات:</h4>
                <p><?php echo nl2br(esc_html($permit->notes)); ?></p>
            </div>
        <?php endif; ?>

        <div class="signatures">
            <div class="signature-box">
                <p>أمين المخزن</p>
                <div class="signature-line"><?php echo esc_html($permit->created_by_name); ?></div>
            </div>
            <div class="signature-box">
                <p>المراجع</p>
                <div class="signature-line"><?php echo esc_html($permit->approved_by_name ?: ''); ?></div>
            </div>
            <div class="signature-box">
                <p>المدير المالي</p>
                <div class="signature-line"></div>
            </div>
        </div>

        <div class="footer">
            <p>تم الطباعة في: <?php echo date_i18n('d/m/Y H:i'); ?></p>
            <?php if (!empty($settings['institute_address'])): ?>
                <p><?php echo esc_html($settings['institute_address']); ?></p>
            <?php endif; ?>
            <?php if (!empty($settings['institute_phone'])): ?>
                <p>هاتف: <?php echo esc_html($settings['institute_phone']); ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
