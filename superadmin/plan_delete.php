<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/superadmin/plans.php');
}

verifyCsrf();
$id = (int) post('id');
dbExecute('DELETE FROM subscription_plans WHERE id = ?', 'i', [$id]);
flash('success', 'تم حذف الباقة بنجاح');
redirect('/superadmin/plans.php');
