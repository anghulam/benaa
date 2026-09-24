<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('contracts');

$companyId = currentCompanyId();
$id = (int) get('id');
$contract = $id ? dbFetchOne('SELECT * FROM contracts WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]) : null;

if ($id && !$contract) {
    flash('danger', 'العقد غير موجود');
    redirect('/modules/contracts/index.php');
}

$projects = dbFetchAll('SELECT id, name FROM projects WHERE company_id = ? ORDER BY name', 'i', [$companyId]);
$clients = dbFetchAll('SELECT id, name FROM clients WHERE company_id = ? ORDER BY name', 'i', [$companyId]);

$errors = [];
$data = $contract ?: [
    'contract_number' => generateReferenceNumber('CT'), 'title' => '', 'project_id' => get('project_id'), 'client_id' => '',
    'value' => '0', 'start_date' => '', 'end_date' => '', 'status' => 'draft', 'notes' => '', 'file_path' => null,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data['contract_number'] = post('contract_number');
    $data['title'] = post('title');
    $data['project_id'] = post('project_id');
    $data['client_id'] = post('client_id');
    $data['value'] = post('value', '0');
    $data['start_date'] = post('start_date');
    $data['end_date'] = post('end_date');
    $data['status'] = post('status', 'draft');
    $data['notes'] = post('notes');

    if ($data['contract_number'] === '') $errors[] = 'الرجاء إدخال رقم العقد';
    if ($data['title'] === '') $errors[] = 'الرجاء إدخال عنوان العقد';

    $projectId = $data['project_id'] !== '' ? (int) $data['project_id'] : null;
    $clientId = $data['client_id'] !== '' ? (int) $data['client_id'] : null;
    $startDate = $data['start_date'] !== '' ? $data['start_date'] : null;
    $endDate = $data['end_date'] !== '' ? $data['end_date'] : null;

    $filePath = $data['file_path'] ?? null;
    $uploaded = handleFileUpload('contract_file', 'contracts');
    if ($uploaded) $filePath = $uploaded;

    if (empty($errors)) {
        if ($contract) {
            dbExecute(
                'UPDATE contracts SET contract_number=?, title=?, project_id=?, client_id=?, value=?, start_date=?, end_date=?, status=?, notes=?, file_path=? WHERE id=? AND company_id=?',
                'ssiidsssssii',
                [$data['contract_number'], $data['title'], $projectId, $clientId, (float) $data['value'], $startDate, $endDate, $data['status'], $data['notes'], $filePath, $id, $companyId]
            );
            flash('success', 'تم تحديث العقد بنجاح');
        } else {
            dbExecute(
                'INSERT INTO contracts (company_id, project_id, client_id, contract_number, title, value, start_date, end_date, status, file_path, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                'iiissdsssssi',
                [$companyId, $projectId, $clientId, $data['contract_number'], $data['title'], (float) $data['value'], $startDate, $endDate, $data['status'], $filePath, $data['notes'], currentUserId()]
            );
            flash('success', 'تمت إضافة العقد بنجاح');
        }
        redirect('/modules/contracts/index.php');
    }
}

$pageTitle = $contract ? 'تعديل عقد' : 'عقد جديد';
$activeModule = 'contracts';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-body section-card">
                <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
                <form method="post" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">رقم العقد *</label>
                            <input type="text" name="contract_number" class="form-control" required value="<?= e($data['contract_number']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">عنوان العقد *</label>
                            <input type="text" name="title" class="form-control" required value="<?= e($data['title']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">المشروع</label>
                            <select name="project_id" class="form-select">
                                <option value="">— بدون مشروع —</option>
                                <?php foreach ($projects as $pr): ?>
                                    <option value="<?= (int) $pr['id'] ?>" <?= (string) $data['project_id'] === (string) $pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">العميل</label>
                            <select name="client_id" class="form-select">
                                <option value="">— بدون عميل —</option>
                                <?php foreach ($clients as $cl): ?>
                                    <option value="<?= (int) $cl['id'] ?>" <?= (string) $data['client_id'] === (string) $cl['id'] ? 'selected' : '' ?>><?= e($cl['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">قيمة العقد</label>
                            <input type="number" step="0.01" name="value" class="form-control" value="<?= e((string) $data['value']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">تاريخ البدء</label>
                            <input type="date" name="start_date" class="form-control" value="<?= e($data['start_date']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">تاريخ الانتهاء</label>
                            <input type="date" name="end_date" class="form-control" value="<?= e($data['end_date']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select">
                                <?php foreach (['draft','active','completed','terminated'] as $st): $sb = statusBadge($st); ?>
                                    <option value="<?= $st ?>" <?= $data['status'] === $st ? 'selected' : '' ?>><?= e($sb[0]) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ملف العقد (PDF/صورة)</label>
                            <input type="file" name="contract_file" class="form-control">
                            <?php if (!empty($data['file_path'])): ?>
                                <a href="<?= BASE_URL ?>/uploads/<?= e($data['file_path']) ?>" target="_blank" class="small">عرض الملف الحالي</a>
                            <?php endif; ?>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">ملاحظات</label>
                            <textarea name="notes" class="form-control" rows="3"><?= e($data['notes']) ?></textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ</button>
                        <a href="<?= BASE_URL ?>/modules/contracts/index.php" class="btn btn-light">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
