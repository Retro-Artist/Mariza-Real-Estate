<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Get all categories using our function from admin_functions.php
$categorias = getAdminCategories();

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

<!-- Categories List Page -->
<div class="admin-page category-admin">
    <?php if (!empty($alertMessage)): ?>
        <div class="alert-message alert-message--<?= $alertType ?>">
            <?= htmlspecialchars($alertMessage) ?>
        </div>
    <?php endif; ?>
    
    <!-- Page Header with Add Button -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Gerenciar Categorias</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Category_Create" class="primary-button">
            <i class="fas fa-plus"></i> Nova Categoria
        </a>
    </div>
    
    <!-- Categories Table -->
    <div class="admin-card">
        <?php if (empty($categorias)): ?>
            <div class="empty-state">
                <p>Nenhuma categoria cadastrada ainda.</p>
                <a href="<?= BASE_URL ?>/admin/index.php?page=Category_Create" class="primary-button">
                    Adicionar Categoria
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th width="120">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td><?= $categoria['id'] ?></td>
                                <td><?= htmlspecialchars($categoria['tipo']) ?></td>
                                <td><?= htmlspecialchars($categoria['categoria']) ?></td>
                                <td class="actions">
                                    <a href="<?= BASE_URL ?>/admin/index.php?page=Category_Update&id=<?= $categoria['id'] ?>" class="action-button action-button--edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/index.php?page=Category_Delete&id=<?= $categoria['id'] ?>" class="action-button action-button--delete delete-button" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Additional styles for category admin page */
.admin-page__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.admin-page__title {
    font-size: var(--font-xl);
    font-family: var(--font-secondary);
    margin: 0;
}

.admin-card {
    background-color: var(--admin-card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 20px;
    margin-bottom: 30px;
}
</style>