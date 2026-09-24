<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('suppliers');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/modules/suppliers/index.php');
}

verifyCsrf();
$id = (int) post('id');
dbExecute('DELETE FROM suppliers WHERE id = ? AND company_id = ?', 'ii', [$id, currentCompanyId()]);
flash('success', 'تم حذف المورد بنجاح');
redirect('/modules/suppliers/index.php');
