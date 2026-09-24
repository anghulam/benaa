<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireSuperAdmin();

$id = (int) get('id');
$company = dbFetchOne('SELECT c.*, sp.name AS plan_name FROM companies c LEFT JOIN subscription_plans sp ON sp.id = c.plan_id WHERE c.id = ?', 'i', [$id]);
if (!$company) {
    flash('danger', 'الشركة غير موجودة');
    redirect('/superadmin/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = post('action');

    if ($action === 'update_status') {
        $status = post('status');
        if (in_array($status, ['trial', 'active', 'suspended', 'expired'], true)) {
            dbExecute('UPDATE companies SET status = ? WHERE id = ?', 'si', [$status, $id]);
            flash('success', 'تم تحديث حالة الشركة بنجاح');
        }
    } elseif ($action === 'delete_company') {
        $confirmName = post('confirm_name');
        if ($confirmName === $company['name']) {
            dbExecute('DELETE FROM companies WHERE id = ?', 'i', [$id]);
            flash('success', 'تم حذف الشركة وجميع بياناتها نهائياً');
            redirect('/superadmin/index.php');
        } else {
            flash('danger', 'اسم الشركة المُدخل للتأكيد غير مطابق، لم يتم الحذف');
        }
    }
    redirect('/superadmin/company_view.php?id=' . $id);
}

$plans = dbFetchAll('SELECT * FROM subscription_plans ORDER BY price');
$users = dbFetchAll('SELECT * FROM users WHERE company_id = ? ORDER BY created_at', 'i', [$id]);

$usage = [
    'clients' => (int) (dbFetchOne('SELECT COUNT(*) c FROM clients WHERE company_id = ?', 'i', [$id])['c'] ?? 0),
    'projects' => (int) (dbFetchOne('SELECT COUNT(*) c FROM projects WHERE company_id = ?', 'i', [$id])['c'] ?? 0),
    'invoices' => (int) (dbFetchOne('SELECT COUNT(*) c FROM invoices WHERE company_id = ?', 'i', [$id])['c'] ?? 0),
    'employees' => (int) (dbFetchOne('SELECT COUNT(*) c FROM employees WHERE company_id = ?', 'i', [$id])['c'] ?? 0),
    'total_invoiced' => (float) (dbFetchOne('SELECT COALESCE(SUM(total),0) s FROM invoices WHERE company_id = ?', 'i', [$id])['s'] ?? 0),
];

$recentActivity = dbFetchAll(
    'SELECT a.*, u.name AS user_name FROM activity_log a LEFT JOIN users u ON u.id = a.user_id
     WHERE a.company_id = ? ORDER BY a.created_at DESC LIMIT 15', 'i', [$id]
);

$sb = statusBadge($company['status']);
$pageTitle = e($company['name']);
$pageSubtitle = 'تفاصيل الشركة المشتركة';
$activeModule = 'superadmin-companies';
require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-icon bg-soft-teal"><i class="bi bi-people"></i></div>
            <div><div class="stat-value"><?= $usage['clients'] ?></div><div class="stat-label">العملاء</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-icon bg-soft-navy"><i class="bi bi-diagram-3"></i></div>
            <div><div class="stat-value"><?= $usage['projects'] ?></div><div class="stat-label">المشاريع</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-icon bg-soft-amber"><i class="bi bi-person-badge"></i></div>
            <div><div class="stat-value"><?= $usage['employees'] ?></div><div class="stat-label">الموظفون</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-icon bg-soft-green"><i class="bi bi-receipt"></i></div>
            <div><div class="stat-value" style="font-size:16px;"><?= formatMoney($usage['total_invoiced']) ?></div><div class="stat-label">إجمالي الفواتير</div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body section-card">
                <h6 class="fw-bold mb-3">معلومات الشركة</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><span class="text-muted">الحالة: </span><span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span></li>
                    <li class="mb-2"><span class="text-muted">الباقة: </span><?= e($company['plan_name'] ?: '—') ?></li>
                    <li class="mb-2"><span class="text-muted">الجوال: </span><?= e($company['phone'] ?: '—') ?></li>
                    <li class="mb-2"><span class="text-muted">البريد: </span><?= e($company['email'] ?: '—') ?></li>
                    <li class="mb-2"><span class="text-muted">تاريخ التسجيل: </span><?= formatDate($company['created_at']) ?></li>
                    <?php if ($company['status'] === 'trial'): ?>
                        <li class="mb-2"><span class="text-muted">تنتهي التجربة: </span><?= formatDate($company['trial_ends_at']) ?></li>
                    <?php else: ?>
                        <li class="mb-2"><span class="text-muted">ينتهي الاشتراك: </span><?= formatDate($company['subscription_ends_at']) ?></li>
                    <?php endif; ?>
                </ul>

                <form method="post" class="mt-3">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_status">
                    <label class="form-label">تحديث حالة الاشتراك</label>
                    <div class="input-group">
                        <select name="status" class="form-select">
                            <?php foreach (['trial', 'active', 'suspended', 'expired'] as $st): $ssb = statusBadge($st); ?>
                                <option value="<?= $st ?>" <?= $company['status'] === $st ? 'selected' : '' ?>><?= e($ssb[0]) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-secondary">تحديث</button>
                    </div>
                </form>
                <a href="<?= BASE_URL ?>/superadmin/index.php" class="btn btn-sm btn-light w-100 mt-3"><i class="bi bi-gear"></i> تغيير الباقة من صفحة الشركات</a>
            </div>
        </div>

        <div class="card border-danger">
            <div class="card-body section-card">
                <h6 class="fw-bold text-danger mb-2"><i class="bi bi-exclamation-triangle"></i> منطقة الخطر</h6>
                <p class="small text-muted">حذف الشركة سيؤدي لحذف جميع بياناتها نهائياً (مستخدمين، مشاريع، فواتير، مصروفات...) ولا يمكن التراجع عن هذا الإجراء.</p>
                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteCompanyModal"><i class="bi bi-trash"></i> حذف الشركة نهائياً</button>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">مستخدمو الشركة (<?= count($users) ?>)</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>الاسم</th><th>البريد</th><th>الدور</th><th>الحالة</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): $usb = statusBadge($u['status']); ?>
                        <tr>
                            <td><?= e($u['name']) ?></td>
                            <td><?= e($u['email']) ?></td>
                            <td><span class="badge bg-dark"><?= e(roleLabel($u['role'])) ?></span></td>
                            <td><span class="badge bg-<?= $usb[1] ?>"><?= e($usb[0]) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">آخر النشاطات</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>المستخدم</th><th>الإجراء</th><th>الوقت</th></tr></thead>
                    <tbody>
                    <?php if (empty($recentActivity)): ?><tr><td colspan="3" class="text-center text-muted py-3">لا يوجد نشاط مسجل</td></tr><?php endif; ?>
                    <?php foreach ($recentActivity as $a): ?>
                        <tr>
                            <td><?= e($a['user_name'] ?? '—') ?></td>
                            <td><?= e($a['description'] ?: $a['action']) ?></td>
                            <td class="text-muted small"><?= formatDate($a['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteCompanyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete_company">
                <div class="modal-header"><h5 class="modal-title text-danger">تأكيد حذف الشركة</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p>هذا الإجراء نهائي ولا يمكن التراجع عنه. للتأكيد، اكتب اسم الشركة بالضبط:</p>
                    <p class="fw-bold"><?= e($company['name']) ?></p>
                    <input type="text" name="confirm_name" class="form-control" required autocomplete="off">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button class="btn btn-danger">حذف نهائياً</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
