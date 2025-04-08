<?php
// Security check - redirect if not admin level
if ($_SESSION['admin_level'] !== 'Administrador' && $_SESSION['admin_level'] != '1') {
    $_SESSION['alert_message'] = 'Você não tem permissão para acessar esta área.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

// Initialize variables
$alertMessage = '';
$alertType = '';

// Get search and filter parameters
$search = trim($_GET['busca'] ?? '');
$nivelFilter = $_GET['nivel'] ?? '';
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;

// Process filters
$filters = [];
if (!empty($search)) {
    $filters['busca'] = $search;
}
if (!empty($nivelFilter)) {
    $filters['nivel'] = $nivelFilter;
}

// Get users with pagination
$usersData = getAdminUsers($filters, $currentPage, $perPage);
$users = $usersData['users'];
$totalUsers = $usersData['total'];
$totalPages = $usersData['totalPages'];
?>

<div class="admin-page__header">
    <h2 class="admin-page__title">Gerenciar Usuários</h2>
    
    <div class="admin-page__actions">
        <a href="<?= BASE_URL ?>/admin/index.php?page=User_Create" class="primary-button">
            <i class="fas fa-plus"></i> Novo Usuário
        </a>
    </div>
</div>

<div class="admin-card user-admin">
    <!-- Filter Form -->
    <form method="GET" action="" class="filter-form">
        <input type="hidden" name="page" value="User_Admin">
        
        <div class="form-row">
            <div class="form-group">
                <label for="busca">Buscar</label>
                <input type="text" id="busca" name="busca" class="form-control" 
                       value="<?= htmlspecialchars($search) ?>" placeholder="Nome ou email">
            </div>
            
            <div class="form-group">
                <label for="nivel">Nível de Acesso</label>
                <select id="nivel" name="nivel" class="form-control">
                    <option value="">Todos</option>
                    <option value="Administrador" <?= $nivelFilter === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                    <option value="Editor" <?= $nivelFilter === 'Editor' ? 'selected' : '' ?>>Editor</option>
                </select>
            </div>
            
            <div class="form-group form-group--submit">
                <button type="submit" class="primary-button search-button">
                    <i class="fas fa-search"></i> Filtrar
                </button>
            </div>
        </div>
    </form>
    
    <!-- Users Table -->
    <div class="table-responsive">
        <?php if (!empty($users)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Nível de Acesso</th>
                        <th width="120">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['nome']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?php if ($user['nivel'] === '1'): ?>
                                    <span class="badge badge--blue">Administrador</span>
                                <?php elseif ($user['nivel'] === '0'): ?>
                                    <span class="badge badge--green">Editor</span>
                                <?php else: ?>
                                    <?= htmlspecialchars($user['nivel']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="<?= BASE_URL ?>/admin/index.php?page=User_Update&id=<?= $user['id'] ?>" 
                                   class="action-button action-button--edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <?php if ($user['id'] !== $_SESSION['admin_id']): ?>
                                    <a href="<?= BASE_URL ?>/admin/index.php?page=User_Delete&id=<?= $user['id'] ?>" 
                                       class="action-button action-button--delete" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="action-button action-button--disabled" title="Você não pode excluir sua própria conta">
                                        <i class="fas fa-trash"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php 
                        // Build pagination URL with all current GET parameters
                        $paginationParams = $_GET;
                        $paginationParams['page'] = 'User_Admin';
                        $paginationParams['pg'] = $i;
                        $paginationUrl = BASE_URL . '/admin/index.php?' . http_build_query($paginationParams);
                        ?>
                        <a href="<?= $paginationUrl ?>" 
                           class="pagination__item <?= $i === $currentPage ? 'pagination__item--active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="empty-state">
                <p>Nenhum usuário encontrado.</p>
                <a href="<?= BASE_URL ?>/admin/index.php?page=User_Create" class="primary-button">
                    <i class="fas fa-plus"></i> Adicionar Usuário
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>