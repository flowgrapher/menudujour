jQuery(document).ready(function ($) {
    function setSeasonalStyle() {
        var now = new Date();
        var month = now.getMonth() + 1; // getMonth() returns 0-11
        var $seasonal = $('.golunch-seasonal');

        if (month >= 3 && month <= 5) { // Spring
            $seasonal.css({
                'background-color': '#e6f3ff',
                'color': '#2c3e50',
                'box-shadow': '0 0 20px rgba(135, 206, 250, 0.3)'
            });
            $seasonal.find('.golunch-menu').css({
                'border': '1px solid #87CEFA',
                'background-color': 'rgba(255, 255, 255, 0.7)'
            });
            $seasonal.find('h2, h3').css('color', '#4a90e2');
            $seasonal.css('background-image', 'url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'20\' height=\'20\' viewBox=\'0 0 20 20\'%3E%3Cpath d=\'M10 2C5.6 2 2 5.6 2 10s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 14c-3.3 0-6-2.7-6-6s2.7-6 6-6 6 2.7 6 6-2.7 6-6 6z\' fill=\'%234a90e2\'/%3E%3C/svg%3E")');
        } else if (month >= 6 && month <= 8) { // Summer
            $seasonal.css({
                'background-color': '#fff9e6',
                'color': '#d35400',
                'box-shadow': '0 0 20px rgba(243, 156, 18, 0.3)'
            });
            $seasonal.find('.golunch-menu').css({
                'border': '1px solid #f39c12',
                'background-color': 'rgba(255, 255, 255, 0.7)'
            });
            $seasonal.find('h2, h3').css('color', '#f39c12');
            $seasonal.css('background-image', 'url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'20\' height=\'20\' viewBox=\'0 0 20 20\'%3E%3Cpath d=\'M10 2c-4.4 0-8 3.6-8 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 14c-3.3 0-6-2.7-6-6s2.7-6 6-6 6 2.7 6 6-2.7 6-6 6z\' fill=\'%23f39c12\'/%3E%3C/svg%3E")');
        } else if (month >= 9 && month <= 11) { // Autumn
            $seasonal.css({
                'background-color': '#fff0e6',
                'color': '#6e4e1e',
                'box-shadow': '0 0 20px rgba(211, 84, 0, 0.3)'
            });
            $seasonal.find('.golunch-menu').css({
                'border': '1px solid #d35400',
                'background-color': 'rgba(255, 255, 255, 0.7)'
            });
            $seasonal.find('h2, h3').css('color', '#d35400');
            $seasonal.css('background-image', 'url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'20\' height=\'20\' viewBox=\'0 0 20 20\'%3E%3Cpath d=\'M10 2c-4.4 0-8 3.6-8 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 14c-3.3 0-6-2.7-6-6s2.7-6 6-6 6 2.7 6 6-2.7 6-6 6z\' fill=\'%23d35400\'/%3E%3C/svg%3E")');
        } else { // Winter
            $seasonal.css({
                'background-color': '#e6f0ff',
                'color': '#34495e',
                'box-shadow': '0 0 20px rgba(52, 152, 219, 0.3)'
            });
            $seasonal.find('.golunch-menu').css({
                'border': '1px solid #3498db',
                'background-color': 'rgba(255, 255, 255, 0.7)'
            });
            $seasonal.find('h2, h3').css('color', '#3498db');
            $seasonal.css('background-image', 'url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'20\' height=\'20\' viewBox=\'0 0 20 20\'%3E%3Cpath d=\'M10 2c-4.4 0-8 3.6-8 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 14c-3.3 0-6-2.7-6-6s2.7-6 6-6 6 2.7 6 6-2.7 6-6 6z\' fill=\'%233498db\'/%3E%3C/svg%3E")');
        }
    }

    setSeasonalStyle();
    // Update style every hour in case the season changes while the page is open
    setInterval(setSeasonalStyle, 3600000);
});