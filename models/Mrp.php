<?php
/**
 * Model: MRP (Material Requirements Planning)
 * Calcula necessidades de materiais baseado na produção planejada
 */

require_once __DIR__ . '/../config/database.php';

class Mrp {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Busca todos os produtos disponíveis
     */
    public function getProdutos() {
        $sql = "SELECT id, nome, descricao FROM produtos WHERE ativo = TRUE ORDER BY nome";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Busca a estrutura (BOM) de um produto específico
     */
    public function getEstruturaProduto($produtoId) {
        $sql = "SELECT 
                    c.id as componente_id,
                    c.nome as componente_nome,
                    c.quantidade_estoque,
                    b.quantidade_necessaria
                FROM bom b
                JOIN componentes c ON b.componente_id = c.id
                WHERE b.produto_id = ?
                ORDER BY c.nome";
        
        return $this->db->fetchAll($sql, [$produtoId]);
    }
    
    /**
     * Calcula as necessidades de materiais para uma demanda específica
     */
    public function calcularNecessidades($demandas) {
        $resultado = [];
        $totalCompras = [];
        
        foreach ($demandas as $produtoId => $quantidade) {
            if ($quantidade <= 0) continue;
            
            // Busca informações do produto
            $produto = $this->getProdutoById($produtoId);
            if (!$produto) continue;
            
            // Busca estrutura do produto
            $estrutura = $this->getEstruturaProduto($produtoId);
            
            $necessidadesProduto = [];
            
            foreach ($estrutura as $item) {
                $necessario = $item['quantidade_necessaria'] * $quantidade;
                $emEstoque = $item['quantidade_estoque'];
                $aComprar = max(0, $necessario - $emEstoque);
                
                $necessidadesProduto[] = [
                    'componente_id' => $item['componente_id'],
                    'componente_nome' => $item['componente_nome'],
                    'necessario' => $necessario,
                    'em_estoque' => $emEstoque,
                    'a_comprar' => $aComprar,
                    'situacao' => $aComprar > 0 ? 'comprar' : 'suficiente'
                ];
                
                // Acumula total de compras por componente
                if ($aComprar > 0) {
                    if (!isset($totalCompras[$item['componente_id']])) {
                        $totalCompras[$item['componente_id']] = [
                            'nome' => $item['componente_nome'],
                            'quantidade' => 0
                        ];
                    }
                    $totalCompras[$item['componente_id']]['quantidade'] += $aComprar;
                }
            }
            
            $resultado[] = [
                'produto' => $produto,
                'quantidade_produzir' => $quantidade,
                'necessidades' => $necessidadesProduto
            ];
        }
        
        return [
            'detalhes' => $resultado,
            'resumo_compras' => $totalCompras,
            'total_itens_comprar' => count($totalCompras)
        ];
    }
    
    /**
     * Busca produto por ID
     */
    public function getProdutoById($id) {
        $sql = "SELECT * FROM produtos WHERE id = ? AND ativo = TRUE";
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Simula uma produção e retorna o impacto no estoque
     */
    public function simularProducao($demandas) {
        $simulacao = [];
        
        foreach ($demandas as $produtoId => $quantidade) {
            if ($quantidade <= 0) continue;
            
            $produto = $this->getProdutoById($produtoId);
            if (!$produto) continue;
            
            $estrutura = $this->getEstruturaProduto($produtoId);
            
            foreach ($estrutura as $item) {
                $necessario = $item['quantidade_necessaria'] * $quantidade;
                $novoEstoque = $item['quantidade_estoque'] - $necessario;
                
                $simulacao[] = [
                    'componente_id' => $item['componente_id'],
                    'componente_nome' => $item['componente_nome'],
                    'estoque_atual' => $item['quantidade_estoque'],
                    'necessario' => $necessario,
                    'estoque_apos_producao' => $novoEstoque,
                    'situacao' => $novoEstoque < 0 ? 'insuficiente' : 'ok'
                ];
            }
        }
        
        return $simulacao;
    }
    
    /**
     * Verifica se é possível produzir com o estoque atual
     */
    public function verificarViabilidade($demandas) {
        $problemas = [];
        $viavel = true;
        
        foreach ($demandas as $produtoId => $quantidade) {
            if ($quantidade <= 0) continue;
            
            $produto = $this->getProdutoById($produtoId);
            if (!$produto) {
                $problemas[] = "Produto ID {$produtoId} não encontrado";
                $viavel = false;
                continue;
            }
            
            $estrutura = $this->getEstruturaProduto($produtoId);
            
            foreach ($estrutura as $item) {
                $necessario = $item['quantidade_necessaria'] * $quantidade;
                
                if ($item['quantidade_estoque'] < $necessario) {
                    $falta = $necessario - $item['quantidade_estoque'];
                    $problemas[] = "Faltam {$falta} unidades de {$item['componente_nome']} para produzir {$quantidade} {$produto['nome']}(s)";
                    $viavel = false;
                }
            }
        }
        
        return [
            'viavel' => $viavel,
            'problemas' => $problemas
        ];
    }
    
    /**
     * Gera relatório detalhado do MRP
     */
    public function gerarRelatorioMrp($demandas) {
        $relatorio = $this->calcularNecessidades($demandas);
        $viabilidade = $this->verificarViabilidade($demandas);
        $simulacao = $this->simularProducao($demandas);
        
        return [
            'necessidades' => $relatorio,
            'viabilidade' => $viabilidade,
            'simulacao_estoque' => $simulacao,
            'data_calculo' => date('Y-m-d H:i:s'),
            'total_produtos' => count($demandas)
        ];
    }
    
    /**
     * Busca estrutura completa de todos os produtos
     */
    public function getEstruturaCompleta() {
        $sql = "SELECT 
                    p.id as produto_id,
                    p.nome as produto_nome,
                    p.descricao as produto_descricao,
                    c.id as componente_id,
                    c.nome as componente_nome,
                    c.quantidade_estoque,
                    b.quantidade_necessaria
                FROM produtos p
                JOIN bom b ON p.id = b.produto_id
                JOIN componentes c ON b.componente_id = c.id
                WHERE p.ativo = TRUE
                ORDER BY p.nome, c.nome";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Valida dados de entrada para cálculo MRP
     */
    public function validarDemandas($demandas) {
        $erros = [];
        
        if (empty($demandas)) {
            $erros[] = "Nenhuma demanda informada";
            return $erros;
        }
        
        foreach ($demandas as $produtoId => $quantidade) {
            if (!is_numeric($produtoId)) {
                $erros[] = "ID do produto deve ser numérico";
                continue;
            }
            
            if (!is_numeric($quantidade) || $quantidade < 0) {
                $erros[] = "Quantidade para produto ID {$produtoId} deve ser um número positivo";
                continue;
            }
            
            if ($quantidade > 0) {
                $produto = $this->getProdutoById($produtoId);
                if (!$produto) {
                    $erros[] = "Produto ID {$produtoId} não encontrado";
                }
            }
        }
        
        return $erros;
    }
    
    /**
     * Calcula custo estimado de compras (se tivéssemos preços)
     */
    public function calcularCustoEstimado($resumoCompras) {
        // Placeholder para futuras implementações com preços
        // Por enquanto retorna apenas informações básicas
        return [
            'total_itens' => count($resumoCompras),
            'observacao' => 'Cálculo de custos requer cadastro de preços dos componentes'
        ];
    }
    
    /**
     * Sugere alternativas para problemas de estoque
     */
    public function sugerirAlternativas($problemas) {
        $sugestoes = [];
        
        foreach ($problemas as $problema) {
            $sugestoes[] = [
                'problema' => $problema,
                'sugestoes' => [
                    'Comprar o componente em falta',
                    'Reduzir a quantidade de produção',
                    'Verificar fornecedores alternativos',
                    'Considerar produção em lotes menores'
                ]
            ];
        }
        
        return $sugestoes;
    }
}