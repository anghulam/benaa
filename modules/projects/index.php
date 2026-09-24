<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('projects');

$companyId = currentCompanyId();
$search = get('q');
$statusFilter = get('status');

$where = 'p.company_id = ?';
$types = 'i';
$params = [$companyId];
if ($search !== '') {
    $where .= ' AND p.name LIKE ?';
    $types .= 's';
    $params[] = "%$search%";
}
if ($statusFilter !== '') {
    $where .= ' AND p.status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}

$total = (int) (dbFetchOne("SELECT COUNT(*) c FROM projects p WHERE $where", $types, $params)['c'] ?? 0);
$p = paginate($total);
$projects = dbFetchAll(
    "SELECT p.*, c.name AS client_name FROM projects p LEFT JOIN clients c ON c.id = p.client_id WHERE $where ORDER BY p.created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}",
    $types, $params
);

$pageTitle = 'المشاريع';
$pageSubtitle = 'متابعة جميع مشاريع الشركة';
$activeModule = 'projects';
$pageActions = '<a href="' . BASE_URL . '/modules/projects/form.php" class="btn btn-brand"><i class="bi bi-plus-lg"></i> مشروع جديد</a>';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-auto flex-grow-1">
                <input type="text" name="q" class="form-control" placeholder="بحث باسم المشروع..." value="<?= e($search) ?>">
            </div>
            <div class="col-auto">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">كل الحالات</option>
                    <?php foreach (['planning','in_progress','on_hold','completed','cancelled'] as $st): $sb = statusBadge($st); ?>
                        <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= e($sb[0]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i> بحث</button></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>المشروع</th><th>العميل</th><th>الميزانية</th><th>التقدم</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($projects)): ?>
                <tr><td colspan="6"><div class="empty-state"><i class="bi bi-diagram-3"></i>لا توجد مشاريع بعد</div></td></tr>
            <?php endif; ?>
            <?php foreach ($projects as $row): $sb = statusBadge($row['status']); ?>
                <tr>
                    <td>
                        <a href="<?= BASE_URL ?>/modules/projects/view.php?id=<?= (int) $row['id'] ?>" class="fw-semibold text-dark"><?= e($row['name']) ?></a>
                        <?php if ($row['code']): ?><div class="text-muted small">#<?= e($row['code']) ?></div><?php endif; ?>
                    </td>
                    <td><?= e($row['client_name'] ?? '—') ?></td>
                    <td><?= formatMoney((float) $row['budget']) ?></td>
                    <td style="width:120px;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1"><div class="progress-bar bg-warning" style="width:<?= (int) $row['progress'] ?>%"></div></div>
                            <small class="text-muted"><?= (int) $row['progress'] ?>%</small>
                        </div>
                    </td>
                    <td><span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span></td>
                    <td class="text-nowrap">
                        <a href="<?= BASE_URL ?>/modules/projects/form.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <form action="<?= BASE_URL ?>/modules/projects/delete.php" method="post" class="d-inline" data-confirm="هل تريد حذف هذا المشروع؟">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
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
        <li class="page-item <?= $i === $p['page'] ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"><?= $i ?></a></li>
    <?php endfor; ?>
</ul></nav>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
