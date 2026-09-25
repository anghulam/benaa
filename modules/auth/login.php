<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (isLoggedIn()) {
    redirect(isSuperAdmin() ? '/superadmin/index.php' : '/modules/dashboard/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = post('email');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'الرجاء إدخال البريد الإلكتروني وكلمة المرور';
    } else {
        $result = attemptLogin($email, $password);
        if ($result['success']) {
            redirect(isSuperAdmin() ? '/superadmin/index.php' : '/modules/dashboard/index.php');
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
<title>تسجيل الدخول | <?= e(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.rtl.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="auth-page">
    <div class="row g-0 w-100">
        <div class="col-lg-6 d-none d-lg-flex">
            <div class="auth-brand-panel w-100">
                <div class="auth-logo">ب</div>
                <h2 class="fw-bold mb-3">نظام بناء لإدارة شركات المقاولات</h2>
                <p class="text-white-50 fs-6 mb-4" style="max-width:420px;">
                    منصة SaaS متكاملة لإدارة المشاريع، العقود، الفواتير، الموظفين، المخزون
                    والمشتريات في مكان واحد — مصممة خصيصاً لشركات المقاولات والإنشاءات.
                </p>
                <ul class="list-unstyled text-white-50">
                    <li class="mb-2"><i class="bi bi-check-circle-fill me-2" style="color: var(--bn-accent-tint);"></i> إدارة كاملة للمشاريع والعقود</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill me-2" style="color: var(--bn-accent-tint);"></i> فواتير ومتابعة مالية دقيقة</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill me-2" style="color: var(--bn-accent-tint);"></i> إدارة الموظفين والحضور والرواتب</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill me-2" style="color: var(--bn-accent-tint);"></i> مخزون ومشتريات ذكية</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="auth-form-panel h-100">
                <div class="auth-card">
                    <div class="d-lg-none text-center mb-4">
                        <div class="auth-logo mx-auto">ب</div>
                    </div>
                    <h3 class="fw-bold mb-1">تسجيل الدخول</h3>
                    <p class="text-muted mb-4">مرحباً بعودتك، الرجاء إدخال بياناتك للمتابعة</p>

                    <?php foreach ($errors as $err): ?>
                        <div class="alert alert-danger"><?= e($err) ?></div>
                    <?php endforeach; ?>

                    <form method="post" novalidate>
                        <?= csrfField() ?>
                        <div class="mb-3">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" name="email" class="form-control" required autofocus value="<?= e($email ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">كلمة المرور</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-brand w-100 py-2 mt-2">دخول</button>
                    </form>

                    <p class="text-center text-muted mt-4 mb-0">
                        ليس لديك حساب؟
                        <a href="<?= BASE_URL ?>/modules/auth/register.php" class="login-link-brand">سجّل شركتك الآن</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
