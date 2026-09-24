<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('projects');

$companyId = currentCompanyId();
$id = (int) get('id');
$project = $id ? dbFetchOne('SELECT * FROM projects WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]) : null;

if ($id && !$project) {
    flash('danger', 'المشروع غير موجود');
    redirect('/modules/projects/index.php');
}

$clients = dbFetchAll('SELECT id, name FROM clients WHERE company_id = ? ORDER BY name', 'i', [$companyId]);
$managers = dbFetchAll('SELECT id, name FROM users WHERE company_id = ? AND status = "active" ORDER BY name', 'i', [$companyId]);

$errors = [];
$data = $project ?: [
    'name' => '', 'code' => '', 'client_id' => '', 'manager_id' => '', 'description' => '',
    'location' => '', 'budget' => '0', 'start_date' => '', 'end_date' => '', 'status' => 'planning', 'progress' => '0',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data['name'] = post('name');
    $data['code'] = post('code');
    $data['client_id'] = post('client_id');
    $data['manager_id'] = post('manager_id');
    $data['description'] = post('description');
    $data['location'] = post('location');
    $data['budget'] = post('budget', '0');
    $data['start_date'] = post('start_date');
    $data['end_date'] = post('end_date');
    $data['status'] = post('status', 'planning');
    $data['progress'] = post('progress', '0');

    if ($data['name'] === '') $errors[] = 'الرجاء إدخال اسم المشروع';

    $clientId = $data['client_id'] !== '' ? (int) $data['client_id'] : null;
    $managerId = $data['manager_id'] !== '' ? (int) $data['manager_id'] : null;
    $startDate = $data['start_date'] !== '' ? $data['start_date'] : null;
    $endDate = $data['end_date'] !== '' ? $data['end_date'] : null;
    $progress = max(0, min(100, (int) $data['progress']));

    if (empty($errors)) {
        if ($project) {
            dbExecute(
                'UPDATE projects SET name=?, code=?, client_id=?, manager_id=?, description=?, location=?, budget=?, start_date=?, end_date=?, status=?, progress=? WHERE id=? AND company_id=?',
                'ssiissdsssiii',
                [$data['name'], $data['code'], $clientId, $managerId, $data['description'], $data['location'], (float) $data['budget'], $startDate, $endDate, $data['status'], $progress, $id, $companyId]
            );
            flash('success', 'تم تحديث بيانات المشروع بنجاح');
        } else {
            dbExecute(
                'INSERT INTO projects (company_id, client_id, manager_id, code, name, description, location, budget, start_date, end_date, status, progress, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                'iiissssdsssii',
                [$companyId, $clientId, $managerId, $data['code'], $data['name'], $data['description'], $data['location'], (float) $data['budget'], $startDate, $endDate, $data['status'], $progress, currentUserId()]
            );
            flash('success', 'تمت إضافة المشروع بنجاح');
        }
        redirect('/modules/projects/index.php');
    }
}

$pageTitle = $project ? 'تعديل مشروع' : 'مشروع جديد';
$activeModule = 'projects';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-body section-card">
                <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
                <form method="post">
                    <?= csrfField() ?>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">اسم المشروع *</label>
                            <input type="text" name="name" class="form-control" required value="<?= e($data['name']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">كود المشروع</label>
                            <input type="text" name="code" class="form-control" value="<?= e($data['code']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">العميل</label>
                            <select name="client_id" class="form-select">
                                <option value="">— بدون عميل —</option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>" <?= (string) $data['client_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">مدير المشروع</label>
                            <select name="manager_id" class="form-select">
                                <option value="">— غير محدد —</option>
                                <?php foreach ($managers as $m): ?>
                                    <option value="<?= (int) $m['id'] ?>" <?= (string) $data['manager_id'] === (string) $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الموقع</label>
                            <input type="text" name="location" class="form-control" value="<?= e($data['location']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الميزانية</label>
                            <input type="number" step="0.01" name="budget" class="form-control" value="<?= e((string) $data['budget']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">تاريخ البدء</label>
                            <input type="date" name="start_date" class="form-control" value="<?= e($data['start_date']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">تاريخ الانتهاء</label>
                            <input type="date" name="end_date" class="form-control" value="<?= e($data['end_date']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select">
                                <?php foreach (['planning','in_progress','on_hold','completed','cancelled'] as $st): $sb = statusBadge($st); ?>
                                    <option value="<?= $st ?>" <?= $data['status'] === $st ? 'selected' : '' ?>><?= e($sb[0]) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">نسبة الإنجاز: <span id="progressVal"><?= (int) $data['progress'] ?></span>%</label>
                            <input type="range" min="0" max="100" name="progress" class="form-range" value="<?= (int) $data['progress'] ?>" oninput="document.getElementById('progressVal').innerText=this.value">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">وصف المشروع</label>
                            <textarea name="description" class="form-control" rows="3"><?= e($data['description']) ?></textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ</button>
                        <a href="<?= BASE_URL ?>/modules/projects/index.php" class="btn btn-light">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
