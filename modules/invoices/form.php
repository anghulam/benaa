<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('invoices');

$companyId = currentCompanyId();
$id = (int) get('id');
$invoice = $id ? dbFetchOne('SELECT * FROM invoices WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]) : null;

if ($id && !$invoice) {
    flash('danger', 'الفاتورة غير موجودة');
    redirect('/modules/invoices/index.php');
}

$items = $invoice ? dbFetchAll('SELECT * FROM invoice_items WHERE invoice_id = ?', 'i', [$id]) : [];
$projects = dbFetchAll('SELECT id, name FROM projects WHERE company_id = ? ORDER BY name', 'i', [$companyId]);
$clients = dbFetchAll('SELECT id, name FROM clients WHERE company_id = ? ORDER BY name', 'i', [$companyId]);
$contracts = dbFetchAll('SELECT id, contract_number FROM contracts WHERE company_id = ? ORDER BY contract_number', 'i', [$companyId]);

$errors = [];
$data = $invoice ?: [
    'invoice_number' => generateReferenceNumber('INV'), 'project_id' => get('project_id'), 'contract_id' => '', 'client_id' => '',
    'issue_date' => date('Y-m-d'), 'due_date' => '', 'tax_rate' => '15', 'status' => 'draft', 'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data['invoice_number'] = post('invoice_number');
    $data['project_id'] = post('project_id');
    $data['contract_id'] = post('contract_id');
    $data['client_id'] = post('client_id');
    $data['issue_date'] = post('issue_date', date('Y-m-d'));
    $data['due_date'] = post('due_date');
    $data['tax_rate'] = post('tax_rate', '15');
    $data['status'] = post('status', 'draft');
    $data['notes'] = post('notes');

    $descriptions = $_POST['item_description'] ?? [];
    $quantities = $_POST['item_qty'] ?? [];
    $prices = $_POST['item_price'] ?? [];

    $lineItems = [];
    $subtotal = 0;
    foreach ($descriptions as $idx => $desc) {
        $desc = clean($desc);
        $qty = (float) ($quantities[$idx] ?? 0);
        $price = (float) ($prices[$idx] ?? 0);
        if ($desc === '' || $qty <= 0) continue;
        $lineTotal = $qty * $price;
        $subtotal += $lineTotal;
        $lineItems[] = ['description' => $desc, 'qty' => $qty, 'price' => $price, 'total' => $lineTotal];
    }

    if ($data['invoice_number'] === '') $errors[] = 'الرجاء إدخال رقم الفاتورة';
    if (empty($lineItems)) $errors[] = 'الرجاء إضافة بند واحد على الأقل للفاتورة';

    $taxRate = max(0, (float) $data['tax_rate']);
    $taxAmount = round($subtotal * $taxRate / 100, 2);
    $total = round($subtotal + $taxAmount, 2);

    $projectId = $data['project_id'] !== '' ? (int) $data['project_id'] : null;
    $contractId = $data['contract_id'] !== '' ? (int) $data['contract_id'] : null;
    $clientId = $data['client_id'] !== '' ? (int) $data['client_id'] : null;
    $dueDate = $data['due_date'] !== '' ? $data['due_date'] : null;

    if (empty($errors)) {
        $conn = db();
        $conn->begin_transaction();
        try {
            if ($invoice) {
                dbExecute(
                    'UPDATE invoices SET invoice_number=?, project_id=?, contract_id=?, client_id=?, issue_date=?, due_date=?, subtotal=?, tax_rate=?, tax_amount=?, total=?, status=?, notes=? WHERE id=? AND company_id=?',
                    'siiissddddssii',
                    [$data['invoice_number'], $projectId, $contractId, $clientId, $data['issue_date'], $dueDate, $subtotal, $taxRate, $taxAmount, $total, $data['status'], $data['notes'], $id, $companyId]
                );
                dbExecute('DELETE FROM invoice_items WHERE invoice_id = ?', 'i', [$id]);
                $invoiceId = $id;
            } else {
                dbExecute(
                    'INSERT INTO invoices (company_id, project_id, contract_id, client_id, invoice_number, issue_date, due_date, subtotal, tax_rate, tax_amount, total, status, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    'iiiisssddddssi',
                    [$companyId, $projectId, $contractId, $clientId, $data['invoice_number'], $data['issue_date'], $dueDate, $subtotal, $taxRate, $taxAmount, $total, $data['status'], $data['notes'], currentUserId()]
                );
                $invoiceId = $conn->insert_id;
            }

            foreach ($lineItems as $li) {
                dbExecute(
                    'INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) VALUES (?,?,?,?,?)',
                    'isddd',
                    [$invoiceId, $li['description'], $li['qty'], $li['price'], $li['total']]
                );
            }

            $conn->commit();
            flash('success', $invoice ? 'تم تحديث الفاتورة بنجاح' : 'تم إنشاء الفاتورة بنجاح');
            redirect('/modules/invoices/view.php?id=' . $invoiceId);
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('invoice save error: ' . $e->getMessage());
            $errors[] = 'حدث خطأ أثناء حفظ الفاتورة';
        }
    }

    if (!empty($errors)) {
        $items = array_values($lineItems) ?: [['description' => '', 'qty' => 1, 'price' => 0, 'total' => 0]];
    }
}

if (empty($items)) {
    $items = [['description' => '', 'quantity' => 1, 'unit_price' => 0]];
}

$pageTitle = $invoice ? 'تعديل فاتورة' : 'فاتورة جديدة';
$activeModule = 'invoices';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-body section-card">
                <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
                <form method="post" id="invoiceForm">
                    <?= csrfField() ?>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">رقم الفاتورة *</label>
                            <input type="text" name="invoice_number" class="form-control" required value="<?= e($data['invoice_number']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">تاريخ الإصدار</label>
                            <input type="date" name="issue_date" class="form-control" value="<?= e($data['issue_date']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">تاريخ الاستحقاق</label>
                            <input type="date" name="due_date" class="form-control" value="<?= e($data['due_date']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">العميل</label>
                            <select name="client_id" class="form-select">
                                <option value="">— بدون عميل —</option>
                                <?php foreach ($clients as $cl): ?>
                                    <option value="<?= (int) $cl['id'] ?>" <?= (string) $data['client_id'] === (string) $cl['id'] ? 'selected' : '' ?>><?= e($cl['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">المشروع</label>
                            <select name="project_id" class="form-select">
                                <option value="">— بدون مشروع —</option>
                                <?php foreach ($projects as $pr): ?>
                                    <option value="<?= (int) $pr['id'] ?>" <?= (string) $data['project_id'] === (string) $pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">العقد</label>
                            <select name="contract_id" class="form-select">
                                <option value="">— بدون عقد —</option>
                                <?php foreach ($contracts as $ct): ?>
                                    <option value="<?= (int) $ct['id'] ?>" <?= (string) $data['contract_id'] === (string) $ct['id'] ? 'selected' : '' ?>><?= e($ct['contract_number']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <hr>
                    <h6 class="fw-bold mb-2">بنود الفاتورة</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="itemsTable">
                            <thead><tr><th>الوصف</th><th style="width:110px;">الكمية</th><th style="width:140px;">سعر الوحدة</th><th style="width:140px;">الإجمالي</th><th style="width:40px;"></th></tr></thead>
                            <tbody>
                            <?php foreach ($items as $it): ?>
                                <tr class="item-row">
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

                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <table class="table table-sm">
                                <tr><td>المجموع الفرعي</td><td class="text-end" id="subtotalDisplay">0.00</td></tr>
                                <tr><td style="width:60%;">نسبة الضريبة (%)</td><td class="text-end"><input type="number" step="0.01" name="tax_rate" id="taxRate" class="form-control form-control-sm text-end" value="<?= e($data['tax_rate']) ?>"></td></tr>
                                <tr><td>قيمة الضريبة</td><td class="text-end" id="taxDisplay">0.00</td></tr>
                                <tr class="fw-bold"><td>الإجمالي</td><td class="text-end" id="totalDisplay">0.00</td></tr>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select">
                                <?php foreach (['draft','sent','partial','paid','overdue','cancelled'] as $st): $sb = statusBadge($st); ?>
                                    <option value="<?= $st ?>" <?= $data['status'] === $st ? 'selected' : '' ?>><?= e($sb[0]) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">ملاحظات</label>
                            <input type="text" name="notes" class="form-control" value="<?= e($data['notes']) ?>">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ الفاتورة</button>
                        <a href="<?= BASE_URL ?>/modules/invoices/index.php" class="btn btn-light">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<template id="rowTemplate">
    <tr class="item-row">
        <td><input type="text" name="item_description[]" class="form-control"></td>
        <td><input type="number" step="0.01" name="item_qty[]" class="form-control item-qty" value="1"></td>
        <td><input type="number" step="0.01" name="item_price[]" class="form-control item-price" value="0"></td>
        <td><span class="item-line-total">0.00</span></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
    </tr>
</template>

<?php
$extraScripts = '<script>
function recalcInvoice() {
    var subtotal = 0;
    document.querySelectorAll("#itemsTable .item-row").forEach(function (row) {
        var qty = parseFloat(row.querySelector(".item-qty").value) || 0;
        var price = parseFloat(row.querySelector(".item-price").value) || 0;
        var lineTotal = qty * price;
        row.querySelector(".item-line-total").innerText = lineTotal.toFixed(2);
        subtotal += lineTotal;
    });
    var taxRate = parseFloat(document.getElementById("taxRate").value) || 0;
    var tax = subtotal * taxRate / 100;
    var total = subtotal + tax;
    document.getElementById("subtotalDisplay").innerText = subtotal.toFixed(2);
    document.getElementById("taxDisplay").innerText = tax.toFixed(2);
    document.getElementById("totalDisplay").innerText = total.toFixed(2);
}

document.getElementById("addRow").addEventListener("click", function () {
    var tpl = document.getElementById("rowTemplate").content.cloneNode(true);
    document.querySelector("#itemsTable tbody").appendChild(tpl);
    bindRowEvents();
    recalcInvoice();
});

function bindRowEvents() {
    document.querySelectorAll("#itemsTable .item-qty, #itemsTable .item-price").forEach(function (el) {
        el.removeEventListener("input", recalcInvoice);
        el.addEventListener("input", recalcInvoice);
    });
    document.querySelectorAll(".remove-row").forEach(function (btn) {
        btn.onclick = function () {
            if (document.querySelectorAll("#itemsTable .item-row").length > 1) {
                btn.closest("tr").remove();
                recalcInvoice();
            }
        };
    });
}

document.getElementById("taxRate").addEventListener("input", recalcInvoice);
bindRowEvents();
recalcInvoice();
</script>';
require __DIR__ . '/../../includes/footer.php';
