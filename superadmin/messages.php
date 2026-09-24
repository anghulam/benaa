<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = (int) post('id');
    $action = post('action');
    if ($action === 'mark_read') {
        dbExecute('UPDATE contact_messages SET status = "read" WHERE id = ? AND status = "new"', 'i', [$id]);
    } elseif ($action === 'mark_replied') {
        dbExecute('UPDATE contact_messages SET status = "replied" WHERE id = ?', 'i', [$id]);
    } elseif ($action === 'delete') {
        dbExecute('DELETE FROM contact_messages WHERE id = ?', 'i', [$id]);
        flash('success', 'تم حذف الرسالة');
    }
    redirect('/superadmin/messages.php');
}

$total = (int) (dbFetchOne('SELECT COUNT(*) c FROM contact_messages')['c'] ?? 0);
$p = paginate($total, 20);
$messages = dbFetchAll("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}");
$newCount = (int) (dbFetchOne('SELECT COUNT(*) c FROM contact_messages WHERE status = "new"')['c'] ?? 0);

$pageTitle = 'رسائل التواصل';
$pageSubtitle = 'استفسارات الزوار الواردة من نموذج "تواصل معنا" في الموقع التسويقي';
$activeModule = 'superadmin-messages';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($newCount > 0): ?>
<div class="alert alert-info"><i class="bi bi-envelope"></i> لديكم <strong><?= $newCount ?></strong> رسالة جديدة لم تُقرأ بعد.</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>الاسم</th><th>البريد / الجوال</th><th>الشركة</th><th>الرسالة</th><th>الحالة</th><th>التاريخ</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($messages)): ?><tr><td colspan="7"><div class="empty-state"><i class="bi bi-envelope-open"></i>لا توجد رسائل بعد</div></td></tr><?php endif; ?>
            <?php foreach ($messages as $m): $msb = statusBadge($m['status']); ?>
                <tr>
                    <td class="fw-semibold"><?= e($m['name']) ?></td>
                    <td class="small"><?= e($m['email']) ?><?php if ($m['phone']): ?><br><?= e($m['phone']) ?><?php endif; ?></td>
                    <td><?= e($m['company_name'] ?: '—') ?></td>
                    <td class="small" style="max-width:280px;white-space:normal;"><?= e($m['message']) ?></td>
                    <td><span class="badge bg-<?= $msb[1] ?>"><?= e($msb[0]) ?></span></td>
                    <td class="text-muted small text-nowrap"><?= formatDate($m['created_at']) ?></td>
                    <td class="text-nowrap">
                        <?php if ($m['status'] === 'new'): ?>
                        <form method="post" class="d-inline"><?= csrfField() ?><input type="hidden" name="action" value="mark_read"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button class="btn btn-sm btn-outline-secondary" title="تحديد كمقروءة"><i class="bi bi-envelope-open"></i></button></form>
                        <?php elseif ($m['status'] === 'read'): ?>
                        <form method="post" class="d-inline"><?= csrfField() ?><input type="hidden" name="action" value="mark_replied"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button class="btn btn-sm btn-outline-success" title="تحديد كتم الرد"><i class="bi bi-check-lg"></i></button></form>
                        <?php endif; ?>
                        <form method="post" class="d-inline" data-confirm="هل تريد حذف هذه الرسالة؟"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($p['totalPages'] > 1): ?>
<nav class="mt-3"><ul class="pagination justify-content-center">
    <?php for ($i = 1; $i <= $p['totalPages']; $i++): ?>
        <li class="page-item <?= $i === $p['page'] ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
    <?php endfor; ?>
</ul></nav>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
