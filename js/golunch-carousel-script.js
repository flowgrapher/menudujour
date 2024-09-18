jQuery(document).ready(function ($) {
    var $menus = $('.golunch-carousel .golunch-menu');
    var currentIndex = 0;

    function showMenu(index) {
        $menus.removeClass('active').eq(index).addClass('active');
    }

    $('.golunch-carousel').append('<div class="golunch-carousel-nav"><button class="prev">Précédent</button><button class="next">Suivant</button></div>');

    $('.golunch-carousel-nav .prev').click(function () {
        currentIndex = (currentIndex > 0) ? currentIndex - 1 : $menus.length - 1;
        showMenu(currentIndex);
    });

    $('.golunch-carousel-nav .next').click(function () {
        currentIndex = (currentIndex < $menus.length - 1) ? currentIndex + 1 : 0;
        showMenu(currentIndex);
    });

    showMenu(currentIndex);
});