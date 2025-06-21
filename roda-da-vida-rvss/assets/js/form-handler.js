/**
 * Roda da Vida RVSS - Manipulador de Formulário
 * Plugin: Roda da Vida by Simone Silvestrin (RVSS)
 * Versão: 0.2.0
 * Autor: InovaMinds
 */

(function($) {
    'use strict';

    /**
     * Classe principal do manipulador de formulário
     */
    class RVSSFormHandler {
        constructor() {
            this.form = null;
            this.isSubmitting = false;
            this.chartVisible = false;
            
            // Seletores dos elementos
            this.selectors = {
                form: '#rvss-form',
                sliders: '.rvss-slider',
                sliderValues: '.rvss-slider-value',
                showChartBtn: '#rvss-show-chart',
                updateChartBtn: '#rvss-update-chart',
                chartContainer: '#rvss-chart',
                questionsSection: '#rvss-questions',
                contactSection: '#rvss-contact',
                submitBtn: '.rvss-btn-submit',
                requiredFields: 'input[required], textarea[required]'
            };

            this.init();
        }

        /**
         * Inicialização
         */
        init() {
            $(document).ready(() => {
                this.bindEvents();
                this.setupSliders();
                this.setupValidation();
                console.log('RVSS: Form Handler inicializado');
            });
        }

        /**
         * Vincula todos os eventos
         */
        bindEvents() {
            this.form = $(this.selectors.form);
            
            if (this.form.length === 0) {
                console.warn('RVSS: Formulário não encontrado');
                return;
            }

            // Eventos dos sliders
            $(document).on('input change', this.selectors.sliders, (e) => {
                this.handleSliderChange(e);
            });

            // Botão mostrar gráfico
            $(document).on('click', this.selectors.showChartBtn, (e) => {
                e.preventDefault();
                this.showChart();
            });

            // Botão atualizar gráfico
            $(document).on('click', this.selectors.updateChartBtn, (e) => {
                e.preventDefault();
                this.updateChart();
            });

            // Submissão do formulário
            this.form.on('submit', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });

            // Validação em tempo real
            $(document).on('blur', this.selectors.requiredFields, (e) => {
                this.validateField($(e.target));
            });

            // Prevenir envio duplo
            $(document).on('click', this.selectors.submitBtn, (e) => {
                if (this.isSubmitting) {
                    e.preventDefault();
                    return false;
                }
            });
        }

        /**
         * Configura comportamento dos sliders
         */
        setupSliders() {
            $(this.selectors.sliders).each((index, slider) => {
                const $slider = $(slider);
                const $valueDisplay = $slider.siblings(this.selectors.sliderValues);
                
                // Valor inicial
                $valueDisplay.text($slider.val());
                
                // Cor do área
                const area = $slider.data('area');
                if (area) {
                    const areaConfig = this.getAreaConfig(area);
                    if (areaConfig) {
                        $slider.css('accent-color', areaConfig.cor);
                    }
                }
            });
        }

        /**
         * Configura validação de campos
         */
        setupValidation() {
            // Adicionar indicadores visuais para campos obrigatórios
            $(this.selectors.requiredFields).each(function() {
                const $field = $(this);
                const $label = $field.closest('.rvss-field, .rvss-question').find('label');
                
                if ($label.length && !$label.find('.rvss-required').length) {
                    $label.append(' <span class="rvss-required">*</span>');
                }
            });
        }

        /**
         * Manipula mudança nos sliders
         */
        handleSliderChange(event) {
            const $slider = $(event.target);
            const value = $slider.val();
            const $valueDisplay = $slider.siblings(this.selectors.sliderValues);
            
            // Atualizar valor exibido
            $valueDisplay.text(value);
            
            // Atualizar gráfico se estiver visível
            if (this.chartVisible) {
                this.debounce(() => {
                    this.updateChart();
                }, 300);
            }

            // Adicionar efeito visual
            $slider.parent().addClass('rvss-slider-active');
            setTimeout(() => {
                $slider.parent().removeClass('rvss-slider-active');
            }, 200);
        }

        /**
         * Mostra o gráfico
         */
        showChart() {
            const $showBtn = $(this.selectors.showChartBtn);
            const $updateBtn = $(this.selectors.updateChartBtn);
            const $chartContainer = $(this.selectors.chartContainer);
            const $questionsSection = $(this.selectors.questionsSection);
            const $contactSection = $(this.selectors.contactSection);

            // Validar se há dados suficientes
            if (!this.hasValidData()) {
                this.showMessage('Por favor, ajuste todas as áreas antes de visualizar o gráfico.', 'warning');
                return;
            }

            // Ocultar botão mostrar
            $showBtn.addClass('rvss-hidden');
            
            // Mostrar elementos
            $chartContainer.removeClass('rvss-hidden').addClass('rvss-fade-in');
            $updateBtn.removeClass('rvss-hidden');
            $questionsSection.removeClass('rvss-hidden').addClass('rvss-fade-in');
            $contactSection.removeClass('rvss-hidden').addClass('rvss-fade-in');

            this.chartVisible = true;

            // Aguardar um pouco para o gráfico renderizar
            setTimeout(() => {
                if (window.RVSSChart && window.RVSSChart.refresh) {
                    window.RVSSChart.refresh();
                }
            }, 100);

            // Scroll suave para o gráfico
            $chartContainer[0].scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }

        /**
         * Atualiza o gráfico
         */
        updateChart() {
            if (window.RVSSChart && window.RVSSChart.refresh) {
                window.RVSSChart.refresh();
                this.showMessage('Gráfico atualizado!', 'success', 2000);
            }
        }

        /**
         * Manipula envio do formulário
         */
        async handleSubmit() {
            if (this.isSubmitting) return;

            // Validar formulário
            if (!this.validateForm()) {
                this.showMessage('Por favor, corrija os erros antes de enviar.', 'error');
                return;
            }

            this.isSubmitting = true;
            const $submitBtn = $(this.selectors.submitBtn);
            
            // Estado de carregamento
            $submitBtn.prop('disabled', true)
                     .addClass('rvss-loading')
                     .html('<span class="rvss-spinner"></span> Enviando...');

            try {
                // Coletar dados
                const formData = this.collectFormData();
                
                // Enviar via AJAX
                const response = await this.submitData(formData);
                
                if (response.success) {
                    this.handleSubmitSuccess(response);
                } else {
                    this.handleSubmitError(response.data || 'Erro desconhecido');
                }
                
            } catch (error) {
                console.error('RVSS: Erro no envio:', error);
                this.handleSubmitError('Erro de conexão. Tente novamente.');
            } finally {
                this.isSubmitting = false;
                $submitBtn.prop('disabled', false)
                         .removeClass('rvss-loading')
                         .html('Enviar Resultados');
            }
        }

        /**
         * Valida o formulário completo
         */
        validateForm() {
            let isValid = true;
            const $requiredFields = $(this.selectors.requiredFields);

            $requiredFields.each((index, field) => {
                const $field = $(field);
                if (!this.validateField($field)) {
                    isValid = false;
                }
            });

            return isValid;
        }

        /**
         * Valida um campo específico
         */
        validateField($field) {
            const value = $field.val().trim();
            const fieldType = $field.attr('type');
            let isValid = true;
            let errorMessage = '';

            // Limpar erros anteriores
            $field.removeClass('rvss-field-error');
            $field.siblings('.rvss-error-message').remove();

            // Validar campo obrigatório
            if ($field.prop('required') && !value) {
                isValid = false;
                errorMessage = 'Este campo é obrigatório.';
            }
            // Validar email
            else if (fieldType === 'email' && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Por favor, insira um email válido.';
                }
            }

            // Mostrar erro se necessário
            if (!isValid) {
                $field.addClass('rvss-field-error');
                $field.after(`<div class="rvss-error-message">${errorMessage}</div>`);
            }

            return isValid;
        }

        /**
         * Verifica se há dados válidos nos sliders
         */
        hasValidData() {
            const sliders = $(this.selectors.sliders);
            return sliders.length > 0;
        }

        /**
         * Coleta todos os dados do formulário
         */
        collectFormData() {
            const formData = new FormData();
            
            // Adicionar nonce
            formData.append('action', 'rvss_submit_form');
            formData.append('rvss_nonce', rvss_ajax.nonce);

            // Dados do formulário
            const serializedData = this.form.serialize();
            const urlParams = new URLSearchParams(serializedData);
            
            for (const [key, value] of urlParams) {
                formData.append(key, value);
            }

            // Adicionar dados do gráfico se disponível
            if (window.RVSSChart && window.RVSSChart.getReportData) {
                const reportData = window.RVSSChart.getReportData();
                formData.append('report_data', JSON.stringify(reportData));
            }

            return formData;
        }

        /**
         * Envia dados via AJAX
         */
        submitData(formData) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: rvss_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    timeout: 30000,
                    success: (response) => {
                        resolve(response);
                    },
                    error: (xhr, status, error) => {
                        reject(new Error(`${status}: ${error}`));
                    }
                });
            });
        }

        /**
         * Manipula sucesso no envio
         */
        handleSubmitSuccess(response) {
            this.showMessage('Seus dados foram enviados com sucesso! 🎉', 'success');
            
            // Opcional: resetar formulário
            // this.form[0].reset();
            
            // Scroll para mensagem
            $('html, body').animate({
                scrollTop: this.form.offset().top - 100
            }, 500);
        }

        /**
         * Manipula erro no envio
         */
        handleSubmitError(message) {
            this.showMessage(`Erro ao enviar: ${message}`, 'error');
        }

        /**
         * Exibe mensagem para o usuário
         */
        showMessage(text, type = 'info', duration = 5000) {
            const messageClass = `rvss-message rvss-message-${type}`;
            const $message = $(`<div class="${messageClass}">${text}</div>`);
            
            // Remover mensagens anteriores
            $('.rvss-message').remove();
            
            // Adicionar nova mensagem
            this.form.prepend($message);
            
            // Auto-remover
            if (duration > 0) {
                setTimeout(() => {
                    $message.fadeOut(() => $message.remove());
                }, duration);
            }

            // Scroll para mensagem
            $message[0].scrollIntoView({ 
                behavior: 'smooth', 
                block: 'nearest' 
            });
        }

        /**
         * Obtém configuração de uma área
         */
        getAreaConfig(area) {
            const configs = {
                'saude': { cor: '#10b981' },
                'familia': { cor: '#ec4899' },
                'amor': { cor: '#f97316' },
                'financas': { cor: '#3b82f6' },
                'carreira': { cor: '#8b5cf6' },
                'pessoal': { cor: '#06b6d4' },
                'lazer': { cor: '#84cc16' },
                'espiritualidade': { cor: '#a855f7' }
            };
            
            return configs[area] || null;
        }

        /**
         * Função de debounce para otimizar performance
         */
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }

    /**
     * Instância global do manipulador
     */
    let rvssFormHandler = null;

    /**
     * Inicialização
     */
    $(document).ready(function() {
        rvssFormHandler = new RVSSFormHandler();
    });

    /**
     * Expor globalmente para uso externo
     */
    window.RVSSFormHandler = {
        getInstance: () => rvssFormHandler,
        showMessage: (text, type, duration) => {
            if (rvssFormHandler) {
                rvssFormHandler.showMessage(text, type, duration);
            }
        }
    };

})(jQuery);
