<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireSuperAdmin();

$companyFilter = get('company_id');
$where = '1=1';
$types = '';
$params = [];
if ($companyFilter !== '') {
    $where .= ' AND a.company_id = ?';
    $types .= 'i';
    $params[] = (int) $companyFilter;
}

$total = (int) (dbFetchOne("SELECT COUNT(*) c FROM activity_log a WHERE $where", $types, $params)['c'] ?? 0);
$p = paginate($total, 40);
$logs = dbFetchAll(
    "SELECT a.*, u.name AS user_name, c.name AS company_name FROM activity_log a
     LEFT JOIN users u ON u.id = a.user_id LEFT JOIN companies c ON c.id = a.company_id
     WHERE $where ORDER BY a.created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}",
    $types, $params
);
$companies = dbFetchAll('SELECT id, name FROM companies ORDER BY name');

$pageTitle = 'سجل النشاطات';
$pageSubtitle = 'سجل تدقيق شامل لكل الأحداث عبر جميع الشركات المشتركة';
$activeModule = 'superadmin-activity';
require __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <form method="get" class="row g-2">
            <div class="col-auto flex-grow-1">
                <select name="company_id" class="form-select" onchange="this.form.submit()">
                    <option value="">كل الشركات</option>
                    <?php foreach ($companies as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= $companyFilter === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>الوقت</th><th>الشركة</th><th>المستخدم</th><th>الإجراء</th><th>التفاصيل</th></tr></thead>
            <tbody>
            <?php if (empty($logs)): ?><tr><td colspan="5"><div class="empty-state"><i class="bi bi-clock-history"></i>لا يوجد نشاط مسجل بعد</div></td></tr><?php endif; ?>
            <?php foreach ($logs as $row): ?>
                <tr>
                    <td class="text-muted small text-nowrap"><?= e(date('Y-m-d H:i', strtotime($row['created_at']))) ?></td>
                    <td><?= e($row['company_name'] ?? '—') ?></td>
                    <td><?= e($row['user_name'] ?? '—') ?></td>
                    <td><span class="badge bg-secondary"><?= e($row['action']) ?></span></td>
                    <td class="small text-muted"><?= e($row['description'] ?: '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($p['totalPages'] > 1): ?>
<nav class="mt-3"><ul class="pagination justify-content-center">
    <?php for ($i = 1; $i <= $p['totalPages']; $i++): ?>
        <li class="page-item <?= $i === $p['page'] ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&company_id=<?= urlencode($companyFilter) ?>"><?= $i ?></a></li>
    <?php endfor; ?>
</ul></nav>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
