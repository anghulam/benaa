<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('contracts');

$companyId = currentCompanyId();
$search = get('q');

$where = 'ct.company_id = ?';
$types = 'i';
$params = [$companyId];
if ($search !== '') {
    $where .= ' AND (ct.contract_number LIKE ? OR ct.title LIKE ?)';
    $types .= 'ss';
    $like = "%$search%";
    $params = array_merge($params, [$like, $like]);
}

$total = (int) (dbFetchOne("SELECT COUNT(*) c FROM contracts ct WHERE $where", $types, $params)['c'] ?? 0);
$p = paginate($total);
$contracts = dbFetchAll(
    "SELECT ct.*, cl.name AS client_name, pr.name AS project_name FROM contracts ct
     LEFT JOIN clients cl ON cl.id = ct.client_id LEFT JOIN projects pr ON pr.id = ct.project_id
     WHERE $where ORDER BY ct.created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}",
    $types, $params
);

$pageTitle = 'العقود';
$pageSubtitle = 'إدارة عقود المشاريع مع العملاء';
$activeModule = 'contracts';
$pageActions = '<a href="' . BASE_URL . '/modules/contracts/form.php" class="btn btn-brand"><i class="bi bi-plus-lg"></i> عقد جديد</a>';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <form method="get" class="row g-2">
            <div class="col-auto flex-grow-1"><input type="text" name="q" class="form-control" placeholder="بحث برقم العقد أو العنوان..." value="<?= e($search) ?>"></div>
            <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i> بحث</button></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>رقم العقد</th><th>العنوان</th><th>المشروع</th><th>العميل</th><th>القيمة</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($contracts)): ?><tr><td colspan="7"><div class="empty-state"><i class="bi bi-file-earmark-text"></i>لا توجد عقود بعد</div></td></tr><?php endif; ?>
            <?php foreach ($contracts as $row): $sb = statusBadge($row['status']); ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>/modules/contracts/view.php?id=<?= (int) $row['id'] ?>" class="fw-semibold text-dark"><?= e($row['contract_number']) ?></a></td>
                    <td><?= e($row['title']) ?></td>
                    <td><?= e($row['project_name'] ?? '—') ?></td>
                    <td><?= e($row['client_name'] ?? '—') ?></td>
                    <td><?= formatMoney((float) $row['value']) ?></td>
                    <td><span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span></td>
                    <td class="text-nowrap">
                        <a href="<?= BASE_URL ?>/modules/contracts/form.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <form action="<?= BASE_URL ?>/modules/contracts/delete.php" method="post" class="d-inline" data-confirm="هل تريد حذف هذا العقد؟">
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
