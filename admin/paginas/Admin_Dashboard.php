<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Get some basic statistics for the dashboard
try {
    // Count total properties
    $stmt = $databaseConnection->query("SELECT COUNT(*) as total FROM sistema_imoveis WHERE status = 'ativo'");
    $totalImoveis = $stmt->fetch()['total'];
    
    // Count properties by type (venda/aluguel)
    $stmt = $databaseConnection->query(
        "SELECT para, COUNT(*) as total FROM sistema_imoveis 
         WHERE status = 'ativo' 
         GROUP BY para"
    );
    $imoveisPorTipo = $stmt->fetchAll();
    
    // Count total categories
    $stmt = $databaseConnection->query("SELECT COUNT(*) as total FROM sistema_imoveis_categorias");
    $totalCategorias = $stmt->fetch()['total'];
    
    // Get latest properties
    $stmt = $databaseConnection->query(
        "SELECT i.id, i.titulo, i.para, i.valor, i.data, c.categoria 
         FROM sistema_imoveis i
         LEFT JOIN sistema_imoveis_categorias c ON i.id_categoria = c.id
         WHERE i.status = 'ativo'
         ORDER BY i.data DESC, i.hora DESC
         LIMIT 5"
    );
    $ultimosImoveis = $stmt->fetchAll();
    
} catch (PDOException $e) {
    logError("Dashboard error: " . $e->getMessage());
    // Initialize with default values in case of error
    $totalImoveis = 0;
    $imoveisPorTipo = [];
    $totalCategorias = 0;
    $ultimosImoveis = [];
}

// Calculate properties by type for easy display
$imoveisVenda = 0;
$imoveisAluguel = 0;

foreach ($imoveisPorTipo as $tipo) {
    if ($tipo['para'] === 'venda') {
        $imoveisVenda = $tipo['total'];
    } elseif ($tipo['para'] === 'aluguel') {
        $imoveisAluguel = $tipo['total'];
    }
}
?>

<!-- Dashboard Content -->
<div class="dashboard">
    <!-- Statistics Cards -->
    <div class="dashboard__stats">
        <div class="stat-card">
            <div class="stat-card__icon">
                <i class="fas fa-home"></i>
            </div>
            <div class="stat-card__content">
                <h3 class="stat-card__title">Total de Imóveis</h3>
                <p class="stat-card__value"><?= $totalImoveis ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--blue">
                <i class="fas fa-tags"></i>
            </div>
            <div class="stat-card__content">
                <h3 class="stat-card__title">Categorias</h3>
                <p class="stat-card__value"><?= $totalCategorias ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--green">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-card__content">
                <h3 class="stat-card__title">Imóveis para Venda</h3>
                <p class="stat-card__value"><?= $imoveisVenda ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--orange">
                <i class="fas fa-key"></i>
            </div>
            <div class="stat-card__content">
                <h3 class="stat-card__title">Imóveis para Aluguel</h3>
                <p class="stat-card__value"><?= $imoveisAluguel ?></p>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="dashboard__actions">
        <h2 class="dashboard__section-title">Ações Rápidas</h2>
        
        <div class="quick-actions">
            <a href="<?= BASE_URL ?>/admin/imoveis/adicionar" class="quick-action">
                <i class="fas fa-plus-circle"></i>
                <span>Adicionar Imóvel</span>
            </a>
            
            <a href="<?= BASE_URL ?>/admin/categorias/adicionar" class="quick-action">
                <i class="fas fa-folder-plus"></i>
                <span>Adicionar Categoria</span>
            </a>
            
            <a href="<?= BASE_URL ?>/admin/imoveis" class="quick-action">
                <i class="fas fa-list"></i>
                <span>Listar Imóveis</span>
            </a>
            
            <a href="<?= BASE_URL ?>/admin/categorias" class="quick-action">
                <i class="fas fa-tag"></i>
                <span>Listar Categorias</span>
            </a>
        </div>
    </div>
    
    <!-- Latest Properties -->
    <div class="dashboard__recent">
        <h2 class="dashboard__section-title">Imóveis Recentes</h2>
        
        <?php if (empty($ultimosImoveis)): ?>
            <div class="empty-state">
                <p>Nenhum imóvel cadastrado ainda.</p>
                <a href="<?= BASE_URL ?>/admin/imoveis/adicionar" class="primary-button">
                    Adicionar Imóvel
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Categoria</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimosImoveis as $imovel): ?>
                            <tr>
                                <td><?= $imovel['id'] ?></td>
                                <td><?= htmlspecialchars($imovel['titulo']) ?></td>
                                <td><?= htmlspecialchars($imovel['categoria'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge <?= $imovel['para'] === 'venda' ? 'badge--green' : 'badge--blue' ?>">
                                        <?= ucfirst($imovel['para']) ?>
                                    </span>
                                </td>
                                <td><?= formatCurrency($imovel['valor']) ?></td>
                                <td><?= formatDate($imovel['data']) ?></td>
                                <td class="actions">
                                    <a href="<?= BASE_URL ?>/admin/imoveis/editar?id=<?= $imovel['id'] ?>" class="action-button action-button--edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/imovel/<?= $imovel['id'] ?>" class="action-button action-button--view" title="Visualizar" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="dashboard__see-all">
                <a href="<?= BASE_URL ?>/admin/imoveis" class="see-all-link">
                    Ver todos os imóveis <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>