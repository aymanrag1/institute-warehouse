<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>الأقسام والموظفين</h1>

    <div class="notice notice-info">
        <p>
            <strong>ملاحظة:</strong> بيانات الأقسام والموظفين تُقرأ من نظام الموارد البشرية (RSYI HR System).
            <?php if (current_user_can('manage_options')): ?>
            <a href="<?php echo admin_url('admin.php?page=rsyi-hr'); ?>" class="button button-primary" style="margin-right: 10px;">
                إدارة الأقسام والموظفين
            </a>
            <?php endif; ?>
        </p>
    </div>

    <?php if (!iw_is_hr_active()): ?>
    <div class="notice notice-error">
        <p><strong>تنبيه:</strong> نظام الموارد البشرية (RSYI HR System) غير مفعل. يرجى تفعيله أولاً.</p>
    </div>
    <?php else: ?>

    <div class="iw-tabs">
        <button class="iw-tab active" onclick="iwDeptTab('depts')">الأقسام</button>
        <button class="iw-tab" onclick="iwDeptTab('emps')">الموظفين</button>
    </div>

    <!-- Departments -->
    <div id="dept-tab-depts" class="iw-tab-content">
        <h2>الأقسام</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>اسم القسم</th>
                    <th>الكود</th>
                    <th>الوصف</th>
                    <th>المدير</th>
                </tr>
            </thead>
            <tbody id="iw-depts-table"></tbody>
        </table>
    </div>

    <!-- Employees -->
    <div id="dept-tab-emps" class="iw-tab-content" style="display:none;">
        <h2>الموظفين</h2>
        <div style="margin-bottom: 15px;">
            <label for="filter-dept">فلترة حسب القسم:</label>
            <select id="filter-dept" class="regular-text">
                <option value="">جميع الأقسام</option>
            </select>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>رقم الموظف</th>
                    <th>الاسم</th>
                    <th>القسم</th>
                    <th>المسمى الوظيفي</th>
                </tr>
            </thead>
            <tbody id="iw-emps-table"></tbody>
        </table>
    </div>

    <?php endif; ?>
</div>

<?php if (iw_is_hr_active()): ?>
<script>
jQuery(document).ready(function($) {
    var allEmployees = [];

    window.iwDeptTab = function(tab) {
        $('.iw-tab-content').hide();
        $('.iw-tab').removeClass('active');
        $('#dept-tab-'+tab).show();
        $('[onclick="iwDeptTab(\''+tab+'\')"]').addClass('active');
        if (tab === 'emps') {
            loadEmps();
            loadDeptDropdown();
        }
    };

    function loadDepts() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_departments', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) {
                $('#iw-depts-table').html('<tr><td colspan="5">'+r.data.message+'</td></tr>');
                return;
            }
            var h = '';
            r.data.forEach(function(d, i) {
                h += '<tr>';
                h += '<td>'+(i+1)+'</td>';
                h += '<td>'+d.name+'</td>';
                h += '<td>'+(d.code||'-')+'</td>';
                h += '<td>'+(d.description||'-')+'</td>';
                h += '<td>'+(d.manager_name||'-')+'</td>';
                h += '</tr>';
            });
            $('#iw-depts-table').html(h || '<tr><td colspan="5">لا توجد أقسام</td></tr>');
        });
    }
    loadDepts();

    function loadDeptDropdown() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_departments', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) return;
            var h = '<option value="">جميع الأقسام</option>';
            r.data.forEach(function(d) {
                h += '<option value="'+d.id+'">'+d.name+'</option>';
            });
            $('#filter-dept').html(h);
        });
    }

    function loadEmps() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_employees', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) {
                $('#iw-emps-table').html('<tr><td colspan="5">'+r.data.message+'</td></tr>');
                return;
            }
            allEmployees = r.data;
            displayEmployees(r.data);
        });
    }

    function displayEmployees(employees) {
        var h = '';
        employees.forEach(function(e, i) {
            h += '<tr>';
            h += '<td>'+(i+1)+'</td>';
            h += '<td>'+(e.employee_number||'-')+'</td>';
            h += '<td>'+e.name+'</td>';
            h += '<td>'+(e.department_name||'-')+'</td>';
            h += '<td>'+(e.position||'-')+'</td>';
            h += '</tr>';
        });
        $('#iw-emps-table').html(h || '<tr><td colspan="5">لا يوجد موظفين</td></tr>');
    }

    // Filter employees by department
    $(document).on('change', '#filter-dept', function() {
        var deptId = $(this).val();
        if (!deptId) {
            displayEmployees(allEmployees);
        } else {
            var filtered = allEmployees.filter(function(e) {
                return e.department_id == deptId;
            });
            displayEmployees(filtered);
        }
    });
});
</script>
<?php endif; ?>
