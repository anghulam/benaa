<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('employees');

$companyId = currentCompanyId();
$id = (int) get('id');
$employee = dbFetchOne('SELECT * FROM employees WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]);
if (!$employee) {
    flash('danger', 'الموظف غير موجود');
    redirect('/modules/employees/index.php');
}

$attendance = dbFetchAll('SELECT * FROM attendance WHERE employee_id = ? ORDER BY attendance_date DESC LIMIT 15', 'i', [$id]);
$payrolls = dbFetchAll('SELECT * FROM payroll WHERE employee_id = ? ORDER BY period_year DESC, period_month DESC LIMIT 12', 'i', [$id]);

$sb = statusBadge($employee['status']);
$pageTitle = e($employee['name']);
$pageSubtitle = 'ملف الموظف';
$activeModule = 'employees';
$pageActions = '<a href="' . BASE_URL . '/modules/employees/form.php?id=' . $id . '" class="btn btn-brand"><i class="bi bi-pencil"></i> تعديل</a>';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body section-card">
                <div class="text-center mb-3">
                    <span class="avatar-circle mx-auto" style="width:64px;height:64px;font-size:24px;"><?= e(mb_substr($employee['name'], 0, 1)) ?></span>
                    <h5 class="mt-2 mb-0"><?= e($employee['name']) ?></h5>
                    <div class="text-muted small"><?= e($employee['job_title'] ?: '—') ?></div>
                    <span class="badge bg-<?= $sb[1] ?> mt-1"><?= e($sb[0]) ?></span>
                </div>
                <ul class="list-unstyled small">
                    <li class="mb-2"><i class="bi bi-telephone me-2 text-muted"></i><?= e($employee['phone'] ?: '—') ?></li>
                    <li class="mb-2"><i class="bi bi-envelope me-2 text-muted"></i><?= e($employee['email'] ?: '—') ?></li>
                    <li class="mb-2"><i class="bi bi-person-vcard me-2 text-muted"></i><?= e($employee['national_id'] ?: '—') ?></li>
                    <li class="mb-2"><i class="bi bi-diagram-2 me-2 text-muted"></i><?= e($employee['department'] ?: '—') ?></li>
                    <li class="mb-2"><i class="bi bi-cash me-2 text-muted"></i><?= formatMoney((float) $employee['basic_salary']) ?></li>
                    <li class="mb-2"><i class="bi bi-calendar-event me-2 text-muted"></i>تاريخ التعيين: <?= formatDate($employee['hire_date']) ?></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">آخر سجلات الحضور</div>
            <div class="table-responsive"><table class="table table-hover mb-0">
                <thead><tr><th>التاريخ</th><th>الحالة</th><th>الحضور</th><th>الانصراف</th></tr></thead>
                <tbody>
                <?php if (empty($attendance)): ?><tr><td colspan="4" class="text-center text-muted py-3">لا توجد سجلات</td></tr><?php endif; ?>
                <?php foreach ($attendance as $a): $asb = statusBadge($a['status']); ?>
                    <tr><td><?= formatDate($a['attendance_date']) ?></td><td><span class="badge bg-<?= $asb[1] ?>"><?= e($asb[0]) ?></span></td><td><?= e($a['check_in'] ?: '—') ?></td><td><?= e($a['check_out'] ?: '—') ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
        <div class="card">
            <div class="card-header">سجل الرواتب</div>
            <div class="table-responsive"><table class="table table-hover mb-0">
                <thead><tr><th>الشهر</th><th>الأساسي</th><th>البدلات</th><th>الخصومات</th><th>الصافي</th><th>الحالة</th></tr></thead>
                <tbody>
                <?php if (empty($payrolls)): ?><tr><td colspan="6" class="text-center text-muted py-3">لا توجد رواتب مسجلة</td></tr><?php endif; ?>
                <?php foreach ($payrolls as $pr): $psb = statusBadge($pr['status']); ?>
                    <tr>
                        <td><?= (int) $pr['period_month'] ?>/<?= (int) $pr['period_year'] ?></td>
                        <td><?= formatMoney((float) $pr['basic_salary']) ?></td>
                        <td><?= formatMoney((float) $pr['allowances']) ?></td>
                        <td><?= formatMoney((float) $pr['deductions']) ?></td>
                        <td class="fw-bold"><?= formatMoney((float) $pr['net_salary']) ?></td>
                        <td><span class="badge bg-<?= $psb[1] ?>"><?= e($psb[0]) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
