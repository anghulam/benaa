<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('expenses');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/modules/expenses/index.php');
}

verifyCsrf();
$id = (int) post('id');
dbExecute('DELETE FROM expenses WHERE id = ? AND company_id = ?', 'ii', [$id, currentCompanyId()]);
flash('success', 'تم حذف المصروف بنجاح');
redirect('/modules/expenses/index.php');
