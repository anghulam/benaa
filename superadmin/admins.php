<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = post('action');

    if ($action === 'add') {
        $name = post('name');
        $email = post('email');
        $password = $_POST['password'] ?? '';
        $errors = [];

        if ($name === '') $errors[] = 'الرجاء إدخال الاسم';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صحيح';
        if (strlen($password) < 8) $errors[] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';

        if (empty($errors)) {
            $existing = dbFetchOne('SELECT id FROM users WHERE email = ?', 's', [$email]);
            if ($existing) {
                flash('danger', 'البريد الإلكتروني مستخدم مسبقاً');
            } else {
                dbExecute(
                    'INSERT INTO users (company_id, name, email, password, role, is_super_admin, status) VALUES (NULL, ?, ?, ?, "owner", 1, "active")',
                    'sss',
                    [$name, $email, password_hash($password, PASSWORD_BCRYPT)]
                );
                flash('success', 'تمت إضافة مشرف جديد بنجاح');
            }
        } else {
            foreach ($errors as $err) flash('danger', $err);
        }
    } elseif ($action === 'remove') {
        $targetId = (int) post('id');
        if ($targetId === currentUserId()) {
            flash('danger', 'لا يمكنك إزالة صلاحياتك الخاصة');
        } else {
            $count = (int) (dbFetchOne('SELECT COUNT(*) c FROM users WHERE is_super_admin = 1')['c'] ?? 0);
            if ($count <= 1) {
                flash('danger', 'لا يمكن حذف آخر مشرف في النظام');
            } else {
                dbExecute('DELETE FROM users WHERE id = ? AND is_super_admin = 1', 'i', [$targetId]);
                flash('success', 'تمت إزالة المشرف بنجاح');
            }
        }
    }
    redirect('/superadmin/admins.php');
}

$admins = dbFetchAll('SELECT * FROM users WHERE is_super_admin = 1 ORDER BY created_at');

$pageTitle = 'حسابات المشرفين';
$pageSubtitle = 'إدارة حسابات مالكي النظام الذين يملكون صلاحية الوصول الكامل';
$activeModule = 'superadmin-admins';
$pageActions = '<button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#addAdminModal"><i class="bi bi-plus-lg"></i> مشرف جديد</button>';
require __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>الاسم</th><th>البريد الإلكتروني</th><th>آخر دخول</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($admins as $a): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-circle" style="width:32px;height:32px;font-size:12px;"><?= e(mb_substr($a['name'], 0, 1)) ?></span>
                            <?= e($a['name']) ?>
                            <?php if ((int) $a['id'] === currentUserId()): ?><span class="badge bg-secondary">أنت</span><?php endif; ?>
                        </div>
                    </td>
                    <td><?= e($a['email']) ?></td>
                    <td><?= $a['last_login_at'] ? formatDate($a['last_login_at']) : '—' ?></td>
                    <td>
                        <?php if ((int) $a['id'] !== currentUserId()): ?>
                        <form method="post" data-confirm="هل تريد إزالة صلاحيات هذا المشرف؟">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> إزالة</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header"><h5 class="modal-title">إضافة مشرف جديد</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">الاسم الكامل</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">البريد الإلكتروني</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">كلمة المرور</label><input type="password" name="password" class="form-control" required minlength="8"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button class="btn btn-brand">إضافة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
