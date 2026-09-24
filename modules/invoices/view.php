<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('invoices');

$companyId = currentCompanyId();
$id = (int) get('id');
$invoice = dbFetchOne(
    'SELECT i.*, c.name AS client_name, c.phone AS client_phone, c.address AS client_address, pr.name AS project_name
     FROM invoices i LEFT JOIN clients c ON c.id = i.client_id LEFT JOIN projects pr ON pr.id = i.project_id
     WHERE i.id = ? AND i.company_id = ?', 'ii', [$id, $companyId]
);
if (!$invoice) {
    flash('danger', 'الفاتورة غير موجودة');
    redirect('/modules/invoices/index.php');
}

$items = dbFetchAll('SELECT * FROM invoice_items WHERE invoice_id = ?', 'i', [$id]);
$payments = dbFetchAll('SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC', 'i', [$id]);
$company = currentCompany();

$letterheadMarginTop = (int) ($company['letterhead_margin_top'] ?? 0);
$letterheadMarginRight = (int) ($company['letterhead_margin_right'] ?? 0);
$letterheadMarginBottom = (int) ($company['letterhead_margin_bottom'] ?? 0);
$letterheadMarginLeft = (int) ($company['letterhead_margin_left'] ?? 0);
$letterheadFile = $company['letterhead'] ?? '';
$letterheadIsImage = $letterheadFile && in_array(strtolower((string) pathinfo($letterheadFile, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true);

$sb = statusBadge($invoice['status']);
$pageTitle = e($invoice['invoice_number']);
$pageSubtitle = 'تفاصيل الفاتورة';
$activeModule = 'invoices';
$pageActions = '<a href="' . BASE_URL . '/modules/invoices/form.php?id=' . $id . '" class="btn btn-outline-secondary no-print"><i class="bi bi-pencil"></i> تعديل</a>
<button onclick="window.print()" class="btn btn-brand no-print"><i class="bi bi-printer"></i> طباعة</button>';
require __DIR__ . '/../../includes/header.php';
?>

<style>
@media print {
    @page { size: A4; margin: 0; }
    body { margin: 0; }
    #printArea {
        padding: <?= $letterheadMarginTop ?>mm <?= $letterheadMarginRight ?>mm <?= $letterheadMarginBottom ?>mm <?= $letterheadMarginLeft ?>mm !important;
        box-shadow: none !important;
        border: none !important;
    }
}
</style>

<?php if ($letterheadIsImage): ?>
<div class="print-only" style="position:fixed; inset:0; z-index:-1;">
    <img src="<?= BASE_URL ?>/uploads/<?= e($letterheadFile) ?>" style="width:100%; height:100%; object-fit:cover;" alt="">
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3" id="printArea">
            <div class="card-body section-card">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h4 class="fw-bold mb-0"><?= e($company['name'] ?? '') ?></h4>
                        <div class="text-muted small"><?= e($company['phone'] ?? '') ?> — <?= e($company['email'] ?? '') ?></div>
                    </div>
                    <div class="text-end">
                        <h5 class="fw-bold mb-0">فاتورة #<?= e($invoice['invoice_number']) ?></h5>
                        <span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="text-muted small">إلى العميل</div>
                        <div class="fw-semibold"><?= e($invoice['client_name'] ?? '—') ?></div>
                        <div class="small text-muted"><?= e($invoice['client_phone'] ?? '') ?></div>
                        <div class="small text-muted"><?= e($invoice['client_address'] ?? '') ?></div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="small"><span class="text-muted">تاريخ الإصدار: </span><?= formatDate($invoice['issue_date']) ?></div>
                        <div class="small"><span class="text-muted">تاريخ الاستحقاق: </span><?= formatDate($invoice['due_date']) ?></div>
                        <div class="small"><span class="text-muted">المشروع: </span><?= e($invoice['project_name'] ?? '—') ?></div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead><tr><th>الوصف</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td><?= e($it['description']) ?></td>
                                <td><?= rtrim(rtrim(number_format((float) $it['quantity'], 2), '0'), '.') ?></td>
                                <td><?= formatMoney((float) $it['unit_price']) ?></td>
                                <td><?= formatMoney((float) $it['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end">
                    <div class="col-md-5">
                        <table class="table table-sm">
                            <tr><td>المجموع الفرعي</td><td class="text-end"><?= formatMoney((float) $invoice['subtotal']) ?></td></tr>
                            <tr><td>الضريبة (<?= e((string) $invoice['tax_rate']) ?>%)</td><td class="text-end"><?= formatMoney((float) $invoice['tax_amount']) ?></td></tr>
                            <tr class="fw-bold"><td>الإجمالي</td><td class="text-end"><?= formatMoney((float) $invoice['total']) ?></td></tr>
                            <tr class="text-success"><td>المدفوع</td><td class="text-end"><?= formatMoney((float) $invoice['paid_amount']) ?></td></tr>
                            <tr class="text-danger fw-bold"><td>المتبقي</td><td class="text-end"><?= formatMoney((float) $invoice['total'] - (float) $invoice['paid_amount']) ?></td></tr>
                        </table>
                    </div>
                </div>
                <?php if ($invoice['notes']): ?><hr><p class="small text-muted mb-0"><?= nl2br(e($invoice['notes'])) ?></p><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4 no-print">
        <?php if (can('payments') && (float) $invoice['total'] - (float) $invoice['paid_amount'] > 0): ?>
        <div class="card mb-3">
            <div class="card-header">تسجيل دفعة جديدة</div>
            <div class="card-body">
                <form method="post" action="<?= BASE_URL ?>/modules/payments/store.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="invoice_id" value="<?= $id ?>">
                    <input type="hidden" name="client_id" value="<?= (int) ($invoice['client_id'] ?? 0) ?>">
                    <input type="hidden" name="redirect_to" value="/modules/invoices/view.php?id=<?= $id ?>">
                    <div class="mb-2">
                        <label class="form-label">المبلغ</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required max="<?= (float) $invoice['total'] - (float) $invoice['paid_amount'] ?>" value="<?= (float) $invoice['total'] - (float) $invoice['paid_amount'] ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">تاريخ الدفع</label>
                        <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">طريقة الدفع</label>
                        <select name="method" class="form-select">
                            <option value="cash">نقدي</option>
                            <option value="bank_transfer">تحويل بنكي</option>
                            <option value="cheque">شيك</option>
                            <option value="card">بطاقة</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">مرجع الدفعة</label>
                        <input type="text" name="reference" class="form-control">
                    </div>
                    <button class="btn btn-brand w-100">تسجيل الدفعة</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">سجل الدفعات</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>التاريخ</th><th>المبلغ</th><th>الطريقة</th></tr></thead>
                    <tbody>
                    <?php if (empty($payments)): ?><tr><td colspan="3" class="text-center text-muted py-3">لا توجد دفعات</td></tr><?php endif; ?>
                    <?php foreach ($payments as $pay): ?>
                        <tr><td><?= formatDate($pay['payment_date']) ?></td><td><?= formatMoney((float) $pay['amount']) ?></td><td><?= e(paymentMethodLabel($pay['method'])) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
