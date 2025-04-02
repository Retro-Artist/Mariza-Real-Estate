<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Check if user has administrator privileges
if ($_SESSION['admin_level'] !== 'Administrador') {
    $_SESSION['alert_message'] = 'Você não tem permissão para acessar esta página.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/');
    exit;
}

// Pagination
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itensPorPagina = 10;

// Filters
$filtros = [];
$filtroNivel = isset($_GET['nivel']) ? $_GET['nivel'] : '';
$filtroBusca = isset($_GET['busca']) ? $_GET['busca'] : '';

if (!empty($filtroNivel)) {
    $filtros['nivel'] = $filtroNivel;
}

if (!empty($filtroBusca)) {
    $filtros['busca'] = $filtroBusca;
}

// Get users with pagination using our function from admin_functions.php
$userResult = getAdminUsers($filtros, $paginaAtual, $itensPorPagina);
$usuarios = $userResult['users'];
$totalRegistros = $userResult['total'];
$totalPaginas = $userResult['totalPages'];

// Get unique user levels for filter
try {
    $stmtNiveis = $databaseConnection->query("SELECT DISTINCT nivel FROM sistema_usuarios ORDER BY nivel ASC");
    $niveis = $stmtNiveis->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    logError("Error fetching user levels: " . $e->getMessage());
    $niveis = [];
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

<div class="admin-page user-admin">
    <?php if (!empty($alertMessage)): ?>
        <div class="alert-message alert-message--<?= $alertType ?>">
            <?= htmlspecialchars($alertMessage) ?>
        </div>
    <?php endif; ?>
    
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Gerenciar Usuários</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=User_Create" class="primary-button">
            <i class="fas fa-plus"></i> Novo Usuário
        </a>
    </div>
    
    <!-- Filter Form -->
    <div class="admin-card">
        <h3 class="card-title">Filtros</h3>
        <form method="GET" action="<?= BASE_URL ?>/admin/index.php" class="filter-form">
            <input type="hidden" name="page" value="User_Admin">
            <div class="form-row">
                <div class="form-group">
                    <label for="nivel">Nível de Acesso</label>
                    <select id="nivel" name="nivel" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($niveis as $nivel): ?>
                            <option value="<?= htmlspecialchars($nivel) ?>" <?= $filtroNivel === $nivel ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nivel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="busca">Busca</label>
                    <input type="text" id="busca" name="busca" class="form-control" 
                           placeholder="Nome ou email" value="<?= htmlspecialchars($filtroBusca) ?>">
                </div>
                
                <div class="form-group form-group--submit">
                    <button type="submit" class="primary-button">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="<?= BASE_URL ?>/admin/index.php?page=User_Admin" class="cancel-button">Limpar</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Users Table -->
    <div class="admin-card">
        <?php if (empty($usuarios)): ?>
            <div class="empty-state">
                <p>Nenhum usuário encontrado.</p>
                <a href="<?= BASE_URL ?>/admin/index.php?page=User_Create" class="primary-button">
                    Adicionar Usuário
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Nível de Acesso</th>
                            <th width="150">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= $usuario['id'] ?></td>
                                <td><?= htmlspecialchars($usuario['nome']) ?></td>
                                <td><?= htmlspecialchars($usuario['email']) ?></td>
                                <td>
                                    <span class="badge <?= $usuario['nivel'] === 'Administrador' ? 'badge--blue' : 'badge--green' ?>">
                                        <?= htmlspecialchars($usuario['nivel']) ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <?php if ((int)$_SESSION['admin_id'] !== (int)$usuario['id']): ?>
                                        <a href="<?= BASE_URL ?>/admin/index.php?page=User_Update&id=<?= $usuario['id'] ?>" class="action-button action-button--edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/index.php?page=User_Delete&id=<?= $usuario['id'] ?>" class="action-button action-button--delete delete-button" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>/admin/index.php?page=User_Update&id=<?= $usuario['id'] ?>" class="action-button action-button--edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <span class="action-button action-button--disabled" title="Não é possível excluir seu próprio usuário">
                                            <i class="fas fa-ban"></i>
                                        </span>
                                    <?php endif; ?>
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
                        <a href="<?= BASE_URL ?>/admin/index.php?page=User_Admin&pagina=<?= $paginaAtual-1 ?><?= !empty($filtroNivel) ? '&nivel='.$filtroNivel : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item">
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
                        echo '<a href="'.BASE_URL.'/admin/index.php?page=User_Admin&pagina=1'.(!empty($filtroNivel) ? '&nivel='.$filtroNivel : '').(!empty($filtroBusca) ? '&busca='.$filtroBusca : '').'" class="pagination__item">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="pagination__item pagination__item--ellipsis">...</span>';
                        }
                    }
                    
                    // Display page numbers within range
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $activeClass = ($i === $paginaAtual) ? 'pagination__item--active' : '';
                        echo '<a href="'.BASE_URL.'/admin/index.php?page=User_Admin&pagina='.$i.(!empty($filtroNivel) ? '&nivel='.$filtroNivel : '').(!empty($filtroBusca) ? '&busca='.$filtroBusca : '').'" class="pagination__item '.$activeClass.'">'.$i.'</a>';
                    }
                    
                    // Always show last page
                    if ($endPage < $totalPaginas) {
                        if ($endPage < $totalPaginas - 1) {
                            echo '<span class="pagination__item pagination__item--ellipsis">...</span>';
                        }
                        echo '<a href="'.BASE_URL.'/admin/index.php?page=User_Admin&pagina='.$totalPaginas.(!empty($filtroNivel) ? '&nivel='.$filtroNivel : '').(!empty($filtroBusca) ? '&busca='.$filtroBusca : '').'" class="pagination__item">'.$totalPaginas.'</a>';
                    }
                    ?>
                    
                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=User_Admin&pagina=<?= $paginaAtual+1 ?><?= !empty($filtroNivel) ? '&nivel='.$filtroNivel : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>