<?php
header("Content-Type: text/html; charset=UTF-8");
/**
 * Página de Planejamento MRP
 * Controla cálculos de necessidades de materiais
 */

// Configuração e includes
session_start();
require_once 'config/database.php';
require_once 'controllers/MrpController.php';

// Instancia o controller
$controller = new MrpController();

// Processa ações
$action = $_POST['action'] ?? $_GET['action'] ?? 'index';

try {
    switch ($action) {
        case 'calcular':
            $controller->calcular();
            break;
            
        case 'simular':
            $controller->simular();
            break;
            
        case 'estrutura':
            $controller->getEstruturaProduto();
            break;
            
        case 'index':
        default:
            $controller->index();
            break;
    }
    
} catch (Exception $e) {
    // Log do erro
    error_log("Erro na página de MRP: " . $e->getMessage());
    
    // Para requisições AJAX, retorna JSON
    if ($action === 'simular' || $action === 'estrutura') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Erro interno do servidor']);
        exit;
    }
    
    // Para outras requisições, redireciona com erro
    $_SESSION['errors'] = ["Erro interno: " . $e->getMessage()];
    header("Location: mrp.php");
    exit;
}
?>
