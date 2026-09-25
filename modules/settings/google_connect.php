<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/google_drive.php';
requirePermission('settings');

if (!in_array(currentUser()['role'], ['owner', 'admin'], true) && !isSuperAdmin()) {
    http_response_code(403);
    die('فقط مالك الشركة أو مدير النظام يمكنه ربط Google Drive.');
}

if (!googleDriveIsConfigured()) {
    flash('danger', 'لم يتم إعداد بيانات Google بعد من قبل مالك النظام، الرجاء المحاولة لاحقاً.');
    redirect('/modules/settings/index.php');
}

$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;

header('Location: ' . googleDriveAuthUrl($state));
exit;
