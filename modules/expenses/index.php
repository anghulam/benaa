<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('expenses');

$companyId = currentCompanyId();
$search = get('q');
$projectFilter = get('project_id');

$where = 'e.company_id = ?';
$types = 'i';
$params = [$companyId];
if ($search !== '') {
    $where .= ' AND e.title LIKE ?';
    $types .= 's';
    $params[] = "%$search%";
}
if ($projectFilter !== '') {
    $where .= ' AND e.project_id = ?';
    $types .= 'i';
    $params[] = (int) $projectFilter;
}

$total = (int) (dbFetchOne("SELECT COUNT(*) c FROM expenses e WHERE $where", $types, $params)['c'] ?? 0);
$p = paginate($total);
$expenses = dbFetchAll(
    "SELECT e.*, pr.name AS project_name, ec.name AS category_name FROM expenses e
     LEFT JOIN projects pr ON pr.id = e.project_id LEFT JOIN expense_categories ec ON ec.id = e.category_id
     WHERE $where ORDER BY e.expense_date DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}",
    $types, $params
);
$sumTotal = (float) (dbFetchOne("SELECT COALESCE(SUM(e.amount),0) s FROM expenses e WHERE $where", $types, $params)['s'] ?? 0);
$projects = dbFetchAll('SELECT id, name FROM projects WHERE company_id = ? ORDER BY name', 'i', [$companyId]);

$pageTitle = 'المصروفات';
$pageSubtitle = 'متابعة مصروفات المشاريع والشركة';
$activeModule = 'expenses';
$pageActions = '<a href="' . BASE_URL . '/modules/expenses/form.php" class="btn btn-brand"><i class="bi bi-plus-lg"></i> مصروف جديد</a>';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="stat-card"><div class="stat-icon bg-soft-red"><i class="bi bi-wallet2"></i></div>
            <div><div class="stat-value" style="font-size:18px;"><?= formatMoney($sumTotal) ?></div><div class="stat-label">إجمالي المصروفات (النتائج الحالية)</div></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="get" class="row g-2">
            <div class="col-auto flex-grow-1"><input type="text" name="q" class="form-control" placeholder="بحث بالعنوان..." value="<?= e($search) ?>"></div>
            <div class="col-auto">
                <select name="project_id" class="form-select" onchange="this.form.submit()">
                    <option value="">كل المشاريع</option>
                    <?php foreach ($projects as $pr): ?>
                        <option value="<?= (int) $pr['id'] ?>" <?= $projectFilter === (string) $pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i> بحث</button></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>البند</th><th>التصنيف</th><th>المشروع</th><th>المبلغ</th><th>التاريخ</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($expenses)): ?><tr><td colspan="6"><div class="empty-state"><i class="bi bi-wallet2"></i>لا توجد مصروفات بعد</div></td></tr><?php endif; ?>
            <?php foreach ($expenses as $row): ?>
                <tr>
                    <td class="fw-semibold"><?= e($row['title']) ?></td>
                    <td><?= e($row['category_name'] ?? '—') ?></td>
                    <td><?= e($row['project_name'] ?? '—') ?></td>
                    <td class="text-danger fw-semibold"><?= formatMoney((float) $row['amount']) ?></td>
                    <td><?= formatDate($row['expense_date']) ?></td>
                    <td class="text-nowrap">
                        <?php if ($row['attachment']): ?><a href="<?= BASE_URL ?>/uploads/<?= e($row['attachment']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-paperclip"></i></a><?php endif; ?>
                        <a href="<?= BASE_URL ?>/modules/expenses/form.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <form action="<?= BASE_URL ?>/modules/expenses/delete.php" method="post" class="d-inline" data-confirm="هل تريد حذف هذا المصروف؟">
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
        <li class="page-item <?= $i === $p['page'] ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&project_id=<?= urlencode($projectFilter) ?>"><?= $i ?></a></li>
    <?php endfor; ?>
</ul></nav>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
