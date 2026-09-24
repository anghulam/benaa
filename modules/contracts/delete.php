<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('contracts');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/modules/contracts/index.php');
}

verifyCsrf();
$id = (int) post('id');
dbExecute('DELETE FROM contracts WHERE id = ? AND company_id = ?', 'ii', [$id, currentCompanyId()]);
flash('success', 'تم حذف العقد بنجاح');
redirect('/modules/contracts/index.php');
