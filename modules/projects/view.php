<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('projects');

$companyId = currentCompanyId();
$id = (int) get('id');
$project = dbFetchOne(
    'SELECT p.*, c.name AS client_name, u.name AS manager_name FROM projects p
     LEFT JOIN clients c ON c.id = p.client_id LEFT JOIN users u ON u.id = p.manager_id
     WHERE p.id = ? AND p.company_id = ?', 'ii', [$id, $companyId]
);
if (!$project) {
    flash('danger', 'المشروع غير موجود');
    redirect('/modules/projects/index.php');
}

$contracts = dbFetchAll('SELECT * FROM contracts WHERE project_id = ? AND company_id = ? ORDER BY created_at DESC', 'ii', [$id, $companyId]);
$invoices = dbFetchAll('SELECT * FROM invoices WHERE project_id = ? AND company_id = ? ORDER BY created_at DESC', 'ii', [$id, $companyId]);
$expenses = dbFetchAll('SELECT * FROM expenses WHERE project_id = ? AND company_id = ? ORDER BY expense_date DESC LIMIT 10', 'ii', [$id, $companyId]);

$totalInvoiced = array_sum(array_column($invoices, 'total'));
$totalExpenses = (float) (dbFetchOne('SELECT COALESCE(SUM(amount),0) s FROM expenses WHERE project_id = ? AND company_id = ?', 'ii', [$id, $companyId])['s'] ?? 0);

$sb = statusBadge($project['status']);
$pageTitle = e($project['name']);
$pageSubtitle = 'تفاصيل المشروع';
$activeModule = 'projects';
$pageActions = '<a href="' . BASE_URL . '/modules/projects/form.php?id=' . $id . '" class="btn btn-brand"><i class="bi bi-pencil"></i> تعديل</a>';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-icon bg-soft-navy"><i class="bi bi-cash-stack"></i></div>
            <div><div class="stat-value" style="font-size:18px;"><?= formatMoney((float) $project['budget']) ?></div><div class="stat-label">الميزانية</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-icon bg-soft-teal"><i class="bi bi-receipt"></i></div>
            <div><div class="stat-value" style="font-size:18px;"><?= formatMoney($totalInvoiced) ?></div><div class="stat-label">إجمالي الفواتير</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-icon bg-soft-red"><i class="bi bi-wallet2"></i></div>
            <div><div class="stat-value" style="font-size:18px;"><?= formatMoney($totalExpenses) ?></div><div class="stat-label">إجمالي المصروفات</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-icon bg-soft-amber"><i class="bi bi-percent"></i></div>
            <div><div class="stat-value" style="font-size:18px;"><?= (int) $project['progress'] ?>%</div><div class="stat-label">نسبة الإنجاز</div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body section-card">
                <h6 class="fw-bold mb-3">معلومات المشروع</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><span class="text-muted">الحالة: </span><span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span></li>
                    <li class="mb-2"><span class="text-muted">العميل: </span><?= e($project['client_name'] ?: '—') ?></li>
                    <li class="mb-2"><span class="text-muted">مدير المشروع: </span><?= e($project['manager_name'] ?: '—') ?></li>
                    <li class="mb-2"><span class="text-muted">الموقع: </span><?= e($project['location'] ?: '—') ?></li>
                    <li class="mb-2"><span class="text-muted">تاريخ البدء: </span><?= formatDate($project['start_date']) ?></li>
                    <li class="mb-2"><span class="text-muted">تاريخ الانتهاء: </span><?= formatDate($project['end_date']) ?></li>
                </ul>
                <div class="progress mb-1"><div class="progress-bar bg-warning" style="width:<?= (int) $project['progress'] ?>%"></div></div>
                <?php if ($project['description']): ?><hr><p class="small text-muted mb-0"><?= nl2br(e($project['description'])) ?></p><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span>العقود (<?= count($contracts) ?>)</span>
                <a href="<?= BASE_URL ?>/modules/contracts/form.php?project_id=<?= $id ?>" class="small">+ إضافة عقد</a>
            </div>
            <div class="table-responsive"><table class="table table-hover mb-0">
                <thead><tr><th>رقم العقد</th><th>القيمة</th><th>الحالة</th></tr></thead>
                <tbody>
                <?php if (empty($contracts)): ?><tr><td colspan="3" class="text-center text-muted py-3">لا توجد عقود</td></tr><?php endif; ?>
                <?php foreach ($contracts as $ct): $csb = statusBadge($ct['status']); ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/modules/contracts/view.php?id=<?= (int) $ct['id'] ?>"><?= e($ct['contract_number']) ?></a></td>
                        <td><?= formatMoney((float) $ct['value']) ?></td>
                        <td><span class="badge bg-<?= $csb[1] ?>"><?= e($csb[0]) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span>الفواتير (<?= count($invoices) ?>)</span>
                <a href="<?= BASE_URL ?>/modules/invoices/form.php?project_id=<?= $id ?>" class="small">+ إضافة فاتورة</a>
            </div>
            <div class="table-responsive"><table class="table table-hover mb-0">
                <thead><tr><th>رقم الفاتورة</th><th>الإجمالي</th><th>الحالة</th></tr></thead>
                <tbody>
                <?php if (empty($invoices)): ?><tr><td colspan="3" class="text-center text-muted py-3">لا توجد فواتير</td></tr><?php endif; ?>
                <?php foreach ($invoices as $inv): $isb = statusBadge($inv['status']); ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/modules/invoices/view.php?id=<?= (int) $inv['id'] ?>"><?= e($inv['invoice_number']) ?></a></td>
                        <td><?= formatMoney((float) $inv['total']) ?></td>
                        <td><span class="badge bg-<?= $isb[1] ?>"><?= e($isb[0]) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span>آخر المصروفات</span>
                <a href="<?= BASE_URL ?>/modules/expenses/form.php?project_id=<?= $id ?>" class="small">+ إضافة مصروف</a>
            </div>
            <div class="table-responsive"><table class="table table-hover mb-0">
                <thead><tr><th>البند</th><th>المبلغ</th><th>التاريخ</th></tr></thead>
                <tbody>
                <?php if (empty($expenses)): ?><tr><td colspan="3" class="text-center text-muted py-3">لا توجد مصروفات</td></tr><?php endif; ?>
                <?php foreach ($expenses as $ex): ?>
                    <tr><td><?= e($ex['title']) ?></td><td><?= formatMoney((float) $ex['amount']) ?></td><td><?= formatDate($ex['expense_date']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
