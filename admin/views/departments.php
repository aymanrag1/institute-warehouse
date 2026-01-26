<?php
/**
 * Departments View
 * صفحة إدارة الأقسام والموظفين والمخازن
 */

if (!defined('ABSPATH')) {
    exit;
}

$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'departments';
?>

<div class="wrap iw-wrap" dir="rtl">
    <h1>
        <span class="dashicons dashicons-building"></span>
        إدارة الأقسام والموظفين
    </h1>

    <nav class="nav-tab-wrapper">
        <a href="?page=iw-departments&tab=departments" class="nav-tab <?php echo $tab === 'departments' ? 'nav-tab-active' : ''; ?>">الأقسام</a>
        <a href="?page=iw-departments&tab=employees" class="nav-tab <?php echo $tab === 'employees' ? 'nav-tab-active' : ''; ?>">الموظفين</a>
        <a href="?page=iw-departments&tab=warehouses" class="nav-tab <?php echo $tab === 'warehouses' ? 'nav-tab-active' : ''; ?>">المخازن</a>
    </nav>

    <?php if ($tab === 'departments'): ?>
        <!-- الأقسام -->
        <div class="iw-section">
            <button type="button" class="button button-primary" id="btn-add-dept">
                <span class="dashicons dashicons-plus"></span> إضافة قسم
            </button>

            <table class="wp-list-table widefat fixed striped" id="departments-table">
                <thead>
                    <tr>
                        <th>اسم القسم</th>
                        <th>الكود</th>
                        <th>عدد الموظفين</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="departments-tbody"></tbody>
            </table>
        </div>

    <?php elseif ($tab === 'employees'): ?>
        <!-- الموظفين -->
        <?php $departments = IW_Departments::get_departments(array('status' => 'active')); ?>
        <div class="iw-section">
            <div class="iw-filters-bar">
                <select id="filter-department">
                    <option value="">جميع الأقسام</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo esc_attr($dept->id); ?>"><?php echo esc_html($dept->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" id="filter-search" placeholder="بحث...">
                <button type="button" class="button" id="btn-filter">تصفية</button>
                <button type="button" class="button button-primary" id="btn-add-emp">
                    <span class="dashicons dashicons-plus"></span> إضافة موظف
                </button>
            </div>

            <table class="wp-list-table widefat fixed striped" id="employees-table">
                <thead>
                    <tr>
                        <th>الرقم الوظيفي</th>
                        <th>الاسم</th>
                        <th>القسم</th>
                        <th>المسمى الوظيفي</th>
                        <th>الهاتف</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="employees-tbody"></tbody>
            </table>
        </div>

    <?php elseif ($tab === 'warehouses'): ?>
        <!-- المخازن -->
        <div class="iw-section">
            <button type="button" class="button button-primary" id="btn-add-wh">
                <span class="dashicons dashicons-plus"></span> إضافة مخزن
            </button>

            <table class="wp-list-table widefat fixed striped" id="warehouses-table">
                <thead>
                    <tr>
                        <th>اسم المخزن</th>
                        <th>الموقع</th>
                        <th>الوصف</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="warehouses-tbody"></tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- نموذج القسم -->
<div id="dept-modal" class="iw-modal" style="display:none;">
    <div class="iw-modal-content">
        <div class="iw-modal-header">
            <h2 id="dept-modal-title">إضافة قسم</h2>
            <button type="button" class="iw-modal-close">&times;</button>
        </div>
        <form id="dept-form">
            <input type="hidden" name="id" id="dept-id">
            <div class="iw-modal-body">
                <div class="iw-form-group">
                    <label>اسم القسم <span class="required">*</span></label>
                    <input type="text" name="name" id="dept-name" required>
                </div>
                <div class="iw-form-group">
                    <label>الكود</label>
                    <input type="text" name="code" id="dept-code">
                </div>
                <div class="iw-form-group">
                    <label>الوصف</label>
                    <textarea name="description" id="dept-description"></textarea>
                </div>
                <div class="iw-form-group">
                    <label>الحالة</label>
                    <select name="status" id="dept-status">
                        <option value="active">نشط</option>
                        <option value="inactive">غير نشط</option>
                    </select>
                </div>
            </div>
            <div class="iw-modal-footer">
                <button type="button" class="button" onclick="jQuery('#dept-modal').hide()">إلغاء</button>
                <button type="submit" class="button button-primary">حفظ</button>
            </div>
        </form>
    </div>
</div>

<!-- نموذج الموظف -->
<div id="emp-modal" class="iw-modal" style="display:none;">
    <div class="iw-modal-content">
        <div class="iw-modal-header">
            <h2 id="emp-modal-title">إضافة موظف</h2>
            <button type="button" class="iw-modal-close">&times;</button>
        </div>
        <form id="emp-form">
            <input type="hidden" name="id" id="emp-id">
            <div class="iw-modal-body">
                <div class="iw-form-row">
                    <div class="iw-form-group">
                        <label>الرقم الوظيفي</label>
                        <input type="text" name="employee_number" id="emp-number">
                    </div>
                    <div class="iw-form-group">
                        <label>الاسم <span class="required">*</span></label>
                        <input type="text" name="name" id="emp-name" required>
                    </div>
                </div>
                <div class="iw-form-row">
                    <div class="iw-form-group">
                        <label>القسم</label>
                        <select name="department_id" id="emp-department">
                            <option value="">اختر القسم</option>
                            <?php foreach (IW_Departments::get_departments() as $dept): ?>
                                <option value="<?php echo $dept->id; ?>"><?php echo esc_html($dept->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="iw-form-group">
                        <label>المسمى الوظيفي</label>
                        <input type="text" name="position" id="emp-position">
                    </div>
                </div>
                <div class="iw-form-row">
                    <div class="iw-form-group">
                        <label>الهاتف</label>
                        <input type="text" name="phone" id="emp-phone">
                    </div>
                    <div class="iw-form-group">
                        <label>البريد الإلكتروني</label>
                        <input type="email" name="email" id="emp-email">
                    </div>
                </div>
                <div class="iw-form-group">
                    <label>الحالة</label>
                    <select name="status" id="emp-status">
                        <option value="active">نشط</option>
                        <option value="inactive">غير نشط</option>
                    </select>
                </div>
            </div>
            <div class="iw-modal-footer">
                <button type="button" class="button" onclick="jQuery('#emp-modal').hide()">إلغاء</button>
                <button type="submit" class="button button-primary">حفظ</button>
            </div>
        </form>
    </div>
</div>

<!-- نموذج المخزن -->
<div id="wh-modal" class="iw-modal" style="display:none;">
    <div class="iw-modal-content">
        <div class="iw-modal-header">
            <h2 id="wh-modal-title">إضافة مخزن</h2>
            <button type="button" class="iw-modal-close">&times;</button>
        </div>
        <form id="wh-form">
            <input type="hidden" name="id" id="wh-id">
            <div class="iw-modal-body">
                <div class="iw-form-group">
                    <label>اسم المخزن <span class="required">*</span></label>
                    <input type="text" name="name" id="wh-name" required>
                </div>
                <div class="iw-form-group">
                    <label>الموقع</label>
                    <input type="text" name="location" id="wh-location">
                </div>
                <div class="iw-form-group">
                    <label>الوصف</label>
                    <textarea name="description" id="wh-description"></textarea>
                </div>
                <div class="iw-form-group">
                    <label>الحالة</label>
                    <select name="status" id="wh-status">
                        <option value="active">نشط</option>
                        <option value="inactive">غير نشط</option>
                    </select>
                </div>
            </div>
            <div class="iw-modal-footer">
                <button type="button" class="button" onclick="jQuery('#wh-modal').hide()">إلغاء</button>
                <button type="submit" class="button button-primary">حفظ</button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const tab = '<?php echo $tab; ?>';

    $('.iw-modal-close').on('click', function() {
        $(this).closest('.iw-modal').hide();
    });

    <?php if ($tab === 'departments'): ?>
    // الأقسام
    function loadDepartments() {
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: { action: 'iw_get_departments', nonce: iwAdmin.nonce },
            success: function(response) {
                if (response.success) {
                    let html = '';
                    response.data.forEach(function(d) {
                        html += `<tr>
                            <td><strong>${d.name}</strong></td>
                            <td>${d.code || '-'}</td>
                            <td>${d.employee_count || 0}</td>
                            <td><span class="iw-badge ${d.status === 'active' ? 'success' : 'danger'}">${d.status === 'active' ? 'نشط' : 'غير نشط'}</span></td>
                            <td>
                                <button class="button button-small" onclick="editDept(${d.id})">تعديل</button>
                                <button class="button button-small" onclick="deleteDept(${d.id})">حذف</button>
                            </td>
                        </tr>`;
                    });
                    $('#departments-tbody').html(html || '<tr><td colspan="5" class="iw-empty">لا توجد أقسام</td></tr>');
                }
            }
        });
    }

    $('#btn-add-dept').on('click', function() {
        $('#dept-form')[0].reset();
        $('#dept-id').val('');
        $('#dept-modal-title').text('إضافة قسم');
        $('#dept-modal').show();
    });

    window.editDept = function(id) {
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: { action: 'iw_get_department', nonce: iwAdmin.nonce, id: id },
            success: function(response) {
                if (response.success) {
                    const d = response.data;
                    $('#dept-id').val(d.id);
                    $('#dept-name').val(d.name);
                    $('#dept-code').val(d.code);
                    $('#dept-description').val(d.description);
                    $('#dept-status').val(d.status);
                    $('#dept-modal-title').text('تعديل القسم');
                    $('#dept-modal').show();
                }
            }
        });
    };

    window.deleteDept = function(id) {
        if (!confirm('هل تريد حذف هذا القسم؟')) return;
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: { action: 'iw_delete_department', nonce: iwAdmin.nonce, id: id },
            success: function(response) {
                alert(response.data.message);
                if (response.success) loadDepartments();
            }
        });
    };

    $('#dept-form').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'iw_save_department',
                nonce: iwAdmin.nonce,
                id: $('#dept-id').val(),
                name: $('#dept-name').val(),
                code: $('#dept-code').val(),
                description: $('#dept-description').val(),
                status: $('#dept-status').val()
            },
            success: function(response) {
                alert(response.data.message);
                if (response.success) {
                    $('#dept-modal').hide();
                    loadDepartments();
                }
            }
        });
    });

    loadDepartments();

    <?php elseif ($tab === 'employees'): ?>
    // الموظفين
    function loadEmployees() {
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'iw_get_employees',
                nonce: iwAdmin.nonce,
                department_id: $('#filter-department').val(),
                search: $('#filter-search').val()
            },
            success: function(response) {
                if (response.success) {
                    let html = '';
                    response.data.forEach(function(e) {
                        html += `<tr>
                            <td>${e.employee_number || '-'}</td>
                            <td><strong>${e.name}</strong></td>
                            <td>${e.department_name || '-'}</td>
                            <td>${e.position || '-'}</td>
                            <td>${e.phone || '-'}</td>
                            <td><span class="iw-badge ${e.status === 'active' ? 'success' : 'danger'}">${e.status === 'active' ? 'نشط' : 'غير نشط'}</span></td>
                            <td>
                                <button class="button button-small" onclick="editEmp(${e.id})">تعديل</button>
                                <button class="button button-small" onclick="deleteEmp(${e.id})">حذف</button>
                            </td>
                        </tr>`;
                    });
                    $('#employees-tbody').html(html || '<tr><td colspan="7" class="iw-empty">لا توجد موظفين</td></tr>');
                }
            }
        });
    }

    $('#btn-filter').on('click', loadEmployees);

    $('#btn-add-emp').on('click', function() {
        $('#emp-form')[0].reset();
        $('#emp-id').val('');
        $('#emp-modal-title').text('إضافة موظف');
        $('#emp-modal').show();
    });

    window.editEmp = function(id) {
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: { action: 'iw_get_employee', nonce: iwAdmin.nonce, id: id },
            success: function(response) {
                if (response.success) {
                    const e = response.data;
                    $('#emp-id').val(e.id);
                    $('#emp-number').val(e.employee_number);
                    $('#emp-name').val(e.name);
                    $('#emp-department').val(e.department_id);
                    $('#emp-position').val(e.position);
                    $('#emp-phone').val(e.phone);
                    $('#emp-email').val(e.email);
                    $('#emp-status').val(e.status);
                    $('#emp-modal-title').text('تعديل الموظف');
                    $('#emp-modal').show();
                }
            }
        });
    };

    window.deleteEmp = function(id) {
        if (!confirm('هل تريد حذف هذا الموظف؟')) return;
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: { action: 'iw_delete_employee', nonce: iwAdmin.nonce, id: id },
            success: function(response) {
                alert(response.data.message);
                if (response.success) loadEmployees();
            }
        });
    };

    $('#emp-form').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'iw_save_employee',
                nonce: iwAdmin.nonce,
                id: $('#emp-id').val(),
                employee_number: $('#emp-number').val(),
                name: $('#emp-name').val(),
                department_id: $('#emp-department').val(),
                position: $('#emp-position').val(),
                phone: $('#emp-phone').val(),
                email: $('#emp-email').val(),
                status: $('#emp-status').val()
            },
            success: function(response) {
                alert(response.data.message);
                if (response.success) {
                    $('#emp-modal').hide();
                    loadEmployees();
                }
            }
        });
    });

    loadEmployees();

    <?php elseif ($tab === 'warehouses'): ?>
    // المخازن
    function loadWarehouses() {
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: { action: 'iw_get_warehouses', nonce: iwAdmin.nonce },
            success: function(response) {
                if (response.success) {
                    let html = '';
                    response.data.forEach(function(w) {
                        html += `<tr>
                            <td><strong>${w.name}</strong></td>
                            <td>${w.location || '-'}</td>
                            <td>${w.description || '-'}</td>
                            <td><span class="iw-badge ${w.status === 'active' ? 'success' : 'danger'}">${w.status === 'active' ? 'نشط' : 'غير نشط'}</span></td>
                            <td>
                                <button class="button button-small" onclick="editWh(${w.id})">تعديل</button>
                                <button class="button button-small" onclick="deleteWh(${w.id})">حذف</button>
                            </td>
                        </tr>`;
                    });
                    $('#warehouses-tbody').html(html || '<tr><td colspan="5" class="iw-empty">لا توجد مخازن</td></tr>');
                }
            }
        });
    }

    $('#btn-add-wh').on('click', function() {
        $('#wh-form')[0].reset();
        $('#wh-id').val('');
        $('#wh-modal-title').text('إضافة مخزن');
        $('#wh-modal').show();
    });

    window.editWh = function(id) {
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: { action: 'iw_get_warehouse', nonce: iwAdmin.nonce, id: id },
            success: function(response) {
                if (response.success) {
                    const w = response.data;
                    $('#wh-id').val(w.id);
                    $('#wh-name').val(w.name);
                    $('#wh-location').val(w.location);
                    $('#wh-description').val(w.description);
                    $('#wh-status').val(w.status);
                    $('#wh-modal-title').text('تعديل المخزن');
                    $('#wh-modal').show();
                }
            }
        });
    };

    window.deleteWh = function(id) {
        if (!confirm('هل تريد حذف هذا المخزن؟')) return;
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: { action: 'iw_delete_warehouse', nonce: iwAdmin.nonce, id: id },
            success: function(response) {
                alert(response.data.message);
                if (response.success) loadWarehouses();
            }
        });
    };

    $('#wh-form').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: iwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'iw_save_warehouse',
                nonce: iwAdmin.nonce,
                id: $('#wh-id').val(),
                name: $('#wh-name').val(),
                location: $('#wh-location').val(),
                description: $('#wh-description').val(),
                status: $('#wh-status').val()
            },
            success: function(response) {
                alert(response.data.message);
                if (response.success) {
                    $('#wh-modal').hide();
                    loadWarehouses();
                }
            }
        });
    });

    loadWarehouses();
    <?php endif; ?>
});
</script>
