<?php
/*
Plugin Name: Menu du Jour
Description: Affiche les menus du jour depuis l'API GoLunch
Version: 1.1
Author: Florian Gay
Author URI: https://golun.ch
Text Domain: menudujour
Domain Path: /languages
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class MDJ_Menu_Du_Jour {

    public function __construct() {
        add_action('init', array($this, 'load_textdomain'));
        add_shortcode('golunch_menus', array($this, 'display_menus'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('menudujour', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function get_menus($restaurant_id) {
        $cache_key = 'glmd_menus_' . $restaurant_id;
        $cached_menus = get_transient($cache_key);

        if (false !== $cached_menus) {
            return $cached_menus;
        }

        $api_url = "https://golun.ch/api/v1/restaurants/" . $restaurant_id . "/menus";
        $response = wp_remote_get($api_url, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            error_log(__('Erreur API: ', 'menudujour') . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log(__('Erreur JSON: ', 'menudujour') . json_last_error_msg());
            return false;
        }
        
        $menus = $data['menus'] ?? [];

        if (empty($menus)) {
            error_log(__('Aucun menu trouvé pour le restaurant ID: ', 'menudujour') . $restaurant_id);
        }

        set_transient($cache_key, $menus, HOUR_IN_SECONDS); // Cache for 1 hour

        return $menus;
    }

    public function display_menus($atts) {
        $atts = shortcode_atts(array(
            'restaurant_id' => get_option('mdj_default_restaurant_id', 'ff9e484a-3e56-4404-a4bc-881dfae500c6'),
            'show_title' => get_option('mdj_show_title', 'yes'),
            'currency' => get_option('mdj_currency', 'CHF'),
        ), $atts, 'golunch_menus');

        if (empty($atts['restaurant_id'])) {
            return __('Erreur : ID du restaurant manquant.', 'menudujour');
        }

        $menus = $this->get_menus($atts['restaurant_id']);
        
        if (false === $menus) {
            return __('Erreur : Impossible de récupérer les menus. Veuillez réessayer plus tard.', 'menudujour');
        }
        
        if (empty($menus)) {
            return __('Aucun menu disponible pour le moment.', 'menudujour');
        }
        
        $style_choice = get_option('mdj_style_choice', 'elegant');
        $date_format = get_option('mdj_date_format', 'd/m/Y');
        $currency = $atts['currency'];
        $output = '<div class="golunch-menus golunch-' . esc_attr($style_choice) . '">';
        
        // Trier les menus par date de début
        usort($menus, function($a, $b) {
            return strtotime($a['date_start']) - strtotime($b['date_start']);
        });

        $current_week_start = null;
        $current_week_end = null;

        foreach ($menus as $menu) {
            $menu_date = strtotime($menu['date_start']);
            $week_start = date('Y-m-d', strtotime('monday this week', $menu_date));
            $week_end = date('Y-m-d', strtotime('sunday this week', $menu_date));

            if ($week_start !== $current_week_start) {
                // Nouvelle semaine, fermer le div précédent si nécessaire
                if ($current_week_start !== null) {
                    $output .= '</div>';
                }
                $current_week_start = $week_start;
                $current_week_end = $week_end;

                $output .= '<div class="golunch-week">';
                if ($atts['show_title'] === 'yes') {
                    $output .= '<h2>' . sprintf(__('Menus du %s au %s', 'menudujour'), date_i18n($date_format, strtotime($week_start)), date_i18n($date_format, strtotime($week_end))) . '</h2>';
                }
            }

            $output .= '<div class="golunch-menu">';
            if (!empty($menu['date_end'])) {
                $date_end = strtotime($menu['date_end']);
                $output .= '<h3>' . date_i18n($date_format, $menu_date) . ' - ' . date_i18n($date_format, $date_end) . '</h3>';
            } else {
                $output .= '<h3>' . date_i18n($date_format, $menu_date) . '</h3>';
            }
            $output .= '<div class="golunch-menu-content">';
            if (!empty($menu['entree'])) {
                $output .= '<p><strong>' . __('Entrée:', 'menudujour') . '</strong> ' . esc_html($menu['entree']) . '</p>';
            }
            $output .= '<p><strong>' . __('Plat:', 'menudujour') . '</strong> ' . esc_html($menu['plat']) . '</p>';
            if (!empty($menu['dessert'])) {
                $output .= '<p><strong>' . __('Dessert:', 'menudujour') . '</strong> ' . esc_html($menu['dessert']) . '</p>';
            }
            $output .= '<p><strong>' . __('Prix:', 'menudujour') . '</strong> ' . esc_html($menu['prix']) . ' ' . esc_html($currency) . '</p>';
            $output .= '</div></div>';
        }

        // Fermer le dernier div de semaine
        if ($current_week_start !== null) {
            $output .= '</div>';
        }

        $output .= '</div>';
        
        return $output;
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_menu-du-jour-settings') {
            return;
        }

        wp_enqueue_script('mdj-admin-script', plugins_url('js/mdj-admin-script.js', __FILE__), array('jquery'), null, true);

        // Passer l'URL de base du plugin à JavaScript
        wp_localize_script('mdj-admin-script', 'mdj_ajax_object', array(
            'plugin_url' => plugins_url('', __FILE__),
        ));
    }

    public function enqueue_styles() {
        $style_choice = get_option('mdj_style_choice', 'elegant');
        switch ($style_choice) {
            case 'elegant':
                wp_enqueue_style('golunch-elegant-styles', plugins_url('styles/golunch-elegant-styles.css', __FILE__));
                break;
            case 'modern':
                wp_enqueue_style('golunch-modern-styles', plugins_url('styles/golunch-modern-styles.css', __FILE__));
                break;
            case 'rustic':
                wp_enqueue_style('golunch-rustic-styles', plugins_url('styles/golunch-rustic-styles.css', __FILE__));
                break;
            case 'minimalist':
                wp_enqueue_style('golunch-minimalist-styles', plugins_url('styles/golunch-minimalist-styles.css', __FILE__));
                break;
            case 'interactive':
                wp_enqueue_style('golunch-interactive-styles', plugins_url('styles/golunch-interactive-styles.css', __FILE__));
                wp_enqueue_script('golunch-interactive-script', plugins_url('js/golunch-interactive-script.js', __FILE__), array('jquery'), null, true);
                break;
            case 'dark':
                wp_enqueue_style('golunch-dark-styles', plugins_url('styles/golunch-dark-styles.css', __FILE__));
                break;
            case 'colorful':
                wp_enqueue_style('golunch-colorful-styles', plugins_url('styles/golunch-colorful-styles.css', __FILE__));
                break;
            case 'minimalist-pro':
                wp_enqueue_style('golunch-minimalist-pro-styles', plugins_url('styles/golunch-minimalist-pro-styles.css', __FILE__));
                break;
            case 'retro':
                wp_enqueue_style('golunch-retro-styles', plugins_url('styles/golunch-retro-styles.css', __FILE__));
                break;
            case 'futuristic':
                wp_enqueue_style('golunch-futuristic-styles', plugins_url('styles/golunch-futuristic-styles.css', __FILE__));
                break;
            case 'accordion':
                wp_enqueue_style('golunch-accordion-styles', plugins_url('styles/golunch-accordion-styles.css', __FILE__));
                wp_enqueue_script('golunch-accordion-script', plugins_url('js/golunch-accordion-script.js', __FILE__), array('jquery'), null, true);
                break;
            case 'carousel':
                wp_enqueue_style('golunch-carousel-styles', plugins_url('styles/golunch-carousel-styles.css', __FILE__));
                wp_enqueue_script('golunch-carousel-script', plugins_url('js/golunch-carousel-script.js', __FILE__), array('jquery'), null, true);
                break;
            case 'fadein':
                wp_enqueue_style('golunch-fadein-styles', plugins_url('styles/golunch-fadein-styles.css', __FILE__));
                wp_enqueue_script('golunch-fadein-script', plugins_url('js/golunch-fadein-script.js', __FILE__), array('jquery'), null, true);
                break;
            case 'vintage':
                wp_enqueue_style('golunch-vintage-styles', plugins_url('styles/golunch-vintage-styles.css', __FILE__));
                break;
            case 'neon':
                wp_enqueue_style('golunch-neon-styles', plugins_url('styles/golunch-neon-styles.css', __FILE__));
                break;
            case 'pastel':
                wp_enqueue_style('golunch-pastel-styles', plugins_url('styles/golunch-pastel-styles.css', __FILE__));
                break;
            case 'chalkboard':
                wp_enqueue_style('golunch-chalkboard-styles', plugins_url('styles/golunch-chalkboard-styles.css', __FILE__));
                break;
            case 'bistro':
                wp_enqueue_style('golunch-bistro-styles', plugins_url('styles/golunch-bistro-styles.css', __FILE__));
                break;
            case 'seasonal':
                wp_enqueue_style('golunch-seasonal-styles', plugins_url('styles/golunch-seasonal-styles.css', __FILE__));
                wp_enqueue_script('golunch-seasonal-script', plugins_url('js/golunch-seasonal-script.js', __FILE__), array('jquery'), null, true);
                break;
            default:
                wp_enqueue_style('golunch-styles', plugins_url('styles/golunch-styles.css', __FILE__));
                break;
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Réglages Menu du Jour', 'menudujour'), // Titre de la page
            __('Menu du Jour', 'menudujour'), // Titre dans le menu
            'manage_options', // Capacité requise
            'menu-du-jour-settings', // Slug du menu
            array($this, 'settings_page'), // Fonction pour afficher la page
            'dashicons-food', // Icône (optionnel)
            30 // Position dans le menu (optionnel)
        );
    }

    public function register_settings() {
        register_setting('mdj_settings', 'mdj_default_restaurant_id', array($this, 'sanitize_restaurant_id'));
        register_setting('mdj_settings', 'mdj_style_choice', array($this, 'sanitize_style_choice'));
        register_setting('mdj_settings', 'mdj_date_format', array($this, 'sanitize_date_format'));
        register_setting('mdj_settings', 'mdj_show_title', array($this, 'sanitize_show_title'));
        register_setting('mdj_settings', 'mdj_currency', array($this, 'sanitize_currency'));
    }

    public function sanitize_restaurant_id($input) {
        return sanitize_text_field($input);
    }

    public function sanitize_style_choice($input) {
        $valid_choices = array('default', 'elegant', 'modern', 'rustic', 'minimalist', 'interactive', 'dark', 'colorful', 'minimalist-pro', 'retro', 'futuristic', 'accordion', 'carousel', 'fadein', 'vintage', 'neon', 'pastel', 'chalkboard', 'bistro', 'seasonal');
        if (in_array($input, $valid_choices)) {
            return $input;
        } else {
            return 'default';
        }
    }

    public function sanitize_date_format($input) {
        $valid_formats = array(
            'd/m/Y', 'd.m.Y', 'd-m-Y', 'j F Y', 'F j, Y', 'j M Y', 'D j M Y', 'l j F Y', 'Y-m-d', 'Y.m.d',
            'd/m/y', 'd.m.y', 'j/n/Y', 'j.n.Y', 'j/n/y', 'j.n.y', 'd F Y', 'l d F Y', 'D d F Y', 'Y F j',
            'j. F Y', 'j. M Y', 'j. M. Y', 'W/Y', 'W/y'
        );
        if (in_array($input, $valid_formats)) {
            return $input;
        } else {
            return 'd/m/Y';
        }
    }

    public function sanitize_show_title($input) {
        $valid_values = array('yes', 'no');
        if (in_array($input, $valid_values)) {
            return $input;
        } else {
            return 'yes';
        }
    }

    public function sanitize_currency($input) {
        $valid_currencies = array('CHF', '€', '$');
        if (in_array($input, $valid_currencies)) {
            return $input;
        } else {
            return 'CHF';
        }
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><span class="dashicons dashicons-food" style="font-size: 30px; vertical-align: text-center; margin-right: 10px;"></span> <?php _e('Réglages Menu du Jour', 'menudujour'); ?></h1>

            <div class="notice notice-info">
                <p><?php _e('Utilisez le shortcode', 'menudujour'); ?> <code>[golunch_menus]</code> <?php _e('pour afficher les menus sur vos pages ou articles.', 'menudujour'); ?></p>
            </div>
            <h3><?php _e('Réglages', 'menudujour'); ?></h3>
            
            <div style="display: flex; justify-content: space-between;">
                <form method="post" action="options.php" style="flex: 1; margin-right: 20px;">
                    <?php
                    settings_fields('mdj_settings');
                    do_settings_sections('mdj_settings');
                    ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><?php _e('ID du restaurant', 'menudujour'); ?></th>
                            <td>
                                <input type="text" name="mdj_default_restaurant_id" value="<?php echo esc_attr(get_option('mdj_default_restaurant_id')); ?>" style="width: 300px;" />
                                <p class="description"><?php _e('Entrez l\'ID GoLunch du restaurant.', 'menudujour'); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Style d\'affichage', 'menudujour'); ?></th>
                            <td>
                                <select name="mdj_style_choice" id="mdj_style_choice">
                                    <?php
                                    $styles = array(
                                        'default' => __('Par défaut', 'menudujour'),
                                        'elegant' => __('Élégant', 'menudujour'),
                                        'modern' => __('Moderne', 'menudujour'),
                                        'rustic' => __('Rustique', 'menudujour'),
                                        'minimalist' => __('Minimaliste', 'menudujour'),
                                        'interactive' => __('Interactif', 'menudujour'),
                                        'dark' => __('Sombre', 'menudujour'),
                                        'colorful' => __('Coloré', 'menudujour'),
                                        'minimalist-pro' => __('Minimaliste Pro', 'menudujour'),
                                        'retro' => __('Rétro', 'menudujour'),
                                        'futuristic' => __('Futuriste', 'menudujour'),
                                        'accordion' => __('Accordéon', 'menudujour'),
                                        'carousel' => __('Carrousel', 'menudujour'),
                                        'fadein' => __('Fade-in', 'menudujour'),
                                        'vintage' => __('Vintage', 'menudujour'),
                                        'neon' => __('Néon', 'menudujour'),
                                        'pastel' => __('Pastel', 'menudujour'),
                                        'chalkboard' => __('Ardoise', 'menudujour'),
                                        'bistro' => __('Bistro', 'menudujour'),
                                        'seasonal' => __('Saisonnier', 'menudujour')
                                    );
                                    $current_style = get_option('mdj_style_choice', 'default');
                                    foreach ($styles as $value => $label) {
                                        echo '<option value="' . esc_attr($value) . '" ' . selected($current_style, $value, false) . '>' . esc_html($label) . '</option>';
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e('Choisissez le style d\'affichage pour vos menus.', 'menudujour'); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Format de date', 'menudujour'); ?></th>
                            <td>
                                <select name="mdj_date_format" id="mdj_date_format">
                                    <?php
                                    $date_formats = array(
                                        'd/m/Y' => __('JJ/MM/AAAA', 'menudujour'),
                                        'd.m.Y' => __('JJ.MM.AAAA', 'menudujour'),
                                        'd-m-Y' => __('JJ-MM-AAAA', 'menudujour'),
                                        'j F Y' => __('J Mois AAAA', 'menudujour'),
                                        'F j, Y' => __('Mois J, AAAA', 'menudujour'),
                                        'j M Y' => __('J Mois AAAA abrégé', 'menudujour'),
                                        'D j M Y' => __('Jour J Mois AAAA', 'menudujour'),
                                        'l j F Y' => __('Jour J Mois AAAA complet', 'menudujour'),
                                        'Y-m-d' => __('AAAA-MM-JJ', 'menudujour'),
                                        'Y.m.d' => __('AAAA.MM.JJ', 'menudujour'),
                                        'd/m/y' => __('JJ/MM/AA', 'menudujour'),
                                        'd.m.y' => __('JJ.MM.AA', 'menudujour'),
                                        'j/n/Y' => __('J/M/AAAA sans zéros', 'menudujour'),
                                        'j.n.Y' => __('J.M.AAAA sans zéros', 'menudujour'),
                                        'j/n/y' => __('J/M/AA sans zéros', 'menudujour'),
                                        'j.n.y' => __('J.M.AA sans zéros', 'menudujour'),
                                        'd F Y' => __('JJ Mois AAAA', 'menudujour'),
                                        'l d F Y' => __('Jour JJ Mois AAAA', 'menudujour'),
                                        'D d F Y' => __('Jour JJ Mois AAAA abrégé', 'menudujour'),
                                        'Y F j' => __('AAAA Mois J', 'menudujour'),
                                        'j. F Y' => __('J. Mois AAAA', 'menudujour'),
                                        'j. M Y' => __('J. Mois AAAA abrégé', 'menudujour'),
                                        'j. M. Y' => __('J. Mois. AAAA abrégé', 'menudujour'),
                                        'W/Y' => __('Semaine/AAAA', 'menudujour'),
                                        'W/y' => __('Semaine/AA', 'menudujour'),
                                    );
                                    $current_format = get_option('mdj_date_format', 'd/m/Y');
                                    $current_timestamp = current_time('timestamp'); // Obtenir l'horodatage actuel selon WordPress

                                    foreach ($date_formats as $value => $label) {
                                        $formatted_date = date_i18n($value, $current_timestamp);
                                        echo '<option value="' . esc_attr($value) . '" ' . selected($current_format, $value, false) . '>' . esc_html($label) . ' (' . esc_html($formatted_date) . ')</option>';
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e('Choisissez le format de date pour l\'affichage des menus.', 'menudujour'); ?></p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"><?php _e('Afficher le titre', 'menudujour'); ?></th>
                            <td>
                                <select name="mdj_show_title">
                                    <option value="yes" <?php selected(get_option('mdj_show_title', 'yes'), 'yes'); ?>><?php _e('Oui', 'menudujour'); ?></option>
                                    <option value="no" <?php selected(get_option('mdj_show_title', 'yes'), 'no'); ?>><?php _e('Non', 'menudujour'); ?></option>
                                </select>
                                <p class="description"><?php _e('Choisissez si vous voulez afficher le titre "Menus du ... au ..."', 'menudujour'); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Devise', 'menudujour'); ?></th>
                            <td>
                                <select name="mdj_currency" id="mdj_currency">
                                    <?php
                                    $currencies = array(
                                        'CHF' => __('CHF', 'menudujour'),
                                        '€'   => __('Euro (€)', 'menudujour'),
                                        '$'   => __('Dollar ($)', 'menudujour')
                                    );
                                    $current_currency = get_option('mdj_currency', 'CHF');
                                    foreach ($currencies as $value => $label) {
                                        echo '<option value="' . esc_attr($value) . '" ' . selected($current_currency, $value, false) . '>' . esc_html($label) . '</option>';
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e('Choisissez la devise pour l\'affichage des prix.', 'menudujour'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Enregistrer les modifications', 'menudujour')); ?>
                </form>

                <div id="style-preview" style="flex: 1; border: 1px solid #ddd; padding: 10px; max-width: 500px;">
                    <h3><?php _e('Aperçu du style', 'menudujour'); ?></h3>
                    <div id="preview-content">
                        <!-- Le contenu de l'aperçu sera chargé ici via JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <?php
    }
}

$mdj_menu_du_jour = new MDJ_Menu_Du_Jour();
