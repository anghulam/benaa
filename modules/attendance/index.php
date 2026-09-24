<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('attendance');

$companyId = currentCompanyId();
$date = get('date', date('Y-m-d'));

// تسجيل حضور سريع
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'quick_mark') {
    verifyCsrf();
    $employeeId = (int) post('employee_id');
    $status = post('status', 'present');
    $checkIn = post('check_in');
    $checkOut = post('check_out');
    $markDate = post('attendance_date', $date);

    $exists = dbFetchOne('SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = ?', 'is', [$employeeId, $markDate]);
    if ($exists) {
        dbExecute(
            'UPDATE attendance SET status=?, check_in=?, check_out=? WHERE id=?',
            'sssi',
            [$status, $checkIn ?: null, $checkOut ?: null, $exists['id']]
        );
    } else {
        dbExecute(
            'INSERT INTO attendance (company_id, employee_id, attendance_date, status, check_in, check_out) VALUES (?,?,?,?,?,?)',
            'iissss',
            [$companyId, $employeeId, $markDate, $status, $checkIn ?: null, $checkOut ?: null]
        );
    }
    flash('success', 'تم تسجيل الحضور بنجاح');
    redirect('/modules/attendance/index.php?date=' . urlencode($markDate));
}

$employees = dbFetchAll('SELECT id, name, job_title FROM employees WHERE company_id = ? AND status = "active" ORDER BY name', 'i', [$companyId]);
$records = dbFetchAll('SELECT * FROM attendance WHERE company_id = ? AND attendance_date = ?', 'is', [$companyId, $date]);
$recordsByEmployee = [];
foreach ($records as $r) {
    $recordsByEmployee[$r['employee_id']] = $r;
}

$pageTitle = 'الحضور والانصراف';
$pageSubtitle = 'تسجيل ومتابعة حضور الموظفين اليومي';
$activeModule = 'attendance';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="get" class="d-flex align-items-center gap-2">
            <label class="form-label mb-0">التاريخ:</label>
            <input type="date" name="date" class="form-control" style="max-width:200px;" value="<?= e($date) ?>" onchange="this.form.submit()">
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>الموظف</th><th>الوظيفة</th><th>الحالة</th><th>وقت الحضور</th><th>وقت الانصراف</th><th>تحديث</th></tr></thead>
            <tbody>
            <?php if (empty($employees)): ?><tr><td colspan="6"><div class="empty-state"><i class="bi bi-calendar-check"></i>لا يوجد موظفون نشطون</div></td></tr><?php endif; ?>
            <?php foreach ($employees as $emp): $rec = $recordsByEmployee[$emp['id']] ?? null; ?>
                <tr>
                    <td class="fw-semibold"><?= e($emp['name']) ?></td>
                    <td class="text-muted small"><?= e($emp['job_title'] ?: '—') ?></td>
                    <td colspan="4">
                        <form method="post" class="d-flex flex-wrap align-items-center gap-2">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="quick_mark">
                            <input type="hidden" name="employee_id" value="<?= (int) $emp['id'] ?>">
                            <input type="hidden" name="attendance_date" value="<?= e($date) ?>">
                            <select name="status" class="form-select form-select-sm" style="width:130px;">
                                <?php foreach (['present','absent','leave','sick'] as $st): $sb = statusBadge($st); ?>
                                    <option value="<?= $st ?>" <?= ($rec['status'] ?? 'present') === $st ? 'selected' : '' ?>><?= e($sb[0]) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="time" name="check_in" class="form-control form-control-sm" style="width:120px;" value="<?= e($rec['check_in'] ?? '') ?>" placeholder="وقت الحضور">
                            <input type="time" name="check_out" class="form-control form-control-sm" style="width:120px;" value="<?= e($rec['check_out'] ?? '') ?>" placeholder="وقت الانصراف">
                            <button class="btn btn-sm btn-brand">حفظ</button>
                            <?php if ($rec): $sb = statusBadge($rec['status']); ?>
                                <span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
