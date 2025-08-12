<?php
// public/index.php - Entry Point Corrigido

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && $line[0] !== '#') {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

// Helper function
function env($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

// Simple routing
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// ✅ API Routes
if (strpos($request_uri, '/api/') === 0) {
    header('Content-Type: application/json');

    // MRP Routes
    if ($request_uri === '/api/mrp/calculate' && $method === 'POST') {
        $controller = new \App\Controllers\Api\MrpController();
        $controller->calculate();
    }
    elseif ($request_uri === '/api/mrp/calculate-simple' && $method === 'POST') {
        $controller = new \App\Controllers\Api\MrpController();
        $controller->calculateSimple();
    }
    elseif ($request_uri === '/api/mrp/produtos' && $method === 'GET') {
        $controller = new \App\Controllers\Api\MrpController();
        $controller->getProdutos();
    }
    elseif ($request_uri === '/api/mrp/estrutura-completa' && $method === 'GET') {
        $controller = new \App\Controllers\Api\MrpController();
        $controller->getEstruturaCompleta();
    }
    elseif ($request_uri === '/api/mrp/simular' && $method === 'POST') {
        $controller = new \App\Controllers\Api\MrpController();
        $controller->simularProducao();
    }
    elseif (preg_match('#^/api/mrp/produto/(\d+)/estrutura$#', $request_uri, $matches) && $method === 'GET') {
        $produtoId = (int)$matches[1];
        $controller = new \App\Controllers\Api\MrpController();
        $controller->getEstruturaProduto($produtoId);
    }
    elseif ($request_uri === '/api/mrp/sugestoes' && $method === 'POST') {
        $controller = new \App\Controllers\Api\MrpController();
        $controller->getSugestoes();
    }

    // ===== COMPONENTS ROUTES =====
    
    // 🔥 NOVAS ROTAS ADICIONADAS
    elseif ($request_uri === '/api/components/health' && $method === 'GET') {
        $componentController = new \App\Controllers\Api\ComponentController();
        $componentController->health();
    }
    elseif ($request_uri === '/api/components/search' && $method === 'GET') {
        $componentController = new \App\Controllers\Api\ComponentController();
        $componentController->search();
    }
    
    // ROTAS EXISTENTES (MANTIDAS)
    elseif ($request_uri === '/api/components' && $method === 'GET') {
        $componentController = new \App\Controllers\Api\ComponentController();
        $componentController->index();
    }
    elseif ($request_uri === '/api/components' && $method === 'POST') {
        $componentController = new \App\Controllers\Api\ComponentController();
        $componentController->store();
   }
   elseif ($request_uri === '/api/components/estoque-baixo' && $method === 'GET') {
       $componentController = new \App\Controllers\Api\ComponentController();
       $componentController->lowStock();
    }
    elseif ($request_uri === '/api/components/estatisticas' && $method === 'GET') {
       $componentController = new \App\Controllers\Api\ComponentController();
       $componentController->statistics();
    }
    elseif ($request_uri === '/api/mrp/health' && $method === 'GET') {
    $controller = new \App\Controllers\Api\MrpController();
    $controller->health();
    }
    
    // DEBUG routes (manter para debug)
    elseif (preg_match('/^\/api\/components\/(\d+)\/debug$/', $request_uri, $matches) && $method === 'PUT') {
       $componentController = new \App\Controllers\Api\ComponentController();
       $componentController->debug($matches[1]);
    }
    elseif (preg_match('/^\/api\/components\/(\d+)\/test$/', $request_uri, $matches) && $method === 'PUT') {
       $componentController = new \App\Controllers\Api\ComponentController();
       $componentController->testResponse($matches[1]);
    }
    
    // CRUD routes
    elseif (preg_match('/^\/api\/components\/(\d+)$/', $request_uri, $matches) && $method === 'GET') {
       $componentController = new \App\Controllers\Api\ComponentController();
       $componentController->show($matches[1]);
    }
    elseif (preg_match('/^\/api\/components\/(\d+)$/', $request_uri, $matches) && $method === 'PUT') {
       $componentController = new \App\Controllers\Api\ComponentController();
       $componentController->update($matches[1]);
    }
    elseif (preg_match('/^\/api\/components\/(\d+)$/', $request_uri, $matches) && $method === 'DELETE') {
       $componentController = new \App\Controllers\Api\ComponentController();
       $componentController->destroy($matches[1]);
    }
    elseif (preg_match('/^\/api\/components\/(\d+)\/estoque$/', $request_uri, $matches) && $method === 'PUT') {
       $componentController = new \App\Controllers\Api\ComponentController();
       $componentController->updateStock($matches[1]);
    }
    
    // Test route
    elseif ($request_uri === '/api/test' && $method === 'GET') {
        echo json_encode([
            'success' => true,
            'message' => 'API teste funcionando!',
            'endpoints' => [
                'POST /api/mrp/calculate' => 'Calcular MRP',
                'GET /api/components' => 'Listar componentes',
                'GET /api/components/health' => 'Health check',
                'GET /api/components/search?q=termo' => 'Buscar componentes',
                'GET /api/test' => 'Teste da API'
            ]
        ]);
    }

    // Not Found
    else {
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found']);
    }

    exit;
}

// ✅ Web Routes (página principal)
if ($request_uri === '/' || $request_uri === '/index.php') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>MRP Enterprise Sistema</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .success { color: green; }
            .error { color: red; }
            .info { background: #f0f0f0; padding: 10px; margin: 10px 0; }
            .endpoint { background: #e8f4fd; padding: 8px; margin: 5px 0; border-radius: 4px; }
            a { color: #007cba; text-decoration: none; }
            a:hover { text-decoration: underline; }
            code { background: #f5f5f5; padding: 2px 4px; border-radius: 3px; }
        </style>
    </head>
    <body>
        <h1>🎯 MRP Enterprise System v2.0</h1>

        <div class="info">
            <h2>✅ Status do Sistema:</h2>
            <p class="success">✅ PHP Server: Funcionando</p>
            <p class="success">✅ Composer Autoloader: OK</p>
            <p class="success">✅ Service Layer: Implementado</p>
            <p class="success">✅ API Endpoints: Disponíveis</p>
        </div>

        <h2>🔗 Endpoints Disponíveis:</h2>
        <div class="endpoint">
            <strong>GET</strong> <a href="/api/test">/api/test</a> - Teste básico da API
        </div>
        <div class="endpoint">
            <strong>GET</strong> <a href="/api/components">/api/components</a> - Listar componentes
        </div>
        <div class="endpoint">
            <strong>GET</strong> <a href="/api/components/health">/api/components/health</a> - Health check
        </div>
        <div class="endpoint">
            <strong>GET</strong> <a href="/api/components/search?q=roda">/api/components/search?q=roda</a> - Buscar "roda"
        </div>
        <div class="endpoint">
            <strong>GET</strong> <a href="/api/components/estatisticas">/api/components/estatisticas</a> - Estatísticas
        </div>
        <div class="endpoint">
            <strong>GET</strong> <a href="/api/mrp/produtos">/api/mrp/produtos</a> - Produtos MRP
        </div>

        <h2>🧪 Testes Interativos:</h2>
        <button onclick="testMrpApi()">🎯 Testar MRP Calculation</button>
        <button onclick="testComponentsApi()">📦 Testar Components API</button>
        <button onclick="testHealthCheck()">🔍 Health Check</button>

        <div id="result" style="margin-top: 20px; padding: 10px; background: #f9f9f9; border-radius: 4px; display: none;">
            <h3>Resultado:</h3>
            <pre id="resultContent"></pre>
        </div>

        <h2>🏗️ Arquitetura Implementada:</h2>
        <ul>
            <li>✅ <strong>Service Layer Pattern</strong> - Business logic separada</li>
            <li>✅ <strong>Thin Controllers</strong> - Controllers limpos</li>
            <li>✅ <strong>PSR-4 Autoloading</strong> - Namespaces organizados</li>
            <li>✅ <strong>RESTful APIs</strong> - Endpoints padronizados</li>
            <li>✅ <strong>Error Handling</strong> - Tratamento consistente</li>
        </ul>

        <script>
        function showResult(data) {
            document.getElementById('result').style.display = 'block';
            document.getElementById('resultContent').textContent = JSON.stringify(data, null, 2);
        }

        function testMrpApi() {
            fetch('/api/mrp/calculate-simple', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ products: [1], quantities: [10] })
            })
            .then(response => response.json())
            .then(data => showResult(data))
            .catch(error => showResult({error: error.toString()}));
        }

        function testComponentsApi() {
            fetch('/api/components/search?q=roda')
            .then(response => response.json())
            .then(data => showResult(data))
            .catch(error => showResult({error: error.toString()}));
        }

        function testHealthCheck() {
            fetch('/api/components/health')
            .then(response => response.json())
            .then(data => showResult(data))
            .catch(error => showResult({error: error.toString()}));
        }
        </script>
    </body>
    </html>
    <?php
    exit;
}

// ❌ Página não encontrada
http_response_code(404);
echo "<h1>404 - Page Not Found</h1>";
?>