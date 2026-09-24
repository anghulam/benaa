<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('purchases');

$companyId = currentCompanyId();
$total = (int) (dbFetchOne('SELECT COUNT(*) c FROM purchase_orders WHERE company_id = ?', 'i', [$companyId])['c'] ?? 0);
$p = paginate($total);
$orders = dbFetchAll(
    "SELECT po.*, s.name AS supplier_name, pr.name AS project_name FROM purchase_orders po
     LEFT JOIN suppliers s ON s.id = po.supplier_id LEFT JOIN projects pr ON pr.id = po.project_id
     WHERE po.company_id = ? ORDER BY po.created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}",
    'i', [$companyId]
);

$pageTitle = 'أوامر الشراء';
$pageSubtitle = 'إدارة طلبات الشراء من الموردين';
$activeModule = 'purchases';
$pageActions = '<a href="' . BASE_URL . '/modules/purchases/form.php" class="btn btn-brand"><i class="bi bi-plus-lg"></i> أمر شراء جديد</a>';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>رقم الأمر</th><th>المورد</th><th>المشروع</th><th>الإجمالي</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($orders)): ?><tr><td colspan="6"><div class="empty-state"><i class="bi bi-cart-check"></i>لا توجد أوامر شراء بعد</div></td></tr><?php endif; ?>
            <?php foreach ($orders as $row): $sb = statusBadge($row['status']); ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>/modules/purchases/view.php?id=<?= (int) $row['id'] ?>" class="fw-semibold text-dark"><?= e($row['po_number']) ?></a></td>
                    <td><?= e($row['supplier_name'] ?? '—') ?></td>
                    <td><?= e($row['project_name'] ?? '—') ?></td>
                    <td><?= formatMoney((float) $row['total']) ?></td>
                    <td><span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span></td>
                    <td class="text-nowrap">
                        <?php if ($row['status'] !== 'received'): ?>
                        <a href="<?= BASE_URL ?>/modules/purchases/form.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <form action="<?= BASE_URL ?>/modules/purchases/delete.php" method="post" class="d-inline" data-confirm="هل تريد حذف أمر الشراء هذا؟">
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
        <li class="page-item <?= $i === $p['page'] ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
    <?php endfor; ?>
</ul></nav>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
