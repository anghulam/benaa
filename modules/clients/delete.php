<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('clients');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/modules/clients/index.php');
}

verifyCsrf();
$id = (int) post('id');
dbExecute('DELETE FROM clients WHERE id = ? AND company_id = ?', 'ii', [$id, currentCompanyId()]);
flash('success', 'تم حذف العميل بنجاح');
redirect('/modules/clients/index.php');
