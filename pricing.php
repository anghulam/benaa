<?php
$pageTitle = 'الأسعار';
$pageDescription = 'باقات اشتراك بناء: تجريبية مجانية، أساسية، احترافية، ومؤسسية — اختاروا ما يناسب حجم شركتكم وابدأوا فوراً.';
$activeNav = 'pricing';
require __DIR__ . '/site/inc/header.php';

$plans = [
    [
        'name' => 'تجريبية', 'price' => '0', 'period' => '14 يوماً', 'featured' => false,
        'desc' => 'لتجربة النظام كاملاً قبل الاشتراك',
        'features' => ['حتى 3 مستخدمين', 'حتى 3 مشاريع', '200 ميجابايت تخزين', 'كل الوحدات مفعّلة بالكامل'],
    ],
    [
        'name' => 'أساسية', 'price' => '99', 'period' => 'شهرياً', 'featured' => false,
        'desc' => 'للشركات الناشئة والمكاتب الصغيرة',
        'features' => ['حتى 5 مستخدمين', 'حتى 15 مشروعاً', '1 جيجابايت تخزين', 'إدارة المشاريع والعملاء والفواتير الأساسية'],
    ],
    [
        'name' => 'احترافية', 'price' => '249', 'period' => 'شهرياً', 'featured' => true,
        'desc' => 'الأنسب لمعظم شركات المقاولات',
        'features' => ['حتى 20 مستخدماً', 'حتى 100 مشروع', '5 جيجابايت تخزين', 'كل المزايا + الموظفون والمخزون والتقارير المتقدمة'],
    ],
    [
        'name' => 'مؤسسية', 'price' => '599', 'period' => 'شهرياً', 'featured' => false,
        'desc' => 'لكبرى شركات المقاولات والإنشاءات',
        'features' => ['مستخدمون غير محدودين عملياً', 'مشاريع غير محدودة عملياً', '50 جيجابايت تخزين', 'دعم أولوية وكل المزايا بلا استثناء'],
    ],
];

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
            <?php foreach ($plans as $plan): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="pricing-card <?= $plan['featured'] ? 'featured' : '' ?>">
                        <?php if ($plan['featured']): ?><span class="featured-badge">الأكثر طلباً</span><?php endif; ?>
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

<section class="site-section bg-light-alt">
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
