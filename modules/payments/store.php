<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('payments');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/modules/payments/index.php');
}

verifyCsrf();
$companyId = currentCompanyId();

$invoiceId = post('invoice_id') !== '' ? (int) post('invoice_id') : null;
$clientId = post('client_id') !== '' ? (int) post('client_id') : null;
$amount = (float) post('amount', '0');
$paymentDate = post('payment_date', date('Y-m-d'));
$method = post('method', 'cash');
$reference = post('reference');
$notes = post('notes');
$redirectTo = post('redirect_to', '/modules/payments/index.php');

if ($amount <= 0) {
    flash('danger', 'الرجاء إدخال مبلغ صحيح');
    redirect($redirectTo);
}

$invoice = null;
if ($invoiceId) {
    $invoice = dbFetchOne('SELECT * FROM invoices WHERE id = ? AND company_id = ?', 'ii', [$invoiceId, $companyId]);
    if (!$invoice) {
        flash('danger', 'الفاتورة غير موجودة');
        redirect($redirectTo);
    }
    $remaining = (float) $invoice['total'] - (float) $invoice['paid_amount'];
    if ($amount > $remaining + 0.01) {
        $amount = $remaining;
    }
    if (!$clientId) {
        $clientId = $invoice['client_id'];
    }
}

$conn = db();
$conn->begin_transaction();
try {
    dbExecute(
        'INSERT INTO payments (company_id, invoice_id, client_id, amount, payment_date, method, reference, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?)',
        'iiidssssi',
        [$companyId, $invoiceId, $clientId, $amount, $paymentDate, $method, $reference, $notes, currentUserId()]
    );

    if ($invoice) {
        $newPaid = (float) $invoice['paid_amount'] + $amount;
        $newStatus = $invoice['status'];
        if ($newPaid >= (float) $invoice['total'] - 0.01) {
            $newStatus = 'paid';
        } elseif ($newPaid > 0) {
            $newStatus = 'partial';
        }
        dbExecute('UPDATE invoices SET paid_amount = ?, status = ? WHERE id = ? AND company_id = ?', 'dsii', [$newPaid, $newStatus, $invoiceId, $companyId]);
    }

    $conn->commit();
    flash('success', 'تم تسجيل الدفعة بنجاح');
} catch (Throwable $e) {
    $conn->rollback();
    error_log('payment store error: ' . $e->getMessage());
    flash('danger', 'حدث خطأ أثناء تسجيل الدفعة');
}

redirect($redirectTo);
