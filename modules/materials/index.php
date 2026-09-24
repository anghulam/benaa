<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('materials');

$companyId = currentCompanyId();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'adjust_stock') {
    verifyCsrf();
    $materialId = (int) post('material_id');
    $type = post('type') === 'out' ? 'out' : 'in';
    $qty = (float) post('quantity', '0');
    $projectId = post('project_id') !== '' ? (int) post('project_id') : null;
    $reference = post('reference');

    $material = dbFetchOne('SELECT * FROM materials WHERE id = ? AND company_id = ?', 'ii', [$materialId, $companyId]);
    if ($material && $qty > 0) {
        $conn = db();
        $conn->begin_transaction();
        try {
            dbExecute(
                'INSERT INTO material_transactions (company_id, material_id, project_id, type, quantity, reference, transaction_date, created_by) VALUES (?,?,?,?,?,?,CURDATE(),?)',
                'iiisdsi',
                [$companyId, $materialId, $projectId, $type, $qty, $reference, currentUserId()]
            );
            $newStock = $type === 'in' ? (float) $material['current_stock'] + $qty : (float) $material['current_stock'] - $qty;
            dbExecute('UPDATE materials SET current_stock = ? WHERE id = ? AND company_id = ?', 'dii', [$newStock, $materialId, $companyId]);
            $conn->commit();
            flash('success', 'تم تحديث المخزون بنجاح');
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('stock adjust error: ' . $e->getMessage());
            flash('danger', 'حدث خطأ أثناء تحديث المخزون');
        }
    }
    redirect('/modules/materials/index.php');
}

$search = get('q');
$where = 'company_id = ?';
$types = 'i';
$params = [$companyId];
if ($search !== '') {
    $where .= ' AND name LIKE ?';
    $types .= 's';
    $params[] = "%$search%";
}

$total = (int) (dbFetchOne("SELECT COUNT(*) c FROM materials WHERE $where", $types, $params)['c'] ?? 0);
$p = paginate($total);
$materials = dbFetchAll("SELECT * FROM materials WHERE $where ORDER BY name LIMIT {$p['perPage']} OFFSET {$p['offset']}", $types, $params);
$projects = dbFetchAll('SELECT id, name FROM projects WHERE company_id = ? ORDER BY name', 'i', [$companyId]);

$pageTitle = 'المخزون والمواد';
$pageSubtitle = 'متابعة مخزون مواد البناء';
$activeModule = 'materials';
$pageActions = '<a href="' . BASE_URL . '/modules/materials/form.php" class="btn btn-brand"><i class="bi bi-plus-lg"></i> مادة جديدة</a>';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <form method="get" class="row g-2">
            <div class="col-auto flex-grow-1"><input type="text" name="q" class="form-control" placeholder="بحث عن مادة..." value="<?= e($search) ?>"></div>
            <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i> بحث</button></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>المادة</th><th>التصنيف</th><th>الوحدة</th><th>سعر الوحدة</th><th>المخزون الحالي</th><th>الحد الأدنى</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($materials)): ?><tr><td colspan="7"><div class="empty-state"><i class="bi bi-boxes"></i>لا توجد مواد مسجلة بعد</div></td></tr><?php endif; ?>
            <?php foreach ($materials as $row): $low = (float) $row['current_stock'] <= (float) $row['min_stock']; ?>
                <tr class="<?= $low ? 'table-danger' : '' ?>">
                    <td class="fw-semibold"><?= e($row['name']) ?></td>
                    <td><?= e($row['category'] ?: '—') ?></td>
                    <td><?= e($row['unit']) ?></td>
                    <td><?= formatMoney((float) $row['unit_price']) ?></td>
                    <td><?= rtrim(rtrim(number_format((float) $row['current_stock'], 2), '0'), '.') ?> <?= $low ? '<i class="bi bi-exclamation-triangle text-danger" title="مخزون منخفض"></i>' : '' ?></td>
                    <td><?= rtrim(rtrim(number_format((float) $row['min_stock'], 2), '0'), '.') ?></td>
                    <td class="text-nowrap">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#stockModal<?= (int) $row['id'] ?>"><i class="bi bi-arrow-left-right"></i></button>
                        <a href="<?= BASE_URL ?>/modules/materials/form.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <form action="<?= BASE_URL ?>/modules/materials/delete.php" method="post" class="d-inline" data-confirm="هل تريد حذف هذه المادة؟">
                            <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>

                <div class="modal fade" id="stockModal<?= (int) $row['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="adjust_stock">
                                <input type="hidden" name="material_id" value="<?= (int) $row['id'] ?>">
                                <div class="modal-header"><h5 class="modal-title">تحديث مخزون: <?= e($row['name']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">نوع الحركة</label>
                                        <select name="type" class="form-select">
                                            <option value="in">توريد / إضافة للمخزون</option>
                                            <option value="out">صرف / سحب من المخزون</option>
                                        </select>
                                    </div>
                                    <div class="mb-3"><label class="form-label">الكمية</label><input type="number" step="0.01" name="quantity" class="form-control" required></div>
                                    <div class="mb-3">
                                        <label class="form-label">المشروع (اختياري)</label>
                                        <select name="project_id" class="form-select">
                                            <option value="">— بدون مشروع —</option>
                                            <?php foreach ($projects as $pr): ?><option value="<?= (int) $pr['id'] ?>"><?= e($pr['name']) ?></option><?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3"><label class="form-label">المرجع / الملاحظات</label><input type="text" name="reference" class="form-control"></div>
                                </div>
                                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-brand">حفظ</button></div>
                            </form>
                        </div>
                    </div>
                </div>
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
