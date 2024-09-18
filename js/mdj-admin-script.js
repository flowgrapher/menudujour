jQuery(document).ready(function ($) {
    // Fonction de formatage de date similaire à PHP
    function formatDate(date, format) {
        const padZero = (num) => num.toString().padStart(2, '0');

        const replacements = {
            // Day
            'd': padZero(date.getDate()), // 01 to 31
            'j': date.getDate(), // 1 to 31
            // Month
            'm': padZero(date.getMonth() + 1), // 01 to 12
            'n': date.getMonth() + 1, // 1 to 12
            'F': date.toLocaleString('fr-FR', { month: 'long' }), // janvier à décembre
            'M': date.toLocaleString('fr-FR', { month: 'short' }), // janv. à déc.
            // Year
            'Y': date.getFullYear(), // 2023
            'y': date.getFullYear().toString().slice(-2), // 23
            // Week number
            'W': getWeekNumber(date), // 01 to 53
            // Weekday
            'l': date.toLocaleString('fr-FR', { weekday: 'long' }), // dimanche à samedi
            'D': date.toLocaleString('fr-FR', { weekday: 'short' }), // dim. à sam.
        };

        // Fonction pour obtenir le numéro de la semaine selon ISO-8601
        function getWeekNumber(d) {
            // Copie de la date pour ne pas modifier l'original
            d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
            // Définit au jeudi le plus proche : date actuelle + 4 - numéro du jour
            d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
            // Obtient le premier jour de l'année
            const yearStart = new Date(Date.UTC(d.getFullYear(), 0, 1));
            // Calcule les semaines complètes jusqu'au jeudi le plus proche
            const weekNo = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
            return padZero(weekNo);
        }

        let formatted = '';

        for (let i = 0; i < format.length; i++) {
            let char = format[i];
            if (replacements.hasOwnProperty(char)) {
                formatted += replacements[char];
            } else {
                formatted += char;
            }
        }

        return formatted;
    }

    function updatePreview() {
        var selectedStyle = $('#mdj_style_choice').val();
        var previewContent = $('#preview-content');
        var dateFormat = $('#mdj_date_format').val();
        var currentDate = new Date();
        var currency = $('#mdj_currency').val();

        // Formater la date en utilisant le format PHP sélectionné
        var formattedDate = formatDate(currentDate, dateFormat);

        var menuHtml = '<div class="golunch-menus golunch-' + selectedStyle + '">' +
            '<div class="golunch-menu">' +
            '<h3>Menu du ' + formattedDate + '</h3>' +
            '<div class="golunch-menu-content">' +
            '<p><strong>Entrée:</strong> Salade verte</p>' +
            '<p><strong>Plat:</strong> Poulet rôti, sauce champignons, frites et légumes du jour</p>' +
            '<p><strong>Dessert:</strong> Tarte aux pommes</p>' +
            '<p><strong>Prix:</strong> 25 ' + currency + '</p>' +
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
        var stylesWithScript = ['interactive', 'accordion', 'carousel', 'fadein', 'seasonal'];
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

    $('#mdj_style_choice, #mdj_date_format, #mdj_currency').change(updatePreview);
    updatePreview(); // Appeler au chargement initial
});
