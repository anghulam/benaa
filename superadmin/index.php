<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = post('action');
    $companyId = (int) post('company_id');

    if ($action === 'update_status') {
        $status = post('status');
        if (in_array($status, ['trial', 'active', 'suspended', 'expired'], true)) {
            dbExecute('UPDATE companies SET status = ? WHERE id = ?', 'si', [$status, $companyId]);
            flash('success', 'تم تحديث حالة الشركة بنجاح');
        }
    } elseif ($action === 'update_plan') {
        $planId = post('plan_id') !== '' ? (int) post('plan_id') : null;
        $extendDays = (int) post('extend_days', '0');
        if ($extendDays > 0) {
            dbExecute('UPDATE companies SET plan_id = ?, subscription_ends_at = DATE_ADD(COALESCE(GREATEST(subscription_ends_at, NOW()), NOW()), INTERVAL ? DAY), status = "active" WHERE id = ?', 'iii', [$planId, $extendDays, $companyId]);
        } else {
            dbExecute('UPDATE companies SET plan_id = ? WHERE id = ?', 'ii', [$planId, $companyId]);
        }
        flash('success', 'تم تحديث باقة الشركة بنجاح');
    }
    redirect('/superadmin/index.php');
}

$search = get('q');
$where = '1=1';
$types = '';
$params = [];
if ($search !== '') {
    $where .= ' AND c.name LIKE ?';
    $types .= 's';
    $params[] = "%$search%";
}

$companies = dbFetchAll(
    "SELECT c.*, sp.name AS plan_name,
        (SELECT COUNT(*) FROM users u WHERE u.company_id = c.id) AS users_count,
        (SELECT COUNT(*) FROM projects p WHERE p.company_id = c.id) AS projects_count
     FROM companies c LEFT JOIN subscription_plans sp ON sp.id = c.plan_id
     WHERE $where ORDER BY c.created_at DESC",
    $types, $params
);
$plans = dbFetchAll('SELECT * FROM subscription_plans ORDER BY price');

$totalCompanies = count($companies);
$activeCompanies = count(array_filter($companies, fn($c) => $c['status'] === 'active'));
$trialCompanies = count(array_filter($companies, fn($c) => $c['status'] === 'trial'));

$pageTitle = 'إدارة الشركات المشتركة';
$pageSubtitle = 'لوحة تحكم مالك النظام - نظرة عامة على جميع المشتركين';
$activeModule = 'superadmin';
require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="stat-card"><div class="stat-icon bg-soft-navy"><i class="bi bi-buildings"></i></div>
            <div><div class="stat-value"><?= $totalCompanies ?></div><div class="stat-label">إجمالي الشركات</div></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div class="stat-icon bg-soft-green"><i class="bi bi-check-circle"></i></div>
            <div><div class="stat-value"><?= $activeCompanies ?></div><div class="stat-label">شركات نشطة (مدفوعة)</div></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div class="stat-icon bg-soft-amber"><i class="bi bi-hourglass-split"></i></div>
            <div><div class="stat-value"><?= $trialCompanies ?></div><div class="stat-label">في الفترة التجريبية</div></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="get" class="row g-2">
            <div class="col-auto flex-grow-1"><input type="text" name="q" class="form-control" placeholder="بحث باسم الشركة..." value="<?= e($search) ?>"></div>
            <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i> بحث</button></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>الشركة</th><th>الباقة</th><th>المستخدمون</th><th>المشاريع</th><th>الحالة</th><th>تنتهي في</th><th>إجراءات</th></tr></thead>
            <tbody>
            <?php if (empty($companies)): ?><tr><td colspan="7"><div class="empty-state"><i class="bi bi-buildings"></i>لا توجد شركات مسجلة بعد</div></td></tr><?php endif; ?>
            <?php foreach ($companies as $row): $sb = statusBadge($row['status']); ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($row['name']) ?></div>
                        <div class="text-muted small"><?= e($row['email'] ?: $row['phone'] ?: '') ?></div>
                    </td>
                    <td><?= e($row['plan_name'] ?? '—') ?></td>
                    <td><?= (int) $row['users_count'] ?></td>
                    <td><?= (int) $row['projects_count'] ?></td>
                    <td><span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span></td>
                    <td class="small text-muted"><?= formatDate($row['status'] === 'trial' ? $row['trial_ends_at'] : $row['subscription_ends_at']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manageModal<?= (int) $row['id'] ?>"><i class="bi bi-gear"></i> إدارة</button>
                    </td>
                </tr>

                <div class="modal fade" id="manageModal<?= (int) $row['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header"><h5 class="modal-title">إدارة: <?= e($row['name']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <form method="post" class="mb-4">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="company_id" value="<?= (int) $row['id'] ?>">
                                    <label class="form-label">حالة الاشتراك</label>
                                    <div class="input-group">
                                        <select name="status" class="form-select">
                                            <?php foreach (['trial','active','suspended','expired'] as $st): $ssb = statusBadge($st); ?>
                                                <option value="<?= $st ?>" <?= $row['status'] === $st ? 'selected' : '' ?>><?= e($ssb[0]) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-outline-secondary">تحديث</button>
                                    </div>
                                </form>
                                <form method="post">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="update_plan">
                                    <input type="hidden" name="company_id" value="<?= (int) $row['id'] ?>">
                                    <label class="form-label">الباقة</label>
                                    <select name="plan_id" class="form-select mb-2">
                                        <?php foreach ($plans as $pl): ?>
                                            <option value="<?= (int) $pl['id'] ?>" <?= (int) $row['plan_id'] === (int) $pl['id'] ? 'selected' : '' ?>><?= e($pl['name']) ?> (<?= formatMoney((float) $pl['price']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label class="form-label">تمديد الاشتراك (أيام) - اختياري</label>
                                    <input type="number" name="extend_days" class="form-control mb-2" value="0" min="0">
                                    <button class="btn btn-brand w-100">حفظ الباقة</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
