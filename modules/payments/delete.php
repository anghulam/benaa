<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('payments');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/modules/payments/index.php');
}

verifyCsrf();
$companyId = currentCompanyId();
$id = (int) post('id');

$payment = dbFetchOne('SELECT * FROM payments WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]);
if ($payment) {
    $conn = db();
    $conn->begin_transaction();
    try {
        if ($payment['invoice_id']) {
            $invoice = dbFetchOne('SELECT * FROM invoices WHERE id = ? AND company_id = ?', 'ii', [$payment['invoice_id'], $companyId]);
            if ($invoice) {
                $newPaid = max(0, (float) $invoice['paid_amount'] - (float) $payment['amount']);
                $newStatus = $newPaid <= 0 ? 'sent' : ($newPaid >= (float) $invoice['total'] ? 'paid' : 'partial');
                dbExecute('UPDATE invoices SET paid_amount = ?, status = ? WHERE id = ? AND company_id = ?', 'dsii', [$newPaid, $newStatus, $invoice['id'], $companyId]);
            }
        }
        dbExecute('DELETE FROM payments WHERE id = ? AND company_id = ?', 'ii', [$id, $companyId]);
        $conn->commit();
        flash('success', 'تم حذف الدفعة بنجاح');
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('payment delete error: ' . $e->getMessage());
        flash('danger', 'حدث خطأ أثناء حذف الدفعة');
    }
}

redirect('/modules/payments/index.php');
