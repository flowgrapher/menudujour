jQuery(document).ready(function ($) {
    $('.golunch-accordion .golunch-menu h3').click(function () {
        $(this).next('.golunch-menu-content').slideToggle(300);
        $(this).closest('.golunch-menu').toggleClass('active');
        $('.golunch-accordion .golunch-menu').not($(this).closest('.golunch-menu')).removeClass('active').find('.golunch-menu-content').slideUp(300);
    });
});