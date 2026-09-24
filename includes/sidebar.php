<?php
/**
 * القائمة الجانبية - تُبنى ديناميكياً حسب صلاحيات المستخدم
 * المتغير $activeModule يجب تعريفه في الصفحة قبل تضمين header.php
 */

$activeModule = $activeModule ?? '';

// ملاحظة: نستخدم بادئة __sb لكل متغيرات هذا الملف لأن require يشارك النطاق مع الصفحة
// المستدعية، وأي اسم عام مثل $items أو $item قد يصطدم بمتغيرات الصفحة نفسها.
$__sbMenuGroups = [
    'عام' => [
        ['module' => 'dashboard', 'label' => 'لوحة التحكم', 'icon' => 'bi-speedometer2', 'href' => '/modules/dashboard/index.php'],
    ],
    'إدارة الأعمال' => [
        ['module' => 'clients', 'label' => 'العملاء', 'icon' => 'bi-people', 'href' => '/modules/clients/index.php'],
        ['module' => 'projects', 'label' => 'المشاريع', 'icon' => 'bi-diagram-3', 'href' => '/modules/projects/index.php'],
        ['module' => 'contracts', 'label' => 'العقود', 'icon' => 'bi-file-earmark-text', 'href' => '/modules/contracts/index.php'],
    ],
    'الشؤون المالية' => [
        ['module' => 'invoices', 'label' => 'الفواتير', 'icon' => 'bi-receipt', 'href' => '/modules/invoices/index.php'],
        ['module' => 'payments', 'label' => 'الدفعات', 'icon' => 'bi-cash-coin', 'href' => '/modules/payments/index.php'],
        ['module' => 'expenses', 'label' => 'المصروفات', 'icon' => 'bi-wallet2', 'href' => '/modules/expenses/index.php'],
    ],
    'الموارد البشرية' => [
        ['module' => 'employees', 'label' => 'الموظفون', 'icon' => 'bi-person-badge', 'href' => '/modules/employees/index.php'],
        ['module' => 'attendance', 'label' => 'الحضور والانصراف', 'icon' => 'bi-calendar-check', 'href' => '/modules/attendance/index.php'],
        ['module' => 'payroll', 'label' => 'الرواتب', 'icon' => 'bi-cash-stack', 'href' => '/modules/payroll/index.php'],
    ],
    'المخزون والمشتريات' => [
        ['module' => 'suppliers', 'label' => 'الموردون', 'icon' => 'bi-truck', 'href' => '/modules/suppliers/index.php'],
        ['module' => 'materials', 'label' => 'المخزون والمواد', 'icon' => 'bi-boxes', 'href' => '/modules/materials/index.php'],
        ['module' => 'purchases', 'label' => 'أوامر الشراء', 'icon' => 'bi-cart-check', 'href' => '/modules/purchases/index.php'],
    ],
    'النظام' => [
        ['module' => 'reports', 'label' => 'التقارير', 'icon' => 'bi-bar-chart-line', 'href' => '/modules/reports/index.php'],
        ['module' => 'users', 'label' => 'المستخدمون', 'icon' => 'bi-person-lines-fill', 'href' => '/modules/users/index.php'],
        ['module' => 'settings', 'label' => 'إعدادات الشركة', 'icon' => 'bi-gear', 'href' => '/modules/settings/index.php'],
    ],
];
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <span class="badge-mark">ب</span>
        <span><?= e(APP_NAME) ?></span>
    </div>

    <?php if (isSuperAdmin()): ?>
    <div class="sidebar-section-title">إدارة النظام</div>
    <nav class="nav flex-column">
        <a class="nav-link <?= $activeModule === 'superadmin' ? 'active' : '' ?>" href="<?= BASE_URL ?>/superadmin/index.php">
            <i class="bi bi-building-gear"></i> الشركات المشتركة
        </a>
    </nav>
    <?php endif; ?>

    <?php if (currentCompanyId()): foreach ($__sbMenuGroups as $__sbGroupTitle => $__sbGroupItems): ?>
        <?php
        $__sbVisibleItems = array_filter($__sbGroupItems, fn($__sbItem) => can($__sbItem['module']));
        if (empty($__sbVisibleItems)) continue;
        ?>
        <div class="sidebar-section-title"><?= e($__sbGroupTitle) ?></div>
        <nav class="nav flex-column">
            <?php foreach ($__sbVisibleItems as $__sbItem): ?>
                <a class="nav-link <?= $activeModule === $__sbItem['module'] ? 'active' : '' ?>" href="<?= BASE_URL . $__sbItem['href'] ?>">
                    <i class="bi <?= e($__sbItem['icon']) ?>"></i>
                    <span><?= e($__sbItem['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endforeach; endif; ?>
</aside>
<?php unset($__sbMenuGroups, $__sbGroupTitle, $__sbGroupItems, $__sbVisibleItems, $__sbItem); ?>
