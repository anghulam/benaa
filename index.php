<?php
/**
 * نقطة الدخول الرئيسية: تعرض الموقع التسويقي للزوار، وتوجّه المستخدمين
 * المسجّلين مباشرة لتطبيقهم (دون الحاجة لقاعدة بيانات لهذا الفحص البسيط).
 */
require_once __DIR__ . '/config/config.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
    exit;
}

$pageTitle = 'نظام إدارة شركات المقاولات';
$pageDescription = 'بناء (Benaa): منصة SaaS عربية متكاملة لإدارة شركات المقاولات — المشاريع، العقود، الفواتير، الموظفون، المخزون، وصلاحيات مخصصة بالكامل لكل شركة.';
$activeNav = 'home';
require __DIR__ . '/site/inc/header.php';
?>

<!-- ============================= القسم الرئيسي ============================= -->
<section class="site-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="hero-badge"><i class="bi bi-stars"></i> منصة عربية 100% لشركات المقاولات</span>
                <h1>إدارة شركة مقاولاتك <span>بذكاء واحترافية</span> من مكان واحد</h1>
                <p class="lead">
                    مشاريع، عقود، فواتير، موظفون، مخزون، ومشتريات — نظام واحد متكامل بالكامل بالعربية،
                    بصلاحيات تخصصونها بأنفسكم لكل موظف، بدون تعقيد وبدون اعتماد على أنظمة متفرقة.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= BASE_URL ?>/modules/auth/register.php" class="btn btn-brand btn-lg px-4"><i class="bi bi-rocket-takeoff me-1"></i> ابدأ تجربتك المجانية 14 يوماً</a>
                    <a href="<?= BASE_URL ?>/features.php" class="btn btn-outline-light btn-lg px-4">استكشف المزايا</a>
                </div>
                <div class="hero-stats">
                    <div><div class="stat-num">16+</div><div class="stat-label">وحدة متكاملة</div></div>
                    <div><div class="stat-num">100%</div><div class="stat-label">واجهة عربية RTL</div></div>
                    <div><div class="stat-num">∞</div><div class="stat-label">تخصيص صلاحيات لكل شركة</div></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-mockup">
                    <div class="hero-mockup-header"><span></span><span></span><span></span></div>
                    <div class="hero-mockup-grid">
                        <div class="hero-mockup-stat"><div class="n">24</div><div class="l">مشروع نشط</div></div>
                        <div class="hero-mockup-stat"><div class="n">318</div><div class="l">عميل</div></div>
                        <div class="hero-mockup-stat"><div class="n">92%</div><div class="l">نسبة التحصيل</div></div>
                        <div class="hero-mockup-stat"><div class="n">57</div><div class="l">موظف</div></div>
                    </div>
                    <div class="hero-mockup-chart">
                        <div class="bar" style="height:35%"></div>
                        <div class="bar" style="height:55%"></div>
                        <div class="bar" style="height:40%"></div>
                        <div class="bar" style="height:70%"></div>
                        <div class="bar" style="height:50%"></div>
                        <div class="bar" style="height:85%"></div>
                        <div class="bar" style="height:65%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================= أبرز المزايا ============================= -->
<section class="site-section">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-eyebrow">لماذا <?= e($platformName) ?></span>
            <h2 class="section-title">كل ما تحتاجه شركة مقاولات، في نظام واحد</h2>
            <p class="section-subtitle">من أول عملية بحث عن عميل حتى إقفال آخر فاتورة في المشروع — <?= e($platformName) ?> يغطي دورة عمل المقاولات كاملة.</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon bg-soft-navy"><i class="bi bi-diagram-3"></i></div>
                    <h5>إدارة المشاريع والعقود</h5>
                    <p>تتبّع كل مشروع من التخطيط حتى التسليم، مع عقود مرتبطة، نسبة إنجاز، وميزانية واضحة لحظة بلحظة.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon bg-soft-teal"><i class="bi bi-receipt"></i></div>
                    <h5>فواتير ومتابعة مالية دقيقة</h5>
                    <p>فواتير ببنود متعددة وضريبة، تسجيل دفعات، ومتابعة المستحقات — بدون جداول إكسل متناثرة.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon bg-soft-amber"><i class="bi bi-person-badge"></i></div>
                    <h5>موظفون، حضور، ورواتب</h5>
                    <p>سجل حضور يومي، وتوليد رواتب شهرية تلقائياً بناءً على بيانات كل موظف.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon bg-soft-red"><i class="bi bi-boxes"></i></div>
                    <h5>مخزون ومشتريات ذكية</h5>
                    <p>تنبيهات انخفاض المخزون، وأوامر شراء تُحدّث كميات المواد تلقائياً عند الاستلام.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon bg-soft-green"><i class="bi bi-shield-lock"></i></div>
                    <h5>صلاحيات تُصمّمونها بأنفسكم</h5>
                    <p>حدّدوا بالضبط ما يمكن لكل دور (محاسب، مهندس، مدير مشاريع...) الوصول إليه، بمصفوفة بسيطة.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon bg-soft-blue"><i class="bi bi-bar-chart-line"></i></div>
                    <h5>تقارير تدعم قراراتكم</h5>
                    <p>ربحية كل مشروع، تحليل المصروفات، وملخصات مالية جاهزة دون الحاجة لإعدادها يدوياً.</p>
                </div>
            </div>
        </div>
        <div class="text-center mt-5">
            <a href="<?= BASE_URL ?>/features.php" class="btn btn-outline-navy">عرض كل المزايا بالتفصيل <i class="bi bi-arrow-left"></i></a>
        </div>
    </div>
</section>

<!-- ============================= كيف يعمل ============================= -->
<section class="site-section bg-light-alt">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-eyebrow">البداية سهلة</span>
            <h2 class="section-title">ابدأوا خلال دقائق، لا أيام</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="step-item">
                    <div class="step-num">1</div>
                    <h5>سجّلوا شركتكم</h5>
                    <p>أنشئوا حساب شركتكم مجاناً وابدأوا فترة تجريبية 14 يوماً بدون بطاقة ائتمانية.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-item">
                    <div class="step-num">2</div>
                    <h5>أضيفوا فريقكم وخصّصوا الصلاحيات</h5>
                    <p>ادعوا موظفيكم وحدّدوا بالضبط ما يراه ويفعله كل دور من صفحة الصلاحيات.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-item">
                    <div class="step-num">3</div>
                    <h5>ابدأوا الإدارة الفعلية</h5>
                    <p>أضيفوا مشاريعكم وعملاءكم، وابدأوا بإصدار الفواتير ومتابعة كل شيء من لوحة واحدة.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================= لمحة عن الأسعار ============================= -->
<section class="site-section">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-eyebrow">التسعير</span>
            <h2 class="section-title">باقات تناسب كل حجم شركة</h2>
            <p class="section-subtitle">من الشركات الناشئة إلى المؤسسات الكبرى — ابدأوا مجاناً وترقّوا وقتما احتجتم.</p>
        </div>
        <?php
        $homePlans = site_get_plans();
        $homeTrialPlan = null;
        $homeFeaturedPlan = null;
        foreach ($homePlans as $__p) {
            if ($homeTrialPlan === null && $__p['price'] === '0') $homeTrialPlan = $__p;
            if (!empty($__p['featured'])) $homeFeaturedPlan = $__p;
        }
        if ($homeTrialPlan === null) $homeTrialPlan = $homePlans[0] ?? null;
        if ($homeFeaturedPlan === null) $homeFeaturedPlan = $homePlans[1] ?? ($homePlans[0] ?? null);
        unset($__p);
        ?>
        <div class="row g-4 justify-content-center">
            <?php if ($homeTrialPlan): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="pricing-card text-center">
                        <h6 class="text-muted fw-bold mb-2"><?= e($homeTrialPlan['name']) ?></h6>
                        <div class="price">مجاناً</div>
                        <p class="text-muted small mt-2"><?= e($homeTrialPlan['desc']) ?></p>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($homeFeaturedPlan): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="pricing-card featured text-center">
                        <span class="featured-badge">الأكثر طلباً</span>
                        <h6 class="text-muted fw-bold mb-2"><?= e($homeFeaturedPlan['name']) ?></h6>
                        <div class="price">
                            <?= $homeFeaturedPlan['price'] === '0' ? 'مجاناً' : e($homeFeaturedPlan['price']) ?>
                            <?php if ($homeFeaturedPlan['price'] !== '0'): ?><small> ر.س/<?= e($homeFeaturedPlan['period']) ?></small><?php endif; ?>
                        </div>
                        <p class="text-muted small mt-2"><?= e($homeFeaturedPlan['desc']) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-5">
            <a href="<?= BASE_URL ?>/pricing.php" class="btn btn-brand">عرض كل الباقات ومقارنة التفاصيل <i class="bi bi-arrow-left"></i></a>
        </div>
    </div>
</section>

<!-- ============================= دعوة ختامية ============================= -->
<section class="site-section pt-0">
    <div class="container">
        <div class="cta-banner">
            <h2>جاهزون لإدارة شركتكم باحترافية؟</h2>
            <p>ابدأوا تجربتكم المجانية الآن، بدون بطاقة ائتمانية وبدون التزام.</p>
            <a href="<?= BASE_URL ?>/modules/auth/register.php" class="btn btn-brand btn-lg px-5"><i class="bi bi-rocket-takeoff me-1"></i> ابدأ تجربتك المجانية</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/site/inc/footer.php'; ?>
