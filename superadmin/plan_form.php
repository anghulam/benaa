<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireSuperAdmin();

$id = (int) get('id');
$plan = $id ? dbFetchOne('SELECT * FROM subscription_plans WHERE id = ?', 'i', [$id]) : null;

if ($id && !$plan) {
    flash('danger', 'الباقة غير موجودة');
    redirect('/superadmin/plans.php');
}

$errors = [];
$data = $plan ?: [
    'name' => '', 'slug' => '', 'price' => '0', 'duration_days' => '30', 'max_users' => '5',
    'max_projects' => '10', 'max_storage_mb' => '1000', 'features' => '', 'is_active' => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data['name'] = post('name');
    $data['slug'] = post('slug');
    $data['price'] = post('price', '0');
    $data['duration_days'] = post('duration_days', '30');
    $data['max_users'] = post('max_users', '5');
    $data['max_projects'] = post('max_projects', '10');
    $data['max_storage_mb'] = post('max_storage_mb', '1000');
    $data['features'] = post('features');
    $data['is_active'] = post('is_active', '1');

    if ($data['name'] === '') $errors[] = 'الرجاء إدخال اسم الباقة';
    if ($data['slug'] === '') {
        // أسماء الباقات غالباً عربية، وتحويلها لحروف لاتينية قد ينتج نصاً فارغاً؛
        // لذا نضمن عدم تكرار المعرّف بإضافة لاحقة عشوائية قصيرة دائماً
        $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $data['name']), '-'));
        $data['slug'] = ($base !== '' ? $base : 'plan') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }

    $isActive = $data['is_active'] === '1' ? 1 : 0;

    if (empty($errors)) {
        $existingSlug = dbFetchOne('SELECT id FROM subscription_plans WHERE slug = ? AND id != ?', 'si', [$data['slug'], $id]);
        if ($existingSlug) {
            $errors[] = 'المعرّف (slug) مستخدم مسبقاً لباقة أخرى، الرجاء اختيار معرّف مختلف';
        }
    }

    if (empty($errors)) {
        if ($plan) {
            dbExecute(
                'UPDATE subscription_plans SET name=?, slug=?, price=?, duration_days=?, max_users=?, max_projects=?, max_storage_mb=?, features=?, is_active=? WHERE id=?',
                'ssdiiiisii',
                [$data['name'], $data['slug'], (float) $data['price'], (int) $data['duration_days'], (int) $data['max_users'], (int) $data['max_projects'], (int) $data['max_storage_mb'], $data['features'], $isActive, $id]
            );
            $planId = $id;
            flash('success', 'تم تحديث الباقة بنجاح');
        } else {
            $conn = db();
            dbExecute(
                'INSERT INTO subscription_plans (name, slug, price, duration_days, max_users, max_projects, max_storage_mb, features, is_active) VALUES (?,?,?,?,?,?,?,?,?)',
                'ssdiiiisi',
                [$data['name'], $data['slug'], (float) $data['price'], (int) $data['duration_days'], (int) $data['max_users'], (int) $data['max_projects'], (int) $data['max_storage_mb'], $data['features'], $isActive]
            );
            $planId = $conn->insert_id;
            flash('success', 'تمت إضافة الباقة بنجاح');
        }

        dbExecute('DELETE FROM plan_modules WHERE plan_id = ?', 'i', [$planId]);
        foreach (array_keys(permissionModulesList()) as $moduleKey) {
            $enabled = isset($_POST['module'][$moduleKey]) ? 1 : 0;
            dbExecute(
                'INSERT INTO plan_modules (plan_id, module_key, enabled) VALUES (?,?,?)',
                'isi',
                [$planId, $moduleKey, $enabled]
            );
        }

        redirect('/superadmin/plans.php');
    }
}

// الوحدات المفعّلة حالياً لهذه الباقة لعرضها كنقطة انطلاق في النموذج؛
// باقة غير مخصَّصة بعد (أو باقة جديدة) تُعرَض بكل الوحدات مفعّلة افتراضياً
$effectiveModules = $id ? planModules($id) : null;
if ($effectiveModules === null) {
    $effectiveModules = array_fill_keys(array_keys(permissionModulesList()), true);
}

$pageTitle = $plan ? 'تعديل باقة اشتراك' : 'باقة اشتراك جديدة';
$activeModule = 'superadmin-plans';
require __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body section-card">
                <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
                <form method="post">
                    <?= csrfField() ?>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">اسم الباقة *</label>
                            <input type="text" name="name" class="form-control" required value="<?= e($data['name']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">المعرّف (slug)</label>
                            <input type="text" name="slug" class="form-control" value="<?= e($data['slug']) ?>" placeholder="يُولَّد تلقائياً">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">السعر</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="<?= e((string) $data['price']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">مدة الاشتراك (أيام)</label>
                            <input type="number" name="duration_days" class="form-control" value="<?= e((string) $data['duration_days']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">الحد الأقصى للمستخدمين</label>
                            <input type="number" name="max_users" class="form-control" value="<?= e((string) $data['max_users']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">الحد الأقصى للمشاريع</label>
                            <input type="number" name="max_projects" class="form-control" value="<?= e((string) $data['max_projects']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">مساحة التخزين (ميجابايت)</label>
                            <input type="number" name="max_storage_mb" class="form-control" value="<?= e((string) $data['max_storage_mb']) ?>">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">وصف المزايا</label>
                            <input type="text" name="features" class="form-control" value="<?= e($data['features']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">الحالة</label>
                            <select name="is_active" class="form-select">
                                <option value="1" <?= (string) $data['is_active'] === '1' ? 'selected' : '' ?>>مفعّلة</option>
                                <option value="0" <?= (string) $data['is_active'] === '0' ? 'selected' : '' ?>>معطّلة</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold mb-1">الوحدات المتاحة في هذه الباقة</h6>
                    <p class="text-muted small mb-3">
                        حدّدوا الوحدات التي تستطيع الشركات المشتركة في هذه الباقة الوصول إليها. الوحدة غير المفعّلة هنا
                        تختفي عن كل مستخدمي الشركة (حتى مالك الشركة) بغض النظر عن صلاحياتهم الداخلية.
                    </p>
                    <div class="row">
                        <?php foreach (permissionModulesList() as $moduleKey => $moduleLabel): ?>
                            <div class="col-lg-2 col-md-3 col-6 mb-2">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="module_<?= e($moduleKey) ?>"
                                           name="module[<?= e($moduleKey) ?>]"
                                           <?= !empty($effectiveModules[$moduleKey]) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="module_<?= e($moduleKey) ?>"><?= e($moduleLabel) ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ</button>
                        <a href="<?= BASE_URL ?>/superadmin/plans.php" class="btn btn-light">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
