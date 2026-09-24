<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('invoices');

$companyId = currentCompanyId();
$search = get('q');
$statusFilter = get('status');

$where = 'i.company_id = ?';
$types = 'i';
$params = [$companyId];
if ($search !== '') {
    $where .= ' AND i.invoice_number LIKE ?';
    $types .= 's';
    $params[] = "%$search%";
}
if ($statusFilter !== '') {
    $where .= ' AND i.status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}

$total = (int) (dbFetchOne("SELECT COUNT(*) c FROM invoices i WHERE $where", $types, $params)['c'] ?? 0);
$p = paginate($total);
$invoices = dbFetchAll(
    "SELECT i.*, c.name AS client_name, pr.name AS project_name FROM invoices i
     LEFT JOIN clients c ON c.id = i.client_id LEFT JOIN projects pr ON pr.id = i.project_id
     WHERE $where ORDER BY i.created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}",
    $types, $params
);

$pageTitle = 'الفواتير';
$pageSubtitle = 'إدارة فواتير العملاء والمتابعة المالية';
$activeModule = 'invoices';
$pageActions = '<a href="' . BASE_URL . '/modules/invoices/form.php" class="btn btn-brand"><i class="bi bi-plus-lg"></i> فاتورة جديدة</a>';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <form method="get" class="row g-2">
            <div class="col-auto flex-grow-1"><input type="text" name="q" class="form-control" placeholder="بحث برقم الفاتورة..." value="<?= e($search) ?>"></div>
            <div class="col-auto">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">كل الحالات</option>
                    <?php foreach (['draft','sent','partial','paid','overdue','cancelled'] as $st): $sb = statusBadge($st); ?>
                        <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= e($sb[0]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i> بحث</button></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>رقم الفاتورة</th><th>العميل</th><th>المشروع</th><th>الإجمالي</th><th>المدفوع</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($invoices)): ?><tr><td colspan="7"><div class="empty-state"><i class="bi bi-receipt"></i>لا توجد فواتير بعد</div></td></tr><?php endif; ?>
            <?php foreach ($invoices as $row): $sb = statusBadge($row['status']); ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>/modules/invoices/view.php?id=<?= (int) $row['id'] ?>" class="fw-semibold text-dark"><?= e($row['invoice_number']) ?></a></td>
                    <td><?= e($row['client_name'] ?? '—') ?></td>
                    <td><?= e($row['project_name'] ?? '—') ?></td>
                    <td><?= formatMoney((float) $row['total']) ?></td>
                    <td><?= formatMoney((float) $row['paid_amount']) ?></td>
                    <td><span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span></td>
                    <td class="text-nowrap">
                        <a href="<?= BASE_URL ?>/modules/invoices/form.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <form action="<?= BASE_URL ?>/modules/invoices/delete.php" method="post" class="d-inline" data-confirm="هل تريد حذف هذه الفاتورة؟">
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
        <li class="page-item <?= $i === $p['page'] ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"><?= $i ?></a></li>
    <?php endfor; ?>
</ul></nav>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
