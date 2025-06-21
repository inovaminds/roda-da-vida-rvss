/**
 * Internationalization for Roda da Vida by Simone Silvestrin (RVSS)
 *
 * Handles text domain loading and internationalization
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
 * Class RVSS_I18n
 */
class RVSS_I18n {
    
    /**
     * Inicializa a classe de internacionalização
     */
    public static function init() {
        add_action('plugins_loaded', array(__CLASS__, 'load_plugin_textdomain'));
    }
    
    /**
     * Carrega o domínio de texto do plugin
     */
    public static function load_plugin_textdomain() {
        load_plugin_textdomain(
            'rvss',
            false,
            dirname(plugin_basename(RVSS_PLUGIN_FILE)) . '/languages/'
        );
    }
    
    /**
     * Retorna o texto traduzido para uso em JavaScript
     *
     * @return array Textos traduzidos
     */
    public static function get_js_translations() {
        return array(
            'error' => __('Erro', 'rvss'),
            'success' => __('Sucesso', 'rvss'),
            'confirm_delete' => __('Tem certeza que deseja excluir? Esta ação não pode ser desfeita.', 'rvss'),
            'loading' => __('Carregando...', 'rvss'),
            'save' => __('Salvar', 'rvss'),
            'cancel' => __('Cancelar', 'rvss'),
            'close' => __('Fechar', 'rvss'),
            'submit' => __('Enviar', 'rvss'),
            'required_field' => __('Este campo é obrigatório', 'rvss'),
            'invalid_email' => __('Por favor, informe um email válido', 'rvss'),
            'form_error' => __('Existem erros no formulário. Por favor, verifique os campos destacados.', 'rvss'),
            'update_chart' => __('Atualizar Gráfico', 'rvss'),
            'see_chart' => __('Ver Minha Roda da Vida', 'rvss'),
            'light_theme' => __('Tema Claro', 'rvss'),
            'dark_theme' => __('Tema Escuro', 'rvss'),
            'auto_theme' => __('Tema Automático', 'rvss'),
            'share_facebook' => __('Compartilhar no Facebook', 'rvss'),
            'share_twitter' => __('Compartilhar no Twitter', 'rvss'),
            'share_whatsapp' => __('Compartilhar no WhatsApp', 'rvss'),
            'share_instagram' => __('Compartilhar no Instagram', 'rvss'),
            'share_linkedin' => __('Compartilhar no LinkedIn', 'rvss'),
            'export_success' => __('Dados exportados com sucesso!', 'rvss'),
            'import_success' => __('Dados importados com sucesso!', 'rvss')
        );
    }
}

// Inicializa a classe
RVSS_I18n::init();