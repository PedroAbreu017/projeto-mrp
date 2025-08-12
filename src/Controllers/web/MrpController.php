<?php
/**
 * Controller: MrpController
 * Gerencia cálculos de MRP (Material Requirements Planning)
 */

require_once __DIR__ . '/../models/Mrp.php';
require_once __DIR__ . '/../models/Estoque.php';

class MrpController {
    private $mrpModel;
    private $estoqueModel;
    private $errors = [];
    private $success = [];
    
    public function __construct() {
        $this->mrpModel = new Mrp();
        $this->estoqueModel = new Estoque();
    }
    
    /**
     * Exibe a página principal do MRP
     */
    public function index() {
        try {
            $produtos = $this->mrpModel->getProdutos();
            $estruturaCompleta = $this->mrpModel->getEstruturaCompleta();
            
            // Se há dados POST, processa o cálculo
            $resultado = null;
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $resultado = $this->calcular();
            }
            
            $data = [
                'produtos' => $produtos,
                'estrutura' => $estruturaCompleta,
                'resultado' => $resultado,
                'title' => 'Planejamento MRP',
                'errors' => $this->errors,
                'success' => $this->success
            ];
            
            $this->renderView('mrp/index', $data);
            
        } catch (Exception $e) {
            $this->addError("Erro ao carregar MRP: " . $e->getMessage());
            $this->renderView('mrp/index', ['errors' => $this->errors]);
        }
    }
    
    /**
     * Calcula necessidades de MRP
     */
    public function calcular() {
        try {
            // Coleta demandas do formulário
            $demandas = $this->coletarDemandas();
            
            if (empty($demandas)) {
                throw new Exception("Nenhuma quantidade de produção informada");
            }
            
            // Valida demandas
            $erros = $this->mrpModel->validarDemandas($demandas);
            if (!empty($erros)) {
                foreach ($erros as $erro) {
                    $this->addError($erro);
                }
                return null;
            }
            
            // Calcula necessidades
            $relatorio = $this->mrpModel->gerarRelatorioMrp($demandas);
            
            // Adiciona informações extras para a view
            $relatorio['demandas_originais'] = $demandas;
            $relatorio['tem_problemas'] = !$relatorio['viabilidade']['viavel'];
            
            if ($relatorio['tem_problemas']) {
                foreach ($relatorio['viabilidade']['problemas'] as $problema) {
                    $this->addError($problema);
                }
            } else {
                $this->addSuccess("Cálculo MRP realizado com sucesso!");
            }
            
            return $relatorio;
            
        } catch (Exception $e) {
            $this->addError("Erro no cálculo MRP: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Exporta resultado MRP (JSON/CSV)
     */
    public function exportar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/mrp');
            return;
        }
        
        try {
            $formato = $this->getPostData('formato', 'json');
            $demandas = $this->coletarDemandas();
            
            if (empty($demandas)) {
                throw new Exception("Nenhuma demanda para exportar");
            }
            
            $relatorio = $this->mrpModel->gerarRelatorioMrp($demandas);
            
            switch ($formato) {
                case 'json':
                    $this->exportarJson($relatorio);
                    break;
                case 'csv':
                    $this->exportarCsv($relatorio);
                    break;
                default:
                    throw new Exception("Formato de exportação inválido");
            }
            
        } catch (Exception $e) {
            $this->addError("Erro na exportação: " . $e->getMessage());
            $this->redirect('/mrp');
        }
    }
    
    /**
     * Simula produção e mostra impacto no estoque
     */
    public function simular() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método não permitido'], 405);
            return;
        }
        
        try {
            $demandas = $this->coletarDemandas();
            
            if (empty($demandas)) {
                throw new Exception("Nenhuma demanda informada");
            }
            
            $simulacao = $this->mrpModel->simularProducao($demandas);
            $viabilidade = $this->mrpModel->verificarViabilidade($demandas);
            
            $this->jsonResponse([
                'success' => true,
                'simulacao' => $simulacao,
                'viabilidade' => $viabilidade,
                'resumo' => [
                    'total_componentes' => count($simulacao),
                    'componentes_insuficientes' => count(array_filter($simulacao, function($item) {
                        return $item['situacao'] === 'insuficiente';
                    }))
                ]
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    
    /**
     * Retorna estrutura de um produto específico (AJAX)
     */
    public function getEstruturaProduto() {
        try {
            $produtoId = $_GET['produto_id'] ?? null;
            
            if (empty($produtoId) || !is_numeric($produtoId)) {
                throw new InvalidArgumentException("ID do produto inválido");
            }
            
            $produto = $this->mrpModel->getProdutoById($produtoId);
            if (!$produto) {
                throw new Exception("Produto não encontrado");
            }
            
            $estrutura = $this->mrpModel->getEstruturaProduto($produtoId);
            
            $this->jsonResponse([
                'success' => true,
                'produto' => $produto,
                'estrutura' => $estrutura
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    
    /**
     * Coleta demandas do formulário
     */
    private function coletarDemandas() {
        $demandas = [];
        $produtos = $this->mrpModel->getProdutos();
        
        foreach ($produtos as $produto) {
            $quantidade = $this->getPostData("produto_{$produto['id']}", 0);
            if (is_numeric($quantidade) && $quantidade > 0) {
                $demandas[$produto['id']] = (int)$quantidade;
            }
        }
        
        return $demandas;
    }
    
    /**
     * Exporta relatório em formato JSON
     */
    private function exportarJson($relatorio) {
        $filename = "mrp_relatorio_" . date('Y-m-d_H-i-s') . ".json";
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        
        echo json_encode($relatorio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Exporta relatório em formato CSV
     */
    private function exportarCsv($relatorio) {
        $filename = "mrp_relatorio_" . date('Y-m-d_H-i-s') . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        // Cabeçalho
        fputcsv($output, [
            'Produto',
            'Componente', 
            'Necessário',
            'Em Estoque',
            'A Comprar',
            'Situação'
        ], ';');
        
        // Dados
        foreach ($relatorio['necessidades']['detalhes'] as $produto) {
            foreach ($produto['necessidades'] as $necessidade) {
                fputcsv($output, [
                    $produto['produto']['nome'],
                    $necessidade['componente_nome'],
                    $necessidade['necessario'],
                    $necessidade['em_estoque'],
                    $necessidade['a_comprar'],
                    $necessidade['situacao'] === 'comprar' ? 'Comprar' : 'Suficiente'
                ], ';');
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Helpers e Utilities
     */
    
    private function getPostData($key, $default = null) {
        return $_POST[$key] ?? $default;
    }
    
    private function addError($message) {
        $this->errors[] = $message;
        $_SESSION['errors'] = $this->errors;
    }
    
    private function addSuccess($message) {
        $this->success[] = $message;
        $_SESSION['success'] = $this->success;
    }
    
    private function redirect($url) {
        header("Location: " . $url);
        exit;
    }
    
    private function jsonResponse($data, $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    private function renderView($viewName, $data = []) {
        // Extrai variáveis para a view
        extract($data);
        
        // Carrega mensagens da sessão
        if (isset($_SESSION['errors'])) {
            $errors = $_SESSION['errors'];
            unset($_SESSION['errors']);
        } else {
            $errors = $this->errors;
        }
        
        if (isset($_SESSION['success'])) {
            $success = $_SESSION['success'];
            unset($_SESSION['success']);
        } else {
            $success = $this->success;
        }
        
        // Inclui header
        include __DIR__ . '/../views/layout/header.php';
        
        // Inclui view específica
        $viewFile = __DIR__ . "/../views/{$viewName}.php";
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo "<div class='alert alert-error'>View '{$viewName}' não encontrada</div>";
        }
        
        // Inclui footer
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    /**
     * Gera sugestões baseadas nos problemas encontrados
     */
    private function gerarSugestoes($relatorio) {
        $sugestoes = [];
        
        if (!empty($relatorio['necessidades']['resumo_compras'])) {
            $sugestoes[] = "Considere negociar descontos para compras em lote";
            $sugestoes[] = "Verifique prazos de entrega dos fornecedores";
        }
        
        if ($relatorio['viabilidade']['viavel']) {
            $sugestoes[] = "Produção pode ser iniciada imediatamente";
        } else {
            $sugestoes[] = "Aguarde chegada dos componentes em falta";
            $sugestoes[] = "Considere produção parcial com itens disponíveis";
        }
        
        return $sugestoes;
    }
}
?>