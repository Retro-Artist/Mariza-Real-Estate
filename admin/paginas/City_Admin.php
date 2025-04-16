<?php

// If security check passes, proceed with page logic

    // Get current page number for pagination
    $page = isset($_GET['p']) ? intval($_GET['p']) : 1;
    $perPage = 15; // Number of cities per page
    
    // Initialize search filter
    $searchFilter = '';
    
    // Process search form
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $searchFilter = trim($_GET['search']);
    }
    
    // Get all states for the filter dropdown
    $states = getStates();
    
    // Initialize state filter
    $stateFilter = '';
    
    // Process state filter
    if (isset($_GET['state']) && !empty($_GET['state'])) {
        $stateFilter = intval($_GET['state']);
    }
    
    // Build filter array for the function
    $filters = [
        'search' => $searchFilter,
        'state' => $stateFilter
    ];
    
    // Get cities with pagination and filters
    try {
        // Prepare the WHERE clause based on filters
        $whereConditions = [];
        $params = [];
        
        if (!empty($searchFilter)) {
            $whereConditions[] = "c.nome LIKE :search";
            $params[':search'] = '%' . $searchFilter . '%';
        }
        
        if (!empty($stateFilter)) {
            $whereConditions[] = "c.id_estado = :state_id";
            $params[':state_id'] = $stateFilter;
        }
        
        // Combine conditions if any
        $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";
        
        // Count total records for pagination
        $countSql = "SELECT COUNT(*) as total FROM sistema_cidades c" . $whereClause;
        $countStmt = $databaseConnection->prepare($countSql);
        
        // Bind parameters for the count query
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        
        $countStmt->execute();
        $totalCities = $countStmt->fetch()['total'];
        $totalPages = ceil($totalCities / $perPage);
        
        // Make sure page is valid
        if ($page < 1) $page = 1;
        if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
        
        // Calculate offset for pagination
        $offset = ($page - 1) * $perPage;
        
        // Get cities for current page
        $sql = "SELECT c.*, e.nome as estado_nome, e.uf 
                FROM sistema_cidades c
                LEFT JOIN sistema_estados e ON c.id_estado = e.id
                $whereClause
                ORDER BY c.nome ASC
                LIMIT :limit OFFSET :offset";
                
        $stmt = $databaseConnection->prepare($sql);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $cities = $stmt->fetchAll();
    } catch (PDOException $e) {
        logError("Error fetching cities: " . $e->getMessage());
        $cities = [];
        $totalPages = 0;
    }

?>


<div class="admin-page city-admin">
    <!-- Page Header -->
    <div class="admin-page__header">
        <div class="admin-page__actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=City_Create" class="primary-button">
                <i class="fas fa-plus"></i> Adicionar Nova Cidade
            </a>
        </div>
    </div>
    
    <!-- Search and Filters -->
    <div class="admin-card">
        <form action="" method="GET" class="filter-form">
            <input type="hidden" name="page" value="City_Admin">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="search">Buscar Cidades Cadastradas:</label>
                    <input type="text" id="search" name="search" class="form-control" value="<?= htmlspecialchars($searchFilter) ?>">
                </div>
                
                <div class="form-group">
                    <label for="state">Filtrar Cidades por Estado:</label>
                    <select id="state" name="state" class="form-control">
                        <option value="">Todos os Estados</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?= $state['id'] ?>" <?= $stateFilter == $state['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($state['nome']) ?> (<?= htmlspecialchars($state['uf']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group form-group--submit">
                    <button type="submit" class="primary-button">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    
                    <?php if (!empty($searchFilter) || !empty($stateFilter)): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=City_Admin" class="cancel-button">
                            <i class="fas fa-times"></i> Limpar Filtros
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Cities List -->
    <div class="admin-card">
        <?php if (empty($cities)): ?>
            <div class="empty-state">
                <p>Nenhuma cidade encontrada. <?= !empty($searchFilter) || !empty($stateFilter) ? 'Tente outros filtros ou ' : '' ?>adicione uma nova cidade.</p>
                <a href="<?= BASE_URL ?>/admin/index.php?page=City_Create" class="primary-button">
                    <i class="fas fa-plus"></i> Adicionar Nova Cidade
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Estado</th>
                            <th>CEP</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cities as $city): ?>
                            <tr>
                                <td><?= htmlspecialchars($city['nome']) ?></td>
                                <td><?= htmlspecialchars($city['estado_nome']) ?> (<?= htmlspecialchars($city['uf']) ?>)</td>
                                <td><?= !empty($city['cep']) ? htmlspecialchars($city['cep']) : '<span class="no-data">Não informado</span>' ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="<?= BASE_URL ?>/admin/index.php?page=City_Update&id=<?= $city['id'] ?>" class="action-button action-button--edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/index.php?page=City_Delete&id=<?= $city['id'] ?>" class="action-button action-button--delete" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=City_Admin&p=<?= $page - 1 ?><?= !empty($searchFilter) ? '&search=' . urlencode($searchFilter) : '' ?><?= !empty($stateFilter) ? '&state=' . $stateFilter : '' ?>" class="pagination__item">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    // Show limited page numbers with ellipsis
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    // Always show first page
                    if ($startPage > 1) {
                        echo '<a href="' . BASE_URL . '/admin/index.php?page=City_Admin&p=1' . (!empty($searchFilter) ? '&search=' . urlencode($searchFilter) : '') . (!empty($stateFilter) ? '&state=' . $stateFilter : '') . '" class="pagination__item">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="pagination__item pagination__item--ellipsis">...</span>';
                        }
                    }
                    
                    // Show current page range
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $activeClass = ($i == $page) ? 'pagination__item--active' : '';
                        echo '<a href="' . BASE_URL . '/admin/index.php?page=City_Admin&p=' . $i . (!empty($searchFilter) ? '&search=' . urlencode($searchFilter) : '') . (!empty($stateFilter) ? '&state=' . $stateFilter : '') . '" class="pagination__item ' . $activeClass . '">' . $i . '</a>';
                    }
                    
                    // Always show last page
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span class="pagination__item pagination__item--ellipsis">...</span>';
                        }
                        echo '<a href="' . BASE_URL . '/admin/index.php?page=City_Admin&p=' . $totalPages . (!empty($searchFilter) ? '&search=' . urlencode($searchFilter) : '') . (!empty($stateFilter) ? '&state=' . $stateFilter : '') . '" class="pagination__item">' . $totalPages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=City_Admin&p=<?= $page + 1 ?><?= !empty($searchFilter) ? '&search=' . urlencode($searchFilter) : '' ?><?= !empty($stateFilter) ? '&state=' . $stateFilter : '' ?>" class="pagination__item">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>