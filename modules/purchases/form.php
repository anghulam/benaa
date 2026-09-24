<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('purchases');

$companyId = currentCompanyId();
$id = (int) get('id');
$po = $id ? dbFetchOne('SELECT * FROM purchase_orders WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]) : null;

if ($id && !$po) {
    flash('danger', 'أمر الشراء غير موجود');
    redirect('/modules/purchases/index.php');
}

$items = $po ? dbFetchAll('SELECT * FROM purchase_order_items WHERE purchase_order_id = ?', 'i', [$id]) : [];
$suppliers = dbFetchAll('SELECT id, name FROM suppliers WHERE company_id = ? ORDER BY name', 'i', [$companyId]);
$projects = dbFetchAll('SELECT id, name FROM projects WHERE company_id = ? ORDER BY name', 'i', [$companyId]);
$materials = dbFetchAll('SELECT id, name, unit FROM materials WHERE company_id = ? ORDER BY name', 'i', [$companyId]);

$errors = [];
$data = $po ?: ['po_number' => generateReferenceNumber('PO'), 'supplier_id' => '', 'project_id' => '', 'order_date' => date('Y-m-d'), 'status' => 'pending', 'notes' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data['po_number'] = post('po_number');
    $data['supplier_id'] = post('supplier_id');
    $data['project_id'] = post('project_id');
    $data['order_date'] = post('order_date', date('Y-m-d'));
    $data['status'] = post('status', 'pending');
    $data['notes'] = post('notes');

    $descriptions = $_POST['item_description'] ?? [];
    $materialIds = $_POST['item_material_id'] ?? [];
    $quantities = $_POST['item_qty'] ?? [];
    $prices = $_POST['item_price'] ?? [];

    $lineItems = [];
    $total = 0;
    foreach ($descriptions as $idx => $desc) {
        $desc = clean($desc);
        $qty = (float) ($quantities[$idx] ?? 0);
        $price = (float) ($prices[$idx] ?? 0);
        $matId = ($materialIds[$idx] ?? '') !== '' ? (int) $materialIds[$idx] : null;
        if ($desc === '' || $qty <= 0) continue;
        $lineTotal = $qty * $price;
        $total += $lineTotal;
        $lineItems[] = ['material_id' => $matId, 'description' => $desc, 'qty' => $qty, 'price' => $price, 'total' => $lineTotal];
    }

    if ($data['po_number'] === '') $errors[] = 'الرجاء إدخال رقم أمر الشراء';
    if (empty($lineItems)) $errors[] = 'الرجاء إضافة بند واحد على الأقل';

    $supplierId = $data['supplier_id'] !== '' ? (int) $data['supplier_id'] : null;
    $projectId = $data['project_id'] !== '' ? (int) $data['project_id'] : null;
    $wasReceived = $po && $po['status'] === 'received';
    $nowReceived = $data['status'] === 'received';

    if (empty($errors)) {
        $conn = db();
        $conn->begin_transaction();
        try {
            if ($po) {
                dbExecute(
                    'UPDATE purchase_orders SET po_number=?, supplier_id=?, project_id=?, order_date=?, status=?, total=?, notes=? WHERE id=? AND company_id=?',
                    'siissdsii',
                    [$data['po_number'], $supplierId, $projectId, $data['order_date'], $data['status'], $total, $data['notes'], $id, $companyId]
                );
                dbExecute('DELETE FROM purchase_order_items WHERE purchase_order_id = ?', 'i', [$id]);
                $poId = $id;
            } else {
                dbExecute(
                    'INSERT INTO purchase_orders (company_id, supplier_id, project_id, po_number, order_date, status, total, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?)',
                    'iiisssdsi',
                    [$companyId, $supplierId, $projectId, $data['po_number'], $data['order_date'], $data['status'], $total, $data['notes'], currentUserId()]
                );
                $poId = $conn->insert_id;
            }

            foreach ($lineItems as $li) {
                dbExecute(
                    'INSERT INTO purchase_order_items (purchase_order_id, material_id, description, quantity, unit_price, total) VALUES (?,?,?,?,?,?)',
                    'iisddd',
                    [$poId, $li['material_id'], $li['description'], $li['qty'], $li['price'], $li['total']]
                );
            }

            // عند تحويل حالة الأمر إلى "تم الاستلام" لأول مرة: تحديث المخزون تلقائياً
            if (!$wasReceived && $nowReceived) {
                foreach ($lineItems as $li) {
                    if (!$li['material_id']) continue;
                    dbExecute(
                        'INSERT INTO material_transactions (company_id, material_id, project_id, type, quantity, reference, transaction_date, created_by) VALUES (?,?,?,"in",?,?,CURDATE(),?)',
                        'iiidsi',
                        [$companyId, $li['material_id'], $projectId, $li['qty'], 'استلام ' . $data['po_number'], currentUserId()]
                    );
                    dbExecute('UPDATE materials SET current_stock = current_stock + ? WHERE id = ? AND company_id = ?', 'dii', [$li['qty'], $li['material_id'], $companyId]);
                }
            }

            $conn->commit();
            flash('success', $po ? 'تم تحديث أمر الشراء بنجاح' : 'تم إنشاء أمر الشراء بنجاح');
            redirect('/modules/purchases/view.php?id=' . $poId);
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('purchase order save error: ' . $e->getMessage());
            $errors[] = 'حدث خطأ أثناء حفظ أمر الشراء';
        }
    }

    if (!empty($errors)) {
        $items = $lineItems ?: [['description' => '', 'quantity' => 1, 'unit_price' => 0, 'material_id' => null]];
    }
}

if (empty($items)) {
    $items = [['description' => '', 'quantity' => 1, 'unit_price' => 0, 'material_id' => null]];
}

$pageTitle = $po ? 'تعديل أمر شراء' : 'أمر شراء جديد';
$activeModule = 'purchases';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-body section-card">
                <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
                <form method="post">
                    <?= csrfField() ?>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">رقم أمر الشراء *</label><input type="text" name="po_number" class="form-control" required value="<?= e($data['po_number']) ?>"></div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">المورد</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">— بدون مورد —</option>
                                <?php foreach ($suppliers as $s): ?><option value="<?= (int) $s['id'] ?>" <?= (string) $data['supplier_id'] === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">المشروع</label>
                            <select name="project_id" class="form-select">
                                <option value="">— بدون مشروع —</option>
                                <?php foreach ($projects as $pr): ?><option value="<?= (int) $pr['id'] ?>" <?= (string) $data['project_id'] === (string) $pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3"><label class="form-label">تاريخ الطلب</label><input type="date" name="order_date" class="form-control" value="<?= e($data['order_date']) ?>"></div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select">
                                <?php foreach (['pending','ordered','received','cancelled'] as $st): $sb = statusBadge($st); ?>
                                    <option value="<?= $st ?>" <?= $data['status'] === $st ? 'selected' : '' ?>><?= e($sb[0]) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <hr><h6 class="fw-bold mb-2">بنود الطلب</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="itemsTable">
                            <thead><tr><th>المادة</th><th>الوصف</th><th style="width:100px;">الكمية</th><th style="width:130px;">سعر الوحدة</th><th style="width:130px;">الإجمالي</th><th style="width:40px;"></th></tr></thead>
                            <tbody>
                            <?php foreach ($items as $it): ?>
                                <tr class="item-row">
                                    <td>
                                        <select name="item_material_id[]" class="form-select">
                                            <option value="">—</option>
                                            <?php foreach ($materials as $m): ?><option value="<?= (int) $m['id'] ?>" <?= (string) ($it['material_id'] ?? '') === (string) $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option><?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="item_description[]" class="form-control" value="<?= e($it['description'] ?? '') ?>"></td>
                                    <td><input type="number" step="0.01" name="item_qty[]" class="form-control item-qty" value="<?= e((string) ($it['quantity'] ?? 1)) ?>"></td>
                                    <td><input type="number" step="0.01" name="item_price[]" class="form-control item-price" value="<?= e((string) ($it['unit_price'] ?? 0)) ?>"></td>
                                    <td><span class="item-line-total">0.00</span></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" id="addRow" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-plus"></i> إضافة بند</button>

                    <div class="row justify-content-end mb-3">
                        <div class="col-md-4">
                            <table class="table table-sm"><tr class="fw-bold"><td>الإجمالي</td><td class="text-end" id="totalDisplay">0.00</td></tr></table>
                        </div>
                    </div>

                    <div class="mb-3"><label class="form-label">ملاحظات</label><textarea name="notes" class="form-control" rows="2"><?= e($data['notes']) ?></textarea></div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ</button>
                        <a href="<?= BASE_URL ?>/modules/purchases/index.php" class="btn btn-light">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<template id="rowTemplate">
    <tr class="item-row">
        <td><select name="item_material_id[]" class="form-select"><option value="">—</option><?php foreach ($materials as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?></select></td>
        <td><input type="text" name="item_description[]" class="form-control"></td>
        <td><input type="number" step="0.01" name="item_qty[]" class="form-control item-qty" value="1"></td>
        <td><input type="number" step="0.01" name="item_price[]" class="form-control item-price" value="0"></td>
        <td><span class="item-line-total">0.00</span></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
    </tr>
</template>

<?php
$extraScripts = '<script>
function recalcPO() {
    var total = 0;
    document.querySelectorAll("#itemsTable .item-row").forEach(function (row) {
        var qty = parseFloat(row.querySelector(".item-qty").value) || 0;
        var price = parseFloat(row.querySelector(".item-price").value) || 0;
        var lineTotal = qty * price;
        row.querySelector(".item-line-total").innerText = lineTotal.toFixed(2);
        total += lineTotal;
    });
    document.getElementById("totalDisplay").innerText = total.toFixed(2);
}
document.getElementById("addRow").addEventListener("click", function () {
    var tpl = document.getElementById("rowTemplate").content.cloneNode(true);
    document.querySelector("#itemsTable tbody").appendChild(tpl);
    bindRowEvents();
    recalcPO();
});
function bindRowEvents() {
    document.querySelectorAll("#itemsTable .item-qty, #itemsTable .item-price").forEach(function (el) {
        el.removeEventListener("input", recalcPO);
        el.addEventListener("input", recalcPO);
    });
    document.querySelectorAll(".remove-row").forEach(function (btn) {
        btn.onclick = function () {
            if (document.querySelectorAll("#itemsTable .item-row").length > 1) { btn.closest("tr").remove(); recalcPO(); }
        };
    });
}
bindRowEvents();
recalcPO();
</script>';
require __DIR__ . '/../../includes/footer.php';
