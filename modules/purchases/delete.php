<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('purchases');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/modules/purchases/index.php');
}

verifyCsrf();
$id = (int) post('id');
dbExecute('DELETE FROM purchase_orders WHERE id = ? AND company_id = ?', 'ii', [$id, currentCompanyId()]);
flash('success', 'تم حذف أمر الشراء بنجاح');
redirect('/modules/purchases/index.php');
