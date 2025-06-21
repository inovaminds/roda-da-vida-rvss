/**
 * Roda da Vida RVSS - Integração AMCharts v5
 * Plugin: Roda da Vida by Simone Silvestrin (RVSS)
 * Versão: 0.2.0
 * Autor: InovaMinds
 */

(function($) {
    'use strict';

    /**
     * Classe principal de integração com AMCharts
     */
    class RVSSChart {
        constructor() {
            this.chart = null;
            this.root = null;
            this.series = null;
            this.data = [];
            this.isInitialized = false;
            
            // Configuração das áreas da vida
            this.areasConfig = {
                'saude': { nome: 'Saúde', cor: '#10b981', grupo: 'Qualidade de Vida' },
                'familia': { nome: 'Família', cor: '#ec4899', grupo: 'Relacionamentos' },
                'amor': { nome: 'Vida Amorosa', cor: '#f97316', grupo: 'Relacionamentos' },
                'financas': { nome: 'Dinheiro e Finanças', cor: '#3b82f6', grupo: 'Profissional' },
                'carreira': { nome: 'Trabalho e Carreira', cor: '#8b5cf6', grupo: 'Profissional' },
                'pessoal': { nome: 'Desenvolvimento Pessoal', cor: '#06b6d4', grupo: 'Pessoal' },
                'lazer': { nome: 'Lazer e Diversão', cor: '#84cc16', grupo: 'Qualidade de Vida' },
                'espiritualidade': { nome: 'Espiritualidade', cor: '#a855f7', grupo: 'Qualidade de Vida' }
            };

            this.bindEvents();
        }

        /**
         * Vincula eventos
         */
        bindEvents() {
            $(document).ready(() => {
                this.initializeChart();
            });
        }

        /**
         * Inicializa o gráfico AMCharts
         */
        initializeChart() {
            if (typeof am5 === 'undefined') {
                console.error('RVSS: AMCharts v5 não foi carregado');
                this.showError('Erro ao carregar biblioteca de gráficos');
                return;
            }

            const chartContainer = document.getElementById('rvss-chart');
            if (!chartContainer) {
                console.warn('RVSS: Container do gráfico não encontrado');
                return;
            }

            try {
                // Aguarda AMCharts estar pronto
                am5.ready(() => {
                    this.createChart();
                    this.isInitialized = true;
                });
            } catch (error) {
                console.error('RVSS: Erro ao inicializar AMCharts:', error);
                this.showError('Erro ao inicializar gráfico');
            }
        }

        /**
         * Cria o gráfico radar
         */
        createChart() {
            // Criar root
            this.root = am5.Root.new("rvss-chart");

            // Aplicar temas
            this.root.setThemes([
                am5themes_Animated.new(this.root),
                am5themes_Responsive.new(this.root)
            ]);

            // Criar gráfico radar
            this.chart = this.root.container.children.push(
                am5radar.RadarChart.new(this.root, {
                    panX: "none",
                    panY: "none",
                    startAngle: -90,
                    endAngle: 270,
                    innerRadius: am5.percent(30),
                    paddingTop: 40,
                    paddingBottom: 40,
                    paddingLeft: 40,
                    paddingRight: 40
                })
            );

            // Configurar responsividade
            this.setupResponsive();

            // Criar eixos
            this.createAxes();

            // Criar série
            this.createSeries();

            // Configurar dados iniciais
            this.updateChartData();

            // Animação inicial
            this.series.appear(1000);
            this.chart.appear(1000, 100);

            console.log('RVSS: Gráfico inicializado com sucesso');
        }

        /**
         * Configura regras responsivas
         */
        setupResponsive() {
            const responsive = am5themes_Responsive.new(this.root);

            // Regras para telas pequenas
            responsive.addRule({
                relevant: am5themes_Responsive.widthS,
                applying: () => {
                    this.chart.set("innerRadius", am5.percent(20));
                    this.chart.set("paddingTop", 20);
                    this.chart.set("paddingBottom", 20);
                    this.chart.set("paddingLeft", 20);
                    this.chart.set("paddingRight", 20);
                }
            });

            // Regras para telas médias
            responsive.addRule({
                relevant: am5themes_Responsive.widthM,
                applying: () => {
                    this.chart.set("innerRadius", am5.percent(25));
                    this.chart.set("paddingTop", 30);
                    this.chart.set("paddingBottom", 30);
                }
            });
        }

        /**
         * Cria os eixos do gráfico
         */
        createAxes() {
            // Eixo de categorias (áreas da vida)
            this.categoryAxis = this.chart.xAxes.push(
                am5xy.CategoryAxis.new(this.root, {
                    categoryField: "category",
                    renderer: am5radar.AxisRendererCircular.new(this.root, {
                        minGridDistance: 20,
                        strokeOpacity: 0.5,
                        strokeWidth: 1
                    })
                })
            );

            // Estilizar labels das categorias
            this.categoryAxis.get("renderer").labels.template.setAll({
                fontSize: 12,
                fontWeight: "600",
                textAlign: "center",
                paddingTop: 10,
                paddingBottom: 10
            });

            // Eixo de valores (notas de 1 a 10)
            this.valueAxis = this.chart.yAxes.push(
                am5xy.ValueAxis.new(this.root, {
                    min: 0,
                    max: 10,
                    strictMinMax: true,
                    renderer: am5radar.AxisRendererRadial.new(this.root, {
                        strokeOpacity: 0.3,
                        minGridDistance: 20
                    })
                })
            );

            // Estilizar grid radial
            this.valueAxis.get("renderer").grid.template.setAll({
                strokeOpacity: 0.3,
                strokeDasharray: [2, 2]
            });

            // Labels dos valores
            this.valueAxis.get("renderer").labels.template.setAll({
                fontSize: 10,
                fill: am5.color("#666")
            });
        }

        /**
         * Cria a série do gráfico
         */
        createSeries() {
            this.series = this.chart.series.push(
                am5radar.RadarLineSeries.new(this.root, {
                    name: "Roda da Vida",
                    xAxis: this.categoryAxis,
                    yAxis: this.valueAxis,
                    valueYField: "value",
                    categoryXField: "category"
                })
            );

            // Configurar linha
            this.series.strokes.template.setAll({
                strokeWidth: 3,
                stroke: am5.color("#3b82f6"),
                strokeOpacity: 0.8
            });

            // Configurar preenchimento
            this.series.fills.template.setAll({
                fillOpacity: 0.2,
                fill: am5.color("#3b82f6")
            });

            // Adicionar pontos (bullets)
            this.series.bullets.push(() => {
                const circle = am5.Circle.new(this.root, {
                    radius: 6,
                    fill: am5.color("#3b82f6"),
                    stroke: am5.color("#ffffff"),
                    strokeWidth: 3,
                    shadowColor: am5.color("#000000"),
                    shadowOpacity: 0.3,
                    shadowOffsetY: 2,
                    shadowBlur: 4
                });

                return am5.Bullet.new(this.root, {
                    sprite: circle
                });
            });

            // Configurar tooltip
            this.series.set("tooltip", am5.Tooltip.new(this.root, {
                labelText: "{category}: {value}/10",
                getFillFromSprite: false,
                pointerOrientation: "vertical"
            }));

            this.series.get("tooltip").get("background").setAll({
                fill: am5.color("#1f2937"),
                strokeOpacity: 0
            });

            this.series.get("tooltip").label.setAll({
                fill: am5.color("#ffffff"),
                fontSize: 12
            });
        }

        /**
         * Atualiza os dados do gráfico
         */
        updateChartData() {
            const data = this.collectFormData();
            
            if (this.categoryAxis && this.series) {
                this.categoryAxis.data.setAll(data);
                this.series.data.setAll(data);
            }

            this.data = data;
        }

        /**
         * Coleta dados do formulário
         */
        collectFormData() {
            const data = [];
            
            Object.keys(this.areasConfig).forEach(area => {
                const input = document.querySelector(`[name="notas[${area}]"]`);
                const config = this.areasConfig[area];
                
                if (input) {
                    const value = parseInt(input.value) || 5;
                    
                    data.push({
                        category: config.nome,
                        value: value,
                        area: area,
                        color: config.cor,
                        grupo: config.grupo
                    });
                }
            });

            return data;
        }

        /**
         * Força atualização do gráfico
         */
        refresh() {
            if (this.isInitialized) {
                this.updateChartData();
                console.log('RVSS: Gráfico atualizado');
            }
        }

        /**
         * Obtém dados atuais para relatório
         */
        getReportData() {
            return {
                areas: this.data,
                media: this.calculateAverage(),
                pontosMaisBaixos: this.getLowestScores(),
                pontosMaisAltos: this.getHighestScores()
            };
        }

        /**
         * Calcula média geral
         */
        calculateAverage() {
            if (this.data.length === 0) return 0;
            
            const total = this.data.reduce((sum, item) => sum + item.value, 0);
            return Math.round((total / this.data.length) * 10) / 10;
        }

        /**
         * Identifica pontuações mais baixas
         */
        getLowestScores(limit = 3) {
            return [...this.data]
                .sort((a, b) => a.value - b.value)
                .slice(0, limit);
        }

        /**
         * Identifica pontuações mais altas
         */
        getHighestScores(limit = 3) {
            return [...this.data]
                .sort((a, b) => b.value - a.value)
                .slice(0, limit);
        }

        /**
         * Exibe mensagem de erro
         */
        showError(message) {
            const container = document.getElementById('rvss-chart');
            if (container) {
                container.innerHTML = `
                    <div class="rvss-chart-error">
                        <p style="color: #ef4444; text-align: center; padding: 2rem;">
                            <strong>⚠️ ${message}</strong><br>
                            <small>Verifique se todos os recursos foram carregados corretamente.</small>
                        </p>
                    </div>
                `;
            }
        }

        /**
         * Limpa recursos do gráfico
         */
        dispose() {
            if (this.root) {
                this.root.dispose();
                this.root = null;
                this.chart = null;
                this.series = null;
                this.isInitialized = false;
            }
        }

        /**
         * Redimensiona o gráfico
         */
        resize() {
            if (this.root) {
                this.root.resize();
            }
        }
    }

    /**
     * Instância global do gráfico
     */
    let rvssChart = null;

    /**
     * Inicialização quando documento estiver pronto
     */
    $(document).ready(function() {
        // Criar instância do gráfico
        rvssChart = new RVSSChart();

        // Listener para redimensionamento
        $(window).on('resize', debounce(function() {
            if (rvssChart) {
                rvssChart.resize();
            }
        }, 250));
    });

    /**
     * Função utilitária de debounce
     */
    function debounce(func, wait) {
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

    /**
     * Expor funções globalmente para uso em outros scripts
     */
    window.RVSSChart = {
        getInstance: () => rvssChart,
        refresh: () => rvssChart ? rvssChart.refresh() : null,
        getReportData: () => rvssChart ? rvssChart.getReportData() : null,
        dispose: () => rvssChart ? rvssChart.dispose() : null
    };

})(jQuery);
