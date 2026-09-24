<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('users');

$companyId = currentCompanyId();
$id = (int) get('id');
$user = $id ? dbFetchOne('SELECT * FROM users WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]) : null;

if ($id && !$user) {
    flash('danger', 'المستخدم غير موجود');
    redirect('/modules/users/index.php');
}

$errors = [];
$data = $user ?: ['name' => '', 'email' => '', 'phone' => '', 'role' => 'employee', 'status' => 'active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data['name'] = post('name');
    $data['email'] = post('email');
    $data['phone'] = post('phone');
    $data['role'] = post('role', 'employee');
    $data['status'] = post('status', 'active');
    $password = $_POST['password'] ?? '';

    if ($data['name'] === '') $errors[] = 'الرجاء إدخال الاسم';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صحيح';
    if (!$user && strlen($password) < 8) $errors[] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
    if ($data['role'] === 'owner') $errors[] = 'لا يمكن تعيين دور المالك';

    if (!$user) {
        $existing = dbFetchOne('SELECT id FROM users WHERE email = ?', 's', [$data['email']]);
        if ($existing) $errors[] = 'البريد الإلكتروني مستخدم مسبقاً';

        $company = currentCompany();
        $plan = $company['plan_id'] ? dbFetchOne('SELECT * FROM subscription_plans WHERE id = ?', 'i', [$company['plan_id']]) : null;
        if ($plan) {
            $count = (int) (dbFetchOne('SELECT COUNT(*) c FROM users WHERE company_id = ?', 'i', [$companyId])['c'] ?? 0);
            if ($count >= (int) $plan['max_users']) {
                $errors[] = 'وصلتم للحد الأقصى لعدد المستخدمين في باقتكم الحالية، الرجاء ترقية الباقة';
            }
        }
    }

    if (empty($errors)) {
        if ($user) {
            if ($user['role'] === 'owner') {
                $data['role'] = 'owner'; // لا يمكن تغيير دور المالك
            }
            if ($password !== '') {
                if (strlen($password) < 8) {
                    $errors[] = 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل';
                } else {
                    dbExecute('UPDATE users SET password = ? WHERE id = ? AND company_id = ?', 'sii', [password_hash($password, PASSWORD_BCRYPT), $id, $companyId]);
                }
            }
            if (empty($errors)) {
                dbExecute(
                    'UPDATE users SET name=?, email=?, phone=?, role=?, status=? WHERE id=? AND company_id=?',
                    'sssssii',
                    [$data['name'], $data['email'], $data['phone'], $data['role'], $data['status'], $id, $companyId]
                );
                flash('success', 'تم تحديث بيانات المستخدم بنجاح');
                redirect('/modules/users/index.php');
            }
        } else {
            dbExecute(
                'INSERT INTO users (company_id, name, email, password, phone, role, status) VALUES (?,?,?,?,?,?,?)',
                'issssss',
                [$companyId, $data['name'], $data['email'], password_hash($password, PASSWORD_BCRYPT), $data['phone'], $data['role'], $data['status']]
            );
            flash('success', 'تمت إضافة المستخدم بنجاح');
            redirect('/modules/users/index.php');
        }
    }
}

$pageTitle = $user ? 'تعديل مستخدم' : 'مستخدم جديد';
$activeModule = 'users';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body section-card">
                <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
                <form method="post">
                    <?= csrfField() ?>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">الاسم الكامل *</label><input type="text" name="name" class="form-control" required value="<?= e($data['name']) ?>"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">البريد الإلكتروني *</label><input type="email" name="email" class="form-control" required value="<?= e($data['email']) ?>"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">الجوال</label><input type="text" name="phone" class="form-control" value="<?= e($data['phone']) ?>"></div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الدور</label>
                            <?php if ($user && $user['role'] === 'owner'): ?>
                                <input type="text" class="form-control" value="مالك الشركة" disabled>
                            <?php else: ?>
                                <select name="role" class="form-select">
                                    <?php foreach (['admin','manager','accountant','engineer','employee'] as $r): ?>
                                        <option value="<?= $r ?>" <?= $data['role'] === $r ? 'selected' : '' ?>><?= e(roleLabel($r)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $user ? 'كلمة مرور جديدة (اختياري)' : 'كلمة المرور *' ?></label>
                            <input type="password" name="password" class="form-control" <?= $user ? '' : 'required' ?> minlength="8">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select" <?= ($user && $user['role'] === 'owner') ? 'disabled' : '' ?>>
                                <option value="active" <?= $data['status'] === 'active' ? 'selected' : '' ?>>نشط</option>
                                <option value="inactive" <?= $data['status'] === 'inactive' ? 'selected' : '' ?>>غير نشط</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ</button>
                        <a href="<?= BASE_URL ?>/modules/users/index.php" class="btn btn-light">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
