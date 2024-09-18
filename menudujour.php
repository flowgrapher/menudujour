<?php
/*
Plugin Name: Menu du Jour
Description: Affiche les menus du jour depuis l'API GoLunch
Version: 1.1
Author: Votre Nom
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Menu_Du_Jour {
    public function __construct() {
        add_shortcode('golunch_menus', array($this, 'display_menus'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function get_menus($restaurant_id) {
        $cache_key = 'glmd_menus_' . $restaurant_id;
        $cached_menus = get_transient($cache_key);

        if (false !== $cached_menus) {
            return $cached_menus;
        }

        $api_url = "https://golun.ch/api/v1/restaurants/" . $restaurant_id . "/menus";
        $response = wp_remote_get($api_url);
        
        if (is_wp_error($response)) {
            error_log('Erreur API: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Erreur JSON: ' . json_last_error_msg());
            return false;
        }
        
        $menus = $data['menus'] ?? [];

        if (empty($menus)) {
            error_log('Aucun menu trouvé pour le restaurant ID: ' . $restaurant_id);
        }

        //set_transient($cache_key, $menus, HOUR_IN_SECONDS); // Cache for 1 hour

        return $menus;
    }

    public function display_menus($atts) {
        $atts = shortcode_atts(array(
            'restaurant_id' => get_option('mdj_default_restaurant_id', ''),
            'show_title' => get_option('mdj_show_title', 'yes'),
            'currency' => get_option('mdj_currency', 'CHF'),
        ), $atts, 'golunch_menus');

        if (empty($atts['restaurant_id'])) {
            return 'Erreur : ID du restaurant manquant.';
        }

        $menus = $this->get_menus($atts['restaurant_id']);
        
        if (false === $menus) {
            return 'Erreur : Impossible de récupérer les menus. Veuillez réessayer plus tard.';
        }
        
        if (empty($menus)) {
            return 'Aucun menu disponible pour le moment.';
        }
        
        $style_choice = get_option('mdj_style_choice', 'default');
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
                    $output .= '<h2>Menus du ' . date_i18n($date_format, strtotime($week_start)) . 
                               ' au ' . date_i18n($date_format, strtotime($week_end)) . '</h2>';
                }
            }

            $output .= '<div class="golunch-menu">';
            $output .= '<h3>' . date_i18n('l', $menu_date) . ' ' . date_i18n($date_format, $menu_date) . '</h3>';
            $output .= '<div class="golunch-menu-content">';
            if (!empty($menu['entree'])) {
                $output .= '<p><strong>Entrée:</strong> ' . esc_html($menu['entree']) . '</p>';
            }
            $output .= '<p><strong>Plat:</strong> ' . esc_html($menu['plat']) . '</p>';
            if (!empty($menu['dessert'])) {
                $output .= '<p><strong>Dessert:</strong> ' . esc_html($menu['dessert']) . '</p>';
            }
            $output .= '<p><strong>Prix:</strong> ' . esc_html($menu['prix']) . ' ' . esc_html($currency) . '</p>';
            $output .= '</div></div>';
        }

        // Fermer le dernier div de semaine
        if ($current_week_start !== null) {
            $output .= '</div>';
        }

        $output .= '</div>';
        
        return $output;
    }

    private function determine_menu_type($menu) {
        $today = new DateTime();
        $start_date = isset($menu['date_start']) ? new DateTime($menu['date_start']) : null;
        $end_date = isset($menu['date_end']) ? new DateTime($menu['date_end']) : null;

        if (!$start_date) {
            return 'unknown';
        }

        if ($start_date == $today) {
            return 'daily';
        } elseif ($start_date > $today) {
            return 'upcoming';
        } elseif ($end_date && $end_date >= $today) {
            return 'weekly';
        } else {
            return 'past';
        }
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
        $style_choice = get_option('mdj_style_choice', 'default');
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
            default:
                wp_enqueue_style('golunch-styles', plugins_url('styles/golunch-styles.css', __FILE__));
                break;
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'Réglages Menu du Jour', // Titre de la page
            'Menu du Jour', // Titre dans le menu
            'manage_options', // Capacité requise
            'menu-du-jour-settings', // Slug du menu
            array($this, 'settings_page'), // Fonction pour afficher la page
            'dashicons-food', // Icône (optionnel)
            30 // Position dans le menu (optionnel)
        );
    }

    public function register_settings() {
        register_setting('mdj_settings', 'mdj_default_restaurant_id');
        register_setting('mdj_settings', 'mdj_style_choice');
        register_setting('mdj_settings', 'mdj_date_format');
        register_setting('mdj_settings', 'mdj_show_title');
        register_setting('mdj_settings', 'mdj_currency'); // Enregistrement de la devise
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><span class="dashicons dashicons-food" style="font-size: 30px; vertical-align: text-center; margin-right: 10px;"></span> Réglages Menu du Jour</h1>

            <div class="notice notice-info">
                <p>Utilisez le shortcode <code>[golunch_menus]</code> pour afficher les menus sur vos pages ou articles.</p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('mdj_settings');
                do_settings_sections('mdj_settings');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">ID du restaurant par défaut</th>
                        <td>
                            <input type="text" name="mdj_default_restaurant_id" value="<?php echo esc_attr(get_option('mdj_default_restaurant_id')); ?>" />
                            <p class="description">Entrez l'ID du restaurant GoLunch par défaut.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Style d'affichage</th>
                        <td>
                            <select name="mdj_style_choice" id="mdj_style_choice">
                                <?php
                                $styles = array(
                                    'default' => 'Par défaut',
                                    'elegant' => 'Élégant',
                                    'modern' => 'Moderne',
                                    'rustic' => 'Rustique',
                                    'minimalist' => 'Minimaliste',
                                    'interactive' => 'Interactif',
                                    'dark' => 'Sombre',
                                    'colorful' => 'Coloré',
                                    'minimalist-pro' => 'Minimaliste Pro',
                                    'retro' => 'Rétro',
                                    'futuristic' => 'Futuriste',
                                    'accordion' => 'Accordéon',
                                    'carousel' => 'Carrousel',
                                    'fadein' => 'Fade-in'
                                );
                                $current_style = get_option('mdj_style_choice', 'default');
                                foreach ($styles as $value => $label) {
                                    echo '<option value="' . esc_attr($value) . '" ' . selected($current_style, $value, false) . '>' . esc_html($label) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">Choisissez le style d'affichage pour vos menus.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Format de date</th>
                        <td>
                            <select name="mdj_date_format" id="mdj_date_format">
                                <?php
                                $date_formats = array(
                                    'd/m/Y' => 'JJ/MM/AAAA (',
                                    'd.m.Y' => 'JJ.MM.AAAA (',
                                    'd-m-Y' => 'JJ-MM-AAAA (',
                                    'j F Y' => 'J Mois AAAA (',
                                    'F j, Y' => 'Mois J, AAAA (',
                                    'j M Y' => 'J Mois AAAA abrégé (',
                                    'D j M Y' => 'Jour J Mois AAAA (',
                                    'l j F Y' => 'Jour J Mois AAAA complet (',
                                    'Y-m-d' => 'AAAA-MM-JJ (',
                                    'Y.m.d' => 'AAAA.MM.JJ (',
                                    'd/m/y' => 'JJ/MM/AA (',
                                    'd.m.y' => 'JJ.MM.AA (',
                                    'j/n/Y' => 'J/M/AAAA sans zéros (',
                                    'j.n.Y' => 'J.M.AAAA sans zéros (',
                                    'j/n/y' => 'J/M/AA sans zéros (',
                                    'j.n.y' => 'J.M.AA sans zéros (',
                                    'd F Y' => 'JJ Mois AAAA (',
                                    'l d F Y' => 'Jour JJ Mois AAAA (',
                                    'D d F Y' => 'Jour JJ Mois AAAA abrégé (',
                                    'Y F j' => 'AAAA Mois J (',
                                    'j. F Y' => 'J. Mois AAAA (',
                                    'j. M Y' => 'J. Mois AAAA abrégé (',
                                    'j. M. Y' => 'J. Mois. AAAA abrégé (',
                                    'W/Y' => 'Semaine/AAAA (',
                                    'W/y' => 'Semaine/AA (',
                                );
                                $current_format = get_option('mdj_date_format', 'd/m/Y');
                                $current_timestamp = current_time('timestamp'); // Obtenir l'horodatage actuel selon WordPress

                                foreach ($date_formats as $value => $label) {
                                    // Formater la date actuelle selon le format courant
                                    $formatted_date = date_i18n($value, $current_timestamp);
                                    echo '<option value="' . esc_attr($value) . '" ' . selected($current_format, $value, false) . '>' . esc_html($label) . esc_html($formatted_date) . ')</option>';
                                }
                                ?>
                            </select>
                            <p class="description">Choisissez le format de date pour l'affichage des menus.</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Afficher le titre</th>
                        <td>
                            <select name="mdj_show_title">
                                <option value="yes" <?php selected(get_option('mdj_show_title', 'yes'), 'yes'); ?>>Oui</option>
                                <option value="no" <?php selected(get_option('mdj_show_title', 'yes'), 'no'); ?>>Non</option>
                            </select>
                            <p class="description">Choisissez si vous voulez afficher le titre "Menus du ... au ..."</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Devise</th>
                        <td>
                            <select name="mdj_currency" id="mdj_currency">
                                <?php
                                $currencies = array(
                                    'CHF' => 'CHF',
                                    '€'   => 'Euro (€)',
                                    '$'   => 'Dollar ($)'
                                );
                                $current_currency = get_option('mdj_currency', 'CHF');
                                foreach ($currencies as $value => $label) {
                                    echo '<option value="' . esc_attr($value) . '" ' . selected($current_currency, $value, false) . '>' . esc_html($label) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">Choisissez la devise pour l'affichage des prix.</p>
                        </td>
                    </tr>
                </table>
                
                <div id="style-preview" style="margin-top: 20px;">
                    <h3>Aperçu du style</h3>
                    <div id="preview-content" style="border: 1px solid #ddd; padding: 10px; max-width: 500px;">
                        <!-- Le contenu de l'aperçu sera chargé ici via JavaScript -->
                    </div>
                </div>
                
                <?php submit_button('Enregistrer les modifications'); ?>
            </form>
        </div>

        <?php
    }
}

$menu_du_jour = new Menu_Du_Jour();