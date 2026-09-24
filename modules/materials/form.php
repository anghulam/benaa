<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('materials');

$companyId = currentCompanyId();
$id = (int) get('id');
$material = $id ? dbFetchOne('SELECT * FROM materials WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]) : null;

if ($id && !$material) {
    flash('danger', 'المادة غير موجودة');
    redirect('/modules/materials/index.php');
}

$errors = [];
$data = $material ?: ['name' => '', 'category' => '', 'unit' => 'وحدة', 'unit_price' => '0', 'current_stock' => '0', 'min_stock' => '0'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach (['name', 'category', 'unit', 'unit_price', 'current_stock', 'min_stock'] as $f) $data[$f] = post($f);

    if ($data['name'] === '') $errors[] = 'الرجاء إدخال اسم المادة';

    if (empty($errors)) {
        if ($material) {
            dbExecute(
                'UPDATE materials SET name=?, category=?, unit=?, unit_price=?, current_stock=?, min_stock=? WHERE id=? AND company_id=?',
                'sssdddii',
                [$data['name'], $data['category'], $data['unit'], (float) $data['unit_price'], (float) $data['current_stock'], (float) $data['min_stock'], $id, $companyId]
            );
            flash('success', 'تم تحديث بيانات المادة بنجاح');
        } else {
            dbExecute(
                'INSERT INTO materials (company_id, name, category, unit, unit_price, current_stock, min_stock) VALUES (?,?,?,?,?,?,?)',
                'isssddd',
                [$companyId, $data['name'], $data['category'], $data['unit'], (float) $data['unit_price'], (float) $data['current_stock'], (float) $data['min_stock']]
            );
            flash('success', 'تمت إضافة المادة بنجاح');
        }
        redirect('/modules/materials/index.php');
    }
}

$pageTitle = $material ? 'تعديل مادة' : 'مادة جديدة';
$activeModule = 'materials';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body section-card">
                <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
                <form method="post">
                    <?= csrfField() ?>
                    <div class="mb-3"><label class="form-label">اسم المادة *</label><input type="text" name="name" class="form-control" required value="<?= e($data['name']) ?>"></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">التصنيف</label><input type="text" name="category" class="form-control" value="<?= e($data['category']) ?>"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">وحدة القياس</label><input type="text" name="unit" class="form-control" value="<?= e($data['unit']) ?>"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">سعر الوحدة</label><input type="number" step="0.01" name="unit_price" class="form-control" value="<?= e((string) $data['unit_price']) ?>"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">المخزون الحالي</label><input type="number" step="0.01" name="current_stock" class="form-control" value="<?= e((string) $data['current_stock']) ?>"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">الحد الأدنى للمخزون</label><input type="number" step="0.01" name="min_stock" class="form-control" value="<?= e((string) $data['min_stock']) ?>"></div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ</button>
                        <a href="<?= BASE_URL ?>/modules/materials/index.php" class="btn btn-light">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
