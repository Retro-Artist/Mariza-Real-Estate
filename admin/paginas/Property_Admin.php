<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Paginação
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itensPorPagina = 10;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Filtros
$filtros = [];
$filtroTipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtroCategoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$filtroBusca = isset($_GET['busca']) ? $_GET['busca'] : '';

if (!empty($filtroTipo)) {
    $filtros[] = "i.para = :tipo";
}

if (!empty($filtroCategoria)) {
    $filtros[] = "i.id_categoria = :categoria";
}

if (!empty($filtroBusca)) {
    $filtros[] = "(i.titulo LIKE :busca OR i.codigo LIKE :busca OR i.ref LIKE :busca)";
}

// Construir a cláusula WHERE
$whereClause = !empty($filtros) ? " AND " . implode(" AND ", $filtros) : "";

// Get all properties
try {
    // Pegar total de registros para paginação
    $sqlCount = "SELECT COUNT(*) as total FROM sistema_imoveis i WHERE 1=1" . $whereClause;
    $stmtCount = $databaseConnection->prepare($sqlCount);
    
    // Bind parameters for count query
    if (!empty($filtroTipo)) {
        $stmtCount->bindParam(':tipo', $filtroTipo);
    }
    if (!empty($filtroCategoria)) {
        $stmtCount->bindParam(':categoria', $filtroCategoria);
    }
    if (!empty($filtroBusca)) {
        $termoBusca = "%" . $filtroBusca . "%";
        $stmtCount->bindParam(':busca', $termoBusca);
    }
    
    $stmtCount->execute();
    $totalRegistros = $stmtCount->fetch()['total'];
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);
    
    // Pegar registros paginados
    $sql = "SELECT i.*, c.categoria 
            FROM sistema_imoveis i
            LEFT JOIN sistema_imoveis_categorias c ON i.id_categoria = c.id
            WHERE 1=1" . $whereClause . " 
            ORDER BY i.data DESC, i.id DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $databaseConnection->prepare($sql);
    
    // Bind parameters for main query
    if (!empty($filtroTipo)) {
        $stmt->bindParam(':tipo', $filtroTipo);
    }
    if (!empty($filtroCategoria)) {
        $stmt->bindParam(':categoria', $filtroCategoria);
    }
    if (!empty($filtroBusca)) {
        $stmt->bindParam(':busca', $termoBusca);
    }
    
    $stmt->bindParam(':limit', $itensPorPagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $imoveis = $stmt->fetchAll();
    
    // Get all categories for filter
    $stmtCategorias = $databaseConnection->query("SELECT * FROM sistema_imoveis_categorias ORDER BY categoria ASC");
    $categorias = $stmtCategorias->fetchAll();
} catch (PDOException $e) {
    logError("Error fetching properties: " . $e->getMessage());
    $imoveis = [];
    $categorias = [];
    $totalRegistros = 0;
    $totalPaginas = 1;
}

// Check for alert messages in session
$alertMessage = '';
$alertType = '';

if (isset($_SESSION['alert_message'])) {
    $alertMessage = $_SESSION['alert_message'];
    $alertType = $_SESSION['alert_type'] ?? 'success';
    
    // Clear alert message from session
    unset($_SESSION['alert_message']);
    unset($_SESSION['alert_type']);
}
?>

<!-- Properties List Page -->
<div class="admin-page property-admin">
    <?php if (!empty($alertMessage)): ?>
        <div class="alert-message alert-message--<?= $alertType ?>">
            <?= htmlspecialchars($alertMessage) ?>
        </div>
    <?php endif; ?>
    
    <!-- Page Header with Add Button -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Gerenciar Imóveis</h2>
        <a href="<?= BASE_URL ?>/admin/imoveis/adicionar" class="primary-button">
            <i class="fas fa-plus"></i> Novo Imóvel
        </a>
    </div>
    
    <!-- Filter Form -->
    <div class="admin-card">
        <h3 class="card-title">Filtros</h3>
        <form method="GET" action="" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="tipo">Tipo</label>
                    <select id="tipo" name="tipo" class="form-control">
                        <option value="">Todos</option>
                        <option value="venda" <?= $filtroTipo === 'venda' ? 'selected' : '' ?>>Venda</option>
                        <option value="aluguel" <?= $filtroTipo === 'aluguel' ? 'selected' : '' ?>>Aluguel</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="categoria">Categoria</label>
                    <select id="categoria" name="categoria" class="form-control">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria['id'] ?>" <?= $filtroCategoria === (int)$categoria['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($categoria['categoria']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="busca">Busca</label>
                    <input type="text" id="busca" name="busca" class="form-control" 
                           placeholder="Título, código ou referência" value="<?= htmlspecialchars($filtroBusca) ?>">
                </div>
                
                <div class="form-group form-group--submit">
                    <button type="submit" class="primary-button">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="<?= BASE_URL ?>/admin/imoveis" class="cancel-button">Limpar</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Properties Table -->
    <div class="admin-card">
        <?php if (empty($imoveis)): ?>
            <div class="empty-state">
                <p>Nenhum imóvel encontrado.</p>
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
                            <th>Imagem</th>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th width="150">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($imoveis as $imovel): ?>
                            <tr>
                                <td><?= $imovel['id'] ?></td>
                                <td>
                                    <div class="property-thumbnail">
                                        <img src="<?= getPropertyMainImage($imovel) ?>" alt="<?= htmlspecialchars($imovel['titulo']) ?>">
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($imovel['titulo']) ?></td>
                                <td>
                                    <span class="badge <?= $imovel['para'] === 'venda' ? 'badge--green' : 'badge--blue' ?>">
                                        <?= ucfirst($imovel['para']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($imovel['categoria'] ?? 'N/A') ?></td>
                                <td><?= formatCurrency($imovel['valor']) ?></td>
                                <td>
                                    <span class="badge <?= $imovel['status'] === 'ativo' ? 'badge--green' : 'badge--red' ?>">
                                        <?= ucfirst($imovel['status']) ?>
                                    </span>
                                </td>
                                <td><?= formatDate($imovel['data']) ?></td>
                                <td class="actions">
                                    <a href="<?= BASE_URL ?>/admin/imoveis/editar?id=<?= $imovel['id'] ?>" class="action-button action-button--edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/imovel/<?= $imovel['id'] ?>" class="action-button action-button--view" title="Visualizar" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/imoveis/excluir?id=<?= $imovel['id'] ?>" class="action-button action-button--delete delete-button" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPaginas > 1): ?>
                <div class="pagination">
                    <?php if ($paginaAtual > 1): ?>
                        <a href="<?= BASE_URL ?>/admin/imoveis?pagina=1<?= !empty($filtroTipo) ? '&tipo='.$filtroTipo : '' ?><?= !empty($filtroCategoria) ? '&categoria='.$filtroCategoria : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/admin/imoveis?pagina=<?= $paginaAtual-1 ?><?= !empty($filtroTipo) ? '&tipo='.$filtroTipo : '' ?><?= !empty($filtroCategoria) ? '&categoria='.$filtroCategoria : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    // Determine range of page numbers to display
                    $range = 2; // Show 2 pages before and after current page
                    $startPage = max(1, $paginaAtual - $range);
                    $endPage = min($totalPaginas, $paginaAtual + $range);
                    
                    // Always show first page
                    if ($startPage > 1) {
                        echo '<a href="'.BASE_URL.'/admin/imoveis?pagina=1'.(!empty($filtroTipo) ? '&tipo='.$filtroTipo : '').(!empty($filtroCategoria) ? '&categoria='.$filtroCategoria : '').(!empty($filtroBusca) ? '&busca='.$filtroBusca : '').'" class="pagination__item">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="pagination__item pagination__item--ellipsis">...</span>';
                        }
                    }
                    
                    // Display page numbers within range
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $activeClass = ($i === $paginaAtual) ? 'pagination__item--active' : '';
                        echo '<a href="'.BASE_URL.'/admin/imoveis?pagina='.$i.(!empty($filtroTipo) ? '&tipo='.$filtroTipo : '').(!empty($filtroCategoria) ? '&categoria='.$filtroCategoria : '').(!empty($filtroBusca) ? '&busca='.$filtroBusca : '').'" class="pagination__item '.$activeClass.'">'.$i.'</a>';
                    }
                    
                    // Always show last page
                    if ($endPage < $totalPaginas) {
                        if ($endPage < $totalPaginas - 1) {
                            echo '<span class="pagination__item pagination__item--ellipsis">...</span>';
                        }
                        echo '<a href="'.BASE_URL.'/admin/imoveis?pagina='.$totalPaginas.(!empty($filtroTipo) ? '&tipo='.$filtroTipo : '').(!empty($filtroCategoria) ? '&categoria='.$filtroCategoria : '').(!empty($filtroBusca) ? '&busca='.$filtroBusca : '').'" class="pagination__item">'.$totalPaginas.'</a>';
                    }
                    ?>
                    
                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <a href="<?= BASE_URL ?>/admin/imoveis?pagina=<?= $paginaAtual+1 ?><?= !empty($filtroTipo) ? '&tipo='.$filtroTipo : '' ?><?= !empty($filtroCategoria) ? '&categoria='.$filtroCategoria : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/admin/imoveis?pagina=<?= $totalPaginas ?><?= !empty($filtroTipo) ? '&tipo='.$filtroTipo : '' ?><?= !empty($filtroCategoria) ? '&categoria='.$filtroCategoria : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* Additional styles for property admin page */
.property-thumbnail {
    width: 60px;
    height: 60px;
    border-radius: var(--border-radius);
    overflow: hidden;
}

.property-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.filter-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-group--submit {
    display: flex;
    align-items: flex-end;
    gap: 10px;
}

.card-title {
    font-size: var(--font-lg);
    margin-top: 0;
    margin-bottom: 20px;
    font-family: var(--font-secondary);
}

/* Mobile responsive adjustments */
@media (max-width: 768px) {
    .form-group--submit {
        margin-top: 10px;
    }
    
    .action-button {
        width: 25px;
        height: 25px;
    }
}
</style>