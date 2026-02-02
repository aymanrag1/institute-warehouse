<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>التوقيع الإلكتروني</h1>
    <p>يُستخدم التوقيع في اعتماد أوامر الصرف وطلبات الشراء. يجب رفع التوقيع قبل اعتماد أي إذن.</p>

    <div style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:5px;max-width:500px;">
        <h2>رفع / تحديث التوقيع</h2>
        <form id="iw-signature-form" enctype="multipart/form-data">
            <p><input type="file" id="signature_file" accept="image/*" required></p>
            <button type="submit" class="button button-primary">رفع التوقيع</button>
        </form>

        <div id="current-signature" style="margin-top:20px;">
            <?php
            $sig = get_user_meta(get_current_user_id(), 'iw_signature_url', true);
            if ($sig) {
                echo '<p><strong>التوقيع الحالي:</strong></p>';
                echo '<img src="' . esc_url($sig) . '" style="max-height:120px;border:1px solid #ccc;padding:10px;background:#fff;" />';
                echo '<p style="color:green;margin-top:10px;">✓ التوقيع مرفوع وجاهز للاستخدام</p>';
            } else {
                echo '<p style="color:red;"><strong>لم يتم رفع التوقيع بعد.</strong> يجب رفع التوقيع لتتمكن من اعتماد الأوامر.</p>';
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
                    $('#current-signature').html('<p><strong>التوقيع الحالي:</strong></p><img src="'+r.data.url+'" style="max-height:120px;border:1px solid #ccc;padding:10px;background:#fff;" /><p style="color:green;margin-top:10px;">✓ التوقيع مرفوع وجاهز للاستخدام</p>');
                }
            }
        });
    });
});
</script>
