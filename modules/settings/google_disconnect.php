<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/google_drive.php';
requirePermission('settings');

if (!in_array(currentUser()['role'], ['owner', 'admin'], true) && !isSuperAdmin()) {
    http_response_code(403);
    die('فقط مالك الشركة أو مدير النظام يمكنه إلغاء ربط Google Drive.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/modules/settings/index.php');
}

verifyCsrf();
googleDriveDisconnect(currentCompanyId());
logActivity('google_drive_disconnect', 'تم إلغاء ربط Google Drive');
flash('success', 'تم إلغاء ربط Google Drive بنجاح');
redirect('/modules/settings/index.php');
