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
})();
