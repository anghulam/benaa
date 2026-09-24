<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('employees');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/modules/employees/index.php');
}

verifyCsrf();
$id = (int) post('id');
dbExecute('DELETE FROM employees WHERE id = ? AND company_id = ?', 'ii', [$id, currentCompanyId()]);
flash('success', 'تم حذف الموظف بنجاح');
redirect('/modules/employees/index.php');
