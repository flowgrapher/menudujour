jQuery(document).ready(function ($) {
    function updatePreview() {
        var selectedStyle = $('#mdj_style_choice').val();
        var previewContent = $('#preview-content');
        var dateFormat = $('#mdj_date_format').val();
        var currentDate = new Date();

        // Formater la date en utilisant un format de date simple pour l'aperçu
        var options = { year: 'numeric', month: 'short', day: 'numeric' };
        var formattedDate = currentDate.toLocaleDateString('fr-FR', options);

        var menuHtml = '<div class="golunch-menus golunch-' + selectedStyle + '">' +
            '<div class="golunch-menu">' +
            '<h3>Menu du ' + formattedDate + '</h3>' +
            '<div class="golunch-menu-content">' +
            '<p><strong>Entrée:</strong> Salade César</p>' +
            '<p><strong>Plat:</strong> Steak frites</p>' +
            '<p><strong>Dessert:</strong> Tarte aux pommes</p>' +
            '<p><strong>Prix:</strong> 25 CHF</p>' +
            '</div></div></div>';

        previewContent.html(menuHtml);

        // Charger le CSS correspondant
        $('head').find('link[id^="golunch-preview-"]').remove();
        $('<link>')
            .attr({
                id: 'golunch-preview-' + selectedStyle + '-styles',
                rel: 'stylesheet',
                type: 'text/css',
                href: mdj_ajax_object.plugin_url + '/styles/golunch-' + selectedStyle + '-styles.css'
            })
            .appendTo('head');

        // Charger le script JS si nécessaire
        var stylesWithScript = ['interactive', 'accordion', 'carousel', 'fadein'];
        if (stylesWithScript.includes(selectedStyle)) {
            // Vérifiez si le script n'est pas déjà chargé
            if (!$('script[src="' + mdj_ajax_object.plugin_url + '/js/golunch-' + selectedStyle + '-script.js"]').length) {
                $.getScript(mdj_ajax_object.plugin_url + '/js/golunch-' + selectedStyle + '-script.js')
                    .done(function () {
                        console.log(selectedStyle + ' script loaded successfully');
                    })
                    .fail(function (jqxhr, settings, exception) {
                        console.log('Failed to load ' + selectedStyle + ' script: ' + exception);
                    });
            }
        }
    }

    $('#mdj_style_choice, #mdj_date_format').change(updatePreview);
    updatePreview(); // Appeler au chargement initial
});
