<?php
/**
 * Assets handler for Roda da Vida by Simone Silvestrin (RVSS)
 *
 * Handles loading of styles and scripts
 *
 * @package   RVSS
 * @author    InovaMinds
 * @license   GPL v2 or later
 * @version   0.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class RVSS_Assets
 */
class RVSS_Assets {
    
    /**
     * Inicializa o handler de assets
     */
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
        add_action('wp_head', array(__CLASS__, 'add_custom_css'));
        add_action('admin_head', array(__CLASS__, 'add_admin_custom_css'));
    }
    
    /**
     * Carrega os assets do frontend
     */
    public static function enqueue_frontend_assets() {
        // Versão com timestamp para evitar cache em desenvolvimento
        $version = defined('WP_DEBUG') && WP_DEBUG ? RVSS_VERSION . '.' . time() : RVSS_VERSION;
        
        // Estilos
        wp_enqueue_style(
            'rvss-style',
            RVSS_PLUGIN_URL . 'assets/css/style.css',
            array(),
            $version
        );
        
        // Dashicons para ícones
        wp_enqueue_style('dashicons');
        
        // AMCharts (carrega somente na página com o shortcode)
        if (self::should_load_amcharts()) {
            wp_enqueue_script(
                'amcharts5-core',
                'https://cdn.amcharts.com/lib/5/index.js',
                array(),
                $version,
                true
            );
            
            wp_enqueue_script(
                'amcharts5-xy',
                'https://cdn.amcharts.com/lib/5/xy.js',
                array('amcharts5-core'),
                $version,
                true
            );
            
            wp_enqueue_script(
                'amcharts5-radar',
                'https://cdn.amcharts.com/lib/5/radar.js',
                array('amcharts5-core', 'amcharts5-xy'),
                $version,
                true
            );
            
            wp_enqueue_script(
                'amcharts5-animated',
                'https://cdn.amcharts.com/lib/5/themes/Animated.js',
                array('amcharts5-core'),
                $version,
                true
            );
            
            wp_enqueue_script(
                'amcharts5-responsive',
                'https://cdn.amcharts.com/lib/5/themes/Responsive.js',
                array('amcharts5-core'),
                $version,
                true
            );
            
            wp_enqueue_script(
                'rvss-amcharts',
                RVSS_PLUGIN_URL . 'assets/js/amcharts-integration.js',
                array('jquery', 'amcharts5-core', 'amcharts5-radar', 'amcharts5-animated', 'amcharts5-responsive'),
                $version,
                true
            );
            
            // Passa variáveis para o script
            wp_localize_script(
                'rvss-amcharts',
                'rvss_vars',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('rvss_nonce'),
                    'settings' => self::get_frontend_settings(),
                    'translations' => array(
                        'chart_title' => esc_html__('Roda da Vida', 'rvss'),
                        'loading' => esc_html__('Carregando...', 'rvss'),
                        'update_chart' => esc_html__('Atualizar Gráfico', 'rvss'),
                        'error' => esc_html__('Ocorreu um erro. Por favor, tente novamente.', 'rvss')
                    )
                )
            );
        }
        
        // Scripts principais do frontend
        wp_enqueue_script(
            'rvss-frontend',
            RVSS_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            $version,
            true
        );
    }
    
    /**
     * Carrega os assets do admin
     *
     * @param string $hook Página atual do admin
     */
    public static function enqueue_admin_assets($hook) {
        // Verifica se estamos em uma página do plugin
        if (strpos($hook, 'rvss') === false) {
            return;
        }
        
        // Versão com timestamp para evitar cache em desenvolvimento
        $version = defined('WP_DEBUG') && WP_DEBUG ? RVSS_VERSION . '.' . time() : RVSS_VERSION;
        
        // Estilos do admin
        wp_enqueue_style(
            'rvss-admin-style',
            RVSS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $version
        );
        
        // Scripts do admin
        wp_enqueue_script(
            'rvss-admin',
            RVSS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            $version,
            true
        );
        
        // Passa variáveis para o script
        wp_localize_script(
            'rvss-admin',
            'rvss_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rvss_admin_nonce'),
                'delete_confirm' => esc_html__('Tem certeza que deseja excluir? Esta ação não pode ser desfeita.', 'rvss'),
                'export_url' => admin_url('admin.php?page=rvss-clientes&action=export')
            )
        );
        
        // Carrega assets específicos para a página de clientes
        if ($hook === 'rvss_page_rvss-clientes') {
            // AMCharts para dashboard
            wp_enqueue_script('amcharts5-core', 'https://cdn.amcharts.com/lib/5/index.js', array(), $version, true);
            wp_enqueue_script('amcharts5-xy', 'https://cdn.amcharts.com/lib/5/xy.js', array('amcharts5-core'), $version, true);
            wp_enqueue_script('amcharts5-percent', 'https://cdn.amcharts.com/lib/5/percent.js', array('amcharts5-core'), $version, true);
            wp_enqueue_script('amcharts5-radar', 'https://cdn.amcharts.com/lib/5/radar.js', array('amcharts5-core'), $version, true);
            wp_enqueue_script('amcharts5-animated', 'https://cdn.amcharts.com/lib/5/themes/Animated.js', array('amcharts5-core'), $version, true);
            
            // Scripts específicos do dashboard
            wp_enqueue_script(
                'rvss-admin-dashboard',
                RVSS_PLUGIN_URL . 'assets/js/admin-dashboard.js',
                array('jquery', 'amcharts5-core', 'amcharts5-radar', 'amcharts5-xy', 'amcharts5-percent', 'amcharts5-animated'),
                $version,
                true
            );
        }
    }
    
    /**
     * Adiciona CSS personalizado no frontend
     */
    public static function add_custom_css() {
        $settings = self::get_frontend_settings();
        
        // Cores personalizadas do plugin
        $primary_color = isset($settings['primary_color']) ? sanitize_hex_color($settings['primary_color']) : '#3b82f6';
        
        echo '<style type="text/css">
            :root {
                --rvss-primary-color: ' . esc_attr($primary_color) . ';
                --rvss-primary-color-dark: ' . esc_attr(self::adjust_brightness($primary_color, -20)) . ';
                --rvss-primary-color-light: ' . esc_attr(self::adjust_brightness($primary_color, 20)) . ';
            }
        </style>';
    }
    
    /**
     * Adiciona CSS personalizado no admin
     */
    public static function add_admin_custom_css() {
        // Verifica se estamos em uma página do plugin
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'rvss') === false) {
            return;
        }
        
        $settings = self::get_frontend_settings();
        
        // Cores personalizadas do plugin
        $primary_color = isset($settings['primary_color']) ? sanitize_hex_color($settings['primary_color']) : '#3b82f6';
        
        echo '<style type="text/css">
            :root {
                --rvss-primary-color: ' . esc_attr($primary_color) . ';
                --rvss-primary-color-dark: ' . esc_attr(self::adjust_brightness($primary_color, -20)) . ';
                --rvss-primary-color-light: ' . esc_attr(self::adjust_brightness($primary_color, 20)) . ';
            }
        </style>';
    }
    
    /**
     * Verifica se deve carregar os scripts do AMCharts
     *
     * @return bool Verdadeiro se deve carregar
     */
    private static function should_load_amcharts() {
        // Verifica se o shortcode existe na página atual
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'roda_vida_rvss')) {
            return true;
        }
        
        // Verificação adicional para temas/plugins que adicionam o shortcode dinamicamente
        return apply_filters('rvss_should_load_amcharts', false);
    }
    
    /**
     * Obtém as configurações do frontend
     *
     * @return array Configurações
     */
    private static function get_frontend_settings() {
        $theme_settings = get_option('rvss_theme_settings', array());
        $social_sharing = get_option('rvss_social_sharing', array());
        
        $defaults = array(
            'enable_dark_mode' => true,
            'default_theme' => 'auto',
            'primary_color' => '#3b82f6',
            'enable_social_sharing' => !empty($social_sharing['enabled']) ? $social_sharing['enabled'] : true,
            'social_networks' => !empty($social_sharing['networks']) ? $social_sharing['networks'] : array(
                'facebook' => true,
                'twitter' => true,
                'whatsapp' => true,
                'instagram' => false,
                'linkedin' => false
            )
        );
        
        // Mescla com as configurações salvas
        $settings = wp_parse_args($theme_settings, $defaults);
        
        return $settings;
    }
    
    /**
     * Ajusta o brilho de uma cor hex
     *
     * @param string $hex Cor em formato hexadecimal
     * @param int $steps Passos para ajustar (-255 a 255)
     * @return string Cor ajustada em formato hexadecimal
     */
    private static function adjust_brightness($hex, $steps) {
        // Validação
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }
        
        // Converte para RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Ajusta brilho
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));
        
        // Converte de volta para hexadecimal
        return '#' . sprintf('%02x%02x%02x', $r, $g, $b);
    }
}

// Não inicializa a classe aqui, será feito pelo arquivo principal