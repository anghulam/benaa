<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('invoices');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/modules/invoices/index.php');
}

verifyCsrf();
$id = (int) post('id');
dbExecute('DELETE FROM invoices WHERE id = ? AND company_id = ?', 'ii', [$id, currentCompanyId()]);
flash('success', 'تم حذف الفاتورة بنجاح');
redirect('/modules/invoices/index.php');
