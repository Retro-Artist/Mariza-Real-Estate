<?php


$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
$perPage = 15;
$searchFilter = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchFilter = trim($_GET['search']);
}

try {
    $whereConditions = [];
    $params = [];

    if (!empty($searchFilter)) {
        $whereConditions[] = "(nome LIKE :search OR uf LIKE :search)";
        $params[':search'] = '%' . $searchFilter . '%';
    }

    $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";

    $countSql = "SELECT COUNT(*) as total FROM sistema_estados" . $whereClause;
    $countStmt = $databaseConnection->prepare($countSql);

    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }

    $countStmt->execute();
    $totalStates = $countStmt->fetch()['total'];
    $totalPages = ceil($totalStates / $perPage);

    if ($page < 1) $page = 1;
    if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

    $offset = ($page - 1) * $perPage;

    $sql = "SELECT * FROM sistema_estados
                $whereClause
                ORDER BY nome ASC
                LIMIT :limit OFFSET :offset";

    $stmt = $databaseConnection->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $states = $stmt->fetchAll();
} catch (PDOException $e) {
    logError("Error fetching states: " . $e->getMessage());
    $states = [];
    $totalPages = 0;
}

?>

<main class="Location">
    <div class="admin-page state-admin">
        <!-- Page Header -->
        <div class="admin-page__header">
            <div class="admin-page__actions">
                <a href="<?= BASE_URL ?>/admin/index.php?page=State_Create" class="primary-button">
                    <i class="fas fa-plus"></i> Adicionar Novo Estado
                </a>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="admin-card">
            <form action="" method="GET" class="filter-form">
                <input type="hidden" name="page" value="State_Admin">

                <div class="form-row">
                    <div class="form-group">
                        <label for="search">Buscar por nome ou UF:</label>
                        <input type="text" id="search" name="search" class="form-control" value="<?= htmlspecialchars($searchFilter) ?>">
                    </div>

                    <div class="form-group form-group--submit">
                        <button type="submit" class="primary-button">
                            <i class="fas fa-search"></i> Filtrar
                        </button>

                        <?php if (!empty($searchFilter)): ?>
                            <a href="<?= BASE_URL ?>/admin/index.php?page=State_Admin" class="cancel-button">
                                <i class="fas fa-times"></i> Limpar Filtros
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- States List -->
        <div class="admin-card">
            <?php if (empty($states)): ?>
                <div class="empty-state">
                    <p>Nenhum estado encontrado. <?= !empty($searchFilter) ? 'Tente outros filtros ou ' : '' ?>adicione um novo estado.</p>
                    <a href="<?= BASE_URL ?>/admin/index.php?page=State_Create" class="primary-button">
                        <i class="fas fa-plus"></i> Adicionar Novo Estado
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>UF</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($states as $state): ?>
                                <tr>
                                    <td><?= htmlspecialchars($state['nome']) ?></td>
                                    <td><?= htmlspecialchars($state['uf']) ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="<?= BASE_URL ?>/admin/index.php?page=State_Update&id=<?= $state['id'] ?>" class="action-button action-button--edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>/admin/index.php?page=State_Delete&id=<?= $state['id'] ?>" class="action-button action-button--delete" title="Excluir">
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
                            <a href="<?= BASE_URL ?>/admin/index.php?page=State_Admin&p=<?= $page - 1 ?><?= !empty($searchFilter) ? '&search=' . urlencode($searchFilter) : '' ?>" class="pagination__item">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        if ($startPage > 1) {
                            echo '<a href="' . BASE_URL . '/admin/index.php?page=State_Admin&p=1' . (!empty($searchFilter) ? '&search=' . urlencode($searchFilter) : '') . '" class="pagination__item">1</a>';
                            if ($startPage > 2) {
                                echo '<span class="pagination__item pagination__item--ellipsis">...</span>';
                            }
                        }

                        for ($i = $startPage; $i <= $endPage; $i++) {
                            $activeClass = ($i == $page) ? 'pagination__item--active' : '';
                            echo '<a href="' . BASE_URL . '/admin/index.php?page=State_Admin&p=' . $i . (!empty($searchFilter) ? '&search=' . urlencode($searchFilter) : '') . '" class="pagination__item ' . $activeClass . '">' . $i . '</a>';
                        }

                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<span class="pagination__item pagination__item--ellipsis">...</span>';
                            }
                            echo '<a href="' . BASE_URL . '/admin/index.php?page=State_Admin&p=' . $totalPages . (!empty($searchFilter) ? '&search=' . urlencode($searchFilter) : '') . '" class="pagination__item">' . $totalPages . '</a>';
                        }
                        ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="<?= BASE_URL ?>/admin/index.php?page=State_Admin&p=<?= $page + 1 ?><?= !empty($searchFilter) ? '&search=' . urlencode($searchFilter) : '' ?>" class="pagination__item">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>