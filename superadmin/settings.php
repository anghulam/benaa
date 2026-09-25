<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireSuperAdmin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $form = post('form', 'logo');

    if ($form === 'logo') {
        $logo = getSystemSetting('system_logo');
        $uploaded = handleFileUpload('system_logo', 'system', ['jpg', 'jpeg', 'png', 'svg']);
        if ($uploaded) $logo = $uploaded;
        setSystemSetting('system_logo', $logo);
        flash('success', 'تم حفظ شعار النظام بنجاح');
        redirect('/superadmin/settings.php');
    }

    if ($form === 'google') {
        $googleClientId = post('google_client_id');
        $googleClientSecret = post('google_client_secret');
        setSystemSetting('google_client_id', $googleClientId);
        setSystemSetting('google_client_secret', $googleClientSecret);
        flash('success', 'تم حفظ إعدادات Google بنجاح');
        redirect('/superadmin/settings.php');
    }
}

$currentLogo = getSystemSetting('system_logo');
$googleClientId = getSystemSetting('google_client_id', '');
$googleClientSecret = getSystemSetting('google_client_secret', '');
$redirectUri = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'your-domain.com') . BASE_URL . '/modules/settings/google_callback.php';

$pageTitle = 'إعدادات النظام';
$pageSubtitle = 'إعدادات عامة على مستوى المنصة بأكملها (شعار النظام، ربط Google Drive)';
$activeModule = 'superadmin-settings';
require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">شعار النظام</div>
            <div class="card-body section-card">
                <form method="post" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="form" value="logo">
                    <div class="mb-3">
                        <?php if ($currentLogo): ?>
                            <img src="<?= BASE_URL ?>/uploads/<?= e($currentLogo) ?>" style="max-height:60px;" class="mb-3 d-block">
                        <?php endif; ?>
                        <label class="form-label">رفع شعار جديد</label>
                        <input type="file" name="system_logo" class="form-control" accept="image/*">
                        <div class="form-text">يظهر هذا الشعار في الموقع التسويقي وشريط التنقل بدلاً من الشعار الافتراضي "ب". اتركوا الحقل فارغاً للإبقاء على الشعار الحالي.</div>
                    </div>
                    <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">ربط Google Drive (لكل الشركات)</div>
            <div class="card-body section-card">
                <p class="text-muted small">
                    لتفعيل ميزة ربط Google Drive لكل شركة مشتركة، أنشئوا تطبيق OAuth في
                    <a href="https://console.cloud.google.com/" target="_blank" rel="noopener">Google Cloud Console</a>
                    وأضيفوا رابط إعادة التوجيه التالي بالضبط ضمن "Authorized redirect URIs":
                </p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control small" value="<?= e($redirectUri) ?>" readonly onclick="this.select()">
                </div>
                <form method="post" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="form" value="google">
                    <div class="mb-3">
                        <label class="form-label">Google Client ID</label>
                        <input type="text" name="google_client_id" class="form-control" value="<?= e($googleClientId) ?>" dir="ltr">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Google Client Secret</label>
                        <input type="password" name="google_client_secret" class="form-control" value="<?= e($googleClientSecret) ?>" dir="ltr">
                    </div>
                    <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ إعدادات Google</button>
                </form>
                <?php if ($googleClientId && $googleClientSecret): ?>
                    <div class="alert alert-success mt-3 mb-0 py-2 small"><i class="bi bi-check-circle"></i> إعدادات Google مكتملة — يمكن للشركات الآن ربط Google Drive من صفحة إعدادات الشركة.</div>
                <?php else: ?>
                    <div class="alert alert-warning mt-3 mb-0 py-2 small"><i class="bi bi-exclamation-triangle"></i> لم تُضبط بيانات Google بعد — لن تتمكن الشركات من ربط Google Drive حتى تُستكمَل هذه الإعدادات.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
