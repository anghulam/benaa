<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('expenses');

$companyId = currentCompanyId();
$id = (int) get('id');
$expense = $id ? dbFetchOne('SELECT * FROM expenses WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]) : null;

if ($id && !$expense) {
    flash('danger', 'المصروف غير موجود');
    redirect('/modules/expenses/index.php');
}

$projects = dbFetchAll('SELECT id, name FROM projects WHERE company_id = ? ORDER BY name', 'i', [$companyId]);
$categories = dbFetchAll('SELECT id, name FROM expense_categories WHERE company_id = ? ORDER BY name', 'i', [$companyId]);

$errors = [];
$data = $expense ?: [
    'title' => '', 'project_id' => get('project_id'), 'category_id' => '', 'amount' => '0',
    'expense_date' => date('Y-m-d'), 'paid_by' => '', 'notes' => '', 'attachment' => null,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data['title'] = post('title');
    $data['project_id'] = post('project_id');
    $data['category_id'] = post('category_id');
    $data['amount'] = post('amount', '0');
    $data['expense_date'] = post('expense_date', date('Y-m-d'));
    $data['paid_by'] = post('paid_by');
    $data['notes'] = post('notes');

    if ($data['title'] === '') $errors[] = 'الرجاء إدخال عنوان المصروف';
    if ((float) $data['amount'] <= 0) $errors[] = 'الرجاء إدخال مبلغ صحيح';

    $projectId = $data['project_id'] !== '' ? (int) $data['project_id'] : null;
    $categoryId = $data['category_id'] !== '' ? (int) $data['category_id'] : null;

    $attachment = $data['attachment'] ?? null;
    $uploaded = handleFileUpload('attachment', 'expenses');
    if ($uploaded) $attachment = $uploaded;

    if (empty($errors)) {
        if ($expense) {
            dbExecute(
                'UPDATE expenses SET title=?, project_id=?, category_id=?, amount=?, expense_date=?, paid_by=?, notes=?, attachment=? WHERE id=? AND company_id=?',
                'siidsssiii' ,
                [$data['title'], $projectId, $categoryId, (float) $data['amount'], $data['expense_date'], $data['paid_by'], $data['notes'], $attachment, $id, $companyId]
            );
            flash('success', 'تم تحديث المصروف بنجاح');
        } else {
            dbExecute(
                'INSERT INTO expenses (company_id, project_id, category_id, title, amount, expense_date, paid_by, attachment, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)',
                'iiisdssssi',
                [$companyId, $projectId, $categoryId, $data['title'], (float) $data['amount'], $data['expense_date'], $data['paid_by'], $attachment, $data['notes'], currentUserId()]
            );
            flash('success', 'تمت إضافة المصروف بنجاح');
        }
        redirect('/modules/expenses/index.php');
    }
}

$pageTitle = $expense ? 'تعديل مصروف' : 'مصروف جديد';
$activeModule = 'expenses';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body section-card">
                <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
                <form method="post" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">عنوان المصروف *</label>
                            <input type="text" name="title" class="form-control" required value="<?= e($data['title']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">المبلغ *</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required value="<?= e((string) $data['amount']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">التصنيف</label>
                            <select name="category_id" class="form-select">
                                <option value="">— بدون تصنيف —</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int) $cat['id'] ?>" <?= (string) $data['category_id'] === (string) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
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
                            <label class="form-label">تاريخ المصروف</label>
                            <input type="date" name="expense_date" class="form-control" value="<?= e($data['expense_date']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">دفع بواسطة</label>
                            <input type="text" name="paid_by" class="form-control" value="<?= e($data['paid_by']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">مرفق (إيصال)</label>
                            <input type="file" name="attachment" class="form-control">
                            <?php if (!empty($data['attachment'])): ?><a href="<?= BASE_URL ?>/uploads/<?= e($data['attachment']) ?>" target="_blank" class="small">عرض المرفق الحالي</a><?php endif; ?>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">ملاحظات</label>
                            <textarea name="notes" class="form-control" rows="3"><?= e($data['notes']) ?></textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ</button>
                        <a href="<?= BASE_URL ?>/modules/expenses/index.php" class="btn btn-light">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
