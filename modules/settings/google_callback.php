<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/google_drive.php';
requireLogin();

$companyId = currentCompanyId();
if (!$companyId) {
    redirect('/modules/auth/login.php');
}

$state = get('state');
$storedState = $_SESSION['google_oauth_state'] ?? '';
unset($_SESSION['google_oauth_state']);

if (get('error') !== '') {
    flash('danger', 'تم إلغاء عملية ربط Google Drive.');
    redirect('/modules/settings/index.php');
}

if ($state === '' || !hash_equals($storedState, $state)) {
    flash('danger', 'انتهت صلاحية طلب الربط، الرجاء المحاولة مرة أخرى.');
    redirect('/modules/settings/index.php');
}

$code = get('code');
if ($code === '') {
    flash('danger', 'تعذّر إتمام ربط Google Drive.');
    redirect('/modules/settings/index.php');
}

$token = googleDriveExchangeCode($code);
if (!$token || empty($token['access_token']) || empty($token['refresh_token'])) {
    flash('danger', 'تعذّر إتمام ربط Google Drive. تأكدوا من منح كل الصلاحيات المطلوبة والمحاولة مرة أخرى.');
    redirect('/modules/settings/index.php');
}

$email = googleDriveUserEmail($token['access_token']);
$expiresAt = date('Y-m-d H:i:s', time() + (int) ($token['expires_in'] ?? 3600));

$existing = googleDriveConnection($companyId);
if ($existing) {
    dbExecute(
        'UPDATE company_google_drive SET drive_email=?, access_token=?, refresh_token=?, token_expires_at=?, connected_at=NOW() WHERE company_id=?',
        'ssssi',
        [$email, $token['access_token'], $token['refresh_token'], $expiresAt, $companyId]
    );
} else {
    dbExecute(
        'INSERT INTO company_google_drive (company_id, drive_email, access_token, refresh_token, token_expires_at) VALUES (?,?,?,?,?)',
        'issss',
        [$companyId, $email, $token['access_token'], $token['refresh_token'], $expiresAt]
    );
}

logActivity('google_drive_connect', 'تم ربط Google Drive' . ($email ? " ($email)" : ''));
flash('success', 'تم ربط Google Drive بنجاح' . ($email ? " ($email)" : '') . '.');
redirect('/modules/settings/index.php');
