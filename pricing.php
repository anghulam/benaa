<?php
$pageTitle = 'الأسعار';
$pageDescription = 'باقات اشتراك بناء: تجريبية مجانية، أساسية، احترافية، ومؤسسية — اختاروا ما يناسب حجم شركتكم وابدأوا فوراً.';
$activeNav = 'pricing';
require __DIR__ . '/site/inc/header.php';

$plans = site_get_plans();
$planIcons = ['bi-gift', 'bi-layers', 'bi-rocket-takeoff', 'bi-building'];
$planIconBg = ['bg-soft-teal', 'bg-soft-blue', 'bg-soft-navy', 'bg-soft-red'];
$maxPlanFeatures = 0;
foreach ($plans as $__plan) {
    $maxPlanFeatures = max($maxPlanFeatures, count($__plan['features']));
}
unset($__plan);

$faqs = [
    ['هل أحتاج بطاقة ائتمانية لبدء الفترة التجريبية؟', 'لا، تبدأ فترتكم التجريبية المجانية لمدة 14 يوماً فور تسجيل شركتكم دون أي بيانات دفع.'],
    ['هل يمكن الترقية أو تغيير الباقة لاحقاً؟', 'نعم، يمكن ترقية أو تغيير باقة اشتراك شركتكم في أي وقت بالتواصل معنا.'],
    ['ماذا يحدث لبياناتي عند انتهاء الفترة التجريبية؟', 'تبقى بياناتكم محفوظة بالكامل؛ يكفي الاشتراك في إحدى الباقات المدفوعة لمتابعة العمل دون انقطاع.'],
    ['هل صلاحيات الموظفين تختلف حسب الباقة؟', 'لا، نظام تخصيص الصلاحيات متاح لجميع الباقات بما فيها الباقة التجريبية.'],
];
?>

<section class="page-hero">
    <div class="container">
        <h1>أسعار واضحة تناسب كل حجم شركة</h1>
        <p>ابدأوا مجاناً، وترقّوا فقط عندما تحتاجون فعلاً لمزيد من المستخدمين أو المشاريع.</p>
    </div>
</section>

<section class="site-section">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($plans as $i => $plan): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="pricing-card <?= $plan['featured'] ? 'featured' : '' ?>">
                        <?php if ($plan['featured']): ?><span class="featured-badge">الأكثر طلباً</span><?php endif; ?>
                        <div class="feature-icon <?= $plan['featured'] ? 'bg-soft-navy' : e($planIconBg[$i % count($planIconBg)]) ?>">
                            <i class="bi <?= $plan['featured'] ? 'bi-star-fill' : e($planIcons[$i % count($planIcons)]) ?>"></i>
                        </div>
                        <h5 class="fw-bold mb-1"><?= e($plan['name']) ?></h5>
                        <p class="text-muted small mb-3"><?= e($plan['desc']) ?></p>
                        <div class="price">
                            <?= $plan['price'] === '0' ? 'مجاناً' : e($plan['price']) ?>
                            <?php if ($plan['price'] !== '0'): ?><small> ر.س</small><?php endif; ?>
                        </div>
                        <div class="text-muted small mb-2"><?= e($plan['period']) ?></div>
                        <ul>
                            <?php foreach ($plan['features'] as $f): ?>
                                <li><i class="bi bi-check-circle-fill"></i> <?= e($f) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?= BASE_URL ?>/modules/auth/register.php" class="btn <?= $plan['featured'] ? 'btn-brand' : 'btn-outline-navy' ?> w-100">ابدأ الآن</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="text-center text-muted small mt-4">كل الأسعار بالريال السعودي، ولا تشمل الضريبة. تواصلوا معنا لباقات مخصصة لشركات المجموعات الكبرى.</p>
    </div>
</section>

<?php if ($maxPlanFeatures > 0): ?>
<section class="site-section bg-light-alt">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-eyebrow">مقارنة تفصيلية</span>
            <h2 class="section-title">قارنوا بين الباقات جنباً إلى جنب</h2>
        </div>
        <div class="table-responsive pricing-compare">
            <table class="table align-middle text-center mb-0">
                <thead>
                    <tr>
                        <th class="text-start">الباقة</th>
                        <?php foreach ($plans as $plan): ?>
                            <th class="<?= $plan['featured'] ? 'is-featured' : '' ?>"><?= e($plan['name']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-start fw-bold">السعر</td>
                        <?php foreach ($plans as $plan): ?>
                            <td class="<?= $plan['featured'] ? 'is-featured' : '' ?>">
                                <span class="fw-bold"><?= $plan['price'] === '0' ? 'مجاناً' : e($plan['price']) . ' ر.س' ?></span>
                                <?php if ($plan['price'] !== '0' && $plan['period'] !== ''): ?><span class="text-muted small d-block">/<?= e($plan['period']) ?></span><?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php for ($row = 0; $row < $maxPlanFeatures; $row++): ?>
                        <tr>
                            <td class="text-start text-muted small">&nbsp;</td>
                            <?php foreach ($plans as $plan): ?>
                                <td class="<?= $plan['featured'] ? 'is-featured' : '' ?>">
                                    <?php if (isset($plan['features'][$row])): ?>
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                        <span class="d-block small mt-1"><?= e($plan['features'][$row]) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endfor; ?>
                    <tr>
                        <td class="text-start">&nbsp;</td>
                        <?php foreach ($plans as $plan): ?>
                            <td class="<?= $plan['featured'] ? 'is-featured' : '' ?>">
                                <a href="<?= BASE_URL ?>/modules/auth/register.php" class="btn btn-sm <?= $plan['featured'] ? 'btn-brand' : 'btn-outline-navy' ?>">ابدأ الآن</a>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="site-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <span class="section-eyebrow">أسئلة شائعة</span>
                    <h2 class="section-title">لديكم أسئلة؟ لدينا إجابات</h2>
                </div>
                <?php foreach ($faqs as [$q, $a]): ?>
                    <div class="faq-item">
                        <h6><?= e($q) ?></h6>
                        <p><?= e($a) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="site-section pt-0">
    <div class="container">
        <div class="cta-banner">
            <h2>لم تحددوا الباقة المناسبة بعد؟</h2>
            <p>ابدأوا بالتجربة المجانية أولاً، ولن تحتاجوا لاتخاذ قرار قبل أن تختبروا النظام كاملاً.</p>
            <a href="<?= BASE_URL ?>/modules/auth/register.php" class="btn btn-brand btn-lg px-5"><i class="bi bi-rocket-takeoff me-1"></i> ابدأ تجربتك المجانية</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/site/inc/footer.php'; ?>
