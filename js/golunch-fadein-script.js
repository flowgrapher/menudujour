jQuery(document).ready(function ($) {
    function checkVisible(elm) {
        var rect = elm.getBoundingClientRect();
        var viewHeight = Math.max(document.documentElement.clientHeight, window.innerHeight);
        return !(rect.bottom < 0 || rect.top - viewHeight >= 0);
    }

    function scanVisibility() {
        $('.golunch-fadein .golunch-menu').each(function () {
            if (checkVisible(this)) {
                $(this).addClass('visible');
            }
        });
    }

    $(window).on('scroll resize', scanVisibility);
    scanVisibility();
});