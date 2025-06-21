<?php
/**
 * Plugin Name: Roda da Vida by Simone Silvestrin (RVSS)
 * Plugin URI: https://inovaminds.com.br
 * Description: Plugin interativo para avaliação das 8 áreas da vida com gráfico radar usando AMCharts v5
 * Version: 0.2.0
 * Author: InovaMinds
 * Author URI: https://inovaminds.com.br
 * Text Domain: rvss
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Constantes do plugin
define('RVSS_VERSION', '0.2.0');
define('RVSS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RVSS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RVSS_PLUGIN_FILE', __FILE__);
define('RVSS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principal do plugin
 */
class RodaVidaRVSS {
    
    /**
     * Instância única da classe (Singleton)
     *
     * @var RodaVidaRVSS
     */
    private static $instance = null;
    
    /**
     * Obtém a instância única da classe
     *
     * @return RodaVidaRVSS
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Construtor privado para implementação Singleton
     */
    private function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
        
        // Ativação e desativação
        register_activation_hook(RVSS_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(RVSS_PLUGIN_FILE, array($this, 'deactivate'));
    }
    
    /**
     * Define constantes adicionais
     */
    private function define_constants() {
        define('RVSS_PLUGIN_NAME', 'Roda da Vida RVSS');
        define('RVSS_DB_VERSION', '0.2.0');
        define('RVSS_MINIMUM_WP_VERSION', '5.6');
        define('RVSS_MINIMUM_PHP_VERSION', '7.2');
    }
    
    /**
     * Inclui os arquivos necessários
     */
    private function includes() {
        // Classes principais
        require_once RVSS_PLUGIN_DIR . 'includes/class-rvss-database.php';
        require_once RVSS_PLUGIN_DIR . 'includes/class-rvss-email.php';
        require_once RVSS_PLUGIN_DIR . 'includes/class-rvss-form-handler.php';
        require_once RVSS_PLUGIN_DIR . 'includes/class-rvss-assets.php';
        require_once RVSS_PLUGIN_DIR . 'includes/class-rvss-shortcode.php';
        
        // Internacionalização
        require_once RVSS_PLUGIN_DIR . 'includes/class-rvss-i18n.php';
        
        // Admin
        if (is_admin()) {
            require_once RVSS_PLUGIN_DIR . 'admin/class-rvss-admin.php';
        }
    }
    
    /**
     * Inicializa os hooks do WordPress
     */
    private function init_hooks() {
        // Internacionalização
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Inicializar componentes
        add_action('init', array($this, 'init_components'));
        
        // Verifica compatibilidade
        add_action('admin_init', array($this, 'check_requirements'));
        
        // Adiciona links de configuração
        add_filter('plugin_action_links_' . RVSS_PLUGIN_BASENAME, array($this, 'add_action_links'));
    }
    
    /**
     * Inicializa componentes do plugin
     */
    public function init_components() {
        // Inicializar tratamento de formulários
        RVSS_Form_Handler::init();
        
        // Inicializar shortcodes
        RVSS_Shortcode::init();
        
        // Inicializar carregamento de assets
        RVSS_Assets::init();
        
        // Inicializar admin se no painel
        if (is_admin()) {
            RVSS_Admin::init();
        }
    }
    
    /**
     * Carrega os arquivos de tradução
     */
    public function load_textdomain() {
        load_plugin_textdomain('rvss', false, dirname(RVSS_PLUGIN_BASENAME) . '/languages/');
    }
    
    /**
     * Verifica requisitos do sistema
     */
    public function check_requirements() {
        // Verifica versão do WordPress
        if (version_compare(get_bloginfo('version'), RVSS_MINIMUM_WP_VERSION, '<')) {
            add_action('admin_notices', array($this, 'wp_version_notice'));
            return false;
        }
        
        // Verifica versão do PHP
        if (version_compare(PHP_VERSION, RVSS_MINIMUM_PHP_VERSION, '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Exibe aviso de versão mínima do WordPress
     */
    public function wp_version_notice() {
        echo '<div class="error"><p>';
        printf(
            /* translators: %1$s: Versão mínima do WordPress requerida, %2$s: Versão atual do WordPress */
            esc_html__('Roda da Vida RVSS requer WordPress versão %1$s ou superior. Você está rodando a versão %2$s. Por favor, atualize.', 'rvss'),
            RVSS_MINIMUM_WP_VERSION,
            get_bloginfo('version')
        );
        echo '</p></div>';
    }
    
    /**
     * Exibe aviso de versão mínima do PHP
     */
    public function php_version_notice() {
        echo '<div class="error"><p>';
        printf(
            /* translators: %1$s: Versão mínima do PHP requerida, %2$s: Versão atual do PHP */
            esc_html__('Roda da Vida RVSS requer PHP versão %1$s ou superior. Você está rodando a versão %2$s. Por favor, entre em contato com seu host para atualizar.', 'rvss'),
            RVSS_MINIMUM_PHP_VERSION,
            PHP_VERSION
        );
        echo '</p></div>';
    }
    
    /**
     * Adiciona links de ação na lista de plugins
     *
     * @param array $links Links padrão
     * @return array Links modificados
     */
    public function add_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=rvss-admin') . '">' . esc_html__('Configurações', 'rvss') . '</a>',
            '<a href="' . admin_url('admin.php?page=rvss-clientes') . '">' . esc_html__('Clientes', 'rvss') . '</a>'
        );
        return array_merge($plugin_links, $links);
    }
    
    /**
     * Ativação do plugin
     */
    public function activate() {
        // Cria tabelas no banco de dados
        RVSS_Database::create_tables();
        
        // Configurações padrão
        $this->add_default_options();
        
        // Marca versão instalada
        update_option('rvss_version', RVSS_VERSION);
        update_option('rvss_db_version', RVSS_DB_VERSION);
        
        // Limpa cache
        wp_cache_flush();
        
        // Registra uninstall hook
        register_uninstall_hook(RVSS_PLUGIN_FILE, 'rvss_uninstall');
        
        // Redireciona para página de configuração após ativação
        add_option('rvss_do_activation_redirect', true);
    }
    
    /**
     * Adiciona opções padrão
     */
    private function add_default_options() {
        // Email de feedback
        if (!get_option('rvss_email_feedback')) {
            update_option('rvss_email_feedback', get_option('admin_email'));
        }
        
        // Configurações de tema
        if (!get_option('rvss_theme_settings')) {
            update_option('rvss_theme_settings', array(
                'enable_dark_mode' => true,
                'default_theme' => 'auto',
                'primary_color' => '#3b82f6'
            ));
        }
        
        // Configurações de compartilhamento social
        if (!get_option('rvss_social_sharing')) {
            update_option('rvss_social_sharing', array(
                'enabled' => true,
                'networks' => array(
                    'facebook' => true,
                    'twitter' => true,
                    'whatsapp' => true,
                    'instagram' => false,
                    'linkedin' => false
                )
            ));
        }
    }
    
    /**
     * Desativação do plugin
     */
    public function deactivate() {
        // Limpa transients
        delete_transient('rvss_admin_statistics');
        delete_transient('rvss_admin_cache');
        
        // Limpa cache
        wp_cache_flush();
    }
}

/**
 * Função de desinstalação do plugin
 * Registrada em register_uninstall_hook
 */
function rvss_uninstall() {
    // A lógica principal de desinstalação está em uninstall.php
    // Esta função é apenas um wrapper para compatibilidade
    if (!defined('WP_UNINSTALL_PLUGIN')) {
        return;
    }
    
    require_once plugin_dir_path(__FILE__) . 'uninstall.php';
}

/**
 * Inicializa o plugin
 * 
 * @return RodaVidaRVSS
 */
function RVSS() {
    return RodaVidaRVSS::get_instance();
}

// Inicializa o plugin
RVSS();