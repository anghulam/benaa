<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('projects');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/modules/projects/index.php');
}

verifyCsrf();
$id = (int) post('id');
dbExecute('DELETE FROM projects WHERE id = ? AND company_id = ?', 'ii', [$id, currentCompanyId()]);
flash('success', 'تم حذف المشروع بنجاح');
redirect('/modules/projects/index.php');
