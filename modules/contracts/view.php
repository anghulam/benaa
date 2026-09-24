<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('contracts');

$companyId = currentCompanyId();
$id = (int) get('id');
$contract = dbFetchOne(
    'SELECT ct.*, cl.name AS client_name, pr.name AS project_name FROM contracts ct
     LEFT JOIN clients cl ON cl.id = ct.client_id LEFT JOIN projects pr ON pr.id = ct.project_id
     WHERE ct.id = ? AND ct.company_id = ?', 'ii', [$id, $companyId]
);
if (!$contract) {
    flash('danger', 'العقد غير موجود');
    redirect('/modules/contracts/index.php');
}

$sb = statusBadge($contract['status']);
$pageTitle = e($contract['contract_number']);
$pageSubtitle = e($contract['title']);
$activeModule = 'contracts';
$pageActions = '<a href="' . BASE_URL . '/modules/contracts/form.php?id=' . $id . '" class="btn btn-brand"><i class="bi bi-pencil"></i> تعديل</a>';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body section-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="fw-bold mb-0"><?= e($contract['title']) ?></h5>
                    <span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span>
                </div>
                <div class="row small">
                    <div class="col-md-6 mb-2"><span class="text-muted">رقم العقد: </span><?= e($contract['contract_number']) ?></div>
                    <div class="col-md-6 mb-2"><span class="text-muted">القيمة: </span><?= formatMoney((float) $contract['value']) ?></div>
                    <div class="col-md-6 mb-2"><span class="text-muted">المشروع: </span><?= e($contract['project_name'] ?: '—') ?></div>
                    <div class="col-md-6 mb-2"><span class="text-muted">العميل: </span><?= e($contract['client_name'] ?: '—') ?></div>
                    <div class="col-md-6 mb-2"><span class="text-muted">تاريخ البدء: </span><?= formatDate($contract['start_date']) ?></div>
                    <div class="col-md-6 mb-2"><span class="text-muted">تاريخ الانتهاء: </span><?= formatDate($contract['end_date']) ?></div>
                </div>
                <?php if ($contract['file_path']): ?>
                <a href="<?= BASE_URL ?>/uploads/<?= e($contract['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-paperclip"></i> عرض ملف العقد</a>
                <?php endif; ?>
                <?php if ($contract['notes']): ?><hr><p class="small text-muted mb-0"><?= nl2br(e($contract['notes'])) ?></p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
