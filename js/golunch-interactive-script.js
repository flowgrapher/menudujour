jQuery(document).ready(function ($) {
    $('.golunch-interactive .golunch-menu h3').click(function () {
        $(this).next('.golunch-menu-content').slideToggle(300);
    });
});