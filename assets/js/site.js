(function () {
    var navbar = document.querySelector('.site-navbar');
    var backToTop = document.getElementById('backToTop');
    var SCROLL_THRESHOLD = 60;

    function onScroll() {
        var scrolled = window.scrollY > SCROLL_THRESHOLD;
        if (navbar) navbar.classList.toggle('is-scrolled', scrolled);
        if (backToTop) backToTop.classList.toggle('is-visible', window.scrollY > 500);
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    if (backToTop) {
        backToTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // قائمة الموبايل المنسدلة جزء من الشريط الثابت (sticky)؛ فتحها أثناء
    // التمرير لأسفل يوسّع الشريط في مكانه فيظهر خارج الجزء المرئي من الشاشة.
    // نعيد التمرير لأعلى الصفحة عند فتحها حتى تكون مرئية دوماً.
    var mobileMenu = document.getElementById('siteNav');
    if (mobileMenu) {
        mobileMenu.addEventListener('show.bs.collapse', function () {
            if (window.scrollY > SCROLL_THRESHOLD) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }
})();
