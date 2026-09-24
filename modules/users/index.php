<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('users');

$companyId = currentCompanyId();
$users = dbFetchAll('SELECT * FROM users WHERE company_id = ? ORDER BY created_at ASC', 'i', [$companyId]);

$company = currentCompany();
$plan = $company['plan_id'] ? dbFetchOne('SELECT * FROM subscription_plans WHERE id = ?', 'i', [$company['plan_id']]) : null;

$pageTitle = 'المستخدمون';
$pageSubtitle = 'إدارة مستخدمي الشركة وصلاحياتهم';
$activeModule = 'users';
$pageActions = '<a href="' . BASE_URL . '/modules/users/form.php" class="btn btn-brand"><i class="bi bi-plus-lg"></i> مستخدم جديد</a>';
require __DIR__ . '/../../includes/header.php';
?>

<?php if ($plan): ?>
<div class="alert alert-info d-flex align-items-center gap-2">
    <i class="bi bi-info-circle"></i>
    باقتكم الحالية (<?= e($plan['name']) ?>) تسمح بحد أقصى <?= (int) $plan['max_users'] ?> مستخدم — العدد الحالي: <?= count($users) ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>الاسم</th><th>البريد الإلكتروني</th><th>الدور</th><th>الحالة</th><th>آخر دخول</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $row): $sb = statusBadge($row['status']); ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-circle" style="width:32px;height:32px;font-size:12px;"><?= e(mb_substr($row['name'], 0, 1)) ?></span>
                            <?= e($row['name']) ?>
                            <?php if ((int) $row['id'] === currentUserId()): ?><span class="badge bg-secondary">أنت</span><?php endif; ?>
                        </div>
                    </td>
                    <td><?= e($row['email']) ?></td>
                    <td><span class="badge bg-dark"><?= e(roleLabel($row['role'])) ?></span></td>
                    <td><span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span></td>
                    <td><?= $row['last_login_at'] ? formatDate($row['last_login_at']) : '—' ?></td>
                    <td class="text-nowrap">
                        <a href="<?= BASE_URL ?>/modules/users/form.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <?php if ($row['role'] !== 'owner' && (int) $row['id'] !== currentUserId()): ?>
                        <form action="<?= BASE_URL ?>/modules/users/delete.php" method="post" class="d-inline" data-confirm="هل تريد حذف هذا المستخدم؟">
                            <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
