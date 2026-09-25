<?php
$pageTitle = 'من نحن';
$pageDescription = 'تعرّفوا على بناء: منصة عربية بُنيت خصيصاً لتفهم كيف تعمل شركات المقاولات والإنشاءات فعلياً.';
$activeNav = 'about';
require __DIR__ . '/site/inc/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>بُني خصيصاً لشركات المقاولات</h1>
        <p>لم نبنِ نظام إدارة عام وأضفنا له بعض الحقول — بنينا <?= e($platformName) ?> حول الطريقة الفعلية التي تعمل بها شركات المقاولات.</p>
    </div>
</section>

<section class="site-section">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <span class="section-eyebrow">مهمتنا</span>
                <h2 class="section-title">تبسيط إدارة المقاولات بالكامل</h2>
                <p class="text-muted mb-4">
                    قطاع المقاولات معقّد بطبيعته: مشاريع طويلة، عقود متعددة الأطراف، فرق ميدانية ومكتبية،
                    ومخزون يتحرك يومياً. كثير من الشركات تدير كل هذا عبر جداول إكسل متفرقة وتطبيقات
                    غير مترابطة. مهمتنا في <?= e($platformName) ?> أن نجمع كل هذا في نظام واحد مترابط، بواجهة عربية
                    تفهمها فرق العمل الميدانية والإدارية على حد سواء.
                </p>
                <ul class="icon-check-list">
                    <li><i class="bi bi-check-circle-fill"></i> نظام مصمم من الأساس بلغة عربية RTL كاملة، لا مجرد ترجمة.</li>
                    <li><i class="bi bi-check-circle-fill"></i> يغطي دورة عمل المقاولات الكاملة: من العميل إلى الفاتورة الأخيرة.</li>
                    <li><i class="bi bi-check-circle-fill"></i> صلاحيات مرنة تتيح لكل شركة تنظيم فريقها كما تراه مناسباً.</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="feature-card text-center">
                            <div class="feature-icon bg-soft-navy mx-auto"><i class="bi bi-translate"></i></div>
                            <h5>عربي أصيل</h5>
                            <p>تصميم RTL من الأساس، لا إضافة لاحقة</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="feature-card text-center">
                            <div class="feature-icon bg-soft-teal mx-auto"><i class="bi bi-shield-check"></i></div>
                            <h5>بيانات معزولة وآمنة</h5>
                            <p>كل شركة في بيئة مستقلة تماماً</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="feature-card text-center">
                            <div class="feature-icon bg-soft-amber mx-auto"><i class="bi bi-sliders"></i></div>
                            <h5>مرونة كاملة</h5>
                            <p>صلاحيات وأدوار تُبنى حسب شركتكم</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="feature-card text-center">
                            <div class="feature-icon bg-soft-red mx-auto"><i class="bi bi-headset"></i></div>
                            <h5>دعم قريب منكم</h5>
                            <p>فريق يفهم قطاع المقاولات فعلياً</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="site-section bg-light-alt">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-eyebrow">قيمنا</span>
            <h2 class="section-title">ما الذي يوجّه نظام <?= e($platformName) ?></h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon bg-soft-navy"><i class="bi bi-eye"></i></div>
                    <h5>الوضوح قبل التعقيد</h5>
                    <p>كل شاشة في <?= e($platformName) ?> مصممة لتُفهم من أول نظرة، دون الحاجة لتدريب طويل.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon bg-soft-teal"><i class="bi bi-lock"></i></div>
                    <h5>الموثوقية أولاً</h5>
                    <p>حماية بياناتكم وعزلها عن باقي الشركات ليست ميزة إضافية، بل أساس بُني عليها النظام.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon bg-soft-amber"><i class="bi bi-arrow-repeat"></i></div>
                    <h5>تطوّر مستمر</h5>
                    <p>نضيف مزايا جديدة باستمرار بناءً على احتياجات شركات المقاولات الفعلية.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="site-section pt-0">
    <div class="container">
        <div class="cta-banner">
            <h2>هل تديرون شركة مقاولات؟</h2>
            <p>انضموا إلى <?= e($platformName) ?> وجرّبوا فرقاً حقيقياً في طريقة إدارة مشاريعكم.</p>
            <a href="<?= BASE_URL ?>/modules/auth/register.php" class="btn btn-brand btn-lg px-5"><i class="bi bi-rocket-takeoff me-1"></i> ابدأ تجربتك المجانية</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/site/inc/footer.php'; ?>
