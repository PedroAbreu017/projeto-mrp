<?php
/**
 * View: Gestão de Estoque 
 */
?>

<!-- Cabeçalho da página -->
<div class="content-header animate-fade-in-up">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="content-title">
                <i class="fas fa-boxes text-primary-modern"></i>
                Gestão de Estoque
            </h1>
            <p class="content-subtitle">Gerencie componentes e quantidades em estoque com controle total</p>
        </div>
        <button class="btn btn-primary-modern" data-bs-toggle="modal" data-bs-target="#modalNovoComponente">
            <i class="fas fa-plus me-2"></i>
            Novo Componente
        </button>
    </div>
</div>

<!-- Cards de estatísticas -->
<?php if (isset($estatisticas)): ?>
<div class="row g-4 animate-fade-in-up" style="animation-delay: 0.2s;">
    <div class="col-lg-3 col-md-6">
        <div class="dashboard-card card-primary">
            <div class="card-body-modern">
                <div class="card-content-modern">
                    <h6 class="card-title-modern">Total de Componentes</h6>
                    <h2 class="card-value-modern"><?= $estatisticas['total_componentes'] ?></h2>
                    <p class="card-subtitle-modern">Tipos cadastrados</p>
                </div>
                <div class="card-icon-modern icon-primary">
                    <i class="fas fa-cube"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="dashboard-card card-success">
            <div class="card-body-modern">
                <div class="card-content-modern">
                    <h6 class="card-title-modern">Total de Itens</h6>
                    <h2 class="card-value-modern"><?= number_format($estatisticas['total_itens'], 0, ',', '.') ?></h2>
                    <p class="card-subtitle-modern">Unidades em estoque</p>
                </div>
                <div class="card-icon-modern icon-success">
                    <i class="fas fa-cubes"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="dashboard-card card-warning">
            <div class="card-body-modern">
                <div class="card-content-modern">
                    <h6 class="card-title-modern">Estoque Baixo</h6>
                    <h2 class="card-value-modern"><?= $estatisticas['estoque_baixo'] ?></h2>
                    <p class="card-subtitle-modern">≤ 5 unidades</p>
                </div>
                <div class="card-icon-modern icon-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="dashboard-card card-danger">
            <div class="card-body-modern">
                <div class="card-content-modern">
                    <h6 class="card-title-modern">Sem Estoque</h6>
                    <h2 class="card-value-modern"><?= $estatisticas['sem_estoque'] ?></h2>
                    <p class="card-subtitle-modern">Componentes zerados</p>
                </div>
                <div class="card-icon-modern icon-danger">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Alerta para componentes com estoque baixo -->
<?php if (!empty($estoque_baixo)): ?>
<div class="mt-4 animate-fade-in-up" style="animation-delay: 0.4s;">
    <div class="alert alert-warning-modern">
        <div class="d-flex align-items-start">
            <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem; margin-top: 0.25rem;"></i>
            <div class="flex-grow-1">
                <h6 class="mb-2">Atenção: Componentes com Estoque Baixo</h6>
                <div class="row">
                    <?php foreach ($estoque_baixo as $componente): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                            <span class="badge badge-warning-modern">
                                <?= htmlspecialchars($componente['nome']) ?>: <?= $componente['quantidade_estoque'] ?> unidades
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <hr class="my-3">
                <p class="mb-0">
                    <small>Considere reabastecer estes componentes para evitar paradas na produção.</small>
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tabela de componentes -->
<div class="main-content mt-4 animate-fade-in-up" style="animation-delay: 0.6s;">
    <div class="content-header">
        <h3 class="content-title">
            <i class="fas fa-list text-primary-modern"></i>
            Componentes em Estoque
        </h3>
        <p class="content-subtitle">Visualize e gerencie todos os componentes cadastrados</p>
    </div>
    
    <div class="content-body p-0">
        <?php if (!empty($componentes)): ?>
            <div class="table-modern">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>
                                <i class="fas fa-tag me-2"></i>
                                Componente
                            </th>
                            <th>
                                <i class="fas fa-info-circle me-2"></i>
                                Descrição
                            </th>
                            <th class="text-center">
                                <i class="fas fa-sort-numeric-down me-2"></i>
                                Quantidade
                            </th>
                            <th class="text-center">
                                <i class="fas fa-ruler me-2"></i>
                                Unidade
                            </th>
                            <th class="text-center">
                                <i class="fas fa-traffic-light me-2"></i>
                                Status
                            </th>
                            <th>
                                <i class="fas fa-clock me-2"></i>
                                Atualizado em
                            </th>
                            <th class="text-center">
                                <i class="fas fa-cogs me-2"></i>
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($componentes as $componente): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="card-icon-modern icon-primary me-3" style="width: 35px; height: 35px; font-size: 0.9rem;">
                                            <i class="fas fa-cube"></i>
                                        </div>
                                        <strong><?= htmlspecialchars($componente['nome']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted">
                                        <?= htmlspecialchars($componente['descricao'] ?: 'Sem descrição') ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-<?= $componente['quantidade_estoque'] == 0 ? 'danger' : ($componente['quantidade_estoque'] <= 5 ? 'warning' : 'success') ?>-modern fs-6">
                                        <?= number_format($componente['quantidade_estoque'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <small class="text-muted fw-semibold"><?= htmlspecialchars($componente['unidade_medida']) ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if ($componente['quantidade_estoque'] == 0): ?>
                                        <span class="badge badge-danger-modern">
                                            <i class="fas fa-times me-1"></i>
                                            Sem Estoque
                                        </span>
                                    <?php elseif ($componente['quantidade_estoque'] <= 5): ?>
                                        <span class="badge badge-warning-modern">
                                            <i class="fas fa-exclamation me-1"></i>
                                            Estoque Baixo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success-modern">
                                            <i class="fas fa-check me-1"></i>
                                            OK
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('d/m/Y', strtotime($componente['updated_at'])) ?>
                                        <br>
                                        <i class="fas fa-clock me-1"></i>
                                        <?= date('H:i', strtotime($componente['updated_at'])) ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-outline-primary btn-sm rounded-modern" 
                                            onclick="atualizarEstoque(<?= $componente['id'] ?>, '<?= htmlspecialchars($componente['nome']) ?>', <?= $componente['quantidade_estoque'] ?>)"
                                            data-bs-toggle="tooltip" 
                                            title="Atualizar Estoque">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="card-icon-modern icon-primary mb-4 mx-auto" style="width: 80px; height: 80px; font-size: 2rem;">
                    <i class="fas fa-inbox"></i>
                </div>
                <h5 class="text-muted mb-3">Nenhum componente cadastrado</h5>
                <p class="text-muted mb-4">Comece adicionando seu primeiro componente ao sistema</p>
                <button class="btn btn-primary-modern" data-bs-toggle="modal" data-bs-target="#modalNovoComponente">
                    <i class="fas fa-plus me-2"></i>
                    Adicionar Primeiro Componente
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Novo Componente -->
<div class="modal fade" id="modalNovoComponente" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-modern shadow-lg-modern">
            <form method="POST" action="estoque.php" class="form-modern">
                <input type="hidden" name="action" value="adicionar">
                
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>
                        Novo Componente
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-12">
                            <label for="nome" class="form-label">
                                <i class="fas fa-tag me-2"></i>
                                Nome do Componente *
                            </label>
                            <input type="text" class="form-control" id="nome" name="nome" required 
                                   placeholder="Ex: Rodas, Gabinetes, Processadores...">
                        </div>
                        
                        <div class="col-12">
                            <label for="descricao" class="form-label">
                                <i class="fas fa-info-circle me-2"></i>
                                Descrição
                            </label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3" 
                                      placeholder="Descrição detalhada do componente (opcional)"></textarea>
                        </div>
                        
                        <div class="col-md-8">
                            <label for="quantidade" class="form-label">
                                <i class="fas fa-sort-numeric-down me-2"></i>
                                Quantidade Inicial
                            </label>
                            <div class="input-group input-group-modern">
                                <span class="input-group-text">
                                    <i class="fas fa-hashtag"></i>
                                </span>
                                <input type="number" class="form-control" id="quantidade" 
                                       name="quantidade" min="0" value="0" placeholder="0">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="unidade" class="form-label">
                                <i class="fas fa-ruler me-2"></i>
                                Unidade
                            </label>
                            <select class="form-select" id="unidade" name="unidade">
                                <option value="unidade">Unidade</option>
                                <option value="kg">Quilograma (Kg)</option>
                                <option value="metro">Metro (m)</option>
                                <option value="litro">Litro (L)</option>
                                <option value="caixa">Caixa</option>
                                <option value="pacote">Pacote</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-modern" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary-modern">
                        <i class="fas fa-save me-2"></i>
                        Salvar Componente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Container para modal dinâmico -->
<div id="modalContainer"></div>

<!-- JavaScript específico da página -->
<script>
// Função atualizarEstoque com modal dinâmico moderno
function atualizarEstoque(id, nome, quantidadeAtual) {
    console.log('🔧 Iniciando atualização:', id, nome, quantidadeAtual);
    
    // Remove modal existente
    const modalExistente = document.getElementById('modalAtualizarEstoque');
    if (modalExistente) {
        modalExistente.remove();
    }
    
    // Cria novo modal com design moderno
    const modalHTML = `
        <div class="modal fade" id="modalAtualizarEstoque" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content rounded-modern shadow-lg-modern">
                    <form method="POST" action="estoque.php" class="form-modern">
                        <input type="hidden" name="action" value="atualizar">
                        <input type="hidden" name="id" value="${id}">
                        
                        <div class="modal-header bg-gradient-success text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-sync-alt me-2"></i>
                                Atualizar Estoque
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        
                        <div class="modal-body p-4">
                            <div class="alert alert-info-modern">
                                <div class="d-flex align-items-center">
                                    <div class="card-icon-modern icon-primary me-3" style="width: 40px; height: 40px; font-size: 1rem;">
                                        <i class="fas fa-cube"></i>
                                    </div>
                                    <div>
                                        <strong>Componente:</strong> ${nome}
                                        <br>
                                        <small class="text-muted">Quantidade atual: ${quantidadeAtual} unidades</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-0">
                                <label for="novaQuantidade" class="form-label">
                                    <i class="fas fa-sort-numeric-down me-2"></i>
                                    Nova Quantidade
                                </label>
                                <div class="input-group input-group-modern">
                                    <span class="input-group-text">
                                        <i class="fas fa-hashtag"></i>
                                    </span>
                                    <input type="number" class="form-control form-control-lg" id="novaQuantidade" 
                                           name="quantidade" min="0" required value="${quantidadeAtual}" 
                                           placeholder="Digite a nova quantidade">
                                    <span class="input-group-text">
                                        unidades
                                    </span>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Altere para a quantidade correta em estoque
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-outline-modern" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-success-modern">
                                <i class="fas fa-check me-2"></i>
                                Atualizar Estoque
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    // Insere modal no container
    const container = document.getElementById('modalContainer') || document.body;
    container.insertAdjacentHTML('beforeend', modalHTML);
    
    // Abre modal
    setTimeout(() => {
        const novoModal = document.getElementById('modalAtualizarEstoque');
        const modal = new bootstrap.Modal(novoModal);
        
        // Foca no campo quando modal abrir
        novoModal.addEventListener('shown.bs.modal', function() {
            const inputQuantidade = document.getElementById('novaQuantidade');
            inputQuantidade.focus();
            inputQuantidade.select();
        }, { once: true });
        
        // Remove modal quando fechar
        novoModal.addEventListener('hidden.bs.modal', function() {
            novoModal.remove();
        }, { once: true });
        
        modal.show();
        console.log('✅ Modal moderno aberto');
    }, 100);
}

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl + N = Novo componente
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        const modal = new bootstrap.Modal(document.getElementById('modalNovoComponente'));
        modal.show();
    }
});

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Página de estoque moderna carregada');
    
    // Animar cards com delay
    const cards = document.querySelectorAll('.dashboard-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${0.1 + (index * 0.1)}s`;
        card.classList.add('animate-fade-in-scale');
    });
    
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>