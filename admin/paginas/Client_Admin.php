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
$filtroCategoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$filtroTipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtroBusca = isset($_GET['busca']) ? $_GET['busca'] : '';

if (!empty($filtroCategoria)) {
    $filtros[] = "c.categoria = :categoria";
}

if (!empty($filtroTipo)) {
    $filtros[] = "c.tipo = :tipo";
}

if (!empty($filtroBusca)) {
    $filtros[] = "(c.nome_completo LIKE :busca OR c.razao_social LIKE :busca OR c.email LIKE :busca OR c.telefone1 LIKE :busca)";
}

// Construir a cláusula WHERE
$whereClause = !empty($filtros) ? " AND " . implode(" AND ", $filtros) : "";

// Get all clients
try {
    // Pegar total de registros para paginação
    $sqlCount = "SELECT COUNT(*) as total FROM sistema_clientes c WHERE 1=1" . $whereClause;
    $stmtCount = $databaseConnection->prepare($sqlCount);
    
    // Bind parameters for count query
    if (!empty($filtroCategoria)) {
        $stmtCount->bindParam(':categoria', $filtroCategoria);
    }
    if (!empty($filtroTipo)) {
        $stmtCount->bindParam(':tipo', $filtroTipo);
    }
    if (!empty($filtroBusca)) {
        $termoBusca = "%" . $filtroBusca . "%";
        $stmtCount->bindParam(':busca', $termoBusca);
    }
    
    $stmtCount->execute();
    $totalRegistros = $stmtCount->fetch()['total'];
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);
    
    // Pegar registros paginados
    $sql = "SELECT c.*, 
                  e.nome as estado, 
                  cid.nome as cidade, 
                  b.bairro as bairro
           FROM sistema_clientes c
           LEFT JOIN sistema_estados e ON c.id_estado = e.id
           LEFT JOIN sistema_cidades cid ON c.id_cidade = cid.id
           LEFT JOIN sistema_bairros b ON c.id_bairro = b.id
           WHERE 1=1" . $whereClause . " 
           ORDER BY c.data_cadastro DESC, c.id DESC
           LIMIT :limit OFFSET :offset";
    
    $stmt = $databaseConnection->prepare($sql);
    
    // Bind parameters for main query
    if (!empty($filtroCategoria)) {
        $stmt->bindParam(':categoria', $filtroCategoria);
    }
    if (!empty($filtroTipo)) {
        $stmt->bindParam(':tipo', $filtroTipo);
    }
    if (!empty($filtroBusca)) {
        $stmt->bindParam(':busca', $termoBusca);
    }
    
    $stmt->bindParam(':limit', $itensPorPagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $clientes = $stmt->fetchAll();
    
    // Get client categories for filter
    $stmtCategorias = $databaseConnection->query("SELECT DISTINCT categoria FROM sistema_clientes WHERE categoria != '' ORDER BY categoria ASC");
    $categorias = $stmtCategorias->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    logError("Error fetching clients: " . $e->getMessage());
    $clientes = [];
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

<!-- Clients List Page -->
<div class="admin-page client-admin">
    <?php if (!empty($alertMessage)): ?>
        <div class="alert-message alert-message--<?= $alertType ?>">
            <?= htmlspecialchars($alertMessage) ?>
        </div>
    <?php endif; ?>
    
    <!-- Page Header with Add Button -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Gerenciar Clientes</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Create" class="primary-button">
            <i class="fas fa-plus"></i> Novo Cliente
        </a>
    </div>
    
    <!-- Filter Form -->
    <div class="admin-card">
        <h3 class="card-title">Filtros</h3>
        <form method="GET" action="<?= BASE_URL ?>/admin/index.php" class="filter-form">
            <input type="hidden" name="page" value="Client_Admin">
            <div class="form-row">
                <div class="form-group">
                    <label for="tipo">Tipo</label>
                    <select id="tipo" name="tipo" class="form-control">
                        <option value="">Todos</option>
                        <option value="Pessoa Física" <?= $filtroTipo === 'Pessoa Física' ? 'selected' : '' ?>>Pessoa Física</option>
                        <option value="Pessoa Jurídica" <?= $filtroTipo === 'Pessoa Jurídica' ? 'selected' : '' ?>>Pessoa Jurídica</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="categoria">Categoria</label>
                    <select id="categoria" name="categoria" class="form-control">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= htmlspecialchars($categoria) ?>" <?= $filtroCategoria === $categoria ? 'selected' : '' ?>>
                                <?= htmlspecialchars($categoria) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="busca">Busca</label>
                    <input type="text" id="busca" name="busca" class="form-control" 
                           placeholder="Nome, razão social, email ou telefone" value="<?= htmlspecialchars($filtroBusca) ?>">
                </div>
                
                <div class="form-group form-group--submit">
                    <button type="submit" class="primary-button">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin" class="cancel-button">Limpar</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Clients Table -->
    <div class="admin-card">
        <?php if (empty($clientes)): ?>
            <div class="empty-state">
                <p>Nenhum cliente encontrado.</p>
                <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Create" class="primary-button">
                    Adicionar Cliente
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Nome/Razão Social</th>
                            <th>Telefone</th>
                            <th>Cidade</th>
                            <th>UF</th>
                            <th>Categoria</th>
                            <th>Data Cadastro</th>
                            <th width="150">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?= $cliente['id'] ?></td>
                                <td>
                                    <span class="badge <?= $cliente['tipo'] === 'Pessoa Física' ? 'badge--blue' : 'badge--green' ?>">
                                        <?= htmlspecialchars($cliente['tipo']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($cliente['nome_completo'] ?: $cliente['razao_social']) ?></td>
                                <td><?= !empty($cliente['telefone1']) ? htmlspecialchars($cliente['telefone1']) : '-' ?></td>
                                <td><?= !empty($cliente['cidade']) ? htmlspecialchars($cliente['cidade']) : '-' ?></td>
                                <td><?= !empty($cliente['estado']) ? htmlspecialchars(substr($cliente['estado'], 0, 2)) : '-' ?></td>
                                <td><?= !empty($cliente['categoria']) ? htmlspecialchars($cliente['categoria']) : '-' ?></td>
                                <td><?= formatDate($cliente['data_cadastro']) ?></td>
                                <td class="actions">
                                    <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Update&id=<?= $cliente['id'] ?>" class="action-button action-button--edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Delete&id=<?= $cliente['id'] ?>" class="action-button action-button--delete delete-button" title="Excluir">
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
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin&pagina=1<?= !empty($filtroTipo) ? '&tipo='.urlencode($filtroTipo) : '' ?><?= !empty($filtroCategoria) ? '&categoria='.urlencode($filtroCategoria) : '' ?><?= !empty($filtroBusca) ? '&busca='.urlencode($filtroBusca) : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin&pagina=<?= $paginaAtual-1 ?><?= !empty($filtroTipo) ? '&tipo='.urlencode($filtroTipo) : '' ?><?= !empty($filtroCategoria) ? '&categoria='.urlencode($filtroCategoria) : '' ?><?= !empty($filtroBusca) ? '&busca='.urlencode($filtroBusca) : '' ?>" class="pagination__item">
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
                        echo '<a href="'.BASE_URL.'/admin/index.php?page=Client_Admin&pagina=1'.(!empty($filtroTipo) ? '&tipo='.urlencode($filtroTipo) : '').(!empty($filtroCategoria) ? '&categoria='.urlencode($filtroCategoria) : '').(!empty($filtroBusca) ? '&busca='.urlencode($filtroBusca) : '').'" class="pagination__item">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="pagination__item pagination__item--ellipsis">...</span>';
                        }
                    }
                    
                    // Display page numbers within range
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $activeClass = ($i === $paginaAtual) ? 'pagination__item--active' : '';
                        echo '<a href="'.BASE_URL.'/admin/index.php?page=Client_Admin&pagina='.$i.(!empty($filtroTipo) ? '&tipo='.urlencode($filtroTipo) : '').(!empty($filtroCategoria) ? '&categoria='.urlencode($filtroCategoria) : '').(!empty($filtroBusca) ? '&busca='.urlencode($filtroBusca) : '').'" class="pagination__item '.$activeClass.'">'.$i.'</a>';
                    }
                    
                    // Always show last page
                    if ($endPage < $totalPaginas) {
                        if ($endPage < $totalPaginas - 1) {
                            echo '<span class="pagination__item pagination__item--ellipsis">...</span>';
                        }
                        echo '<a href="'.BASE_URL.'/admin/index.php?page=Client_Admin&pagina='.$totalPaginas.(!empty($filtroTipo) ? '&tipo='.urlencode($filtroTipo) : '').(!empty($filtroCategoria) ? '&categoria='.urlencode($filtroCategoria) : '').(!empty($filtroBusca) ? '&busca='.urlencode($filtroBusca) : '').'" class="pagination__item">'.$totalPaginas.'</a>';
                    }
                    ?>
                    
                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin&pagina=<?= $paginaAtual+1 ?><?= !empty($filtroTipo) ? '&tipo='.urlencode($filtroTipo) : '' ?><?= !empty($filtroCategoria) ? '&categoria='.urlencode($filtroCategoria) : '' ?><?= !empty($filtroBusca) ? '&busca='.urlencode($filtroBusca) : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin&pagina=<?= $totalPaginas ?><?= !empty($filtroTipo) ? '&tipo='.urlencode($filtroTipo) : '' ?><?= !empty($filtroCategoria) ? '&categoria='.urlencode($filtroCategoria) : '' ?><?= !empty($filtroBusca) ? '&busca='.urlencode($filtroBusca) : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* Additional styles for client admin page */
.client-admin .property-thumbnail {
    width: 60px;
    height: 60px;
    border-radius: var(--border-radius);
    overflow: hidden;
}

.client-admin .property-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.client-admin .filter-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.client-admin .form-group--submit {
    display: flex;
    align-items: flex-end;
    gap: 10px;
}

.client-admin .card-title {
    font-size: var(--font-lg);
    margin-top: 0;
    margin-bottom: 20px;
    font-family: var(--font-secondary);
}

/* Mobile responsive adjustments */
@media (max-width: 768px) {
    .client-admin .form-group--submit {
        margin-top: 10px;
    }
    
    .client-admin .action-button {
        width: 25px;
        height: 25px;
    }
}
</style>