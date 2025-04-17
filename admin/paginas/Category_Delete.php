<?php


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
<main class="Category">
    <!-- Delete Category Confirmation Page -->
    <div class="admin-page category-delete">
        <!-- Page Header -->
        <div class="admin-page__header">
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

</main>