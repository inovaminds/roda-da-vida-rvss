<?php
/**
 * Template para o shortcode Roda da Vida RVSS
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
?>

<div id="rvss-container" class="rvss-wrapper">
    <!-- Toggle de tema claro/escuro -->
    <button type="button" id="rvss-theme-switch" class="rvss-theme-switch" title="<?php esc_attr_e('Alterar tema', 'rvss'); ?>">
        <i class="dashicons dashicons-moon"></i>
    </button>
    
    <div class="rvss-header">
        <h2><?php esc_html_e('Roda da Vida - Avalie suas 8 Áreas', 'rvss'); ?></h2>
        <p><?php esc_html_e('Avalie cada área da sua vida de 1 a 10, onde 1 é muito insatisfeito e 10 é completamente satisfeito.', 'rvss'); ?></p>
    </div>
    
    <form id="rvss-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
        <?php wp_nonce_field('rvss_submit', 'rvss_nonce'); ?>
        <input type="hidden" name="action" value="rvss_submit_form">
        <input type="hidden" name="rvss_timer" id="rvss-timer" value="0">
        <input type="hidden" name="rvss_hp" value="">
        <input type="hidden" name="tema" id="rvss-tema" value="light">
        
        <div class="rvss-areas-grid">
            <?php
            $areas = array(
                'saude' => array(
                    'nome' => __('Saúde', 'rvss'),
                    'grupo' => 'Qualidade de Vida',
                    'cor' => '#10b981'
                ),
                'familia' => array(
                    'nome' => __('Família', 'rvss'),
                    'grupo' => 'Relacionamentos',
                    'cor' => '#ec4899'
                ),
                'amor' => array(
                    'nome' => __('Vida Amorosa', 'rvss'),
                    'grupo' => 'Relacionamentos',
                    'cor' => '#ec4899'
                ),
                'financas' => array(
                    'nome' => __('Dinheiro e Finanças', 'rvss'),
                    'grupo' => 'Profissional',
                    'cor' => '#3b82f6'
                ),
                'carreira' => array(
                    'nome' => __('Trabalho e Carreira', 'rvss'),
                    'grupo' => 'Profissional',
                    'cor' => '#3b82f6'
                ),
                'pessoal' => array(
                    'nome' => __('Desenvolvimento Pessoal', 'rvss'),
                    'grupo' => 'Pessoal',
                    'cor' => '#f97316'
                ),
                'lazer' => array(
                    'nome' => __('Lazer e Diversão', 'rvss'),
                    'grupo' => 'Qualidade de Vida',
                    'cor' => '#10b981'
                ),
                'espiritualidade' => array(
                    'nome' => __('Espiritualidade', 'rvss'),
                    'grupo' => 'Qualidade de Vida',
                    'cor' => '#10b981'
                )
            );
            
            foreach ($areas as $slug => $area) : 
                $color_style = "style='color: " . esc_attr($area['cor']) . ";'";
            ?>
                <div class="rvss-area-item">
                    <label for="<?php echo esc_attr($slug); ?>" class="rvss-area-label" <?php echo $color_style; ?>>
                        <?php echo esc_html($area['nome']); ?>
                        <span class="rvss-group-label">(<?php echo esc_html($area['grupo']); ?>)</span>
                    </label>
                    <div class="rvss-slider-container">
                        <input type="range" 
                               id="<?php echo esc_attr($slug); ?>" 
                               name="notas[<?php echo esc_attr($slug); ?>]" 
                               min="1" 
                               max="10" 
                               value="5" 
                               class="rvss-slider"
                               data-area="<?php echo esc_attr($slug); ?>"
                               data-cor="<?php echo esc_attr($area['cor']); ?>">
                        <span class="rvss-slider-value" <?php echo $color_style; ?>>5</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="rvss-chart-section">
            <button type="button" id="rvss-show-chart" class="rvss-btn-primary">
                <?php esc_html_e('Ver Minha Roda da Vida', 'rvss'); ?>
            </button>
            
            <div id="rvss-chart" class="rvss-chart-container hidden"></div>
            
            <div class="rvss-auto-update-container hidden" id="rvss-auto-update-container">
                <input type="checkbox" id="rvss-auto-update" name="auto_update" value="1">
                <label for="rvss-auto-update"><?php esc_html_e('Atualizar automaticamente ao modificar valores', 'rvss'); ?></label>
            </div>
            
            <button type="button" id="rvss-update-chart" class="rvss-btn-secondary hidden">
                <?php esc_html_e('Atualizar Gráfico', 'rvss'); ?>
            </button>
            
            <!-- Compartilhamento social -->
            <?php 
            // Obtém as configurações de compartilhamento
            $social_sharing = get_option('rvss_social_sharing', array());
            $sharing_enabled = isset($social_sharing['enabled']) ? $social_sharing['enabled'] : true;
            $networks = isset($social_sharing['networks']) ? $social_sharing['networks'] : array(
                'facebook' => true,
                'twitter' => true,
                'whatsapp' => true,
                'instagram' => false,
                'linkedin' => false
            );
            
            if ($sharing_enabled) :
            ?>
            <div class="rvss-social-sharing hidden" id="rvss-social-sharing">
                <p><?php esc_html_e('Compartilhe sua Roda da Vida:', 'rvss'); ?></p>
                <div class="rvss-share-buttons">
                    <?php if (!empty($networks['facebook'])) : ?>
                    <button type="button" class="rvss-share-btn facebook" data-network="facebook" title="<?php esc_attr_e('Compartilhar no Facebook', 'rvss'); ?>">
                        <i class="dashicons dashicons-facebook"></i>
                    </button>
                    <?php endif; ?>
                    
                    <?php if (!empty($networks['twitter'])) : ?>
                    <button type="button" class="rvss-share-btn twitter" data-network="twitter" title="<?php esc_attr_e('Compartilhar no Twitter', 'rvss'); ?>">
                        <i class="dashicons dashicons-twitter"></i>
                    </button>
                    <?php endif; ?>
                    
                    <?php if (!empty($networks['whatsapp'])) : ?>
                    <button type="button" class="rvss-share-btn whatsapp" data-network="whatsapp" title="<?php esc_attr_e('Compartilhar no WhatsApp', 'rvss'); ?>">
                        <i class="dashicons dashicons-whatsapp"></i>
                    </button>
                    <?php endif; ?>
                    
                    <?php if (!empty($networks['instagram'])) : ?>
                    <button type="button" class="rvss-share-btn instagram" data-network="instagram" title="<?php esc_attr_e('Compartilhar no Instagram', 'rvss'); ?>">
                        <i class="dashicons dashicons-instagram"></i>
                    </button>
                    <?php endif; ?>
                    
                    <?php if (!empty($networks['linkedin'])) : ?>
                    <button type="button" class="rvss-share-btn linkedin" data-network="linkedin" title="<?php esc_attr_e('Compartilhar no LinkedIn', 'rvss'); ?>">
                        <i class="dashicons dashicons-linkedin"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div id="rvss-questions" class="rvss-questions-section hidden">
            <h3><?php esc_html_e('Reflexões sobre sua Roda da Vida', 'rvss'); ?></h3>
            
            <div class="rvss-question">
                <label for="resposta1" class="rvss-question-label">
                    <?php esc_html_e('1 - Escolha uma área que, se você colocar o foco, aumentará toda a sua roda da vida:', 'rvss'); ?>
                </label>
                <input type="text" 
                       id="resposta1" 
                       name="resposta1" 
                       maxlength="100"
                       class="rvss-input"
                       placeholder="<?php esc_attr_e('Ex: Saúde, Finanças...', 'rvss'); ?>">
            </div>
            
            <div class="rvss-question">
                <label for="resposta2" class="rvss-question-label">
                    <?php esc_html_e('2 - Escreva o aprendizado que você teve ao olhar para como está a roda da sua vida:', 'rvss'); ?>
                </label>
                <textarea id="resposta2" 
                          name="resposta2" 
                          maxlength="255"
                          class="rvss-textarea"
                          placeholder="<?php esc_attr_e('Ex: Entendi que preciso focar no financeiro para viajar e realizar meus objetivos.', 'rvss'); ?>"></textarea>
            </div>
            
            <div class="rvss-question">
                <label class="rvss-question-label">
                    <?php esc_html_e('3 - Escolha 3 áreas que você deseja melhorar primeiro e escreva as ações:', 'rvss'); ?>
                </label>
                
                <?php for ($i = 1; $i <= 3; $i++): ?>
                    <div class="rvss-action-row">
                        <select name="resposta3_area<?php echo $i; ?>" class="rvss-select">
                            <option value=""><?php esc_html_e('Área da Vida', 'rvss'); ?></option>
                            <?php foreach ($areas as $slug => $area) : ?>
                                <option value="<?php echo esc_attr($area['nome']); ?>"><?php echo esc_html($area['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" 
                               name="resposta3_acao<?php echo $i; ?>" 
                               maxlength="255"
                               class="rvss-input"
                               placeholder="<?php esc_attr_e('Ação que você fará', 'rvss'); ?>">
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <div id="rvss-contact" class="rvss-contact-section hidden">
            <h3><?php esc_html_e('Envie seus resultados por email', 'rvss'); ?></h3>
            
            <div class="rvss-contact-fields">
                <div class="rvss-field">
                    <label for="nome" class="rvss-label"><?php esc_html_e('Nome:', 'rvss'); ?></label>
                    <input type="text" 
                           id="nome" 
                           name="nome" 
                           required 
                           class="rvss-input"
                           placeholder="<?php esc_attr_e('Seu nome completo', 'rvss'); ?>">
                </div>
                
                <div class="rvss-field">
                    <label for="email" class="rvss-label"><?php esc_html_e('Email:', 'rvss'); ?></label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           required 
                           class="rvss-input"
                           placeholder="<?php esc_attr_e('seu@email.com', 'rvss'); ?>">
                </div>
            </div>
            
            <div class="rvss-field rvss-mb-4">
                <label class="rvss-checkbox-container">
                    <input type="checkbox" name="compartilhar" id="compartilhar" value="1">
                    <span class="rvss-checkbox-label"><?php esc_html_e('Autorizo o compartilhamento anônimo dos meus dados para análises estatísticas', 'rvss'); ?></span>
                </label>
            </div>
            
            <button type="submit" class="rvss-btn-submit">
                <span class="rvss-btn-text"><?php esc_html_e('Enviar Resultados', 'rvss'); ?></span>
                <span class="rvss-loading hidden"></span>
            </button>
            
            <p class="rvss-privacy-notice rvss-mt-4">
                <?php esc_html_e('Seus dados serão usados apenas para enviar os resultados por email e não serão compartilhados com terceiros.', 'rvss'); ?>
            </p>
        </div>
    </form>
</div>

<script type="text/javascript">
    // Inicia o timer quando a página carrega
    document.addEventListener('DOMContentLoaded', function() {
        let startTime = new Date().getTime();
        let timerField = document.getElementById('rvss-timer');
        
        setInterval(function() {
            if (timerField) {
                let elapsedTime = Math.floor((new Date().getTime() - startTime) / 1000);
                timerField.value = elapsedTime;
            }
        }, 1000);
    });
</script>