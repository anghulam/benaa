<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('suppliers');

$companyId = currentCompanyId();
$id = (int) get('id');
$supplier = $id ? dbFetchOne('SELECT * FROM suppliers WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]) : null;

if ($id && !$supplier) {
    flash('danger', 'المورد غير موجود');
    redirect('/modules/suppliers/index.php');
}

$errors = [];
$data = $supplier ?: ['name' => '', 'phone' => '', 'email' => '', 'address' => '', 'notes' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach (['name', 'phone', 'email', 'address', 'notes'] as $f) $data[$f] = post($f);

    if ($data['name'] === '') $errors[] = 'الرجاء إدخال اسم المورد';

    if (empty($errors)) {
        if ($supplier) {
            dbExecute('UPDATE suppliers SET name=?, phone=?, email=?, address=?, notes=? WHERE id=? AND company_id=?', 'sssssii', [$data['name'], $data['phone'], $data['email'], $data['address'], $data['notes'], $id, $companyId]);
            flash('success', 'تم تحديث بيانات المورد بنجاح');
        } else {
            dbExecute('INSERT INTO suppliers (company_id, name, phone, email, address, notes) VALUES (?,?,?,?,?,?)', 'isssss', [$companyId, $data['name'], $data['phone'], $data['email'], $data['address'], $data['notes']]);
            flash('success', 'تمت إضافة المورد بنجاح');
        }
        redirect('/modules/suppliers/index.php');
    }
}

$pageTitle = $supplier ? 'تعديل مورد' : 'مورد جديد';
$activeModule = 'suppliers';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body section-card">
                <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
                <form method="post">
                    <?= csrfField() ?>
                    <div class="mb-3"><label class="form-label">اسم المورد *</label><input type="text" name="name" class="form-control" required value="<?= e($data['name']) ?>"></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">الجوال</label><input type="text" name="phone" class="form-control" value="<?= e($data['phone']) ?>"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">البريد الإلكتروني</label><input type="email" name="email" class="form-control" value="<?= e($data['email']) ?>"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">العنوان</label><input type="text" name="address" class="form-control" value="<?= e($data['address']) ?>"></div>
                    <div class="mb-3"><label class="form-label">ملاحظات</label><textarea name="notes" class="form-control" rows="3"><?= e($data['notes']) ?></textarea></div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ</button>
                        <a href="<?= BASE_URL ?>/modules/suppliers/index.php" class="btn btn-light">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
