<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('suppliers');

$companyId = currentCompanyId();
$search = get('q');

$where = 'company_id = ?';
$types = 'i';
$params = [$companyId];
if ($search !== '') {
    $where .= ' AND (name LIKE ? OR phone LIKE ?)';
    $types .= 'ss';
    $like = "%$search%";
    $params = array_merge($params, [$like, $like]);
}

$total = (int) (dbFetchOne("SELECT COUNT(*) c FROM suppliers WHERE $where", $types, $params)['c'] ?? 0);
$p = paginate($total);
$suppliers = dbFetchAll("SELECT * FROM suppliers WHERE $where ORDER BY created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}", $types, $params);

$pageTitle = 'الموردون';
$pageSubtitle = 'إدارة موردي المواد والخدمات';
$activeModule = 'suppliers';
$pageActions = '<a href="' . BASE_URL . '/modules/suppliers/form.php" class="btn btn-brand"><i class="bi bi-plus-lg"></i> مورد جديد</a>';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <form method="get" class="row g-2">
            <div class="col-auto flex-grow-1"><input type="text" name="q" class="form-control" placeholder="بحث بالاسم أو الجوال..." value="<?= e($search) ?>"></div>
            <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i> بحث</button></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>الاسم</th><th>الجوال</th><th>البريد الإلكتروني</th><th>العنوان</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($suppliers)): ?><tr><td colspan="5"><div class="empty-state"><i class="bi bi-truck"></i>لا يوجد موردون بعد</div></td></tr><?php endif; ?>
            <?php foreach ($suppliers as $row): ?>
                <tr>
                    <td class="fw-semibold"><?= e($row['name']) ?></td>
                    <td><?= e($row['phone'] ?: '—') ?></td>
                    <td><?= e($row['email'] ?: '—') ?></td>
                    <td><?= e($row['address'] ?: '—') ?></td>
                    <td class="text-nowrap">
                        <a href="<?= BASE_URL ?>/modules/suppliers/form.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <form action="<?= BASE_URL ?>/modules/suppliers/delete.php" method="post" class="d-inline" data-confirm="هل تريد حذف هذا المورد؟">
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
