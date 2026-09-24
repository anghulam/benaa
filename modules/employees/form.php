<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('employees');

$companyId = currentCompanyId();
$id = (int) get('id');
$employee = $id ? dbFetchOne('SELECT * FROM employees WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]) : null;

if ($id && !$employee) {
    flash('danger', 'الموظف غير موجود');
    redirect('/modules/employees/index.php');
}

$errors = [];
$data = $employee ?: [
    'name' => '', 'national_id' => '', 'phone' => '', 'email' => '', 'job_title' => '', 'department' => '',
    'basic_salary' => '0', 'hire_date' => date('Y-m-d'), 'status' => 'active', 'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach (['name', 'national_id', 'phone', 'email', 'job_title', 'department', 'basic_salary', 'hire_date', 'status', 'notes'] as $field) {
        $data[$field] = post($field);
    }

    if ($data['name'] === '') $errors[] = 'الرجاء إدخال اسم الموظف';

    $hireDate = $data['hire_date'] !== '' ? $data['hire_date'] : null;

    if (empty($errors)) {
        if ($employee) {
            dbExecute(
                'UPDATE employees SET name=?, national_id=?, phone=?, email=?, job_title=?, department=?, basic_salary=?, hire_date=?, status=?, notes=? WHERE id=? AND company_id=?',
                'ssssssdsssii',
                [$data['name'], $data['national_id'], $data['phone'], $data['email'], $data['job_title'], $data['department'], (float) $data['basic_salary'], $hireDate, $data['status'], $data['notes'], $id, $companyId]
            );
            flash('success', 'تم تحديث بيانات الموظف بنجاح');
        } else {
            dbExecute(
                'INSERT INTO employees (company_id, name, national_id, phone, email, job_title, department, basic_salary, hire_date, status, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                'i' . str_repeat('s', 6) . 'd' . str_repeat('s', 3),
                [$companyId, $data['name'], $data['national_id'], $data['phone'], $data['email'], $data['job_title'], $data['department'], (float) $data['basic_salary'], $hireDate, $data['status'], $data['notes']]
            );
            flash('success', 'تمت إضافة الموظف بنجاح');
        }
        redirect('/modules/employees/index.php');
    }
}

$pageTitle = $employee ? 'تعديل بيانات موظف' : 'موظف جديد';
$activeModule = 'employees';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body section-card">
                <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
                <form method="post">
                    <?= csrfField() ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">اسم الموظف *</label>
                            <input type="text" name="name" class="form-control" required value="<?= e($data['name']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">رقم الهوية</label>
                            <input type="text" name="national_id" class="form-control" value="<?= e($data['national_id']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الجوال</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($data['phone']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" name="email" class="form-control" value="<?= e($data['email']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">المسمى الوظيفي</label>
                            <input type="text" name="job_title" class="form-control" value="<?= e($data['job_title']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">القسم</label>
                            <input type="text" name="department" class="form-control" value="<?= e($data['department']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">الراتب الأساسي</label>
                            <input type="number" step="0.01" name="basic_salary" class="form-control" value="<?= e((string) $data['basic_salary']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">تاريخ التعيين</label>
                            <input type="date" name="hire_date" class="form-control" value="<?= e($data['hire_date']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select">
                                <?php foreach (['active','inactive','terminated'] as $st): $sb = statusBadge($st); ?>
                                    <option value="<?= $st ?>" <?= $data['status'] === $st ? 'selected' : '' ?>><?= e($sb[0]) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">ملاحظات</label>
                            <textarea name="notes" class="form-control" rows="3"><?= e($data['notes']) ?></textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ</button>
                        <a href="<?= BASE_URL ?>/modules/employees/index.php" class="btn btn-light">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
