<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('employees');

$companyId = currentCompanyId();
$search = get('q');

$where = 'company_id = ?';
$types = 'i';
$params = [$companyId];
if ($search !== '') {
    $where .= ' AND (name LIKE ? OR job_title LIKE ? OR phone LIKE ?)';
    $types .= 'sss';
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
}

$total = (int) (dbFetchOne("SELECT COUNT(*) c FROM employees WHERE $where", $types, $params)['c'] ?? 0);
$p = paginate($total);
$employees = dbFetchAll("SELECT * FROM employees WHERE $where ORDER BY created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}", $types, $params);

$pageTitle = 'الموظفون';
$pageSubtitle = 'إدارة بيانات موظفي الشركة';
$activeModule = 'employees';
$pageActions = '<a href="' . BASE_URL . '/modules/employees/form.php" class="btn btn-brand"><i class="bi bi-plus-lg"></i> موظف جديد</a>';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <form method="get" class="row g-2">
            <div class="col-auto flex-grow-1"><input type="text" name="q" class="form-control" placeholder="بحث بالاسم أو الوظيفة أو الجوال..." value="<?= e($search) ?>"></div>
            <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i> بحث</button></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>الاسم</th><th>الوظيفة</th><th>القسم</th><th>الراتب الأساسي</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($employees)): ?><tr><td colspan="6"><div class="empty-state"><i class="bi bi-person-badge"></i>لا يوجد موظفون بعد</div></td></tr><?php endif; ?>
            <?php foreach ($employees as $row): $sb = statusBadge($row['status']); ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-circle" style="width:32px;height:32px;font-size:12px;"><?= e(mb_substr($row['name'], 0, 1)) ?></span>
                            <a href="<?= BASE_URL ?>/modules/employees/view.php?id=<?= (int) $row['id'] ?>" class="fw-semibold text-dark"><?= e($row['name']) ?></a>
                        </div>
                    </td>
                    <td><?= e($row['job_title'] ?: '—') ?></td>
                    <td><?= e($row['department'] ?: '—') ?></td>
                    <td><?= formatMoney((float) $row['basic_salary']) ?></td>
                    <td><span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span></td>
                    <td class="text-nowrap">
                        <a href="<?= BASE_URL ?>/modules/employees/form.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <form action="<?= BASE_URL ?>/modules/employees/delete.php" method="post" class="d-inline" data-confirm="هل تريد حذف هذا الموظف؟">
                            <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($p['totalPages'] > 1): ?>
<nav class="mt-3"><ul class="pagination justify-content-center">
    <?php for ($i = 1; $i <= $p['totalPages']; $i++): ?>
        <li class="page-item <?= $i === $p['page'] ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a></li>
    <?php endfor; ?>
</ul></nav>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
