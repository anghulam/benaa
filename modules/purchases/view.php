<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('purchases');

$companyId = currentCompanyId();
$id = (int) get('id');
$po = dbFetchOne(
    'SELECT po.*, s.name AS supplier_name, pr.name AS project_name FROM purchase_orders po
     LEFT JOIN suppliers s ON s.id = po.supplier_id LEFT JOIN projects pr ON pr.id = po.project_id
     WHERE po.id = ? AND po.company_id = ?', 'ii', [$id, $companyId]
);
if (!$po) {
    flash('danger', 'أمر الشراء غير موجود');
    redirect('/modules/purchases/index.php');
}

$items = dbFetchAll('SELECT poi.*, m.name AS material_name FROM purchase_order_items poi LEFT JOIN materials m ON m.id = poi.material_id WHERE poi.purchase_order_id = ?', 'i', [$id]);

$sb = statusBadge($po['status']);
$pageTitle = e($po['po_number']);
$pageSubtitle = 'تفاصيل أمر الشراء';
$activeModule = 'purchases';
$pageActions = $po['status'] !== 'received' ? '<a href="' . BASE_URL . '/modules/purchases/form.php?id=' . $id . '" class="btn btn-brand"><i class="bi bi-pencil"></i> تعديل</a>' : '';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-body section-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="fw-bold mb-0">أمر شراء #<?= e($po['po_number']) ?></h5>
                        <div class="text-muted small">المورد: <?= e($po['supplier_name'] ?: '—') ?> | المشروع: <?= e($po['project_name'] ?: '—') ?></div>
                    </div>
                    <span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead><tr><th>المادة</th><th>الوصف</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td><?= e($it['material_name'] ?? '—') ?></td>
                                <td><?= e($it['description']) ?></td>
                                <td><?= rtrim(rtrim(number_format((float) $it['quantity'], 2), '0'), '.') ?></td>
                                <td><?= formatMoney((float) $it['unit_price']) ?></td>
                                <td><?= formatMoney((float) $it['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot><tr class="fw-bold"><td colspan="4" class="text-end">الإجمالي</td><td><?= formatMoney((float) $po['total']) ?></td></tr></tfoot>
                    </table>
                </div>
                <?php if ($po['notes']): ?><hr><p class="small text-muted mb-0"><?= nl2br(e($po['notes'])) ?></p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
