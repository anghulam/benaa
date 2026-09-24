<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('materials');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/modules/materials/index.php');
}

verifyCsrf();
$id = (int) post('id');
dbExecute('DELETE FROM materials WHERE id = ? AND company_id = ?', 'ii', [$id, currentCompanyId()]);
flash('success', 'تم حذف المادة بنجاح');
redirect('/modules/materials/index.php');
