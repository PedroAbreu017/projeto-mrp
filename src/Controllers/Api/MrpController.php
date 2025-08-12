<?php
namespace App\Controllers\Api;

use App\Services\MrpService;

/**
 * MRP API Controller - REFATORADO
 * Thin Controller que delega para MrpService - Arquitetura Enterprise
 */
class MrpController {
    private $mrpService;
    
    public function __construct() {
        $this->carregarEnvDireto();
        $this->mrpService = new MrpService();
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
     * POST /api/mrp/calculate
     * Calcular necessidades MRP completo
     */
    public function calculate() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['demandas'])) {
            return $this->jsonResponse([
                'error' => 'Dados inválidos. Formato: {"demandas": {"1": 10, "2": 5}}'
            ], 400);
        }
        
        $result = $this->mrpService->gerarRelatorioCompleto($input['demandas']);
        
        return $result['success'] 
            ? $this->jsonResponse($result) 
            : $this->jsonResponse([
                'error' => $result['error'],
                'details' => $result['details'] ?? null
            ], 400);
    }
    
    /**
     * POST /api/mrp/calculate-simple
     * Versão simplificada para compatibilidade
     */
    public function calculateSimple() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['products']) || !isset($input['quantities'])) {
            return $this->jsonResponse([
                'error' => 'Formato: {"products": [1,2], "quantities": [10,5]}'
            ], 400);
        }
        
        $result = $this->mrpService->calcularNecessidadesSimples(
            $input['products'], 
            $input['quantities']
        );
        
        return $result['success'] 
            ? $this->jsonResponse($result) 
            : $this->jsonResponse([
                'error' => $result['error'],
                'details' => $result['details'] ?? null
            ], 400);
    }
    
    /**
     * GET /api/mrp/produtos
     * Listar todos os produtos disponíveis
     */
    public function getProdutos() {
        $result = $this->mrpService->listarProdutos();
        
        return $result['success'] 
            ? $this->jsonResponse($result) 
            : $this->jsonResponse(['error' => $result['error']], 500);
    }
    
    /**
     * GET /api/mrp/produto/{id}/estrutura
     * Buscar estrutura (BOM) de um produto
     */
    public function getEstruturaProduto($produtoId) {
        $result = $this->mrpService->obterEstruturaProduto($produtoId);
        
        if ($result['success']) {
            return $this->jsonResponse($result);
        } else {
            $status = $result['error'] === 'Produto não encontrado ou sem estrutura' ? 404 : 400;
            return $this->jsonResponse(['error' => $result['error']], $status);
        }
    }
    
    /**
     * POST /api/mrp/simular
     * Simular produção e impacto no estoque
     */
    public function simularProducao() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['demandas'])) {
            return $this->jsonResponse([
                'error' => 'Formato: {"demandas": {"1": 10, "2": 5}}'
            ], 400);
        }
        
        $result = $this->mrpService->simularProducao($input['demandas']);
        
        return $result['success'] 
            ? $this->jsonResponse($result) 
            : $this->jsonResponse(['error' => $result['error']], 500);
    }
    
    /**
     * GET /api/mrp/estrutura-completa
     * Buscar estrutura completa de todos os produtos
     */
    public function getEstruturaCompleta() {
        try {
            // Buscar todos os produtos
            $produtosResult = $this->mrpService->listarProdutos();
            
            if (!$produtosResult['success']) {
                return $this->jsonResponse(['error' => $produtosResult['error']], 500);
            }
            
            $estruturas = [];
            foreach ($produtosResult['data'] as $produto) {
                $estruturaResult = $this->mrpService->obterEstruturaProduto($produto['id']);
                if ($estruturaResult['success']) {
                    $estruturas[] = [
                        'produto' => $produto,
                        'estrutura' => $estruturaResult['data']
                    ];
                }
            }
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $estruturas,
                'total' => count($estruturas),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'error' => 'Erro ao obter estrutura completa: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/mrp/sugestoes
     * Obter sugestões para problemas de estoque
     */
    public function getSugestoes() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['demandas'])) {
            return $this->jsonResponse([
                'error' => 'Formato: {"demandas": {"1": 10, "2": 5}}'
            ], 400);
        }
        
        $result = $this->mrpService->gerarSugestoes($input['demandas']);
        
        return $result['success'] 
            ? $this->jsonResponse($result) 
            : $this->jsonResponse(['error' => $result['error']], 500);
    }
    
    /**
     * GET /api/mrp/health
     * Health check específico do MRP
     */
    public function health() {
        try {
            // Testar serviço MRP
            $testResult = $this->mrpService->listarProdutos();
            
            $health = [
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'service' => $testResult['success'] ? 'OK' : 'ERROR',
                'database' => $testResult['success'] ? 'Connected' : 'Disconnected',
                'produtos_disponveis' => $testResult['success'] ? count($testResult['data']) : 0,
                'version' => '2.0-enterprise',
                'namespace' => 'App\\Services\\MrpService'
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
     * Helper para resposta JSON
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