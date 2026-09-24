<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('payroll');

$companyId = currentCompanyId();
$month = (int) get('month', (string) date('n'));
$year = (int) get('year', (string) date('Y'));
if ($month < 1 || $month > 12) $month = (int) date('n');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = post('action');

    if ($action === 'generate') {
        $employees = dbFetchAll('SELECT * FROM employees WHERE company_id = ? AND status = "active"', 'i', [$companyId]);
        foreach ($employees as $emp) {
            $exists = dbFetchOne('SELECT id FROM payroll WHERE employee_id = ? AND period_month = ? AND period_year = ?', 'iii', [$emp['id'], $month, $year]);
            if (!$exists) {
                $net = (float) $emp['basic_salary'];
                dbExecute(
                    'INSERT INTO payroll (company_id, employee_id, period_month, period_year, basic_salary, allowances, deductions, net_salary, status) VALUES (?,?,?,?,?,0,0,?,"pending")',
                    'iiiidd',
                    [$companyId, $emp['id'], $month, $year, (float) $emp['basic_salary'], $net]
                );
            }
        }
        flash('success', 'تم توليد رواتب الشهر للموظفين النشطين');
    } elseif ($action === 'update') {
        $id = (int) post('id');
        $allowances = (float) post('allowances', '0');
        $deductions = (float) post('deductions', '0');
        $row = dbFetchOne('SELECT * FROM payroll WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]);
        if ($row) {
            $net = (float) $row['basic_salary'] + $allowances - $deductions;
            dbExecute('UPDATE payroll SET allowances=?, deductions=?, net_salary=? WHERE id=? AND company_id=?', 'dddii', [$allowances, $deductions, $net, $id, $companyId]);
            flash('success', 'تم تحديث الراتب بنجاح');
        }
    } elseif ($action === 'mark_paid') {
        $id = (int) post('id');
        dbExecute('UPDATE payroll SET status="paid", paid_at=NOW() WHERE id=? AND company_id=?', 'ii', [$id, $companyId]);
        flash('success', 'تم تسجيل صرف الراتب');
    }

    redirect('/modules/payroll/index.php?month=' . $month . '&year=' . $year);
}

$records = dbFetchAll(
    'SELECT p.*, e.name AS employee_name, e.job_title FROM payroll p JOIN employees e ON e.id = p.employee_id
     WHERE p.company_id = ? AND p.period_month = ? AND p.period_year = ? ORDER BY e.name',
    'iii', [$companyId, $month, $year]
);
$totalNet = array_sum(array_column($records, 'net_salary'));

$pageTitle = 'الرواتب';
$pageSubtitle = 'إدارة رواتب الموظفين الشهرية';
$activeModule = 'payroll';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-auto">
                <select name="month" class="form-select">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= e(date('F', mktime(0, 0, 0, $m, 1))) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="year" class="form-select">
                    <?php for ($y = (int) date('Y') - 2; $y <= (int) date('Y') + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-outline-secondary">عرض</button></div>
            <div class="col-auto ms-auto">
                <form method="post" class="d-inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="generate">
                    <input type="hidden" name="month" value="<?= $month ?>"><input type="hidden" name="year" value="<?= $year ?>">
                    <button class="btn btn-brand"><i class="bi bi-magic"></i> توليد رواتب الشهر</button>
                </form>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span>رواتب <?= e(date('F Y', mktime(0, 0, 0, $month, 1, $year))) ?></span>
        <span class="fw-bold">الإجمالي: <?= formatMoney($totalNet) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>الموظف</th><th>الأساسي</th><th>البدلات</th><th>الخصومات</th><th>الصافي</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($records)): ?>
                <tr><td colspan="7"><div class="empty-state"><i class="bi bi-cash-stack"></i>لم يتم توليد رواتب لهذا الشهر بعد</div></td></tr>
            <?php endif; ?>
            <?php foreach ($records as $row): $sb = statusBadge($row['status']); ?>
                <tr>
                    <td class="fw-semibold"><?= e($row['employee_name']) ?><div class="text-muted small"><?= e($row['job_title'] ?: '') ?></div></td>
                    <td><?= formatMoney((float) $row['basic_salary']) ?></td>
                    <td colspan="2">
                        <form method="post" class="d-flex gap-2 align-items-center">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <input type="number" step="0.01" name="allowances" class="form-control form-control-sm" style="width:100px;" value="<?= e((string) $row['allowances']) ?>" <?= $row['status'] === 'paid' ? 'disabled' : '' ?>>
                            <input type="number" step="0.01" name="deductions" class="form-control form-control-sm" style="width:100px;" value="<?= e((string) $row['deductions']) ?>" <?= $row['status'] === 'paid' ? 'disabled' : '' ?>>
                            <?php if ($row['status'] !== 'paid'): ?><button class="btn btn-sm btn-outline-secondary">تحديث</button><?php endif; ?>
                        </form>
                    </td>
                    <td class="fw-bold"><?= formatMoney((float) $row['net_salary']) ?></td>
                    <td><span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span></td>
                    <td>
                        <?php if ($row['status'] !== 'paid'): ?>
                        <form method="post" data-confirm="تأكيد صرف الراتب؟">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="mark_paid">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> صرف</button>
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
