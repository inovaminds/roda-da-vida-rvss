<?php
/**
 * Database handler for Roda da Vida by Simone Silvestrin (RVSS)
 *
 * Handles database operations for the plugin
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
 * Class RVSS_Database
 */
class RVSS_Database {
    
    /**
     * Tabela de usuários
     *
     * @var string
     */
    private static $table_usuarios;
    
    /**
     * Tabela de logs
     *
     * @var string
     */
    private static $table_logs;
    
    /**
     * Inicializa o manipulador de banco de dados
     */
    public static function init() {
        global $wpdb;
        
        self::$table_usuarios = $wpdb->prefix . 'rvss_usuarios';
        self::$table_logs = $wpdb->prefix . 'rvss_logs';
        
        // Verifica se é necessário atualizar o banco de dados
        self::check_db_version();
    }
    
    /**
     * Verifica se a versão do banco de dados precisa ser atualizada
     */
    public static function check_db_version() {
        $db_version = get_option('rvss_db_version');
        
        if (!$db_version || version_compare($db_version, RVSS_DB_VERSION, '<')) {
            self::create_tables();
            update_option('rvss_db_version', RVSS_DB_VERSION);
        }
    }
    
    /**
     * Cria as tabelas do plugin no banco de dados
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabela de usuários
        $table_usuarios = $wpdb->prefix . 'rvss_usuarios';
        
        $sql_usuarios = "CREATE TABLE $table_usuarios (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nome varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            saude tinyint(2) NOT NULL,
            familia tinyint(2) NOT NULL,
            amor tinyint(2) NOT NULL,
            financas tinyint(2) NOT NULL,
            carreira tinyint(2) NOT NULL,
            pessoal tinyint(2) NOT NULL,
            lazer tinyint(2) NOT NULL,
            espiritualidade tinyint(2) NOT NULL,
            resposta1 text,
            resposta2 text,
            resposta3_area1 varchar(50),
            resposta3_acao1 text,
            resposta3_area2 varchar(50),
            resposta3_acao2 text,
            resposta3_area3 varchar(50),
            resposta3_acao3 text,
            tema varchar(20) DEFAULT 'light',
            ip_address varchar(45),
            user_agent text,
            compartilhado tinyint(1) DEFAULT 0,
            data_criacao datetime DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_email (email),
            KEY idx_data_criacao (data_criacao)
        ) $charset_collate;";
        
        // Tabela de logs
        $table_logs = $wpdb->prefix . 'rvss_logs';
        
        $sql_logs = "CREATE TABLE $table_logs (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            usuario_id mediumint(9),
            acao varchar(50) NOT NULL,
            descricao text,
            dados longtext,
            ip_address varchar(45),
            user_agent text,
            data_criacao datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_usuario (usuario_id),
            KEY idx_acao (acao),
            KEY idx_data (data_criacao)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Executa as queries com dbDelta para criar ou atualizar as tabelas
        dbDelta($sql_usuarios);
        dbDelta($sql_logs);
        
        // Registra log de criação/atualização
        self::log_system_action('db_update', 'Banco de dados criado ou atualizado para a versão ' . RVSS_DB_VERSION);
    }
    
    /**
     * Salva os dados do usuário no banco
     *
     * @param array $data Dados do usuário
     * @return int|false ID do usuário ou false em caso de erro
     */
    public static function save_user_data($data) {
        global $wpdb;
        
        // Adiciona informações do usuário
        $data['ip_address'] = self::get_client_ip();
        $data['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        
        // Tipos dos campos para o insert
        $format = array(
            '%s', '%s', '%d', '%d', '%d', '%d', 
            '%d', '%d', '%d', '%d', '%s', '%s',
            '%s', '%s', '%s', '%s', '%s', '%s',
            '%s', '%s', '%s', '%d'
        );
        
        // Insere no banco
        $result = $wpdb->insert(
            self::$table_usuarios,
            $data,
            $format
        );
        
        if ($result) {
            $user_id = $wpdb->insert_id;
            
            // Registra log
            self::log_action($user_id, 'novo_usuario', 'Novo usuário criado', $data);
            
            return $user_id;
        }
        
        return false;
    }
    
    /**
     * Atualiza os dados do usuário
     *
     * @param int $user_id ID do usuário
     * @param array $data Dados a serem atualizados
     * @return bool Sucesso ou falha
     */
    public static function update_user_data($user_id, $data) {
        global $wpdb;
        
        // Tipos dos campos para o update
        $format = array();
        foreach ($data as $key => $value) {
            if (in_array($key, array('nome', 'email', 'resposta1', 'resposta2', 'resposta3_area1', 
                                     'resposta3_acao1', 'resposta3_area2', 'resposta3_acao2', 
                                     'resposta3_area3', 'resposta3_acao3', 'tema', 'ip_address', 'user_agent'))) {
                $format[] = '%s';
            } else {
                $format[] = '%d';
            }
        }
        
        // Atualiza no banco
        $result = $wpdb->update(
            self::$table_usuarios,
            $data,
            array('id' => $user_id),
            $format,
            array('%d')
        );
        
        if ($result !== false) {
            // Registra log
            self::log_action($user_id, 'atualiza_usuario', 'Dados do usuário atualizados', $data);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Obtém todos os usuários
     *
     * @param array $args Argumentos para filtragem
     * @return array Lista de usuários
     */
    public static function get_all_users($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'orderby' => 'data_criacao',
            'order' => 'DESC',
            'limit' => 100,
            'offset' => 0,
            'search' => '',
            'date_from' => '',
            'date_to' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Sanitização
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']) ?: 'data_criacao DESC';
        $limit = absint($args['limit']);
        $offset = absint($args['offset']);
        
        // Base da query
        $query = "SELECT * FROM " . self::$table_usuarios;
        
        // Condições WHERE
        $where = array();
        $where_args = array();
        
        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = "(nome LIKE %s OR email LIKE %s)";
            $where_args[] = $search;
            $where_args[] = $search;
        }
        
        if (!empty($args['date_from'])) {
            $where[] = "data_criacao >= %s";
            $where_args[] = $args['date_from'] . ' 00:00:00';
        }
        
        if (!empty($args['date_to'])) {
            $where[] = "data_criacao <= %s";
            $where_args[] = $args['date_to'] . ' 23:59:59';
        }
        
        // Monta a query WHERE se houver condições
        if (!empty($where)) {
            $query .= " WHERE " . implode(' AND ', $where);
        }
        
        // Adiciona ORDER BY
        $query .= " ORDER BY $orderby";
        
        // Adiciona LIMIT
        if ($limit > 0) {
            $query .= " LIMIT %d OFFSET %d";
            $where_args[] = $limit;
            $where_args[] = $offset;
        }
        
        // Prepara a query se houver argumentos
        if (!empty($where_args)) {
            $query = $wpdb->prepare($query, $where_args);
        }
        
        // Executa a query
        return $wpdb->get_results($query);
    }
    
    /**
     * Obtém um usuário pelo ID
     *
     * @param int $user_id ID do usuário
     * @return object|null Dados do usuário ou null
     */
    public static function get_user($user_id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT * FROM " . self::$table_usuarios . " WHERE id = %d",
            $user_id
        );
        
        return $wpdb->get_row($query);
    }
    
    /**
     * Exclui um usuário
     *
     * @param int $user_id ID do usuário
     * @return bool Sucesso ou falha
     */
    public static function delete_user($user_id) {
        global $wpdb;
        
        // Obtém dados para registro de log
        $user_data = self::get_user($user_id);
        
        // Exclui o usuário
        $result = $wpdb->delete(
            self::$table_usuarios,
            array('id' => $user_id),
            array('%d')
        );
        
        if ($result) {
            // Registra log
            self::log_system_action('delete_usuario', 'Usuário excluído', (array) $user_data);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Registra uma ação no log
     *
     * @param int $user_id ID do usuário
     * @param string $action Ação realizada
     * @param string $description Descrição da ação
     * @param array $data Dados relacionados
     * @return int|false ID do log ou false
     */
    public static function log_action($user_id, $action, $description = '', $data = array()) {
        global $wpdb;
        
        $log_data = array(
            'usuario_id' => $user_id,
            'acao' => $action,
            'descricao' => $description,
            'dados' => !empty($data) ? wp_json_encode($data) : '',
            'ip_address' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : ''
        );
        
        $result = $wpdb->insert(
            self::$table_logs,
            $log_data,
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Registra uma ação de sistema no log (sem usuário específico)
     *
     * @param string $action Ação realizada
     * @param string $description Descrição da ação
     * @param array $data Dados relacionados
     * @return int|false ID do log ou false
     */
    public static function log_system_action($action, $description = '', $data = array()) {
        return self::log_action(0, $action, $description, $data);
    }
    
    /**
     * Obtém estatísticas do plugin
     *
     * @param bool $force_refresh Forçar atualização do cache
     * @return array Estatísticas
     */
    public static function get_statistics($force_refresh = false) {
        $transient_key = 'rvss_admin_statistics';
        $cached_stats = get_transient($transient_key);
        
        if ($cached_stats !== false && !$force_refresh) {
            return $cached_stats;
        }
        
        global $wpdb;
        
        $stats = array(
            'total_usuarios' => $wpdb->get_var("SELECT COUNT(*) FROM " . self::$table_usuarios),
            'usuarios_ultimo_mes' => $wpdb->get_var("SELECT COUNT(*) FROM " . self::$table_usuarios . " WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
            'media_saude' => $wpdb->get_var("SELECT AVG(saude) FROM " . self::$table_usuarios),
            'media_familia' => $wpdb->get_var("SELECT AVG(familia) FROM " . self::$table_usuarios),
            'media_amor' => $wpdb->get_var("SELECT AVG(amor) FROM " . self::$table_usuarios),
            'media_financas' => $wpdb->get_var("SELECT AVG(financas) FROM " . self::$table_usuarios),
            'media_carreira' => $wpdb->get_var("SELECT AVG(carreira) FROM " . self::$table_usuarios),
            'media_pessoal' => $wpdb->get_var("SELECT AVG(pessoal) FROM " . self::$table_usuarios),
            'media_lazer' => $wpdb->get_var("SELECT AVG(lazer) FROM " . self::$table_usuarios),
            'media_espiritualidade' => $wpdb->get_var("SELECT AVG(espiritualidade) FROM " . self::$table_usuarios),
            'usuarios_compartilhados' => $wpdb->get_var("SELECT COUNT(*) FROM " . self::$table_usuarios . " WHERE compartilhado = 1"),
            'ultimo_usuario' => $wpdb->get_row("SELECT id, nome, email, data_criacao FROM " . self::$table_usuarios . " ORDER BY data_criacao DESC LIMIT 1")
        );
        
        // Arredonda as médias
        foreach ($stats as $key => $value) {
            if (strpos($key, 'media_') === 0 && is_numeric($value)) {
                $stats[$key] = round($value, 1);
            }
        }
        
        // Armazena em cache por 1 hora
        set_transient($transient_key, $stats, HOUR_IN_SECONDS);
        
        return $stats;
    }
    
    /**
     * Obtém o IP do cliente
     *
     * @return string Endereço IP
     */
    private static function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        }
        
        return sanitize_text_field($ip);
    }
    
    /**
     * Exporta dados de usuários para CSV
     *
     * @param array $user_ids Lista de IDs de usuários (vazio para todos)
     * @return string Conteúdo do CSV
     */
    public static function export_users_to_csv($user_ids = array()) {
        global $wpdb;
        
        // Colunas para o CSV
        $columns = array(
            'id' => 'ID',
            'nome' => 'Nome',
            'email' => 'Email',
            'saude' => 'Saúde',
            'familia' => 'Família',
            'amor' => 'Vida Amorosa',
            'financas' => 'Finanças',
            'carreira' => 'Carreira',
            'pessoal' => 'Desenvolvimento Pessoal',
            'lazer' => 'Lazer',
            'espiritualidade' => 'Espiritualidade',
            'resposta1' => 'Área de Foco',
            'resposta2' => 'Aprendizado',
            'resposta3_area1' => 'Área de Melhoria 1',
            'resposta3_acao1' => 'Ação de Melhoria 1',
            'resposta3_area2' => 'Área de Melhoria 2',
            'resposta3_acao2' => 'Ação de Melhoria 2',
            'resposta3_area3' => 'Área de Melhoria 3',
            'resposta3_acao3' => 'Ação de Melhoria 3',
            'data_criacao' => 'Data de Criação'
        );
        
        // Constrói a query
        $query = "SELECT * FROM " . self::$table_usuarios;
        
        // Filtra por IDs específicos se fornecidos
        if (!empty($user_ids) && is_array($user_ids)) {
            $user_ids = array_map('intval', $user_ids);
            $query .= " WHERE id IN (" . implode(',', $user_ids) . ")";
        }
        
        $query .= " ORDER BY data_criacao DESC";
        
        // Obtém os dados
        $users = $wpdb->get_results($query, ARRAY_A);
        
        // Inicia o output do CSV
        $csv = fopen('php://temp', 'r+');
        
        // Adiciona cabeçalho
        fputcsv($csv, array_values($columns));
        
        // Adiciona linhas de dados
        foreach ($users as $user) {
            $row = array();
            
            foreach (array_keys($columns) as $column) {
                if (isset($user[$column])) {
                    $row[] = $user[$column];
                } else {
                    $row[] = '';
                }
            }
            
            fputcsv($csv, $row);
        }
        
        rewind($csv);
        $csv_content = stream_get_contents($csv);
        fclose($csv);
        
        // Registra log
        self::log_system_action('export_csv', 'Dados exportados para CSV', array('count' => count($users)));
        
        return $csv_content;
    }
}

// Inicializa a classe ao incluir o arquivo
RVSS_Database::init();