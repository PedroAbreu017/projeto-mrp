<?php
/**
 * Controller: EstoqueController
 * Gerencia as operações de estoque seguindo padrão MVC
 */

require_once __DIR__ . '/../models/Estoque.php';

class EstoqueController {
    private $estoqueModel;
    private $errors = [];
    private $success = [];
    
    public function __construct() {
        $this->estoqueModel = new Estoque();
    }
    
    /**
     * Exibe a página principal do estoque
     */
    public function index() {
        try {
            $componentes = $this->estoqueModel->getAllComponentes();
            $estatisticas = $this->estoqueModel->getEstatisticasEstoque();
            $estoqueBaixo = $this->estoqueModel->getComponentesEstoqueBaixo(5);
            
            // Prepara dados para a view
            $data = [
                'componentes' => $componentes,
                'estatisticas' => $estatisticas,
                'estoque_baixo' => $estoqueBaixo,
                'title' => 'Gestão de Estoque',
                'errors' => $this->errors,
                'success' => $this->success
            ];
            
            $this->renderView('estoque/index', $data);
            
        } catch (Exception $e) {
            $this->addError("Erro ao carregar estoque: " . $e->getMessage());
            $this->renderView('estoque/index', ['errors' => $this->errors]);
        }
    }
    
    /**
     * Processa atualização de estoque via POST
     */
    public function atualizar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/estoque');
            return;
        }
        
        try {
            $id = $this->getPostData('id');
            $quantidade = $this->getPostData('quantidade');
            
            // Validação
            if (empty($id) || !is_numeric($id)) {
                throw new InvalidArgumentException("ID do componente inválido");
            }
            
            if (!is_numeric($quantidade) || $quantidade < 0) {
                throw new InvalidArgumentException("Quantidade deve ser um número positivo");
            }
            
            // Atualiza estoque
            $this->estoqueModel->atualizarEstoque($id, $quantidade);
            
            // Busca nome do componente para feedback
            $componente = $this->estoqueModel->getComponenteById($id);
            $this->addSuccess("Estoque de '{$componente['nome']}' atualizado para {$quantidade} unidades");
            
        } catch (Exception $e) {
            $this->addError("Erro ao atualizar estoque: " . $e->getMessage());
        }
        
        $this->redirect('/estoque');
    }
    
    /**
     * Adiciona novo componente
     */
    public function adicionar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/estoque');
            return;
        }
        
        try {
            $nome = trim($this->getPostData('nome'));
            $descricao = trim($this->getPostData('descricao', ''));
            $quantidade = $this->getPostData('quantidade', 0);
            $unidade = trim($this->getPostData('unidade', 'unidade'));
            
            // Validação usando o model
            $dados = [
                'nome' => $nome,
                'descricao' => $descricao,
                'quantidade_estoque' => $quantidade,
                'unidade_medida' => $unidade
            ];
            
            $erros = $this->estoqueModel->validarDadosComponente($dados);
            if (!empty($erros)) {
                foreach ($erros as $erro) {
                    $this->addError($erro);
                }
                throw new Exception("Dados inválidos");
            }
            
            // Adiciona componente
            $novoId = $this->estoqueModel->adicionarComponente($nome, $descricao, $quantidade, $unidade);
            $this->addSuccess("Componente '{$nome}' adicionado com sucesso");
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Dados inválidos') === false) {
                $this->addError("Erro ao adicionar componente: " . $e->getMessage());
            }
        }
        
        $this->redirect('/estoque');
    }
    
    /**
     * Remove componente
     */
    public function remover() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/estoque');
            return;
        }
        
        try {
            $id = $this->getPostData('id');
            
            if (empty($id) || !is_numeric($id)) {
                throw new InvalidArgumentException("ID do componente inválido");
            }
            
            // Busca nome antes de remover para feedback
            $componente = $this->estoqueModel->getComponenteById($id);
            if (!$componente) {
                throw new Exception("Componente não encontrado");
            }
            
            $this->estoqueModel->removerComponente($id);
            $this->addSuccess("Componente '{$componente['nome']}' removido com sucesso");
            
        } catch (Exception $e) {
            $this->addError("Erro ao remover componente: " . $e->getMessage());
        }
        
        $this->redirect('/estoque');
    }
    
    /**
     * Retorna dados de um componente específico (AJAX)
     */
    public function getComponente() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['error' => 'Método não permitido'], 405);
            return;
        }
        
        try {
            $id = $_GET['id'] ?? null;
            
            if (empty($id) || !is_numeric($id)) {
                throw new InvalidArgumentException("ID inválido");
            }
            
            $componente = $this->estoqueModel->getComponenteById($id);
            
            if (!$componente) {
                throw new Exception("Componente não encontrado");
            }
            
            $this->jsonResponse(['success' => true, 'data' => $componente]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    
    /**
     * Busca componentes com estoque baixo (AJAX)
     */
    public function getEstoqueBaixo() {
        try {
            $limite = $_GET['limite'] ?? 5;
            $componentes = $this->estoqueModel->getComponentesEstoqueBaixo($limite);
            
            $this->jsonResponse([
                'success' => true, 
                'data' => $componentes,
                'count' => count($componentes)
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
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
        header('Content-Type: application/json');
        echo json_encode($data);
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
     * Método para validar acesso (se implementar autenticação)
     */
    private function checkAuth() {
        // Placeholder para futuras implementações de autenticação
        return true;
    }
    
    /**
     * Sanitiza dados de entrada
     */
    private function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
?>