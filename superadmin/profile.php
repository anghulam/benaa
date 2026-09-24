<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireSuperAdmin();

$user = currentUser();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = post('name');
    $email = post('email');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($name === '') $errors[] = 'الرجاء إدخال الاسم';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صحيح';

    if (empty($errors)) {
        $existing = dbFetchOne('SELECT id FROM users WHERE email = ? AND id != ?', 'si', [$email, currentUserId()]);
        if ($existing) $errors[] = 'البريد الإلكتروني مستخدم من قبل حساب آخر';
    }

    if ($password !== '' && strlen($password) < 8) {
        $errors[] = 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل';
    } elseif ($password !== $passwordConfirm) {
        $errors[] = 'كلمتا المرور غير متطابقتين';
    }

    if (empty($errors)) {
        if ($password !== '') {
            dbExecute('UPDATE users SET name=?, email=?, password=? WHERE id=?', 'sssi', [$name, $email, password_hash($password, PASSWORD_BCRYPT), currentUserId()]);
        } else {
            dbExecute('UPDATE users SET name=?, email=? WHERE id=?', 'ssi', [$name, $email, currentUserId()]);
        }
        unset($_SESSION['user_cache']);
        flash('success', 'تم تحديث بياناتك بنجاح');
        redirect('/superadmin/profile.php');
    }
    $user = array_merge($user, ['name' => $name, 'email' => $email]);
}

$pageTitle = 'الملف الشخصي';
$pageSubtitle = 'إدارة بيانات حسابك كمالك للنظام';
$activeModule = 'superadmin-profile';
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body section-card">
                <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
                <div class="text-center mb-4">
                    <span class="avatar-circle mx-auto" style="width:64px;height:64px;font-size:24px;"><?= e(mb_substr($user['name'], 0, 1)) ?></span>
                </div>
                <form method="post">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">الاسم الكامل</label>
                        <input type="text" name="name" class="form-control" required value="<?= e($user['name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" class="form-control" required value="<?= e($user['email']) ?>">
                    </div>
                    <hr>
                    <p class="small text-muted">اتركا حقلي كلمة المرور فارغين إن لم ترغب بتغييرها.</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">كلمة مرور جديدة</label>
                            <input type="password" name="password" class="form-control" minlength="8">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">تأكيد كلمة المرور</label>
                            <input type="password" name="password_confirm" class="form-control" minlength="8">
                        </div>
                    </div>
                    <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ التغييرات</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
