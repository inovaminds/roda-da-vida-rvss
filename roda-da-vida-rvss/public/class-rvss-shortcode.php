<?php
/**
 * Classe responsável pelo gerenciamento do shortcode principal
 *
 * @package RVSS
 * @version 0.2.0
 */

defined('ABSPATH') || exit;

/**
 * Classe RVSS_Shortcode
 */
class RVSS_Shortcode {

    /**
     * Instância única da classe
     */
    private static $instance = null;

    /**
     * Obtém instância única da classe
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Construtor privado
     */
    private function __construct() {
        add_action('init', array($this, 'register_shortcode'));
    }

    /**
     * Registra o shortcode principal
     */
    public function register_shortcode() {
        add_shortcode('roda_vida_rvss', array($this, 'render_shortcode'));
    }

    /**
     * Renderiza o shortcode
     */
    public function render_shortcode($atts) {
        // Atributos padrão
        $atts = shortcode_atts(array(
            'theme' => 'auto', // auto, light, dark
            'show_social' => 'true',
            'title' => __('Roda da Vida - Avalie suas 8 Áreas da Vida', 'rvss')
        ), $atts, 'roda_vida_rvss');

        // Enfileirar assets necessários
        $this->enqueue_assets();

        // Capturar saída
        ob_start();
        
        // Incluir template
        $template_path = RVSS_PLUGIN_DIR . 'templates/roda-form-template.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="rvss-error">' . __('Template não encontrado.', 'rvss') . '</div>';
        }

        return ob_get_clean();
    }

    /**
     * Enfileira assets específicos do shortcode
     */
    private function enqueue_assets() {
        // CSS Principal
        wp_enqueue_style(
            'rvss-style', 
            RVSS_PLUGIN_URL . 'assets/css/style.css', 
            array(), 
            RVSS_VERSION
        );

        // AMCharts v5 (CDN)
        wp_enqueue_script(
            'amcharts5-core',
            'https://cdn.amcharts.com/lib/5/index.js',
            array(),
            '5.0.0',
            true
        );

        wp_enqueue_script(
            'amcharts5-xy',
            'https://cdn.amcharts.com/lib/5/xy.js',
            array('amcharts5-core'),
            '5.0.0',
            true
        );

        wp_enqueue_script(
            'amcharts5-radar',
            'https://cdn.amcharts.com/lib/5/radar.js',
            array('amcharts5-core'),
            '5.0.0',
            true
        );

        wp_enqueue_script(
            'amcharts5-themes',
            'https://cdn.amcharts.com/lib/5/themes/Animated.js',
            array('amcharts5-core'),
            '5.0.0',
            true
        );

        wp_enqueue_script(
            'amcharts5-responsive',
            'https://cdn.amcharts.com/lib/5/themes/Responsive.js',
            array('amcharts5-core'),
            '5.0.0',
            true
        );

        // Scripts do plugin
        wp_enqueue_script(
            'rvss-amcharts-integration',
            RVSS_PLUGIN_URL . 'assets/js/amcharts-integration.js',
            array('jquery', 'amcharts5-core', 'amcharts5-radar'),
            RVSS_VERSION,
            true
        );

        wp_enqueue_script(
            'rvss-form-handler',
            RVSS_PLUGIN_URL . 'assets/js/form-handler.js',
            array('jquery', 'rvss-amcharts-integration'),
            RVSS_VERSION,
            true
        );

        // Localizar script para AJAX
        wp_localize_script('rvss-form-handler', 'rvss_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rvss_form_submit'),
            'strings' => array(
                'loading' => __('Carregando...', 'rvss'),
                'error' => __('Erro ao processar. Tente novamente.', 'rvss'),
                'success' => __('Dados enviados com sucesso!', 'rvss'),
                'required_fields' => __('Por favor, preencha todos os campos obrigatórios.', 'rvss'),
                'invalid_email' => __('Por favor, insira um email válido.', 'rvss')
            )
        ));
    }

    /**
     * Verifica se é uma página que contém o shortcode
     */
    public static function has_shortcode($content = null) {
        if ($content === null) {
            global $post;
            if (isset($post->post_content)) {
                $content = $post->post_content;
            }
        }
        
        return has_shortcode($content, 'roda_vida_rvss');
    }
}

// Inicializar classe
RVSS_Shortcode::get_instance();
