<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();

if (isSuperAdmin() && !currentCompanyId()) {
    redirect('/superadmin/index.php');
}

$companyId = currentCompanyId();

// كل بطاقة/قسم في اللوحة مرتبط بوحدة معيّنة، ولا يُستعلَم عنها ولا تُعرض إن لم
// تكن الوحدة متاحة (سواء بسبب صلاحيات الدور أو عدم شمولها في باقة اشتراك الشركة)
$canProjects = can('projects');
$canClients = can('clients');
$canEmployees = can('employees');
$canInvoices = can('invoices');
$canExpenses = can('expenses');
$canPayments = can('payments');
$canMaterials = can('materials');

$stats = [
    'projects_active' => $canProjects ? (int) (dbFetchOne('SELECT COUNT(*) c FROM projects WHERE company_id = ? AND status IN ("planning","in_progress")', 'i', [$companyId])['c'] ?? 0) : 0,
    'projects_total' => $canProjects ? (int) (dbFetchOne('SELECT COUNT(*) c FROM projects WHERE company_id = ?', 'i', [$companyId])['c'] ?? 0) : 0,
    'clients_total' => $canClients ? (int) (dbFetchOne('SELECT COUNT(*) c FROM clients WHERE company_id = ?', 'i', [$companyId])['c'] ?? 0) : 0,
    'employees_active' => $canEmployees ? (int) (dbFetchOne('SELECT COUNT(*) c FROM employees WHERE company_id = ? AND status = "active"', 'i', [$companyId])['c'] ?? 0) : 0,
    'invoices_due' => $canInvoices ? (float) (dbFetchOne('SELECT COALESCE(SUM(total - paid_amount),0) s FROM invoices WHERE company_id = ? AND status NOT IN ("paid","cancelled")', 'i', [$companyId])['s'] ?? 0) : 0,
    'expenses_month' => $canExpenses ? (float) (dbFetchOne('SELECT COALESCE(SUM(amount),0) s FROM expenses WHERE company_id = ? AND MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())', 'i', [$companyId])['s'] ?? 0) : 0,
    'payments_month' => $canPayments ? (float) (dbFetchOne('SELECT COALESCE(SUM(amount),0) s FROM payments WHERE company_id = ? AND MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())', 'i', [$companyId])['s'] ?? 0) : 0,
    'materials_low' => $canMaterials ? (int) (dbFetchOne('SELECT COUNT(*) c FROM materials WHERE company_id = ? AND current_stock <= min_stock', 'i', [$companyId])['c'] ?? 0) : 0,
];

// بيانات آخر 6 أشهر للرسم البياني (إيرادات مقابل مصروفات)
$chartLabels = [];
$chartRevenue = [];
$chartExpenses = [];
$showChart = $canPayments || $canExpenses;
if ($showChart) {
    for ($i = 5; $i >= 0; $i--) {
        $m = (int) date('n', strtotime("-$i months"));
        $y = date('Y', strtotime("-$i months"));
        $chartLabels[] = arabicMonthShort($m);
        $rev = $canPayments ? dbFetchOne('SELECT COALESCE(SUM(amount),0) s FROM payments WHERE company_id = ? AND MONTH(payment_date) = ? AND YEAR(payment_date) = ?', 'iii', [$companyId, $m, $y]) : ['s' => 0];
        $exp = $canExpenses ? dbFetchOne('SELECT COALESCE(SUM(amount),0) s FROM expenses WHERE company_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?', 'iii', [$companyId, $m, $y]) : ['s' => 0];
        $chartRevenue[] = (float) $rev['s'];
        $chartExpenses[] = (float) $exp['s'];
    }
}

$recentProjects = $canProjects ? dbFetchAll('SELECT p.*, c.name AS client_name FROM projects p LEFT JOIN clients c ON c.id = p.client_id WHERE p.company_id = ? ORDER BY p.created_at DESC LIMIT 5', 'i', [$companyId]) : [];
$recentInvoices = $canInvoices ? dbFetchAll('SELECT i.*, c.name AS client_name FROM invoices i LEFT JOIN clients c ON c.id = i.client_id WHERE i.company_id = ? ORDER BY i.created_at DESC LIMIT 5', 'i', [$companyId]) : [];

$pageTitle = 'لوحة التحكم';
$pageSubtitle = 'نظرة عامة على أداء شركتكم';
$activeModule = 'dashboard';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3 mb-4">
    <?php if ($canProjects): ?>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon bg-soft-navy"><i class="bi bi-diagram-3"></i></div>
            <div>
                <div class="stat-value"><?= $stats['projects_active'] ?></div>
                <div class="stat-label">مشاريع جارية (من <?= $stats['projects_total'] ?>)</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($canClients): ?>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon bg-soft-teal"><i class="bi bi-people"></i></div>
            <div>
                <div class="stat-value"><?= $stats['clients_total'] ?></div>
                <div class="stat-label">إجمالي العملاء</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($canEmployees): ?>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon bg-soft-amber"><i class="bi bi-person-badge"></i></div>
            <div>
                <div class="stat-value"><?= $stats['employees_active'] ?></div>
                <div class="stat-label">موظفون نشطون</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($canMaterials): ?>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon bg-soft-red"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
                <div class="stat-value"><?= $stats['materials_low'] ?></div>
                <div class="stat-label">مواد أوشكت على النفاد</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($canInvoices || $canPayments || $canExpenses): ?>
<div class="row g-3 mb-4">
    <?php if ($canInvoices): ?>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon bg-soft-red"><i class="bi bi-receipt"></i></div>
            <div>
                <div class="stat-value" style="font-size:19px;"><?= formatMoney($stats['invoices_due'], e(currentCompany()['currency'] ?? 'ر.س')) ?></div>
                <div class="stat-label">مستحقات غير محصّلة</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($canPayments): ?>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon bg-soft-green"><i class="bi bi-cash-coin"></i></div>
            <div>
                <div class="stat-value" style="font-size:19px;"><?= formatMoney($stats['payments_month'], e(currentCompany()['currency'] ?? 'ر.س')) ?></div>
                <div class="stat-label">تحصيلات هذا الشهر</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($canExpenses): ?>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon bg-soft-blue"><i class="bi bi-wallet2"></i></div>
            <div>
                <div class="stat-value" style="font-size:19px;"><?= formatMoney($stats['expenses_month'], e(currentCompany()['currency'] ?? 'ر.س')) ?></div>
                <div class="stat-label">مصروفات هذا الشهر</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="row g-3">
    <?php if ($showChart || $canProjects): ?>
    <div class="col-lg-7">
        <?php if ($showChart): ?>
        <div class="card mb-3">
            <div class="card-header">الإيرادات والمصروفات - آخر 6 أشهر</div>
            <div class="card-body">
                <canvas id="financeChart" height="130"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($canProjects): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>أحدث المشاريع</span>
                <a href="<?= BASE_URL ?>/modules/projects/index.php" class="small">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>المشروع</th><th>العميل</th><th>الحالة</th><th>التقدم</th></tr></thead>
                    <tbody>
                    <?php if (empty($recentProjects)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">لا توجد مشاريع بعد</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentProjects as $p): $sb = statusBadge($p['status']); ?>
                        <tr>
                            <td><a href="<?= BASE_URL ?>/modules/projects/view.php?id=<?= (int) $p['id'] ?>"><?= e($p['name']) ?></a></td>
                            <td><?= e($p['client_name'] ?? '—') ?></td>
                            <td><span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span></td>
                            <td style="width:120px;">
                                <div class="progress"><div class="progress-bar bg-warning" style="width:<?= (int) $p['progress'] ?>%"></div></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($canInvoices): ?>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>أحدث الفواتير</span>
                <a href="<?= BASE_URL ?>/modules/invoices/index.php" class="small">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>رقم الفاتورة</th><th>العميل</th><th>الإجمالي</th><th>الحالة</th></tr></thead>
                    <tbody>
                    <?php if (empty($recentInvoices)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">لا توجد فواتير بعد</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentInvoices as $inv): $sb = statusBadge($inv['status']); ?>
                        <tr>
                            <td><a href="<?= BASE_URL ?>/modules/invoices/view.php?id=<?= (int) $inv['id'] ?>"><?= e($inv['invoice_number']) ?></a></td>
                            <td><?= e($inv['client_name'] ?? '—') ?></td>
                            <td><?= formatMoney((float) $inv['total']) ?></td>
                            <td><span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$extraScripts = '';
if ($showChart) {
    $extraScripts = '<script src="' . BASE_URL . '/assets/vendor/chartjs/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById("financeChart"), {
    type: "line",
    data: {
        labels: ' . json_encode($chartLabels, JSON_UNESCAPED_UNICODE) . ',
        datasets: [
            { label: "التحصيلات", data: ' . json_encode($chartRevenue) . ', borderColor: "#0ea5a3", backgroundColor: "rgba(14,165,163,0.12)", tension: 0.35, fill: true },
            { label: "المصروفات", data: ' . json_encode($chartExpenses) . ', borderColor: "#d97706", backgroundColor: "rgba(217,119,6,0.12)", tension: 0.35, fill: true }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: "bottom", rtl: true, labels: { font: { family: "Cairo" } } } },
        scales: { y: { beginAtZero: true, ticks: { font: { family: "Cairo" } } }, x: { ticks: { font: { family: "Cairo" } } } }
    }
});
</script>';
}
require __DIR__ . '/../../includes/footer.php';
