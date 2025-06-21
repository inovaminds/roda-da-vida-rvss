/**
 * Form handler for Roda da Vida by Simone Silvestrin (RVSS)
 *
 * Handles form submissions and AJAX requests
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
 * Class RVSS_Form_Handler
 */
class RVSS_Form_Handler {
    
    /**
     * Inicializa o manipulador de formulários
     */
    public static function init() {
        // AJAX para usuários logados
        add_action('wp_ajax_rvss_submit_form', array(__CLASS__, 'handle_form_submission'));
        
        // AJAX para usuários não logados
        add_action('wp_ajax_nopriv_rvss_submit_form', array(__CLASS__, 'handle_form_submission'));
        
        // AJAX para ações no admin
        add_action('wp_ajax_rvss_admin_action', array(__CLASS__, 'handle_admin_action'));
    }
    
    /**
     * Processa o envio do formulário
     */
    public static function handle_form_submission() {
        // Verifica nonce para segurança
        if (!isset($_POST['rvss_nonce']) || !wp_verify_nonce($_POST['rvss_nonce'], 'rvss_submit')) {
            self::send_json_error(__('Erro de segurança. Por favor, recarregue a página e tente novamente.', 'rvss'));
        }
        
        // Verifica honeypot (campo oculto para prevenir spam)
        if (isset($_POST['rvss_hp']) && !empty($_POST['rvss_hp'])) {
            self::send_json_error(__('Erro de validação. Por favor, tente novamente.', 'rvss'));
        }
        
        // Verifica se o formulário foi enviado muito rapidamente (proteção contra bots)
        $form_timer = isset($_POST['rvss_timer']) ? intval($_POST['rvss_timer']) : 0;
        if ($form_timer < 3) { // Menos de 3 segundos para preencher o formulário
            self::send_json_error(__('Formulário enviado muito rapidamente. Por favor, tente novamente.', 'rvss'));
        }
        
        // Validação de campos obrigatórios
        $required_fields = array('nome', 'email');
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                self::send_json_error(sprintf(
                    /* translators: %s é o nome do campo */
                    __('O campo %s é obrigatório.', 'rvss'),
                    self::get_field_label($field)
                ));
            }
        }
        
        // Validação de email
        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) {
            self::send_json_error(__('Por favor, forneça um endereço de email válido.', 'rvss'));
        }
        
        // Preparação dos dados para salvar
        $dados = array(
            'nome' => sanitize_text_field($_POST['nome']),
            'email' => $email,
            'tema' => isset($_POST['tema']) ? sanitize_text_field($_POST['tema']) : 'light'
        );
        
        // Processamento das notas de cada área
        if (isset($_POST['notas']) && is_array($_POST['notas'])) {
            $areas = array('saude', 'familia', 'amor', 'financas', 'carreira', 'pessoal', 'lazer', 'espiritualidade');
            
            foreach ($areas as $area) {
                $valor = isset($_POST['notas'][$area]) ? intval($_POST['notas'][$area]) : 5;
                // Garante que o valor está entre 1 e 10
                $dados[$area] = max(1, min(10, $valor));
            }
        } else {
            // Valores padrão se não houver notas
            $dados['saude'] = 5;
            $dados['familia'] = 5;
            $dados['amor'] = 5;
            $dados['financas'] = 5;
            $dados['carreira'] = 5;
            $dados['pessoal'] = 5;
            $dados['lazer'] = 5;
            $dados['espiritualidade'] = 5;
        }
        
        // Processamento das respostas de reflexão
        $dados['resposta1'] = isset($_POST['resposta1']) ? sanitize_textarea_field($_POST['resposta1']) : '';
        $dados['resposta2'] = isset($_POST['resposta2']) ? sanitize_textarea_field($_POST['resposta2']) : '';
        
        // Processamento das áreas e ações de melhoria
        for ($i = 1; $i <= 3; $i++) {
            $area_key = 'resposta3_area' . $i;
            $acao_key = 'resposta3_acao' . $i;
            
            $dados[$area_key] = isset($_POST[$area_key]) ? sanitize_text_field($_POST[$area_key]) : '';
            $dados[$acao_key] = isset($_POST[$acao_key]) ? sanitize_textarea_field($_POST[$acao_key]) : '';
        }
        
        // Flag de compartilhamento
        $dados['compartilhado'] = isset($_POST['compartilhar']) && $_POST['compartilhar'] === '1' ? 1 : 0;
        
        // Salva os dados no banco
        $resultado = RVSS_Database::save_user_data($dados);
        
        if ($resultado) {
            // Envia email de feedback
            RVSS_Email::send_feedback($dados);
            
            // Retorna sucesso
            self::send_json_success(array(
                'message' => __('Seus dados foram enviados com sucesso!', 'rvss'),
                'user_id' => $resultado
            ));
        } else {
            // Retorna erro
            self::send_json_error(__('Erro ao salvar dados. Por favor, tente novamente.', 'rvss'));
        }
    }
    
    /**
     * Processa ações do admin via AJAX
     */
    public static function handle_admin_action() {
        // Verifica se o usuário tem permissões
        if (!current_user_can('manage_options')) {
            self::send_json_error(__('Você não tem permissão para realizar esta ação.', 'rvss'));
        }
        
        // Verifica nonce para segurança
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'rvss_admin_nonce')) {
            self::send_json_error(__('Erro de segurança. Por favor, recarregue a página e tente novamente.', 'rvss'));
        }
        
        $action = isset($_REQUEST['admin_action']) ? sanitize_text_field($_REQUEST['admin_action']) : '';
        
        switch ($action) {
            case 'delete_user':
                self::handle_delete_user();
                break;
                
            case 'get_user_details':
                self::handle_get_user_details();
                break;
                
            case 'save_settings':
                self::handle_save_settings();
                break;
                
            case 'refresh_stats':
                self::handle_refresh_stats();
                break;
                
            default:
                self::send_json_error(__('Ação desconhecida ou não suportada.', 'rvss'));
                break;
        }
    }
    
    /**
     * Processa a exclusão de um usuário
     */
    private static function handle_delete_user() {
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if ($user_id <= 0) {
            self::send_json_error(__('ID de usuário inválido.', 'rvss'));
        }
        
        $resultado = RVSS_Database::delete_user($user_id);
        
        if ($resultado) {
            self::send_json_success(array(
                'message' => __('Usuário excluído com sucesso.', 'rvss')
            ));
        } else {
            self::send_json_error(__('Erro ao excluir usuário. Por favor, tente novamente.', 'rvss'));
        }
    }
    
    /**
     * Obtém detalhes de um usuário
     */
    private static function handle_get_user_details() {
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        
        if ($user_id <= 0) {
            self::send_json_error(__('ID de usuário inválido.', 'rvss'));
        }
        
        $user = RVSS_Database::get_user($user_id);
        
        if ($user) {
            self::send_json_success(array(
                'user' => $user
            ));
        } else {
            self::send_json_error(__('Usuário não encontrado.', 'rvss'));
        }
    }
    
    /**
     * Salva as configurações do plugin
     */
    private static function handle_save_settings() {
        // Configurações de tema
        $theme_settings = array(
            'enable_dark_mode' => isset($_POST['enable_dark_mode']) && $_POST['enable_dark_mode'] === '1',
            'default_theme' => isset($_POST['default_theme']) ? sanitize_text_field($_POST['default_theme']) : 'auto',
            'primary_color' => isset($_POST['primary_color']) ? sanitize_hex_color($_POST['primary_color']) : '#3b82f6'
        );
        
        update_option('rvss_theme_settings', $theme_settings);
        
        // Configurações de compartilhamento social
        $social_networks = array();
        $available_networks = array('facebook', 'twitter', 'whatsapp', 'instagram', 'linkedin');
        
        foreach ($available_networks as $network) {
            $social_networks[$network] = isset($_POST['social_' . $network]) && $_POST['social_' . $network] === '1';
        }
        
        $social_sharing = array(
            'enabled' => isset($_POST['enable_social_sharing']) && $_POST['enable_social_sharing'] === '1',
            'networks' => $social_networks
        );
        
        update_option('rvss_social_sharing', $social_sharing);
        
        // Email de feedback
        if (isset($_POST['email_feedback'])) {
            $email_feedback = sanitize_email($_POST['email_feedback']);
            if (is_email($email_feedback)) {
                update_option('rvss_email_feedback', $email_feedback);
            }
        }
        
        self::send_json_success(array(
            'message' => __('Configurações salvas com sucesso!', 'rvss')
        ));
    }
    
    /**
     * Atualiza as estatísticas do dashboard
     */
    private static function handle_refresh_stats() {
        $stats = RVSS_Database::get_statistics(true);
        
        self::send_json_success(array(
            'stats' => $stats,
            'message' => __('Estatísticas atualizadas com sucesso!', 'rvss')
        ));
    }
    
    /**
     * Envia resposta de erro em JSON
     *
     * @param string $message Mensagem de erro
     */
    private static function send_json_error($message) {
        wp_send_json_error(array(
            'message' => $message
        ));
    }
    
    /**
     * Envia resposta de sucesso em JSON
     *
     * @param array $data Dados adicionais
     */
    private static function send_json_success($data = array()) {
        wp_send_json_success($data);
    }
    
    /**
     * Obtém o label amigável de um campo
     *
     * @param string $field Nome do campo
     * @return string Label do campo
     */
    private static function get_field_label($field) {
        $labels = array(
            'nome' => __('Nome', 'rvss'),
            'email' => __('Email', 'rvss'),
            'resposta1' => __('Área de Foco', 'rvss'),
            'resposta2' => __('Aprendizado', 'rvss')
        );
        
        return isset($labels[$field]) ? $labels[$field] : $field;
    }
}

// Não inicializa a classe aqui, será feito pelo arquivo principal