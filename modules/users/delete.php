<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('users');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/modules/users/index.php');
}

verifyCsrf();
$companyId = currentCompanyId();
$id = (int) post('id');

if ($id === currentUserId()) {
    flash('danger', 'لا يمكنك حذف حسابك الخاص');
    redirect('/modules/users/index.php');
}

$target = dbFetchOne('SELECT * FROM users WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]);
if ($target && $target['role'] === 'owner') {
    flash('danger', 'لا يمكن حذف مالك الشركة');
    redirect('/modules/users/index.php');
}

dbExecute('DELETE FROM users WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]);
flash('success', 'تم حذف المستخدم بنجاح');
redirect('/modules/users/index.php');
