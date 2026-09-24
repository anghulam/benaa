<?php
/**
 * رأس صفحات الموقع التسويقي الخارجي
 * يفترض أن الصفحة عرّفت: $pageTitle, $pageDescription, $activeNav
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? APP_NAME;
$pageDescription = $pageDescription ?? 'منصة SaaS متكاملة لإدارة شركات المقاولات: المشاريع، العقود، الفواتير، الموظفون، المخزون، والصلاحيات المخصصة — كل ذلك في مكان واحد.';
$activeNav = $activeNav ?? '';
$siteLogo = site_get_system_logo();

$navLinks = [
    'home' => ['label' => 'الرئيسية', 'href' => '/index.php'],
    'features' => ['label' => 'المزايا', 'href' => '/features.php'],
    'pricing' => ['label' => 'الأسعار', 'href' => '/pricing.php'],
    'about' => ['label' => 'من نحن', 'href' => '/about.php'],
    'contact' => ['label' => 'تواصل معنا', 'href' => '/contact.php'],
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
<meta name="description" content="<?= e($pageDescription) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.rtl.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/site.css" rel="stylesheet">
</head>
<body class="site-body">

<header class="site-navbar no-print">
    <div class="container">
        <nav class="navbar navbar-expand-lg">
            <a class="navbar-brand site-brand" href="<?= BASE_URL ?>/index.php">
                <?php if ($siteLogo): ?>
                    <img src="<?= BASE_URL ?>/uploads/<?= e($siteLogo) ?>" class="brand-logo-img" alt="<?= e(APP_NAME) ?>">
                <?php else: ?>
                    <span class="badge-mark">ب</span>
                <?php endif; ?>
                <span><?= e(APP_NAME) ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav">
                <i class="bi bi-list fs-3"></i>
            </button>
            <div class="collapse navbar-collapse" id="siteNav">
                <ul class="navbar-nav mx-auto gap-lg-2">
                    <?php foreach ($navLinks as $key => $link): ?>
                        <li class="nav-item">
                            <a class="nav-link site-nav-link <?= $activeNav === $key ? 'active' : '' ?>" href="<?= BASE_URL . $link['href'] ?>"><?= e($link['label']) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex gap-2 mt-3 mt-lg-0">
                    <a href="<?= BASE_URL ?>/modules/auth/login.php" class="btn btn-outline-navy">تسجيل الدخول</a>
                    <a href="<?= BASE_URL ?>/modules/auth/register.php" class="btn btn-brand">ابدأ تجربتك المجانية</a>
                </div>
            </div>
        </nav>
    </div>
</header>

<?php foreach (site_get_flash() as $__siteFlash): ?>
    <div class="container mt-3">
        <div class="alert alert-<?= e($__siteFlash['type']) ?> alert-dismissible fade show" role="alert">
            <?= e($__siteFlash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endforeach; unset($__siteFlash); ?>
