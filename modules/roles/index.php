<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('roles');

$companyId = currentCompanyId();
$modules = permissionModulesList();
$roles = customizableRoles();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $conn = db();
    $conn->begin_transaction();
    try {
        dbExecute('DELETE FROM role_permissions WHERE company_id = ?', 'i', [$companyId]);

        foreach ($roles as $role) {
            foreach (array_keys($modules) as $moduleKey) {
                $allowed = isset($_POST['perm'][$role][$moduleKey]) ? 1 : 0;
                dbExecute(
                    'INSERT INTO role_permissions (company_id, role, module_key, allowed) VALUES (?,?,?,?)',
                    'issi',
                    [$companyId, $role, $moduleKey, $allowed]
                );
            }
        }

        $conn->commit();
        logActivity('update_permissions', 'تحديث مصفوفة صلاحيات الأدوار');
        flash('success', 'تم حفظ صلاحيات الأدوار بنجاح');
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('role permissions save error: ' . $e->getMessage());
        flash('danger', 'حدث خطأ أثناء حفظ الصلاحيات');
    }
    redirect('/modules/roles/index.php');
}

// تحديد الحالة الحالية الفعّالة لكل دور (المخصَّصة إن وُجدت، وإلا الافتراضية) لعرضها كنقطة انطلاق في النموذج
$effective = [];
foreach ($roles as $role) {
    $overrides = companyRoleOverrides($companyId, $role);
    if ($overrides !== null) {
        $effective[$role] = $overrides;
    } else {
        $default = defaultRolePermissions($role);
        $effective[$role] = array_fill_keys($default, true);
    }
}

$pageTitle = 'الأدوار والصلاحيات';
$pageSubtitle = 'تحديد الوحدات المسموح بها لكل دور من موظفي شركتكم';
$activeModule = 'roles';
require __DIR__ . '/../../includes/header.php';
?>

<div class="alert alert-info d-flex align-items-start gap-2">
    <i class="bi bi-info-circle mt-1"></i>
    <div>
        <strong>مالك الشركة</strong> يملك دائماً صلاحية الوصول الكامل لكل الوحدات ولا يظهر في هذه المصفوفة.
        فعّلوا أو ألغوا الوحدات التي يحتاجها كل دور، ثم اضغطوا "حفظ الصلاحيات" لتطبيق التغييرات على كل موظفي الشركة أصحاب هذا الدور.
    </div>
</div>

<form method="post">
    <?= csrfField() ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                <tr>
                    <th>الوحدة</th>
                    <?php foreach ($roles as $role): ?>
                        <th class="text-center"><?= e(roleLabel($role)) ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($modules as $moduleKey => $moduleLabel): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($moduleLabel) ?></td>
                        <?php foreach ($roles as $role): ?>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input" style="width:1.25em;height:1.25em;"
                                       name="perm[<?= e($role) ?>][<?= e($moduleKey) ?>]"
                                       <?= !empty($effective[$role][$moduleKey]) ? 'checked' : '' ?>>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ الصلاحيات</button>
    </div>
</form>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
