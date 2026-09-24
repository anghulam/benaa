<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('clients');

$companyId = currentCompanyId();
$id = (int) get('id');
$client = $id ? dbFetchOne('SELECT * FROM clients WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]) : null;

if ($id && !$client) {
    flash('danger', 'العميل غير موجود');
    redirect('/modules/clients/index.php');
}

$errors = [];
$data = $client ?: ['name' => '', 'type' => 'individual', 'phone' => '', 'email' => '', 'address' => '', 'tax_number' => '', 'notes' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data['name'] = post('name');
    $data['type'] = post('type', 'individual');
    $data['phone'] = post('phone');
    $data['email'] = post('email');
    $data['address'] = post('address');
    $data['tax_number'] = post('tax_number');
    $data['notes'] = post('notes');

    if ($data['name'] === '') $errors[] = 'الرجاء إدخال اسم العميل';

    if (empty($errors)) {
        if ($client) {
            dbExecute(
                'UPDATE clients SET name=?, type=?, phone=?, email=?, address=?, tax_number=?, notes=? WHERE id=? AND company_id=?',
                'sssssssii',
                [$data['name'], $data['type'], $data['phone'], $data['email'], $data['address'], $data['tax_number'], $data['notes'], $id, $companyId]
            );
            flash('success', 'تم تحديث بيانات العميل بنجاح');
        } else {
            dbExecute(
                'INSERT INTO clients (company_id, name, type, phone, email, address, tax_number, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?)',
                'isssssssi',
                [$companyId, $data['name'], $data['type'], $data['phone'], $data['email'], $data['address'], $data['tax_number'], $data['notes'], currentUserId()]
            );
            flash('success', 'تمت إضافة العميل بنجاح');
        }
        redirect('/modules/clients/index.php');
    }
}

$pageTitle = $client ? 'تعديل بيانات عميل' : 'عميل جديد';
$activeModule = 'clients';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body section-card">
                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger"><?= e($err) ?></div>
                <?php endforeach; ?>
                <form method="post">
                    <?= csrfField() ?>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">اسم العميل *</label>
                            <input type="text" name="name" class="form-control" required value="<?= e($data['name']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">نوع العميل</label>
                            <select name="type" class="form-select">
                                <option value="individual" <?= $data['type'] === 'individual' ? 'selected' : '' ?>>فرد</option>
                                <option value="company" <?= $data['type'] === 'company' ? 'selected' : '' ?>>شركة</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">رقم الجوال</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($data['phone']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" name="email" class="form-control" value="<?= e($data['email']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الرقم الضريبي</label>
                            <input type="text" name="tax_number" class="form-control" value="<?= e($data['tax_number']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">العنوان</label>
                            <input type="text" name="address" class="form-control" value="<?= e($data['address']) ?>">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">ملاحظات</label>
                            <textarea name="notes" class="form-control" rows="3"><?= e($data['notes']) ?></textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ</button>
                        <a href="<?= BASE_URL ?>/modules/clients/index.php" class="btn btn-light">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
