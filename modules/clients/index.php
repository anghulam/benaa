<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('clients');

$companyId = currentCompanyId();
$search = get('q');

$where = 'company_id = ?';
$types = 'i';
$params = [$companyId];
if ($search !== '') {
    $where .= ' AND (name LIKE ? OR phone LIKE ? OR email LIKE ?)';
    $types .= 'sss';
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
}

$total = (int) (dbFetchOne("SELECT COUNT(*) c FROM clients WHERE $where", $types, $params)['c'] ?? 0);
$p = paginate($total);
$clients = dbFetchAll("SELECT * FROM clients WHERE $where ORDER BY created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}", $types, $params);

$pageTitle = 'العملاء';
$pageSubtitle = 'إدارة بيانات عملاء الشركة';
$activeModule = 'clients';
$pageActions = '<a href="' . BASE_URL . '/modules/clients/form.php" class="btn btn-brand"><i class="bi bi-plus-lg"></i> عميل جديد</a>';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-auto flex-grow-1">
                <input type="text" name="q" class="form-control" placeholder="بحث بالاسم أو الجوال أو البريد..." value="<?= e($search) ?>">
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> بحث</button>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr><th>الاسم</th><th>النوع</th><th>الجوال</th><th>البريد الإلكتروني</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($clients)): ?>
                <tr><td colspan="5"><div class="empty-state"><i class="bi bi-people"></i>لا يوجد عملاء حتى الآن</div></td></tr>
            <?php endif; ?>
            <?php foreach ($clients as $c): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-circle" style="width:32px;height:32px;font-size:12px;"><?= e(mb_substr($c['name'], 0, 1)) ?></span>
                            <a href="<?= BASE_URL ?>/modules/clients/view.php?id=<?= (int) $c['id'] ?>" class="fw-semibold text-dark"><?= e($c['name']) ?></a>
                        </div>
                    </td>
                    <td><?= $c['type'] === 'company' ? 'شركة' : 'فرد' ?></td>
                    <td><?= e($c['phone'] ?: '—') ?></td>
                    <td><?= e($c['email'] ?: '—') ?></td>
                    <td class="text-nowrap">
                        <a href="<?= BASE_URL ?>/modules/clients/form.php?id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <form action="<?= BASE_URL ?>/modules/clients/delete.php" method="post" class="d-inline" data-confirm="هل تريد حذف هذا العميل؟">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
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
<nav class="mt-3">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $p['totalPages']; $i++): ?>
            <li class="page-item <?= $i === $p['page'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
