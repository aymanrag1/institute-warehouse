<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>إدارة الصلاحيات</h1>
    <p>اختر المستخدم ثم حدد صلاحياته لكل بند في النظام</p>
    <div class="notice notice-info"><p><strong>ملاحظة:</strong> يجب تعيين صلاحيات لكل مستخدم جديد. المستخدمون بدون صلاحيات لن يتمكنوا من الوصول للنظام (باستثناء مدير الموقع Admin).</p></div>

    <table class="form-table">
        <tr>
            <th>المستخدم</th>
            <td>
                <select id="perm_user_id" class="regular-text">
                    <option value="">اختر المستخدم</option>
                    <?php
                    $users = get_users(array('fields' => array('ID', 'display_name', 'user_login')));
                    foreach ($users as $u) {
                        echo '<option value="' . esc_attr($u->ID) . '">' . esc_html($u->display_name) . ' (' . esc_html($u->user_login) . ')</option>';
                    }
                    ?>
                </select>
                <button class="button" onclick="iwLoadPerms()">تحميل الصلاحيات</button>
            </td>
        </tr>
    </table>

    <div id="perm-matrix" style="display:none;">
        <h2>مصفوفة الصلاحيات</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>البند</th>
                    <th>بدون صلاحية</th>
                    <th>عرض فقط</th>
                    <th>قراءة</th>
                    <th>قراءة وكتابة</th>
                </tr>
            </thead>
            <tbody id="perm-matrix-body"></tbody>
        </table>
        <br>
        <button class="button button-primary button-large" onclick="iwSavePerms()">حفظ الصلاحيات</button>
    </div>

    <!-- Signature Upload Section -->
    <div style="margin-top:40px;border-top:1px solid #ccc;padding-top:20px;">
        <h2>رفع التوقيع الإلكتروني</h2>
        <p>يستخدم التوقيع في اعتماد أوامر الصرف وطلبات الشراء</p>
        <form id="iw-signature-form" enctype="multipart/form-data">
            <input type="file" id="signature_file" accept="image/*" required>
            <button type="submit" class="button button-primary">رفع التوقيع</button>
        </form>
        <div id="current-signature" style="margin-top:10px;">
            <?php
            $sig = get_user_meta(get_current_user_id(), 'iw_signature_url', true);
            if ($sig) {
                echo '<p>التوقيع الحالي:</p><img src="' . esc_url($sig) . '" style="max-height:100px;border:1px solid #ccc;padding:5px;" />';
            }
            ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var features = <?php echo json_encode(IW_Permissions::FEATURES); ?>;

    window.iwLoadPerms = function() {
        var userId = $('#perm_user_id').val();
        if (!userId) { alert('اختر مستخدم'); return; }

        $.post(iwAdmin.ajaxurl, {action: 'iw_get_user_permissions', nonce: iwAdmin.nonce, user_id: userId}, function(r) {
            if (!r.success) return;
            var perms = r.data.permissions;
            var html = '';
            for (var key in features) {
                var current = perms[key] || 'none';
                html += '<tr><td><strong>'+features[key]+'</strong></td>';
                ['none','view','read','read_write'].forEach(function(level) {
                    var checked = current === level ? 'checked' : '';
                    html += '<td><input type="radio" name="perm_'+key+'" value="'+level+'" '+checked+'></td>';
                });
                html += '</tr>';
            }
            $('#perm-matrix-body').html(html);
            $('#perm-matrix').show();
        });
    };

    window.iwSavePerms = function() {
        var userId = $('#perm_user_id').val();
        if (!userId) return;
        var permissions = {};
        for (var key in features) {
            permissions[key] = $('input[name="perm_'+key+'"]:checked').val() || 'none';
        }
        $.post(iwAdmin.ajaxurl, {
            action: 'iw_save_permissions', nonce: iwAdmin.nonce,
            user_id: userId, permissions: permissions
        }, function(r) {
            alert(r.data.message);
        });
    };

    // Signature upload
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
                    $('#current-signature').html('<p>التوقيع الحالي:</p><img src="'+r.data.url+'" style="max-height:100px;border:1px solid #ccc;padding:5px;" />');
                }
            }
        });
    });
});
</script>
