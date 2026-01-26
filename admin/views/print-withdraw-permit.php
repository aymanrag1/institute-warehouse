<?php
/**
 * Print Withdraw Permit View
 * صفحة طباعة إذن الصرف
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
    <title>إذن صرف رقم <?php echo esc_html($permit->permit_number); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; font-size: 14px; line-height: 1.6; direction: rtl; padding: 20px; }
        .permit-container { max-width: 800px; margin: 0 auto; border: 2px solid #333; padding: 20px; }
        .permit-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .permit-header img { max-height: 60px; margin-bottom: 10px; }
        .permit-header h1 { font-size: 18px; margin: 5px 0; }
        .permit-header h2 { font-size: 22px; margin: 10px 0; color: #d63638; }
        .permit-info { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .permit-info-item { flex: 1; min-width: 180px; }
        .permit-info-item label { font-weight: bold; display: block; color: #666; font-size: 12px; }
        .permit-info-item span { font-size: 15px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #333; padding: 10px; text-align: center; }
        th { background: #f0f0f0; font-weight: bold; }
        .purpose-section { margin: 15px 0; padding: 10px; border: 1px solid #ddd; background: #fafafa; }
        .purpose-section h4 { margin-bottom: 5px; color: #666; }
        .notes-section { margin: 15px 0; padding: 10px; border: 1px solid #ddd; background: #fff3cd; }
        .notes-section h4 { margin-bottom: 5px; }
        .signatures { display: flex; justify-content: space-around; margin-top: 40px; padding-top: 20px; border-top: 2px solid #333; }
        .signature-box { text-align: center; width: 180px; }
        .signature-box p { margin-bottom: 50px; font-weight: bold; font-size: 13px; }
        .signature-line { border-top: 1px solid #333; padding-top: 5px; min-height: 20px; }
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
            <h2>إذن صرف من المخزن</h2>
            <p><strong>رقم الإذن:</strong> <?php echo esc_html($permit->permit_number); ?></p>
        </div>

        <div class="permit-info">
            <div class="permit-info-item">
                <label>المخزن:</label>
                <span><?php echo esc_html($permit->warehouse_name); ?></span>
            </div>
            <div class="permit-info-item">
                <label>القسم:</label>
                <span><?php echo esc_html($permit->department_name ?: '-'); ?></span>
            </div>
            <div class="permit-info-item">
                <label>اسم المستلم:</label>
                <span><strong><?php echo esc_html($permit->employee_full_name ?: $permit->employee_name ?: '-'); ?></strong></span>
            </div>
            <div class="permit-info-item">
                <label>مكان التخزين:</label>
                <span><?php echo esc_html($permit->storage_location ?: '-'); ?></span>
            </div>
            <div class="permit-info-item">
                <label>تاريخ الإذن:</label>
                <span><?php echo IW_Admin::format_date($permit->created_at); ?></span>
            </div>
            <div class="permit-info-item">
                <label>تاريخ التسليم:</label>
                <span><?php echo IW_Admin::format_date($permit->delivered_at); ?></span>
            </div>
        </div>

        <?php if ($permit->purpose): ?>
            <div class="purpose-section">
                <h4>الغرض من الصرف:</h4>
                <p><?php echo esc_html($permit->purpose); ?></p>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th style="width:5%">م</th>
                    <th style="width:15%">الكود</th>
                    <th style="width:30%">اسم الصنف</th>
                    <th style="width:10%">الوحدة</th>
                    <th style="width:12%">الكمية المطلوبة</th>
                    <th style="width:12%">الكمية المسلمة</th>
                    <th style="width:16%">مكان التخزين</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($permit->items as $index => $item): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo esc_html($item->product_sku); ?></td>
                        <td style="text-align: right;"><?php echo esc_html($item->product_name); ?></td>
                        <td><?php echo esc_html($item->unit); ?></td>
                        <td><?php echo number_format($item->requested_quantity); ?></td>
                        <td><?php echo number_format($item->delivered_quantity ?: $item->approved_quantity ?: 0); ?></td>
                        <td><?php echo esc_html($item->storage_location ?: $item->default_location ?: '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
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
                <p>المستلم</p>
                <div class="signature-line"><?php echo esc_html($permit->employee_full_name ?: $permit->employee_name ?: ''); ?></div>
            </div>
            <div class="signature-box">
                <p>رئيس القسم</p>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <p>عميد المعهد</p>
                <div class="signature-line"><?php echo esc_html($settings['dean_name'] ?? ''); ?></div>
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
