<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('payments');

$companyId = currentCompanyId();
$total = (int) (dbFetchOne('SELECT COUNT(*) c FROM payments WHERE company_id = ?', 'i', [$companyId])['c'] ?? 0);
$p = paginate($total);
$payments = dbFetchAll(
    "SELECT pay.*, c.name AS client_name, i.invoice_number FROM payments pay
     LEFT JOIN clients c ON c.id = pay.client_id LEFT JOIN invoices i ON i.id = pay.invoice_id
     WHERE pay.company_id = ? ORDER BY pay.payment_date DESC, pay.id DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}",
    'i', [$companyId]
);

$clients = dbFetchAll('SELECT id, name FROM clients WHERE company_id = ? ORDER BY name', 'i', [$companyId]);
$openInvoices = dbFetchAll('SELECT id, invoice_number, total, paid_amount, client_id FROM invoices WHERE company_id = ? AND status NOT IN ("paid","cancelled") ORDER BY issue_date DESC', 'i', [$companyId]);

$pageTitle = 'الدفعات';
$pageSubtitle = 'سجل جميع التحصيلات المالية من العملاء';
$activeModule = 'payments';
$pageActions = '<button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#addPaymentModal"><i class="bi bi-plus-lg"></i> دفعة جديدة</button>';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>التاريخ</th><th>العميل</th><th>الفاتورة</th><th>المبلغ</th><th>الطريقة</th><th>المرجع</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($payments)): ?><tr><td colspan="7"><div class="empty-state"><i class="bi bi-cash-coin"></i>لا توجد دفعات مسجلة بعد</div></td></tr><?php endif; ?>
            <?php foreach ($payments as $row): ?>
                <tr>
                    <td><?= formatDate($row['payment_date']) ?></td>
                    <td><?= e($row['client_name'] ?? '—') ?></td>
                    <td><?= $row['invoice_number'] ? '<a href="' . BASE_URL . '/modules/invoices/view.php?id=' . (int) $row['invoice_id'] . '">' . e($row['invoice_number']) . '</a>' : '—' ?></td>
                    <td class="text-success fw-semibold"><?= formatMoney((float) $row['amount']) ?></td>
                    <td><?= e(paymentMethodLabel($row['method'])) ?></td>
                    <td><?= e($row['reference'] ?: '—') ?></td>
                    <td>
                        <form action="<?= BASE_URL ?>/modules/payments/delete.php" method="post" data-confirm="هل تريد حذف هذه الدفعة؟">
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

<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= BASE_URL ?>/modules/payments/store.php">
                <?= csrfField() ?>
                <input type="hidden" name="redirect_to" value="/modules/payments/index.php">
                <div class="modal-header">
                    <h5 class="modal-title">تسجيل دفعة جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الفاتورة (اختياري)</label>
                        <select name="invoice_id" class="form-select">
                            <option value="">— دفعة بدون فاتورة —</option>
                            <?php foreach ($openInvoices as $inv): ?>
                                <option value="<?= (int) $inv['id'] ?>"><?= e($inv['invoice_number']) ?> (متبقي <?= formatMoney((float) $inv['total'] - (float) $inv['paid_amount']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">العميل</label>
                        <select name="client_id" class="form-select">
                            <option value="">— غير محدد —</option>
                            <?php foreach ($clients as $cl): ?>
                                <option value="<?= (int) $cl['id'] ?>"><?= e($cl['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">المبلغ *</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">تاريخ الدفع</label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">طريقة الدفع</label>
                            <select name="method" class="form-select">
                                <option value="cash">نقدي</option>
                                <option value="bank_transfer">تحويل بنكي</option>
                                <option value="cheque">شيك</option>
                                <option value="card">بطاقة</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">المرجع</label>
                        <input type="text" name="reference" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button class="btn btn-brand">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
