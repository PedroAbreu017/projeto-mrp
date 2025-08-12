<?php
/**
 * Dashboard Principal - Design Moderno Inspirado em Interfaces Enterprise
 */

session_start();
header("Content-Type: text/html; charset=UTF-8");
mb_internal_encoding("UTF-8");

require_once 'src/config/database.php';

// Buscar estatísticas do sistema
try {
    $db = Database::getInstance();
    $conectado = true;
    
    // Estatísticas dos componentes
    $statsComponentes = $db->fetch("
        SELECT 
            COUNT(*) as total_componentes,
            SUM(quantidade_estoque) as total_itens,
            COUNT(CASE WHEN quantidade_estoque = 0 THEN 1 END) as sem_estoque,
            COUNT(CASE WHEN quantidade_estoque <= 5 THEN 1 END) as estoque_baixo
        FROM componentes
    ");
    
    // Produtos configurados
    $totalProdutos = $db->fetch("SELECT COUNT(*) as total FROM produtos WHERE ativo = TRUE");
    
    // Componentes com estoque baixo
    $componentesBaixo = $db->fetchAll("
        SELECT nome, quantidade_estoque 
        FROM componentes 
        WHERE quantidade_estoque <= 5 
        ORDER BY quantidade_estoque ASC 
        LIMIT 5
    ");
    
} catch (Exception $e) {
    $conectado = false;
    $erro = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema MRP</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- CSS Moderno Customizado -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        /* Garantir que o CSS moderno seja aplicado */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #fafafa;
        }
    </style>
</head>
<body>
    <!-- Navbar Moderna -->
    <nav class="navbar navbar-expand-lg navbar-modern">
        <div class="container-fluid">
            <a class="navbar-brand navbar-brand-modern" href="index.php">
                <i class="fas fa-industry"></i>
                Sistema MRP
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link nav-link-modern active" href="index.php">
                            <i class="fas fa-home"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-modern" href="estoque.php">
                            <i class="fas fa-boxes"></i>
                            Estoque
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-modern" href="mrp.php">
                            <i class="fas fa-calculator"></i>
                            Planejamento MRP
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-modern dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i>
                            Sistema
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="mostrarInfo()">
                                <i class="fas fa-info me-2"></i>Sobre o Sistema
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="mostrarAtalhos()">
                                <i class="fas fa-keyboard me-2"></i>Atalhos (F2)
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Conteúdo Principal -->
    <div class="content-wrapper">
        <div class="container-fluid">
            
            <!-- Cabeçalho do Dashboard -->
            <div class="content-header animate-fade-in-up">
                <h1 class="content-title">
                    <i class="fas fa-chart-line text-primary-modern"></i>
                    Dashboard
                </h1>
                <p class="content-subtitle">Visão geral do sistema de planejamento de materiais</p>
            </div>

            <?php if ($conectado): ?>
                <!-- Status de Conexão -->
                <div class="alert alert-success-modern animate-fade-in-scale">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <h6 class="mb-1">Sistema Operacional</h6>
                            <p class="mb-0">Conexão com banco de dados estabelecida. Todos os módulos funcionando perfeitamente.</p>
                        </div>
                    </div>
                </div>

                <!-- Cards de Estatísticas Principais -->
                <div class="row g-4 animate-fade-in-up" style="animation-delay: 0.2s;">
                    <!-- Total de Componentes -->
                    <div class="col-lg-3 col-md-6">
                        <div class="dashboard-card card-primary">
                            <div class="card-body-modern">
                                <div class="card-content-modern">
                                    <h6 class="card-title-modern">Total de Componentes</h6>
                                    <h2 class="card-value-modern"><?= $statsComponentes['total_componentes'] ?></h2>
                                    <p class="card-subtitle-modern">Tipos cadastrados</p>
                                </div>
                                <div class="card-icon-modern icon-primary">
                                    <i class="fas fa-cube"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total de Itens -->
                    <div class="col-lg-3 col-md-6">
                        <div class="dashboard-card card-success">
                            <div class="card-body-modern">
                                <div class="card-content-modern">
                                    <h6 class="card-title-modern">Total de Itens</h6>
                                    <h2 class="card-value-modern"><?= number_format($statsComponentes['total_itens'], 0, ',', '.') ?></h2>
                                    <p class="card-subtitle-modern">Unidades em estoque</p>
                                </div>
                                <div class="card-icon-modern icon-success">
                                    <i class="fas fa-cubes"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estoque Baixo -->
                    <div class="col-lg-3 col-md-6">
                        <div class="dashboard-card card-warning">
                            <div class="card-body-modern">
                                <div class="card-content-modern">
                                    <h6 class="card-title-modern">Estoque Baixo</h6>
                                    <h2 class="card-value-modern"><?= $statsComponentes['estoque_baixo'] ?></h2>
                                    <p class="card-subtitle-modern">Componentes ≤ 5 unidades</p>
                                </div>
                                <div class="card-icon-modern icon-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sem Estoque -->
                    <div class="col-lg-3 col-md-6">
                        <div class="dashboard-card card-danger">
                            <div class="card-body-modern">
                                <div class="card-content-modern">
                                    <h6 class="card-title-modern">Sem Estoque</h6>
                                    <h2 class="card-value-modern"><?= $statsComponentes['sem_estoque'] ?></h2>
                                    <p class="card-subtitle-modern">Componentes zerados</p>
                                </div>
                                <div class="card-icon-modern icon-danger">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção de Ações Rápidas -->
                <div class="row g-4 mt-4 animate-fade-in-up" style="animation-delay: 0.4s;">
                    <div class="col-lg-8">
                        <div class="main-content">
                            <div class="content-header">
                                <h3 class="content-title">
                                    <i class="fas fa-rocket text-primary-modern"></i>
                                    Ações Rápidas
                                </h3>
                                <p class="content-subtitle">Acesse rapidamente as principais funcionalidades do sistema</p>
                            </div>
                            <div class="content-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="d-grid">
                                            <a href="estoque.php" class="btn btn-primary-modern btn-lg">
                                                <i class="fas fa-boxes"></i>
                                                Gerenciar Estoque
                                            </a>
                                            <small class="text-muted mt-2 text-center">
                                                <kbd>Ctrl + Shift + E</kbd>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-grid">
                                            <a href="mrp.php" class="btn btn-success-modern btn-lg">
                                                <i class="fas fa-calculator"></i>
                                                Calcular MRP
                                            </a>
                                            <small class="text-muted mt-2 text-center">
                                                <kbd>Ctrl + Shift + M</kbd>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="main-content">
                            <div class="content-header">
                                <h3 class="content-title">
                                    <i class="fas fa-info-circle text-warning-modern"></i>
                                    Status do Sistema
                                </h3>
                            </div>
                            <div class="content-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="card-icon-modern icon-success me-3" style="width: 40px; height: 40px; font-size: 1rem;">
                                        <i class="fas fa-database"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Banco de Dados</h6>
                                        <small class="text-success-modern">Conectado</small>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="card-icon-modern icon-primary me-3" style="width: 40px; height: 40px; font-size: 1rem;">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Sistema MRP</h6>
                                        <small class="text-primary-modern">Operacional</small>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center">
                                    <div class="card-icon-modern icon-success me-3" style="width: 40px; height: 40px; font-size: 1rem;">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Segurança</h6>
                                        <small class="text-success-modern">Ativa</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerta de Componentes com Estoque Baixo -->
                <?php if (!empty($componentesBaixo)): ?>
                <div class="mt-4 animate-fade-in-up" style="animation-delay: 0.6s;">
                    <div class="alert alert-warning-modern">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem; margin-top: 0.25rem;"></i>
                            <div class="flex-grow-1">
                                <h6 class="mb-2">Atenção: Componentes com Estoque Baixo</h6>
                                <div class="row">
                                    <?php foreach ($componentesBaixo as $componente): ?>
                                        <div class="col-md-4 mb-2">
                                            <span class="badge badge-warning-modern">
                                                <?= htmlspecialchars($componente['nome']) ?>: <?= $componente['quantidade_estoque'] ?> unidades
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <hr class="my-3">
                                <p class="mb-0">
                                    <small>Considere reabastecer estes componentes para evitar paradas na produção.</small>
                                    <a href="estoque.php" class="btn btn-outline-modern btn-sm ms-3">
                                        <i class="fas fa-arrow-right me-1"></i>
                                        Gerenciar Estoque
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Exemplo de Uso -->
                <div class="mt-4 animate-fade-in-up" style="animation-delay: 0.8s;">
                    <div class="main-content">
                        <div class="content-header">
                            <h3 class="content-title">
                                <i class="fas fa-play-circle text-success-modern"></i>
                                Exemplo Prático
                            </h3>
                            <p class="content-subtitle">Demonstração do cálculo MRP com dados reais</p>
                        </div>
                        <div class="content-body">
                            <div class="row align-items-center">
                                <div class="col-lg-8">
                                    <h5>Cenário: Produção de 6 Bicicletas + 3 Computadores</h5>
                                    <p class="text-muted mb-3">Com o estoque atual, o sistema calcula automaticamente:</p>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="p-3 bg-light rounded-modern">
                                                <h6 class="text-danger-modern mb-2">
                                                    <i class="fas fa-shopping-cart me-2"></i>
                                                    Necessário Comprar
                                                </h6>
                                                <ul class="list-unstyled mb-0">
                                                    <li><span class="badge badge-warning-modern">2 Rodas</span></li>
                                                    <li><span class="badge badge-warning-modern">1 Gabinete</span></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="p-3 bg-light rounded-modern">
                                                <h6 class="text-success-modern mb-2">
                                                    <i class="fas fa-check-circle me-2"></i>
                                                    Estoque Suficiente
                                                </h6>
                                                <ul class="list-unstyled mb-0">
                                                    <li><span class="badge badge-success-modern">Guidões</span></li>
                                                    <li><span class="badge badge-success-modern">Quadros</span></li>
                                                    <li><span class="badge badge-success-modern">Placas-mãe</span></li>
                                                    <li><span class="badge badge-success-modern">Memórias RAM</span></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 text-center">
                                    <a href="mrp.php" class="btn btn-success-modern btn-lg">
                                        <i class="fas fa-calculator me-2"></i>
                                        Testar Exemplo
                                    </a>
                                    <p class="text-muted mt-2 mb-0">
                                        <small>Experimente o cálculo automático</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Erro de Conexão -->
                <div class="alert alert-danger-modern animate-fade-in-scale">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <h6 class="mb-1">Erro de Conexão com Banco de Dados</h6>
                            <p class="mb-2"><strong>Erro:</strong> <?= htmlspecialchars($erro) ?></p>
                            
                            <h6 class="mt-3 mb-2">💡 Soluções:</h6>
                            <ul class="mb-0">
                                <li>Verifique se MySQL está rodando: <code>net start mysql</code></li>
                                <li>Teste conexão: <code>mysql -u root -p</code></li>
                                <li>Verifique configuração em <code>config/database.php</code></li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    
    <script>
        function mostrarInfo() {
            const info = `
                🏭 Sistema MRP v2.0
                
                📋 Funcionalidades:
                • Gestão completa de estoque
                • Cálculo automático de necessidades (MRP)
                • Interface moderna e responsiva
                • Relatórios em tempo real
                
                🛠️ Tecnologias:
                • Backend: PHP 8+ com arquitetura MVC
                • Banco: MySQL com prepared statements
                • Frontend: Bootstrap 5 + CSS moderno
                • Segurança: Validações e sanitização
                
                ⚡ Performance:
                • Cálculos instantâneos
                • Interface responsiva
                • Otimizado para produção
            `;
            
            if (window.MRPSystem && window.MRPSystem.showModal) {
                window.MRPSystem.showModal('Sobre o Sistema MRP', info.replace(/\n/g, '<br>'));
            } else {
                alert(info);
            }
        }
        
        function mostrarAtalhos() {
            if (window.MRPSystem && window.MRPSystem.showHelp) {
                window.MRPSystem.showHelp();
            } else {
                const atalhos = `
                    ⌨️ Atalhos do Sistema:
                    
                    F2 - Mostrar ajuda
                    Ctrl+Shift+E - Ir para Estoque
                    Ctrl+Shift+M - Ir para MRP
                    Ctrl+Shift+H - Voltar ao Dashboard
                    Ctrl+S - Salvar formulário ativo
                    Esc - Fechar modal aberto
                    
                    💡 Dica: Navegue rapidamente entre as páginas usando os atalhos!
                `;
                alert(atalhos);
            }
        }
        
        // Atalhos de teclado para o dashboard
        document.addEventListener('keydown', function(e) {
            // F2 = Ajuda
            if (e.key === 'F2') {
                e.preventDefault();
                mostrarAtalhos();
            }
            
            // Ctrl + Shift + E = Estoque
            if (e.ctrlKey && e.shiftKey && e.key === 'E') {
                e.preventDefault();
                window.location.href = 'estoque.php';
            }
            
            // Ctrl + Shift + M = MRP
            if (e.ctrlKey && e.shiftKey && e.key === 'M') {
                e.preventDefault();
                window.location.href = 'mrp.php';
            }
            
            // Ctrl + Shift + H = Home
            if (e.ctrlKey && e.shiftKey && e.key === 'H') {
                e.preventDefault();
                window.location.href = 'index.php';
            }
        });
        
        // Animações escalonadas dos cards
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ Dashboard moderno carregado');
            
            // Animar cards com delay
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${0.1 + (index * 0.1)}s`;
                card.classList.add('animate-fade-in-scale');
            });
        });
    </script>
</body>
</html>