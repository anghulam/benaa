<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (isLoggedIn()) {
    redirect('/modules/dashboard/index.php');
}

$errors = [];
$old = ['company_name' => '', 'owner_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $old['company_name'] = post('company_name');
    $old['owner_name'] = post('owner_name');
    $old['email'] = post('email');
    $old['phone'] = post('phone');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($old['company_name'] === '') $errors[] = 'الرجاء إدخال اسم الشركة';
    if ($old['owner_name'] === '') $errors[] = 'الرجاء إدخال اسمك الكامل';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صحيح';
    if (strlen($password) < 8) $errors[] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
    if ($password !== $passwordConfirm) $errors[] = 'كلمتا المرور غير متطابقتين';

    if (empty($errors)) {
        $result = registerCompany($old['company_name'], $old['owner_name'], $old['email'], $password, $old['phone']);
        if ($result['success']) {
            $login = attemptLogin($old['email'], $password);
            flash('success', 'تم إنشاء حساب شركتكم بنجاح! تم تفعيل فترة تجريبية مجانية لمدة 14 يوماً.');
            redirect('/modules/dashboard/index.php');
        }
        $errors[] = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>إنشاء حساب شركة | <?= e(appName()) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.rtl.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css?v=<?= assetVersion('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="auth-page">
    <div class="row g-0 w-100">
        <div class="col-lg-6 d-none d-lg-flex">
            <div class="auth-brand-panel w-100">
                <?php $__authLogo = getSystemSetting('system_logo'); ?>
                <?php if ($__authLogo): ?>
                    <img src="<?= BASE_URL ?>/uploads/<?= e($__authLogo) ?>" class="auth-logo-img" alt="<?= e(appName()) ?>">
                <?php else: ?>
                    <div class="auth-logo">ب</div>
                <?php endif; ?>
                <h2 class="fw-bold mb-3">ابدأ تجربتك المجانية الآن</h2>
                <p class="text-white-50 fs-6 mb-4" style="max-width:420px;">
                    14 يوماً مجاناً بدون بطاقة ائتمانية، لإدارة مشاريعك ومحاسبة شركتك بكل احترافية.
                </p>
                <ul class="list-unstyled text-white-50">
                    <li class="mb-2"><i class="bi bi-shield-check me-2" style="color: var(--bn-accent-tint);"></i> بياناتك معزولة وآمنة بالكامل</li>
                    <li class="mb-2"><i class="bi bi-lightning-charge me-2" style="color: var(--bn-accent-tint);"></i> إعداد فوري خلال دقيقة</li>
                    <li class="mb-2"><i class="bi bi-headset me-2" style="color: var(--bn-accent-tint);"></i> دعم فني متواصل</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="auth-form-panel h-100">
                <div class="auth-card">
                    <h3 class="fw-bold mb-1">إنشاء حساب شركة جديد</h3>
                    <p class="text-muted mb-4">أنشئ حساب شركتك وابدأ الإدارة الاحترافية فوراً</p>

                    <?php foreach ($errors as $err): ?>
                        <div class="alert alert-danger"><?= e($err) ?></div>
                    <?php endforeach; ?>

                    <form method="post" novalidate>
                        <?= csrfField() ?>
                        <div class="mb-3">
                            <label class="form-label">اسم الشركة</label>
                            <input type="text" name="company_name" class="form-control" required value="<?= e($old['company_name']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">اسمك الكامل</label>
                            <input type="text" name="owner_name" class="form-control" required value="<?= e($old['owner_name']) ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" name="email" class="form-control" required value="<?= e($old['email']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">رقم الجوال</label>
                                <input type="text" name="phone" class="form-control" value="<?= e($old['phone']) ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">كلمة المرور</label>
                                <input type="password" name="password" class="form-control" required minlength="8">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">تأكيد كلمة المرور</label>
                                <input type="password" name="password_confirm" class="form-control" required minlength="8">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-brand w-100 py-2 mt-2">إنشاء الحساب</button>
                    </form>

                    <p class="text-center text-muted mt-4 mb-0">
                        لديك حساب بالفعل؟
                        <a href="<?= BASE_URL ?>/modules/auth/login.php" class="login-link-brand">تسجيل الدخول</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
