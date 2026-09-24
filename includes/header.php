<?php
/**
 * رأس الصفحة العام للوحة التحكم
 * يفترض أن الصفحة المستدعية عرّفت: $pageTitle, $activeModule (اختياري: $pageSubtitle)
 *
 * ملاحظة مهمة: بما أن هذا الملف يُضمَّن عبر require داخل نطاق الصفحة المستدعية،
 * فإن أي متغير نعرّفه هنا قد يصطدم مع متغير بنفس الاسم في الصفحة نفسها (مثل $company
 * أو $sb) ويُفسد قيمته بعد انتهاء require. لذلك نستخدم بادئة __hdr لجميع المتغيرات
 * الداخلية الخاصة بهذا الملف فقط.
 */
$pageTitle = $pageTitle ?? APP_NAME;
$pageSubtitle = $pageSubtitle ?? '';
$__hdrUser = currentUser();
$__hdrCompany = currentCompany();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.rtl.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-wrapper">
    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        <header class="topbar no-print">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-light d-lg-none" id="sidebarToggle" type="button">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <?php if ($__hdrCompany): ?>
                <span class="company-pill">
                    <i class="bi bi-buildings"></i> <?= e($__hdrCompany['name']) ?>
                    <?php $__hdrStatusBadge = statusBadge($__hdrCompany['status']); ?>
                    <span class="badge bg-<?= $__hdrStatusBadge[1] ?> ms-1"><?= e($__hdrStatusBadge[0]) ?></span>
                </span>
                <?php endif; ?>
            </div>

            <div class="dropdown">
                <button class="btn btn-light d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                    <span class="avatar-circle"><?= e(mb_substr($__hdrUser['name'] ?? '?', 0, 1)) ?></span>
                    <span class="d-none d-md-inline">
                        <div class="fw-bold" style="font-size:13.5px;"><?= e($__hdrUser['name'] ?? '') ?></div>
                        <div class="text-muted" style="font-size:11.5px;"><?= e(roleLabel($__hdrUser['role'] ?? '')) ?></div>
                    </span>
                    <i class="bi bi-chevron-down text-muted small"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/settings/index.php"><i class="bi bi-gear me-2"></i> إعدادات الشركة</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/modules/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> تسجيل الخروج</a></li>
                </ul>
            </div>
        </header>

        <main class="page-body">
            <?php foreach (getFlashMessages() as $__hdrFlash): ?>
                <div class="alert alert-<?= e($__hdrFlash['type']) ?> alert-dismissible fade show no-print" data-auto-dismiss role="alert">
                    <?= e($__hdrFlash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>

            <?php if (!empty($pageTitle) || !empty($pageSubtitle)): ?>
            <div class="page-header no-print">
                <div>
                    <h1 class="page-title"><?= e($pageTitle) ?></h1>
                    <?php if ($pageSubtitle): ?><div class="page-subtitle"><?= e($pageSubtitle) ?></div><?php endif; ?>
                </div>
                <?php if (!empty($pageActions)): ?>
                <div class="d-flex gap-2"><?= $pageActions ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
