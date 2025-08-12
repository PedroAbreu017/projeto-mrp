<?php
namespace App\Controllers\Api;

// ✅ CORREÇÃO: Namespace correto para estrutura enterprise
use App\Services\EstoqueService;

/**
 * ComponentController - REFATORADO
 * Thin Controller que delega para Services - Arquitetura Enterprise
 */
class ComponentController {
    private $estoqueService;
    
    public function __construct() {
        $this->carregarEnvDireto();
        $this->estoqueService = new EstoqueService();
    }   

    private function carregarEnvDireto() {
        if (empty($_ENV['DB_NAME'])) {
            $envFile = __DIR__ . '/../../../.env';
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
     * GET /api/components
     * Listar todos os componentes
     */
    public function index() {
        // Pegar filtros da query string se houver
        $filtros = [
            'estoque_baixo' => $_GET['estoque_baixo'] ?? null,
            'busca' => $_GET['busca'] ?? null
        ];
        
        $result = $this->estoqueService->listarComponentes($filtros);
        
        return $result['success'] 
            ? $this->jsonResponse($result) 
            : $this->jsonResponse(['error' => $result['error']], 500);
    }
    
    /**
     * GET /api/components/{id}
     * Buscar componente específico
     */
    public function show($id) {
        $result = $this->estoqueService->buscarComponente($id);
        
        if ($result['success']) {
            return $this->jsonResponse($result);
        } else {
            $status = $result['error'] === 'Componente não encontrado' ? 404 : 400;
            return $this->jsonResponse(['error' => $result['error']], $status);
        }
    }
    
    /**
     * POST /api/components
     * Criar novo componente
     */
    public function store() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            return $this->jsonResponse(['error' => 'Dados JSON inválidos'], 400);
        }
        
        $result = $this->estoqueService->criarComponente($input);
        
        if ($result['success']) {
            return $this->jsonResponse($result, 201);
        } else {
            return $this->jsonResponse([
                'error' => $result['error'],
                'details' => $result['details'] ?? null
            ], 400);
        }
    }
    
    /**
     * PUT /api/components/{id}
     * Atualizar componente existente
     */
    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            return $this->jsonResponse(['error' => 'Dados JSON inválidos'], 400);
        }
        
        $result = $this->estoqueService->atualizarComponente($id, $input);
        
        if ($result['success']) {
            return $this->jsonResponse($result);
        } else {
            $status = $result['error'] === 'Componente não encontrado' ? 404 : 400;
            return $this->jsonResponse([
                'error' => $result['error'],
                'details' => $result['details'] ?? null
            ], $status);
        }
    }
    
    /**
     * DELETE /api/components/{id}
     * Remover componente
     */
    public function destroy($id) {
        $result = $this->estoqueService->removerComponente($id);
        
        if ($result['success']) {
            return $this->jsonResponse($result);
        } else {
            $status = $result['error'] === 'Componente não encontrado' ? 404 : 400;
            return $this->jsonResponse([
                'error' => $result['error'],
                'details' => $result['details'] ?? null
            ], $status);
        }
    }
    
    /**
     * PUT /api/components/{id}/estoque
     * Atualizar apenas estoque de um componente
     */
    public function updateStock($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['quantidade'])) {
            return $this->jsonResponse(['error' => 'Campo "quantidade" é obrigatório'], 400);
        }
        
        $motivo = $input['motivo'] ?? 'Ajuste manual via API';
        $result = $this->estoqueService->atualizarEstoque($id, $input['quantidade'], $motivo);
        
        if ($result['success']) {
            return $this->jsonResponse($result);
        } else {
            $status = $result['error'] === 'Componente não encontrado' ? 404 : 400;
            return $this->jsonResponse(['error' => $result['error']], $status);
        }
    }
    
    /**
     * GET /api/components/estoque-baixo
     * Buscar componentes com estoque baixo
     */
    public function lowStock() {
        $limite = $_GET['limite'] ?? 5;
        
        if (!is_numeric($limite)) {
            return $this->jsonResponse(['error' => 'Limite deve ser numérico'], 400);
        }
        
        $result = $this->estoqueService->verificarEstoqueBaixo($limite);
        
        return $result['success'] 
            ? $this->jsonResponse($result) 
            : $this->jsonResponse(['error' => $result['error']], 500);
    }
    
    /**
     * GET /api/components/estatisticas
     * Obter estatísticas do estoque
     */
    public function statistics() {
        $result = $this->estoqueService->calcularEstatisticas();
        
        return $result['success'] 
            ? $this->jsonResponse($result) 
            : $this->jsonResponse(['error' => $result['error']], 500);
    }
    
    /**
     * GET /api/components/health
     * Health check do sistema
     */
    public function health() {
        try {
            // Testar conexão com service
            $testResult = $this->estoqueService->calcularEstatisticas();
            
            $health = [
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'service' => $testResult['success'] ? 'OK' : 'ERROR',
                'database' => $testResult['success'] ? 'Connected' : 'Disconnected',
                'env' => !empty($_ENV['DB_NAME']) ? 'Loaded' : 'Missing',
                'version' => '2.0-enterprise',
                'namespace' => 'App\\Services\\EstoqueService'
            ];
            
            $statusCode = $testResult['success'] ? 200 : 503;
            
            return $this->jsonResponse($health, $statusCode);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ], 503);
        }
    }
    
    /**
     * GET /api/components/search
     * Buscar componentes por nome/descrição
     */
    public function search() {
        $busca = $_GET['q'] ?? '';
        
        if (empty($busca)) {
            return $this->jsonResponse(['error' => 'Parâmetro de busca "q" é obrigatório'], 400);
        }
        
        $filtros = ['busca' => $busca];
        $result = $this->estoqueService->listarComponentes($filtros);
        
        return $result['success'] 
            ? $this->jsonResponse($result) 
            : $this->jsonResponse(['error' => $result['error']], 500);
    }
    
    // ==================== MÉTODOS DE DEBUG ====================
    
    /**
     * 🚧 Método temporário de debug
     */
    public function debug($id) {
        $rawInput = file_get_contents('php://input');
        $method = $_SERVER['REQUEST_METHOD'];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? 'não definido';

        return $this->jsonResponse([
            'method' => $method,
            'content_type' => $contentType,
            'raw_input' => $rawInput,
            'raw_input_length' => strlen($rawInput),
            'decoded' => json_decode($rawInput, true),
            'json_error' => json_last_error_msg(),
            'headers' => getallheaders(),
            'service_status' => 'EstoqueService carregado',
            'env_loaded' => !empty($_ENV['DB_NAME']) ? 'Sim' : 'Não',
            'namespace_correto' => 'App\\Services\\EstoqueService'
        ]);
    }
    
    /**
     * 🧪 Método de teste para PUT
     */
    public function testResponse($id) {
        return $this->jsonResponse([
            'message' => 'PUT test OK',
            'id' => $id,
            'method' => $_SERVER['REQUEST_METHOD'],
            'timestamp' => date('Y-m-d H:i:s'),
            'service_integration' => 'OK',
            'namespace_correto' => 'App\\Services\\EstoqueService'
        ]);
    }
    
    // ==================== HELPER METHODS ====================
    
    /**
     * Helper para resposta JSON padronizada
     */
    private function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}