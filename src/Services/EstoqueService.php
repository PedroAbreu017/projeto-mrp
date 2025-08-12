<?php
namespace App\Services;

use App\Models\Entities\Estoque;

/**
 * EstoqueService - Business Logic Layer
 * Centraliza toda lógica de negócio relacionada ao estoque
 * Implementa Service Layer Pattern
 */
class EstoqueService {
    private $estoqueEntity;
    
    public function __construct(Estoque $estoque = null) {
        // CARREGAR .env SE NECESSÁRIO
        $this->carregarEnv();
        
        $this->estoqueEntity = $estoque ?? new Estoque();
    }
    
    /**
     * Carregar variáveis de ambiente se não estiverem carregadas
     */
    private function carregarEnv() {
        // Verificar se .env já foi carregado
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
     * Listar todos os componentes com filtros opcionais
     */
    public function listarComponentes($filtros = []) {
        try {
            $componentes = $this->estoqueEntity->getAllComponentes();
            
            // Aplicar filtros se fornecidos
            if (!empty($filtros)) {
                $componentes = $this->aplicarFiltros($componentes, $filtros);
            }
            
            return [
                'success' => true,
                'data' => $componentes,
                'total' => count($componentes),
                'filtros_aplicados' => $filtros,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao listar componentes: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Buscar componente por ID com validações
     */
    public function buscarComponente($id) {
        try {
            if (!$this->validarId($id)) {
                return [
                    'success' => false,
                    'error' => 'ID inválido fornecido'
                ];
            }
            
            $componente = $this->estoqueEntity->getComponenteById($id);
            
            if (!$componente) {
                return [
                    'success' => false,
                    'error' => 'Componente não encontrado'
                ];
            }
            
            // Enriquecer dados com informações adicionais
            $componenteEnriquecido = $this->enriquecerDadosComponente($componente);
            
            return [
                'success' => true,
                'data' => $componenteEnriquecido
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao buscar componente: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Criar novo componente com validações de negócio
     */
    public function criarComponente($dados) {
        try {
            // Validações de negócio
            $validacao = $this->validarDadosComponente($dados, 'create');
            if (!$validacao['valid']) {
                return [
                    'success' => false,
                    'error' => 'Dados inválidos',
                    'details' => $validacao['errors']
                ];
            }
            
            // Verificar regras de negócio específicas
            $regrasPassed = $this->verificarRegrasNegocio($dados, 'create');
            if (!$regrasPassed['valid']) {
                return [
                    'success' => false,
                    'error' => 'Regras de negócio violadas',
                    'details' => $regrasPassed['errors']
                ];
            }
            
            // Criar componente
            $nome = $dados['nome'];
            $descricao = $dados['descricao'] ?? '';
            $quantidade = $dados['quantidade_estoque'] ?? 0;
            $unidade = $dados['unidade_medida'] ?? 'unidade';
            
            $id = $this->estoqueEntity->adicionarComponente($nome, $descricao, $quantidade, $unidade);
            
            // Buscar componente criado
            $componente = $this->estoqueEntity->getComponenteById($id);
            
            // Log da operação
            $this->logOperacao('CREATE', $id, $dados);
            
            return [
                'success' => true,
                'message' => 'Componente criado com sucesso',
                'data' => $componente,
                'id' => $id
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao criar componente: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Atualizar componente existente
     */
    public function atualizarComponente($id, $dados) {
        try {
            if (!$this->validarId($id)) {
                return [
                    'success' => false,
                    'error' => 'ID inválido fornecido'
                ];
            }
            
            // Verificar se componente existe
            $componenteExistente = $this->estoqueEntity->getComponenteById($id);
            if (!$componenteExistente) {
                return [
                    'success' => false,
                    'error' => 'Componente não encontrado'
                ];
            }
            
            // Validações de negócio
            $validacao = $this->validarDadosComponente($dados, 'update');
            if (!$validacao['valid']) {
                return [
                    'success' => false,
                    'error' => 'Dados inválidos',
                    'details' => $validacao['errors']
                ];
            }
            
            // Verificar regras de negócio
            $regrasPassed = $this->verificarRegrasNegocio($dados, 'update', $id);
            if (!$regrasPassed['valid']) {
                return [
                    'success' => false,
                    'error' => 'Regras de negócio violadas',
                    'details' => $regrasPassed['errors']
                ];
            }
            
            // Preparar dados para atualização
            $nome = $dados['nome'];
            $descricao = $dados['descricao'] ?? $componenteExistente['descricao'];
            $quantidade = isset($dados['quantidade_estoque']) ? $dados['quantidade_estoque'] : null;
            $unidade = $dados['unidade_medida'] ?? $componenteExistente['unidade_medida'];
            
            // Atualizar componente
            $this->estoqueEntity->atualizarComponente($id, $nome, $descricao, $quantidade, $unidade);
            
            // Buscar componente atualizado
            $componenteAtualizado = $this->estoqueEntity->getComponenteById($id);
            
            // Log da operação
            $this->logOperacao('UPDATE', $id, $dados, $componenteExistente);
            
            return [
                'success' => true,
                'message' => 'Componente atualizado com sucesso',
                'data' => $componenteAtualizado,
                'changes' => $this->detectarMudancas($componenteExistente, $componenteAtualizado)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao atualizar componente: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Remover componente com verificações de segurança
     */
    public function removerComponente($id) {
        try {
            if (!$this->validarId($id)) {
                return [
                    'success' => false,
                    'error' => 'ID inválido fornecido'
                ];
            }
            
            // Buscar componente antes de remover
            $componente = $this->estoqueEntity->getComponenteById($id);
            if (!$componente) {
                return [
                    'success' => false,
                    'error' => 'Componente não encontrado'
                ];
            }
            
            // Verificar se pode ser removido (regras de negócio)
            $podeRemover = $this->verificarPodeRemover($id);
            if (!$podeRemover['valid']) {
                return [
                    'success' => false,
                    'error' => 'Componente não pode ser removido',
                    'details' => $podeRemover['reasons']
                ];
            }
            
            // Remover componente
            $this->estoqueEntity->removerComponente($id);
            
            // Log da operação
            $this->logOperacao('DELETE', $id, [], $componente);
            
            return [
                'success' => true,
                'message' => 'Componente removido com sucesso',
                'data' => $componente
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao remover componente: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Atualizar apenas estoque de um componente
     */
    public function atualizarEstoque($id, $quantidade, $motivo = '') {
        try {
            if (!$this->validarId($id)) {
                return [
                    'success' => false,
                    'error' => 'ID inválido fornecido'
                ];
            }
            
            if (!is_numeric($quantidade) || $quantidade < 0) {
                return [
                    'success' => false,
                    'error' => 'Quantidade deve ser um número positivo'
                ];
            }
            
            // Buscar estado anterior
            $componenteAnterior = $this->estoqueEntity->getComponenteById($id);
            if (!$componenteAnterior) {
                return [
                    'success' => false,
                    'error' => 'Componente não encontrado'
                ];
            }
            
            // Atualizar estoque
            $this->estoqueEntity->atualizarEstoque($id, $quantidade);
            
            // Buscar estado atualizado
            $componenteAtualizado = $this->estoqueEntity->getComponenteById($id);
            
            // Log da movimentação
            $this->logMovimentacaoEstoque($id, $componenteAnterior['quantidade_estoque'], $quantidade, $motivo);
            
            return [
                'success' => true,
                'message' => 'Estoque atualizado com sucesso',
                'data' => $componenteAtualizado,
                'movimentacao' => [
                    'quantidade_anterior' => $componenteAnterior['quantidade_estoque'],
                    'quantidade_nova' => $quantidade,
                    'diferenca' => $quantidade - $componenteAnterior['quantidade_estoque'],
                    'motivo' => $motivo
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao atualizar estoque: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar componentes com estoque baixo
     */
    public function verificarEstoqueBaixo($limite = 5) {
        try {
            $componentes = $this->estoqueEntity->getComponentesEstoqueBaixo($limite);
            
            // Enriquecer com informações adicionais
            $componentesEnriquecidos = array_map(function($componente) use ($limite) {
                return [
                    ...$componente,
                    'criticidade' => $this->calcularCriticidade($componente['quantidade_estoque'], $limite),
                    'sugestao_reposicao' => $this->sugerirReposicao($componente)
                ];
            }, $componentes);
            
            return [
                'success' => true,
                'data' => $componentesEnriquecidos,
                'total' => count($componentesEnriquecidos),
                'limite_usado' => $limite,
                'alertas' => $this->gerarAlertasEstoque($componentesEnriquecidos)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao verificar estoque baixo: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Calcular estatísticas detalhadas do estoque
     */
    public function calcularEstatisticas() {
        try {
            $estatisticasBasicas = $this->estoqueEntity->getEstatisticasEstoque();
            
            // Estatísticas adicionais
            $estatisticasAvancadas = [
                'distribuicao_estoque' => $this->calcularDistribuicaoEstoque(),
                'componentes_criticos' => $this->identificarComponentesCriticos(),
                'tendencias' => $this->analisarTendencias(),
                'recomendacoes' => $this->gerarRecomendacoes()
            ];
            
            return [
                'success' => true,
                'data' => [
                    'basicas' => $estatisticasBasicas,
                    'avancadas' => $estatisticasAvancadas
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao calcular estatísticas: ' . $e->getMessage()
            ];
        }
    }
    
    // ==================== MÉTODOS PRIVADOS ====================
    
    private function validarId($id) {
        return is_numeric($id) && $id > 0;
    }
    
    private function validarDadosComponente($dados, $operacao) {
        $errors = [];
        
        if ($operacao === 'create' || isset($dados['nome'])) {
            if (empty(trim($dados['nome'] ?? ''))) {
                $errors[] = 'Nome do componente é obrigatório';
            }
        }
        
        if (isset($dados['quantidade_estoque'])) {
            if (!is_numeric($dados['quantidade_estoque']) || $dados['quantidade_estoque'] < 0) {
                $errors[] = 'Quantidade deve ser um número positivo';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function verificarRegrasNegocio($dados, $operacao, $id = null) {
        $errors = [];
        
        // Verificar nome duplicado
        if (isset($dados['nome'])) {
            $existente = $this->estoqueEntity->getComponenteByNome($dados['nome']);
            if ($existente && ($operacao === 'create' || $existente['id'] != $id)) {
                $errors[] = 'Já existe um componente com este nome';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function verificarPodeRemover($id) {
        $reasons = [];
        
        // Verificar se está sendo usado em produtos (BOM)
        // TODO: Implementar verificação na tabela BOM
        
        return [
            'valid' => empty($reasons),
            'reasons' => $reasons
        ];
    }
    
    private function aplicarFiltros($componentes, $filtros) {
        if (!empty($filtros['busca'])) {
            $busca = strtolower($filtros['busca']);
            $componentes = array_filter($componentes, function($componente) use ($busca) {
                return strpos(strtolower($componente['nome']), $busca) !== false ||
                       strpos(strtolower($componente['descricao']), $busca) !== false;
            });
        }
        
        if (!empty($filtros['estoque_baixo'])) {
            $limite = (int)$filtros['estoque_baixo'];
            $componentes = array_filter($componentes, function($componente) use ($limite) {
                return $componente['quantidade_estoque'] <= $limite;
            });
        }
        
        return array_values($componentes); // Reindexar array
    }
    
    private function enriquecerDadosComponente($componente) {
        return [
            ...$componente,
            'status_estoque' => $this->calcularStatusEstoque($componente['quantidade_estoque']),
            'valor_estimado' => $this->calcularValorEstimado($componente)
        ];
    }
    
    private function calcularStatusEstoque($quantidade) {
        if ($quantidade == 0) return 'sem_estoque';
        if ($quantidade <= 5) return 'estoque_baixo';
        if ($quantidade <= 20) return 'estoque_medio';
        return 'estoque_alto';
    }
    
    private function calcularValorEstimado($componente) {
        // TODO: Implementar cálculo de valor baseado em preços médios
        return null;
    }
    
    private function detectarMudancas($anterior, $atual) {
        $mudancas = [];
        
        foreach (['nome', 'descricao', 'quantidade_estoque', 'unidade_medida'] as $campo) {
            if (isset($anterior[$campo]) && isset($atual[$campo]) && $anterior[$campo] != $atual[$campo]) {
                $mudancas[$campo] = [
                    'anterior' => $anterior[$campo],
                    'atual' => $atual[$campo]
                ];
            }
        }
        
        return $mudancas;
    }
    
    private function calcularCriticidade($quantidade, $limite) {
        if ($quantidade == 0) return 'critica';
        if ($quantidade <= $limite * 0.5) return 'alta';
        if ($quantidade <= $limite) return 'media';
        return 'baixa';
    }
    
    private function sugerirReposicao($componente) {
        $quantidade = $componente['quantidade_estoque'];
        $sugestao = max(50, $quantidade * 10); // Exemplo: sugerir 10x o atual ou 50 mínimo
        
        return "Sugerimos repor com {$sugestao} unidades";
    }
    
    private function gerarAlertasEstoque($componentes) {
        $alertas = [];
        
        foreach ($componentes as $componente) {
            if ($componente['quantidade_estoque'] == 0) {
                $alertas[] = "CRÍTICO: {$componente['nome']} está com estoque ZERO";
            }
        }
        
        return $alertas;
    }
    
    private function calcularDistribuicaoEstoque() {
        try {
            $componentes = $this->estoqueEntity->getAllComponentes();
            $distribuicao = [
                'sem_estoque' => 0,
                'estoque_baixo' => 0,
                'estoque_medio' => 0,
                'estoque_alto' => 0
            ];
            
            foreach ($componentes as $componente) {
                $status = $this->calcularStatusEstoque($componente['quantidade_estoque']);
                $distribuicao[$status]++;
            }
            
            return $distribuicao;
        } catch (\Exception $e) {
            return [
                'sem_estoque' => 0,
                'estoque_baixo' => 0,
                'estoque_medio' => 0,
                'estoque_alto' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function identificarComponentesCriticos() {
        try {
            return $this->estoqueEntity->getComponentesEstoqueBaixo(3);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    private function analisarTendencias() {
        // TODO: Implementar análise de tendências de consumo baseada em histórico
        return [
            'consumo_medio_mensal' => 'Dados insuficientes',
            'previsao_reposicao' => 'Implementar histórico de movimentações'
        ];
    }
    
    private function gerarRecomendacoes() {
        return [
            'Implementar sistema de reposição automática',
            'Definir estoques mínimos por componente',
            'Criar alertas personalizados por criticidade',
            'Implementar histórico de movimentações para análise de tendências'
        ];
    }
    
    private function logOperacao($operacao, $id, $dados, $estadoAnterior = null) {
        // Sistema de logs melhorado
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'operacao' => $operacao,
            'componente_id' => $id,
            'dados' => $dados,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        if ($estadoAnterior) {
            $logData['estado_anterior'] = $estadoAnterior;
        }
        
        error_log("ESTOQUE_SERVICE: " . json_encode($logData));
    }
    
    private function logMovimentacaoEstoque($id, $quantidadeAnterior, $quantidadeNova, $motivo) {
        $diferenca = $quantidadeNova - $quantidadeAnterior;
        $tipo = $diferenca > 0 ? 'ENTRADA' : 'SAIDA';
        
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tipo' => $tipo,
            'componente_id' => $id,
            'quantidade_anterior' => $quantidadeAnterior,
            'quantidade_nova' => $quantidadeNova,
            'diferenca' => $diferenca,
            'motivo' => $motivo,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        error_log("MOVIMENTACAO_ESTOQUE: " . json_encode($logData));
    }
}