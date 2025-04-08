<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    // Instead of using header() directly, store the URL for later redirection via JavaScript
    $redirect_url = BASE_URL . '/admin/Admin_Login.php';
    $need_redirect = true;
} else {
    $need_redirect = false;
}

// If security check passes, proceed with page logic
if (!$need_redirect) {
    // Get current page number for pagination
    $page = isset($_GET['p']) ? intval($_GET['p']) : 1;
    $perPage = 15; // Number of neighborhoods per page
    
    // Initialize search filter
    $searchFilter = '';
    
    // Process search form
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $searchFilter = trim($_GET['search']);
    }
    
    // Get all states for filter dropdown
    $states = getStates();
    
    // Initialize state filter
    $stateFilter = '';
    
    // Process state filter
    if (isset($_GET['state']) && !empty($_GET['state'])) {
        $stateFilter = intval($_GET['state']);
    }
    
    // Initialize city filter
    $cityFilter = '';
    
    // Process city filter
    if (isset($_GET['city']) && !empty($_GET['city'])) {
        $cityFilter = intval($_GET['city']);
    }
    
    // Get ALL cities for the dropdown
    $allCities = [];
    try {
        $stmt = $databaseConnection->query("SELECT id, nome, id_estado FROM sistema_cidades ORDER BY nome ASC");
        $allCities = $stmt->fetchAll();
    } catch (PDOException $e) {
        logError("Error fetching all cities: " . $e->getMessage());
    }
    
    // Build filter array
    $filters = [
        'search' => $searchFilter,
        'state' => $stateFilter,
        'city' => $cityFilter
    ];
    
    // Get neighborhoods with pagination and filters
    try {
        // Prepare the WHERE clause based on filters
        $whereConditions = [];
        $params = [];
        
        if (!empty($searchFilter)) {
            $whereConditions[] = "b.bairro LIKE :search";
            $params[':search'] = '%' . $searchFilter . '%';
        }
        
        if (!empty($stateFilter)) {
            $whereConditions[] = "b.id_estado = :state_id";
            $params[':state_id'] = $stateFilter;
        }
        
        if (!empty($cityFilter)) {
            $whereConditions[] = "b.id_cidade = :city_id";
            $params[':city_id'] = $cityFilter;
        }
        
        // Combine conditions if any
        $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";
        
        // Count total records for pagination
        $countSql = "SELECT COUNT(*) as total FROM sistema_bairros b" . $whereClause;
        $countStmt = $databaseConnection->prepare($countSql);
        
        // Bind parameters for the count query
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        
        $countStmt->execute();
        $totalNeighborhoods = $countStmt->fetch()['total'];
        $totalPages = ceil($totalNeighborhoods / $perPage);
        
        // Make sure page is valid
        if ($page < 1) $page = 1;
        if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
        
        // Calculate offset for pagination
        $offset = ($page - 1) * $perPage;
        
        // Get neighborhoods for current page
        $sql = "SELECT b.*, c.nome as cidade_nome, e.nome as estado_nome, e.uf 
                FROM sistema_bairros b
                LEFT JOIN sistema_cidades c ON b.id_cidade = c.id
                LEFT JOIN sistema_estados e ON b.id_estado = e.id
                $whereClause
                ORDER BY b.bairro ASC
                LIMIT :limit OFFSET :offset";
                
        $stmt = $databaseConnection->prepare($sql);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $neighborhoods = $stmt->fetchAll();
    } catch (PDOException $e) {
        logError("Error fetching neighborhoods: " . $e->getMessage());
        $neighborhoods = [];
        $totalPages = 0;
    }
}
?>

<?php if (!$need_redirect): ?>
<div class="admin-page neighborhood-admin">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Gerenciar Bairros</h2>
        <div class="admin-page__actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Neighborhood_Create" class="primary-button">
                <i class="fas fa-plus"></i> Adicionar Novo Bairro
            </a>
        </div>
    </div>
    
    <!-- Search and Filters -->
    <div class="admin-card">
        <form action="" method="GET" class="filter-form">
            <input type="hidden" name="page" value="Neighborhood_Admin">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="search">Buscar Bairro por Nome:</label>
                    <input type="text" id="search" name="search" class="form-control" value="<?= htmlspecialchars($searchFilter) ?>">
                </div>
                
                <div class="form-group">
                    <label for="state">Cidade por Estado:</label>
                    <select id="state" name="state" class="form-control state-select">
                        <option value="">Todos os Estados</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?= $state['id'] ?>" <?= $stateFilter == $state['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($state['nome']) ?> (<?= htmlspecialchars($state['uf']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="city">Cidade do Bairro:</label>
                    <select id="city" name="city" class="form-control city-select">
                        <option value="">Todas as Cidades</option>
                        <?php foreach ($allCities as $city): ?>
                            <option value="<?= $city['id'] ?>" 
                                    data-state="<?= $city['id_estado'] ?>" 
                                    <?= $cityFilter == $city['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($city['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group form-group--submit">
                    <button type="submit" class="primary-button">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    
                    <?php if (!empty($searchFilter) || !empty($stateFilter) || !empty($cityFilter)): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Neighborhood_Admin" class="cancel-button">
                            <i class="fas fa-times"></i> Limpar Filtros
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Neighborhoods List -->
    <div class="admin-card">
        <?php if (empty($neighborhoods)): ?>
            <div class="empty-state">
                <p>Nenhum bairro encontrado. <?= !empty($searchFilter) || !empty($stateFilter) || !empty($cityFilter) ? 'Tente outros filtros ou ' : '' ?>adicione um novo bairro.</p>
                <a href="<?= BASE_URL ?>/admin/index.php?page=Neighborhood_Create" class="primary-button">
                    <i class="fas fa-plus"></i> Adicionar Novo Bairro
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Bairro</th>
                            <th>Cidade</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($neighborhoods as $neighborhood): ?>
                            <tr>
                                <td><?= htmlspecialchars($neighborhood['bairro']) ?></td>
                                <td><?= htmlspecialchars($neighborhood['cidade_nome']) ?></td>
                                <td><?= htmlspecialchars($neighborhood['estado_nome']) ?> (<?= htmlspecialchars($neighborhood['uf']) ?>)</td>
                                <td>
                                    <div class="actions">
                                        <a href="<?= BASE_URL ?>/admin/index.php?page=Neighborhood_Update&id=<?= $neighborhood['id'] ?>" class="action-button action-button--edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/index.php?page=Neighborhood_Delete&id=<?= $neighborhood['id'] ?>" class="action-button action-button--delete" title="Excluir">
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
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Neighborhood_Admin&p=<?= $page - 1 ?><?= !empty($searchFilter) ? '&search=' . urlencode($searchFilter) : '' ?><?= !empty($stateFilter) ? '&state=' . $stateFilter : '' ?><?= !empty($cityFilter) ? '&city=' . $cityFilter : '' ?>" class="pagination__item">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    // Show limited page numbers with ellipsis
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    // Always show first page
                    if ($startPage > 1) {
                        echo '<a href="' . BASE_URL . '/admin/index.php?page=Neighborhood_Admin&p=1' . (!empty($searchFilter) ? '&search=' . urlencode($searchFilter) : '') . (!empty($stateFilter) ? '&state=' . $stateFilter : '') . (!empty($cityFilter) ? '&city=' . $cityFilter : '') . '" class="pagination__item">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="pagination__item pagination__item--ellipsis">...</span>';
                        }
                    }
                    
                    // Show current page range
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $activeClass = ($i == $page) ? 'pagination__item--active' : '';
                        echo '<a href="' . BASE_URL . '/admin/index.php?page=Neighborhood_Admin&p=' . $i . (!empty($searchFilter) ? '&search=' . urlencode($searchFilter) : '') . (!empty($stateFilter) ? '&state=' . $stateFilter : '') . (!empty($cityFilter) ? '&city=' . $cityFilter : '') . '" class="pagination__item ' . $activeClass . '">' . $i . '</a>';
                    }
                    
                    // Always show last page
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span class="pagination__item pagination__item--ellipsis">...</span>';
                        }
                        echo '<a href="' . BASE_URL . '/admin/index.php?page=Neighborhood_Admin&p=' . $totalPages . (!empty($searchFilter) ? '&search=' . urlencode($searchFilter) : '') . (!empty($stateFilter) ? '&state=' . $stateFilter : '') . (!empty($cityFilter) ? '&city=' . $cityFilter : '') . '" class="pagination__item">' . $totalPages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Neighborhood_Admin&p=<?= $page + 1 ?><?= !empty($searchFilter) ? '&search=' . urlencode($searchFilter) : '' ?><?= !empty($stateFilter) ? '&state=' . $stateFilter : '' ?><?= !empty($cityFilter) ? '&city=' . $cityFilter : '' ?>" class="pagination__item">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Script to handle dynamic city dropdown based on state selection -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stateSelect = document.querySelector('.state-select');
    const citySelect = document.querySelector('.city-select');
    const cityOptions = Array.from(citySelect.querySelectorAll('option'));
    
    // Function to filter cities based on the selected state
    function filterCities() {
        const selectedState = stateSelect.value;
        
        // Remove all options except the first one (All Cities)
        while (citySelect.options.length > 1) {
            citySelect.remove(1);
        }
        
        // If no state selected, show all cities
        if (!selectedState) {
            cityOptions.forEach(option => {
                if (option.value) { // Don't include the "All Cities" option again
                    citySelect.appendChild(option.cloneNode(true));
                }
            });
            return;
        }
        
        // Filter and add only cities from the selected state
        const filteredCities = cityOptions.filter(option => {
            return option.value === '' || option.dataset.state === selectedState;
        });
        
        filteredCities.forEach(option => {
            if (option.value !== '') { // Don't include the "All Cities" option again
                citySelect.appendChild(option.cloneNode(true));
            }
        });
    }
    
    // Filter cities when page loads
    filterCities();
    
    // Add change event to state select
    stateSelect.addEventListener('change', filterCities);
});
</script>
<?php endif; ?>

<?php if ($need_redirect): ?>
<script>
    // JavaScript redirect if security check fails
    window.location.href = "<?= $redirect_url ?>";
</script>
<?php endif; ?>