<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('clients');

$companyId = currentCompanyId();
$id = (int) get('id');
$client = dbFetchOne('SELECT * FROM clients WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]);
if (!$client) {
    flash('danger', 'العميل غير موجود');
    redirect('/modules/clients/index.php');
}

$projects = dbFetchAll('SELECT * FROM projects WHERE client_id = ? AND company_id = ? ORDER BY created_at DESC', 'ii', [$id, $companyId]);
$invoices = dbFetchAll('SELECT * FROM invoices WHERE client_id = ? AND company_id = ? ORDER BY created_at DESC', 'ii', [$id, $companyId]);

$pageTitle = e($client['name']);
$pageSubtitle = 'ملف العميل';
$activeModule = 'clients';
$pageActions = '<a href="' . BASE_URL . '/modules/clients/form.php?id=' . $id . '" class="btn btn-brand"><i class="bi bi-pencil"></i> تعديل</a>';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body section-card">
                <div class="text-center mb-3">
                    <span class="avatar-circle mx-auto" style="width:64px;height:64px;font-size:24px;"><?= e(mb_substr($client['name'], 0, 1)) ?></span>
                    <h5 class="mt-2 mb-0"><?= e($client['name']) ?></h5>
                    <span class="badge bg-secondary mt-1"><?= $client['type'] === 'company' ? 'شركة' : 'فرد' ?></span>
                </div>
                <ul class="list-unstyled small">
                    <li class="mb-2"><i class="bi bi-telephone me-2 text-muted"></i><?= e($client['phone'] ?: '—') ?></li>
                    <li class="mb-2"><i class="bi bi-envelope me-2 text-muted"></i><?= e($client['email'] ?: '—') ?></li>
                    <li class="mb-2"><i class="bi bi-geo-alt me-2 text-muted"></i><?= e($client['address'] ?: '—') ?></li>
                    <li class="mb-2"><i class="bi bi-receipt me-2 text-muted"></i>الرقم الضريبي: <?= e($client['tax_number'] ?: '—') ?></li>
                </ul>
                <?php if ($client['notes']): ?>
                <hr><p class="small text-muted mb-0"><?= nl2br(e($client['notes'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">المشاريع المرتبطة (<?= count($projects) ?>)</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>المشروع</th><th>الحالة</th><th>الميزانية</th></tr></thead>
                    <tbody>
                    <?php if (empty($projects)): ?><tr><td colspan="3" class="text-center text-muted py-3">لا توجد مشاريع</td></tr><?php endif; ?>
                    <?php foreach ($projects as $p): $sb = statusBadge($p['status']); ?>
                        <tr>
                            <td><a href="<?= BASE_URL ?>/modules/projects/view.php?id=<?= (int) $p['id'] ?>"><?= e($p['name']) ?></a></td>
                            <td><span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span></td>
                            <td><?= formatMoney((float) $p['budget']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">الفواتير (<?= count($invoices) ?>)</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>رقم الفاتورة</th><th>الإجمالي</th><th>المدفوع</th><th>الحالة</th></tr></thead>
                    <tbody>
                    <?php if (empty($invoices)): ?><tr><td colspan="4" class="text-center text-muted py-3">لا توجد فواتير</td></tr><?php endif; ?>
                    <?php foreach ($invoices as $inv): $sb = statusBadge($inv['status']); ?>
                        <tr>
                            <td><a href="<?= BASE_URL ?>/modules/invoices/view.php?id=<?= (int) $inv['id'] ?>"><?= e($inv['invoice_number']) ?></a></td>
                            <td><?= formatMoney((float) $inv['total']) ?></td>
                            <td><?= formatMoney((float) $inv['paid_amount']) ?></td>
                            <td><span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
