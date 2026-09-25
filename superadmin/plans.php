<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireSuperAdmin();

$plans = dbFetchAll(
    'SELECT sp.*, (SELECT COUNT(*) FROM companies c WHERE c.plan_id = sp.id) AS companies_count
     FROM subscription_plans sp ORDER BY sp.price'
);

$pageTitle = 'خطط الاشتراك';
$pageSubtitle = 'إدارة باقات الاشتراك المتاحة لشركات النظام';
$activeModule = 'superadmin-plans';
$pageActions = '<a href="' . BASE_URL . '/superadmin/plan_form.php" class="btn btn-brand"><i class="bi bi-plus-lg"></i> باقة جديدة</a>';
require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <?php foreach ($plans as $p): ?>
        <div class="col-lg-3 col-md-6">
            <div class="card h-100">
                <div class="card-body section-card d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="fw-bold mb-0"><?= e($p['name']) ?></h5>
                        <span class="badge bg-<?= $p['is_active'] ? 'success' : 'secondary' ?>"><?= $p['is_active'] ? 'مفعّلة' : 'معطّلة' ?></span>
                    </div>
                    <div class="mb-3">
                        <span class="fs-3 fw-bold" style="color: var(--bn-primary);"><?= formatMoney((float) $p['price']) ?></span>
                        <span class="text-muted small">/ <?= (int) $p['duration_days'] ?> يوم</span>
                    </div>
                    <ul class="list-unstyled small text-muted flex-grow-1">
                        <li class="mb-2"><i class="bi bi-people me-1"></i> حتى <?= (int) $p['max_users'] ?> مستخدم</li>
                        <li class="mb-2"><i class="bi bi-diagram-3 me-1"></i> حتى <?= (int) $p['max_projects'] ?> مشروع</li>
                        <li class="mb-2"><i class="bi bi-hdd me-1"></i> <?= (int) $p['max_storage_mb'] ?> ميجابايت تخزين</li>
                        <li class="mb-2"><i class="bi bi-buildings me-1"></i> <?= (int) $p['companies_count'] ?> شركة مشتركة بها</li>
                    </ul>
                    <?php if ($p['features']): ?><p class="small text-muted"><?= e($p['features']) ?></p><?php endif; ?>
                    <div class="d-flex gap-2 mt-2">
                        <a href="<?= BASE_URL ?>/superadmin/plan_form.php?id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-secondary flex-grow-1"><i class="bi bi-pencil"></i> تعديل</a>
                        <form action="<?= BASE_URL ?>/superadmin/plan_delete.php" method="post" data-confirm="هل تريد حذف هذه الباقة؟ الشركات المرتبطة بها ستصبح بدون باقة محددة.">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($plans)): ?>
        <div class="col-12"><div class="empty-state"><i class="bi bi-tags"></i>لا توجد باقات اشتراك بعد</div></div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
