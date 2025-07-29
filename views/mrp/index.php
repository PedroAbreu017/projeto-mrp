<?php
/**
 * View: Planejamento MRP
 * Página principal para cálculo de necessidades de materiais
 */
?>

<!-- Cabeçalho da página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1">
            <i class="fas fa-calculator text-success me-2"></i>
            Planejamento MRP
        </h1>
        <p class="text-muted mb-0">Material Requirements Planning - Calcule necessidades de compra</p>
    </div>
</div>

<!-- Formulário de Planejamento -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-clipboard-list me-2"></i>
            Demanda de Produção
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="mrp.php" id="formMrp">
            <div class="row">
                <?php if (!empty($produtos)): ?>
                    <?php foreach ($produtos as $produto): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-cog me-2 text-primary"></i>
                                        <?= htmlspecialchars($produto['nome']) ?>
                                    </h6>
                                    <p class="card-text text-muted small mb-3">
                                        <?= htmlspecialchars($produto['descricao']) ?>
                                    </p>
                                    
                                    <div class="input-group">
                                        <span class="input-group-text">Quantidade</span>
                                        <input type="number" 
                                               class="form-control" 
                                               name="produto_<?= $produto['id'] ?>" 
                                               id="produto_<?= $produto['id'] ?>"
                                               min="0" 
                                               value="<?= isset($resultado['demandas_originais'][$produto['id']]) ? $resultado['demandas_originais'][$produto['id']] : 0 ?>"
                                               placeholder="0">
                                        <span class="input-group-text">unidades</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Nenhum produto encontrado. Verifique o banco de dados.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-calculator me-2"></i>
                    Calcular Necessidades de Material
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Resultado do Cálculo MRP -->
<?php if (isset($resultado) && $resultado): ?>
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-chart-line me-2"></i>
                Resultado do Cálculo MRP
                <small class="ms-2">
                    (<?= date('d/m/Y H:i', strtotime($resultado['data_calculo'])) ?>)
                </small>
            </h5>
        </div>
        <div class="card-body">
            
            <!-- Resumo Executivo -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h4 class="text-primary"><?= count($resultado['necessidades']['resumo_compras']) ?></h4>
                            <p class="mb-0">Componentes a Comprar</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h4 class="<?= $resultado['viabilidade']['viavel'] ? 'text-success' : 'text-danger' ?>">
                                <?= $resultado['viabilidade']['viavel'] ? 'VIÁVEL' : 'ATENÇÃO' ?>
                            </h4>
                            <p class="mb-0">Status da Produção</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h4 class="text-info"><?= $resultado['total_produtos'] ?></h4>
                            <p class="mb-0">Produtos Planejados</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Lista de Compras (Resumo) -->
            <?php if (!empty($resultado['necessidades']['resumo_compras'])): ?>
                <div class="alert alert-warning">
                    <h6 class="alert-heading">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Lista de Compras Necessárias
                    </h6>
                    <div class="row">
                        <?php foreach ($resultado['necessidades']['resumo_compras'] as $componente): ?>
                            <div class="col-md-4 mb-2">
                                <strong><?= htmlspecialchars($componente['nome']) ?>:</strong>
                                <span class="badge bg-warning text-dark fs-6">
                                    <?= number_format($componente['quantidade'], 0, ',', '.') ?> unidades
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <h6 class="alert-heading">
                        <i class="fas fa-check-circle me-2"></i>
                        Estoque Suficiente!
                    </h6>
                    <p class="mb-0">Todos os componentes necessários estão disponíveis em estoque. A produção pode ser iniciada imediatamente.</p>
                </div>
            <?php endif; ?>
            
            <!-- Detalhamento por Produto -->
            <h6 class="mt-4 mb-3">
                <i class="fas fa-list-alt me-2"></i>
                Detalhamento por Produto
            </h6>
            
            <div class="table-responsive">
                <table class="table table-sm mrp-result-table">
                    <thead class="table-dark">
                        <tr>
                            <th>Produto</th>
                            <th>Componente</th>
                            <th class="text-center">Necessário</th>
                            <th class="text-center">Em Estoque</th>
                            <th class="text-center">A Comprar</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultado['necessidades']['detalhes'] as $produto): ?>
                            <?php foreach ($produto['necessidades'] as $index => $necessidade): ?>
                                <tr class="<?= $necessidade['a_comprar'] > 0 ? 'needs-purchase' : 'sufficient-stock' ?>">
                                    <?php if ($index === 0): ?>
                                        <td rowspan="<?= count($produto['necessidades']) ?>" class="align-middle">
                                            <strong><?= htmlspecialchars($produto['produto']['nome']) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                Qtd: <?= number_format($produto['quantidade_produzir'], 0, ',', '.') ?>
                                            </small>
                                        </td>
                                    <?php endif; ?>
                                    <td><?= htmlspecialchars($necessidade['componente_nome']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">
                                            <?= number_format($necessidade['necessario'], 0, ',', '.') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">
                                            <?= number_format($necessidade['em_estoque'], 0, ',', '.') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($necessidade['a_comprar'] > 0): ?>
                                            <span class="badge bg-warning text-dark">
                                                <?= number_format($necessidade['a_comprar'], 0, ',', '.') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($necessidade['a_comprar'] > 0): ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-shopping-cart me-1"></i>
                                                Comprar
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>
                                                Suficiente
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- JavaScript específico da página -->
<script>
    // Exemplo pré-preenchido para demonstração
    document.addEventListener('DOMContentLoaded', function() {
        // Botão para carregar exemplo da especificação
        const btnExemplo = document.createElement('button');
        btnExemplo.type = 'button';
        btnExemplo.className = 'btn btn-outline-info ms-2';
        btnExemplo.innerHTML = '<i class="fas fa-magic me-2"></i>Carregar Exemplo';
        btnExemplo.onclick = function() {
            document.getElementById('produto_1').value = 6; // 6 bicicletas
            document.getElementById('produto_2').value = 3; // 3 computadores
            showToast('Exemplo carregado: 6 bicicletas + 3 computadores', 'info');
        };
        
        // Adiciona o botão ao lado do botão calcular
        const btnCalcular = document.querySelector('button[type="submit"]');
        btnCalcular.parentNode.appendChild(btnExemplo);
    });
</script>
