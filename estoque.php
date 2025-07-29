<?php
header("Content-Type: text/html; charset=UTF-8");
/**
 * Página de Gestão de Estoque
 * Controla operações CRUD de componentes em estoque
 */

// Configuração e includes
session_start();
require_once 'config/database.php';
require_once 'controllers/EstoqueController.php';

// Instancia o controller
$controller = new EstoqueController();

// Processa ações
$action = $_POST['action'] ?? $_GET['action'] ?? 'index';

try {
    switch ($action) {
        case 'atualizar':
            $controller->atualizar();
            break;
            
        case 'adicionar':
            $controller->adicionar();
            break;
            
        case 'get_componente':
            $controller->getComponente();
            break;
            
        case 'index':
        default:
            $controller->index();
            break;
    }
    
} catch (Exception $e) {
    // Log do erro
    error_log("Erro na página de estoque: " . $e->getMessage());
    
    // Redireciona com erro
    $_SESSION['errors'] = ["Erro interno: " . $e->getMessage()];
    header("Location: estoque.php");
    exit;
}
?>
