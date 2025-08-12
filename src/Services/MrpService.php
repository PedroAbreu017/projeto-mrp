<?php
namespace App\Services;

use App\Models\Entities\Mrp;

/**
 * MrpService - Business Logic Layer
 * Centraliza toda lógica de negócio relacionada ao planejamento MRP
 * Implementa Service Layer Pattern
 */
class MrpService {
    private $mrpEntity;
    
    public function __construct(Mrp $mrp = null) {
        $this->carregarEnv();
        $this->mrpEntity = $mrp ?? new Mrp();
    }
    
    /**
     * Carregar variáveis de ambiente se não estiverem carregadas
     */
    private function carregarEnv() {
        if (empty($_ENV['DB_NAME'])) {
            $envFile = __DIR__ . '/../../.env';
            
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos($line, '=') !== false && $line[0] !== '#') {
                        list($key, $value) = explode('=', $line, 2);
                        $_ENV[trim($key)] = trim($value, '"\'');
                    }
                }
            }
        }
    }
    
    /**
     * Listar todos os produtos disponíveis para MRP
     */
    public function listarProdutos() {
        try {
            $produtos = $this->mrpEntity->getProdutos();
            
            return [
                'success' => true,
                'data' => $produtos,
                'total' => count($produtos),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao listar produtos: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Calcular necessidades de materiais (MRP simples)
     */
    public function calcularNecessidadesSimples($produtos, $quantidades) {
        try {
            // Validações
            $validacao = $this->validarDadosCalculo($produtos, $quantidades);
            if (!$validacao['valid']) {
                return [
                    'success' => false,
                    'error' => 'Dados inválidos',
                    'details' => $validacao['errors']
                ];
            }
            
            // Converter formato para demandas
            $demandas = [];
            foreach ($produtos as $index => $produtoId) {
                $quantidade = $quantidades[$index] ?? 0;
                if ($quantidade > 0) {
                    $demandas[$produtoId] = $quantidade;
                }
            }
            
            // Calcular necessidades
            $necessidades = $this->mrpEntity->calcularNecessidades($demandas);
            
            // Enriquecer com análises de negócio
            $resultado = $this->enriquecerResultadoMrp($necessidades);
            
            return [
                'success' => true,
                'data' => $resultado,
                'demandas_processadas' => $demandas,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro no cálculo MRP: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Gerar relatório completo de MRP
     */
    public function gerarRelatorioCompleto($demandas) {
        try {
            // Validações
            $validacao = $this->validarDemandas($demandas);
            if (!$validacao['valid']) {
                return [
                    'success' => false,
                    'error' => 'Demandas inválidas',
                    'details' => $validacao['errors']
                ];
            }
            
            // Gerar relatório completo
            $relatorio = $this->mrpEntity->gerarRelatorioMrp($demandas);
            
            // Análises avançadas
            $analises = $this->analisarRelatorioMrp($relatorio);
            
            return [
                'success' => true,
                'data' => [
                    'relatorio' => $relatorio,
                    'analises' => $analises,
                    'recomendacoes' => $this->gerarRecomendacoes($relatorio)
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao gerar relatório: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Simular impacto de produção no estoque
     */
    public function simularProducao($demandas) {
        try {
            $validacao = $this->validarDemandas($demandas);
            if (!$validacao['valid']) {
                return [
                    'success' => false,
                    'error' => 'Demandas inválidas',
                    'details' => $validacao['errors']
                ];
            }
            
            // Calcular impacto
            $impacto = $this->calcularImpactoEstoque($demandas);
            
            return [
                'success' => true,
                'data' => $impacto,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro na simulação: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter estrutura completa de produto (BOM)
     */
    public function obterEstruturaProduto($produtoId) {
        try {
            if (!$this->validarId($produtoId)) {
                return [
                    'success' => false,
                    'error' => 'ID de produto inválido'
                ];
            }
            
            $estrutura = $this->mrpEntity->getEstruturaProduto($produtoId);
            
            if (!$estrutura) {
                return [
                    'success' => false,
                    'error' => 'Produto não encontrado ou sem estrutura'
                ];
            }
            
            return [
                'success' => true,
                'data' => $estrutura,
                'produto_id' => $produtoId
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao obter estrutura: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Gerar sugestões baseadas em análise MRP
     */
    public function gerarSugestoes($demandas) {
        try {
            $relatorio = $this->mrpEntity->gerarRelatorioMrp($demandas);
            $sugestoes = $this->analisarEGerarSugestoes($relatorio);
            
            return [
                'success' => true,
                'data' => $sugestoes,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao gerar sugestões: ' . $e->getMessage()
            ];
        }
    }
    
    // ==================== MÉTODOS PRIVADOS ====================
    
    private function validarId($id) {
        return is_numeric($id) && $id > 0;
    }
    
    private function validarDadosCalculo($produtos, $quantidades) {
        $errors = [];
        
        if (empty($produtos) || !is_array($produtos)) {
            $errors[] = 'Lista de produtos é obrigatória';
        }
        
        if (empty($quantidades) || !is_array($quantidades)) {
            $errors[] = 'Lista de quantidades é obrigatória';
        }
        
        if (count($produtos) !== count($quantidades)) {
            $errors[] = 'Número de produtos deve ser igual ao número de quantidades';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function validarDemandas($demandas) {
        $errors = [];
        
        if (empty($demandas) || !is_array($demandas)) {
            $errors[] = 'Demandas são obrigatórias';
            return ['valid' => false, 'errors' => $errors];
        }
        
        foreach ($demandas as $produtoId => $quantidade) {
            if (!is_numeric($produtoId) || $produtoId <= 0) {
                $errors[] = "ID de produto inválido: {$produtoId}";
            }
            
            if (!is_numeric($quantidade) || $quantidade <= 0) {
                $errors[] = "Quantidade inválida para produto {$produtoId}: {$quantidade}";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function enriquecerResultadoMrp($necessidades) {
        // Adicionar análises de negócio
        $resultado = $necessidades;
        
        // Calcular criticidade
        if (isset($resultado['detalhes'])) {
            foreach ($resultado['detalhes'] as &$detalhe) {
                if (isset($detalhe['necessidades'])) {
                    foreach ($detalhe['necessidades'] as &$necessidade) {
                        $necessidade['criticidade'] = $this->calcularCriticidade($necessidade);
                        $necessidade['prazo_sugerido'] = $this->calcularPrazoSugerido($necessidade);
                    }
                }
            }
        }
        
        return $resultado;
    }
    
    private function analisarRelatorioMrp($relatorio) {
        return [
            'total_produtos' => count($relatorio['necessidades']['detalhes'] ?? []),
            'total_componentes_necessarios' => $this->contarComponentesUnicos($relatorio),
            'valor_estimado_compras' => $this->estimarValorCompras($relatorio),
            'tempo_estimado_producao' => $this->estimarTempoProducao($relatorio),
            'nivel_complexidade' => $this->avaliarComplexidade($relatorio)
        ];
    }
    
    private function gerarRecomendacoes($relatorio) {
        $recomendacoes = [];
        
        // Análise de viabilidade
        if (isset($relatorio['viabilidade']) && !$relatorio['viabilidade']['viavel']) {
            $recomendacoes[] = 'Revisar demanda ou reabastecer componentes em falta';
        }
        
        // Análise de estoque
        if (isset($relatorio['necessidades']['resumo_compras']) && 
            count($relatorio['necessidades']['resumo_compras']) > 5) {
            $recomendacoes[] = 'Considerar compra em lote para reduzir custos';
        }
        
        $recomendacoes[] = 'Monitorar estoque de componentes críticos';
        $recomendacoes[] = 'Implementar sistema de reposição automática';
        
        return $recomendacoes;
    }
    
    private function calcularImpactoEstoque($demandas) {
        // Simular impacto sem alterar banco
        $relatorio = $this->mrpEntity->gerarRelatorioMrp($demandas);
        
        return [
            'componentes_afetados' => count($relatorio['necessidades']['resumo_compras'] ?? []),
            'reducao_estoque_estimada' => $this->calcularReducaoEstoque($relatorio),
            'alertas_pos_producao' => $this->gerarAlertasPosProducao($relatorio)
        ];
    }
    
    private function analisarEGerarSugestoes($relatorio) {
        return [
            'otimizacao_producao' => $this->sugerirOtimizacaoProducao($relatorio),
            'gestao_estoque' => $this->sugerirGestaoEstoque($relatorio),
            'planejamento_compras' => $this->sugerirPlanejamentoCompras($relatorio)
        ];
    }
    
    private function calcularCriticidade($necessidade) {
        if ($necessidade['situacao'] === 'comprar') {
            return $necessidade['a_comprar'] > 10 ? 'alta' : 'media';
        }
        return 'baixa';
    }
    
    private function calcularPrazoSugerido($necessidade) {
        return $necessidade['situacao'] === 'comprar' ? '3-5 dias úteis' : 'imediato';
    }
    
    private function contarComponentesUnicos($relatorio) {
        return count($relatorio['necessidades']['resumo_compras'] ?? []);
    }
    
    private function estimarValorCompras($relatorio) {
        // Placeholder - implementar com preços reais
        return 'R$ 1.000,00 (estimativa)';
    }
    
    private function estimarTempoProducao($relatorio) {
        return '2-3 dias úteis';
    }
    
    private function avaliarComplexidade($relatorio) {
        $componentes = count($relatorio['necessidades']['resumo_compras'] ?? []);
        
        if ($componentes <= 3) return 'baixa';
        if ($componentes <= 6) return 'media';
        return 'alta';
    }
    
    private function calcularReducaoEstoque($relatorio) {
        return 'Redução estimada de 15-20% nos componentes utilizados';
    }
    
    private function gerarAlertasPosProducao($relatorio) {
        return ['Verificar estoque de Quadros após produção'];
    }
    
    private function sugerirOtimizacaoProducao($relatorio) {
        return ['Produzir em lotes para otimizar uso de recursos'];
    }
    
    private function sugerirGestaoEstoque($relatorio) {
        return ['Implementar estoque mínimo para componentes críticos'];
    }
    
    private function sugerirPlanejamentoCompras($relatorio) {
        return ['Negociar desconto por volume com fornecedores'];
    }
}