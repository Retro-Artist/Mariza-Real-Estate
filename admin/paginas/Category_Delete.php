<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID da categoria não especificado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Category_Admin');
    exit;
}

$category_id = (int)$_GET['id'];

// Initialize variables
$categoria = '';
$error = '';
$confirmDelete = isset($_GET['confirm']) && $_GET['confirm'] === '1';

// If not confirmed, get category data for confirmation page
if (!$confirmDelete) {
    // Get category data using our function from admin_functions.php
    $categoryData = getAdminCategoryById($category_id);
    
    if (!$categoryData) {
        $_SESSION['alert_message'] = 'Categoria não encontrada.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=Category_Admin');
        exit;
    }
    
    $categoria = $categoryData['categoria'];
}
// If confirmed, process deletion
else {
    // Delete category using our function from admin_functions.php
    $result = deleteCategory($category_id);
    
    if ($result) {
        // Set success message and redirect
        $_SESSION['alert_message'] = 'Categoria excluída com sucesso!';
        $_SESSION['alert_type'] = 'success';
        
        header('Location: ' . BASE_URL . '/admin/index.php?page=Category_Admin');
        exit;
    } else {
        $_SESSION['alert_message'] = 'Esta categoria não pode ser excluída pois existem imóveis associados a ela.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=Category_Admin');
        exit;
    }
}
?>

<!-- Delete Category Confirmation Page -->
<div class="admin-page category-delete">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Excluir Categoria</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Category_Admin" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- Confirmation Card -->
    <div class="admin-card">
        <div class="confirmation-message">
            <i class="fas fa-exclamation-triangle confirmation-icon"></i>
            <h3>Tem certeza que deseja excluir esta categoria?</h3>
            <p>Você está prestes a excluir a categoria <strong><?= htmlspecialchars($categoria) ?></strong>.</p>
            <p>Esta ação não pode ser desfeita.</p>
        </div>
        
        <div class="confirmation-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Category_Admin" class="cancel-button">
                Cancelar
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Category_Delete&id=<?= $category_id ?>&confirm=1" class="delete-button">
                <i class="fas fa-trash"></i> Sim, Excluir Categoria
            </a>
        </div>
    </div>
</div>

<style>
/* Confirmation message styles */
.confirmation-message {
    text-align: center;
    padding: 20px;
}

.confirmation-icon {
    font-size: 48px;
    color: var(--admin-red);
    margin-bottom: 20px;
}

.confirmation-message h3 {
    font-size: var(--font-lg);
    margin-bottom: 15px;
}

.confirmation-message p {
    margin-bottom: 10px;
}

.confirmation-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 30px;
}

.delete-button {
    background-color: var(--admin-red);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: var(--border-radius);
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: var(--transition);
}

.delete-button:hover {
    background-color: #c0392b;
}
</style>