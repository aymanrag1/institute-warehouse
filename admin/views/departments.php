<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap iw-wrap" dir="rtl">
    <h1>إدارة الأقسام والموظفين</h1>

    <div class="iw-tabs">
        <button class="iw-tab active" onclick="iwDeptTab('depts')">الأقسام</button>
        <button class="iw-tab" onclick="iwDeptTab('emps')">الموظفين</button>
    </div>

    <!-- Departments -->
    <div id="dept-tab-depts" class="iw-tab-content">
        <h2>الأقسام <button class="button button-primary" onclick="iwShowDeptForm()">إضافة قسم</button></h2>
        <div id="iw-dept-form-wrap" style="display:none;background:#f9f9f9;padding:15px;margin-bottom:15px;border:1px solid #ddd;">
            <form id="iw-dept-form">
                <input type="hidden" id="dept_id" value="0">
                <table class="form-table">
                    <tr><th>اسم القسم *</th><td><input type="text" id="dept_name" class="regular-text" required></td></tr>
                    <tr><th>الوصف</th><td><textarea id="dept_desc" class="large-text" rows="2"></textarea></td></tr>
                </table>
                <button type="submit" class="button button-primary">حفظ</button>
                <button type="button" class="button" onclick="$('#iw-dept-form-wrap').hide()">إلغاء</button>
            </form>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>#</th><th>اسم القسم</th><th>الوصف</th><th>إجراءات</th></tr></thead>
            <tbody id="iw-depts-table"></tbody>
        </table>
    </div>

    <!-- Employees -->
    <div id="dept-tab-emps" class="iw-tab-content" style="display:none;">
        <h2>الموظفين <button class="button button-primary" onclick="iwShowEmpForm()">إضافة موظف</button></h2>
        <div id="iw-emp-form-wrap" style="display:none;background:#f9f9f9;padding:15px;margin-bottom:15px;border:1px solid #ddd;">
            <form id="iw-emp-form">
                <input type="hidden" id="emp_id" value="0">
                <table class="form-table">
                    <tr><th>اسم الموظف *</th><td><input type="text" id="emp_name" class="regular-text" required></td></tr>
                    <tr><th>القسم *</th><td><select id="emp_dept_id" class="regular-text" required><option value="">اختر القسم</option></select></td></tr>
                    <tr><th>المسمى الوظيفي</th><td><input type="text" id="emp_position" class="regular-text"></td></tr>
                </table>
                <button type="submit" class="button button-primary">حفظ</button>
                <button type="button" class="button" onclick="$('#iw-emp-form-wrap').hide()">إلغاء</button>
            </form>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>#</th><th>الاسم</th><th>القسم</th><th>المسمى الوظيفي</th><th>إجراءات</th></tr></thead>
            <tbody id="iw-emps-table"></tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    window.iwDeptTab = function(tab) {
        $('.iw-tab-content').hide(); $('.iw-tab').removeClass('active');
        $('#dept-tab-'+tab).show();
        $('[onclick="iwDeptTab(\''+tab+'\')"]').addClass('active');
        if (tab === 'emps') { loadEmps(); loadDeptDropdown(); }
    };

    function loadDepts() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_departments', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) return;
            var h = '';
            r.data.forEach(function(d, i) {
                h += '<tr><td>'+(i+1)+'</td><td>'+d.name+'</td><td>'+(d.description||'-')+'</td>';
                h += '<td><button class="button" onclick="iwEditDept('+d.id+',\''+d.name+'\',\''+( d.description||'')+'\')">تعديل</button> ';
                h += '<button class="button iw-btn-danger" onclick="iwDeleteDept('+d.id+')">حذف</button></td></tr>';
            });
            $('#iw-depts-table').html(h || '<tr><td colspan="4">لا توجد أقسام</td></tr>');
        });
    }
    loadDepts();

    function loadDeptDropdown() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_departments', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) return;
            var h = '<option value="">اختر القسم</option>';
            r.data.forEach(function(d) { h += '<option value="'+d.id+'">'+d.name+'</option>'; });
            $('#emp_dept_id').html(h);
        });
    }

    function loadEmps() {
        $.post(iwAdmin.ajaxurl, {action: 'iw_get_employees', nonce: iwAdmin.nonce}, function(r) {
            if (!r.success) return;
            var h = '';
            r.data.forEach(function(e, i) {
                h += '<tr><td>'+(i+1)+'</td><td>'+e.name+'</td><td>'+(e.department_name||'-')+'</td><td>'+(e.position||'-')+'</td>';
                h += '<td><button class="button" onclick="iwEditEmp('+e.id+',\''+e.name+'\','+e.department_id+',\''+(e.position||'')+'\')">تعديل</button> ';
                h += '<button class="button iw-btn-danger" onclick="iwDeleteEmp('+e.id+')">حذف</button></td></tr>';
            });
            $('#iw-emps-table').html(h || '<tr><td colspan="5">لا يوجد موظفين</td></tr>');
        });
    }

    window.iwShowDeptForm = function() { $('#dept_id').val(0); $('#iw-dept-form')[0].reset(); $('#iw-dept-form-wrap').show(); };
    window.iwEditDept = function(id, name, desc) { $('#dept_id').val(id); $('#dept_name').val(name); $('#dept_desc').val(desc); $('#iw-dept-form-wrap').show(); };
    window.iwDeleteDept = function(id) {
        if (!confirm(iwAdmin.strings.confirm_delete)) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_delete_department', nonce: iwAdmin.nonce, department_id: id}, function(r) { alert(r.data.message); loadDepts(); });
    };

    $('#iw-dept-form').on('submit', function(e) {
        e.preventDefault();
        $.post(iwAdmin.ajaxurl, {action: 'iw_save_department', nonce: iwAdmin.nonce, department_id: $('#dept_id').val(), name: $('#dept_name').val(), description: $('#dept_desc').val()}, function(r) {
            alert(r.data.message); if (r.success) { $('#iw-dept-form-wrap').hide(); loadDepts(); }
        });
    });

    window.iwShowEmpForm = function() { $('#emp_id').val(0); $('#iw-emp-form')[0].reset(); loadDeptDropdown(); $('#iw-emp-form-wrap').show(); };
    window.iwEditEmp = function(id, name, dept, pos) { loadDeptDropdown(); setTimeout(function(){ $('#emp_id').val(id); $('#emp_name').val(name); $('#emp_dept_id').val(dept); $('#emp_position').val(pos); $('#iw-emp-form-wrap').show(); }, 300); };
    window.iwDeleteEmp = function(id) {
        if (!confirm(iwAdmin.strings.confirm_delete)) return;
        $.post(iwAdmin.ajaxurl, {action: 'iw_delete_employee', nonce: iwAdmin.nonce, employee_id: id}, function(r) { alert(r.data.message); loadEmps(); });
    };

    $('#iw-emp-form').on('submit', function(e) {
        e.preventDefault();
        $.post(iwAdmin.ajaxurl, {action: 'iw_save_employee', nonce: iwAdmin.nonce, employee_id: $('#emp_id').val(), name: $('#emp_name').val(), department_id: $('#emp_dept_id').val(), position: $('#emp_position').val()}, function(r) {
            alert(r.data.message); if (r.success) { $('#iw-emp-form-wrap').hide(); loadEmps(); }
        });
    });
});
</script>
