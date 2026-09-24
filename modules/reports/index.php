<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requirePermission('reports');

$companyId = currentCompanyId();

// ملخص مالي عام
$totalInvoiced = (float) (dbFetchOne('SELECT COALESCE(SUM(total),0) s FROM invoices WHERE company_id = ?', 'i', [$companyId])['s'] ?? 0);
$totalCollected = (float) (dbFetchOne('SELECT COALESCE(SUM(paid_amount),0) s FROM invoices WHERE company_id = ?', 'i', [$companyId])['s'] ?? 0);
$totalExpenses = (float) (dbFetchOne('SELECT COALESCE(SUM(amount),0) s FROM expenses WHERE company_id = ?', 'i', [$companyId])['s'] ?? 0);
$outstanding = $totalInvoiced - $totalCollected;
$netProfit = $totalCollected - $totalExpenses;

// ربحية المشاريع: الميزانية مقابل الفواتير والمصروفات
$projectsReport = dbFetchAll(
    'SELECT p.id, p.name, p.budget,
        (SELECT COALESCE(SUM(total),0) FROM invoices i WHERE i.project_id = p.id) AS invoiced,
        (SELECT COALESCE(SUM(amount),0) FROM expenses e WHERE e.project_id = p.id) AS spent
     FROM projects p WHERE p.company_id = ? ORDER BY p.created_at DESC LIMIT 20',
    'i', [$companyId]
);

// ملخص المصروفات حسب التصنيف
$expenseByCategory = dbFetchAll(
    'SELECT COALESCE(ec.name, "غير مصنف") AS category, COALESCE(SUM(e.amount),0) AS total
     FROM expenses e LEFT JOIN expense_categories ec ON ec.id = e.category_id
     WHERE e.company_id = ? GROUP BY ec.name ORDER BY total DESC',
    'i', [$companyId]
);

// حضور الشهر الحالي
$attendanceSummary = dbFetchOne(
    'SELECT
        SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) AS absent_count,
        SUM(CASE WHEN status IN ("leave","sick") THEN 1 ELSE 0 END) AS leave_count
     FROM attendance WHERE company_id = ? AND MONTH(attendance_date) = MONTH(CURDATE()) AND YEAR(attendance_date) = YEAR(CURDATE())',
    'i', [$companyId]
);

$pageTitle = 'التقارير';
$pageSubtitle = 'نظرة تحليلية شاملة على أداء الشركة';
$activeModule = 'reports';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-icon bg-soft-teal"><i class="bi bi-receipt"></i></div>
            <div><div class="stat-value" style="font-size:17px;"><?= formatMoney($totalInvoiced) ?></div><div class="stat-label">إجمالي الفواتير</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-icon bg-soft-green"><i class="bi bi-cash-coin"></i></div>
            <div><div class="stat-value" style="font-size:17px;"><?= formatMoney($totalCollected) ?></div><div class="stat-label">التحصيلات</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-icon bg-soft-red"><i class="bi bi-exclamation-circle"></i></div>
            <div><div class="stat-value" style="font-size:17px;"><?= formatMoney($outstanding) ?></div><div class="stat-label">مستحقات غير محصلة</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-icon bg-soft-navy"><i class="bi bi-graph-up-arrow"></i></div>
            <div><div class="stat-value" style="font-size:17px;"><?= formatMoney($netProfit) ?></div><div class="stat-label">صافي الربح (تقديري)</div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">ربحية المشاريع (الميزانية مقابل الفواتير والمصروفات)</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>المشروع</th><th>الميزانية</th><th>المفوتر</th><th>المصروف</th><th>الفرق</th></tr></thead>
                    <tbody>
                    <?php if (empty($projectsReport)): ?><tr><td colspan="5" class="text-center text-muted py-3">لا توجد بيانات</td></tr><?php endif; ?>
                    <?php foreach ($projectsReport as $row): $diff = (float) $row['budget'] - (float) $row['spent']; ?>
                        <tr>
                            <td><a href="<?= BASE_URL ?>/modules/projects/view.php?id=<?= (int) $row['id'] ?>"><?= e($row['name']) ?></a></td>
                            <td><?= formatMoney((float) $row['budget']) ?></td>
                            <td><?= formatMoney((float) $row['invoiced']) ?></td>
                            <td><?= formatMoney((float) $row['spent']) ?></td>
                            <td class="<?= $diff >= 0 ? 'text-success' : 'text-danger' ?> fw-semibold"><?= formatMoney($diff) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">المصروفات حسب التصنيف</div>
            <div class="card-body">
                <canvas id="expenseChart" height="110"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">ملخص حضور الشهر الحالي</div>
            <div class="card-body section-card">
                <div class="d-flex justify-content-between mb-2"><span>أيام حضور</span><span class="fw-bold text-success"><?= (int) ($attendanceSummary['present_count'] ?? 0) ?></span></div>
                <div class="d-flex justify-content-between mb-2"><span>أيام غياب</span><span class="fw-bold text-danger"><?= (int) ($attendanceSummary['absent_count'] ?? 0) ?></span></div>
                <div class="d-flex justify-content-between"><span>إجازات</span><span class="fw-bold text-info"><?= (int) ($attendanceSummary['leave_count'] ?? 0) ?></span></div>
            </div>
        </div>
    </div>
</div>

<?php
$categoryLabels = array_column($expenseByCategory, 'category');
$categoryTotals = array_map('floatval', array_column($expenseByCategory, 'total'));
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById("expenseChart"), {
    type: "bar",
    data: {
        labels: ' . json_encode($categoryLabels, JSON_UNESCAPED_UNICODE) . ',
        datasets: [{ label: "المصروفات", data: ' . json_encode($categoryTotals) . ', backgroundColor: "#f5a623", borderRadius: 6 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
</script>';
require __DIR__ . '/../../includes/footer.php';
