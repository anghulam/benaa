<footer class="site-footer no-print">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="site-brand mb-3">
                    <?php if (!empty($siteLogo)): ?>
                        <img src="<?= BASE_URL ?>/uploads/<?= e($siteLogo) ?>" class="brand-logo-img" alt="<?= e($platformName) ?>">
                    <?php else: ?>
                        <span class="badge-mark">ب</span>
                    <?php endif; ?>
                    <span><?= e($platformName) ?></span>
                </div>
                <p class="text-white-50 small" style="max-width:320px;">
                    منصة SaaS متكاملة لإدارة شركات المقاولات والإنشاءات — المشاريع، العقود، الفواتير، الموظفون،
                    المخزون، والصلاحيات المخصصة، بواجهة عربية احترافية بالكامل.
                </p>
            </div>
            <div class="col-lg-2 col-6">
                <h6 class="text-white fw-bold mb-3">روابط</h6>
                <ul class="list-unstyled site-footer-links">
                    <li><a href="<?= BASE_URL ?>/index.php">الرئيسية</a></li>
                    <li><a href="<?= BASE_URL ?>/features.php">المزايا</a></li>
                    <li><a href="<?= BASE_URL ?>/pricing.php">الأسعار</a></li>
                    <li><a href="<?= BASE_URL ?>/about.php">من نحن</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-6">
                <h6 class="text-white fw-bold mb-3">الحساب</h6>
                <ul class="list-unstyled site-footer-links">
                    <li><a href="<?= BASE_URL ?>/modules/auth/login.php">تسجيل الدخول</a></li>
                    <li><a href="<?= BASE_URL ?>/modules/auth/register.php">إنشاء حساب شركة</a></li>
                    <li><a href="<?= BASE_URL ?>/contact.php">تواصل معنا</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h6 class="text-white fw-bold mb-3">تواصل معنا</h6>
                <ul class="list-unstyled site-footer-links">
                    <li><i class="bi bi-envelope me-1"></i> info@example.com</li>
                    <li><i class="bi bi-telephone me-1"></i> 966+ 5XXXXXXXX</li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary my-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 text-white-50 small">
            <span>&copy; <?= date('Y') ?> <?= e($platformName) ?> — جميع الحقوق محفوظة</span>
            <span>صُمم خصيصاً لشركات المقاولات والإنشاءات</span>
        </div>
    </div>
</footer>

<button type="button" id="backToTop" class="back-to-top no-print" aria-label="العودة لأعلى الصفحة">
    <i class="bi bi-arrow-up"></i>
</button>

<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/site.js"></script>
</body>
</html>
