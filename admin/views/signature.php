<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="<?php echo iw_dir(); ?>">
    <h1><?php echo iw_t('التوقيع الإلكتروني', 'Electronic Signature'); ?></h1>
    <p><?php echo iw_t('يُستخدم التوقيع في اعتماد أوامر الصرف وطلبات الشراء. يجب رفع التوقيع قبل اعتماد أي إذن.', 'Your signature is used when approving withdrawal orders and purchase requests. Upload it before approving any permit.'); ?></p>

    <div style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:5px;max-width:500px;">
        <h2><?php echo iw_t('رفع / تحديث التوقيع', 'Upload / Update Signature'); ?></h2>
        <form id="iw-signature-form" enctype="multipart/form-data">
            <p><input type="file" id="signature_file" accept="image/*" required></p>
            <button type="submit" class="button button-primary"><?php echo iw_t('رفع التوقيع', 'Upload Signature'); ?></button>
        </form>

        <div id="current-signature" style="margin-top:20px;">
            <?php
            $sig = get_user_meta(get_current_user_id(), 'iw_signature_url', true);
            if ($sig) {
                echo '<p><strong>' . iw_t('التوقيع الحالي:', 'Current Signature:') . '</strong></p>';
                echo '<img src="' . esc_url($sig) . '" style="max-height:120px;border:1px solid #ccc;padding:10px;background:#fff;" />';
                echo '<p style="color:green;margin-top:10px;">✓ ' . iw_t('التوقيع مرفوع وجاهز للاستخدام', 'Signature uploaded and ready to use.') . '</p>';
            } else {
                echo '<p style="color:red;"><strong>' . iw_t('لم يتم رفع التوقيع بعد.', 'No signature uploaded yet.') . '</strong> ' . iw_t('يجب رفع التوقيع لتتمكن من اعتماد الأوامر.', 'You must upload a signature to approve orders.') . '</p>';
            }
            ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#iw-signature-form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData();
        formData.append('action', 'iw_upload_signature');
        formData.append('nonce', iwAdmin.nonce);
        formData.append('signature', $('#signature_file')[0].files[0]);

        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(r) {
                alert(r.data.message);
                if (r.success) {
                    $('#current-signature').html('<p><strong>' + iwT('التوقيع الحالي:', 'Current Signature:') + '</strong></p><img src="'+r.data.url+'" style="max-height:120px;border:1px solid #ccc;padding:10px;background:#fff;" /><p style="color:green;margin-top:10px;">✓ ' + iwT('التوقيع مرفوع وجاهز للاستخدام', 'Signature uploaded and ready.') + '</p>');
                }
            }
        });
    });
});
</script>
