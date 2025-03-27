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
$tipo = '';
$categoria = '';
$error = '';

// Get category data
try {
    $stmt = $databaseConnection->prepare(
        "SELECT * FROM sistema_imoveis_categorias WHERE id = :id LIMIT 1"
    );
    $stmt->bindParam(':id', $category_id);
    $stmt->execute();
    
    $categoryData = $stmt->fetch();
    
    if (!$categoryData) {
        $_SESSION['alert_message'] = 'Categoria não encontrada.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=Category_Admin');
        exit;
    }
    
    // Set values for form
    $tipo = $categoryData['tipo'];
    $categoria = $categoryData['categoria'];
    
} catch (PDOException $e) {
    logError("Error fetching category data: " . $e->getMessage());
    $_SESSION['alert_message'] = 'Erro ao buscar dados da categoria.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Category_Admin');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $tipo = trim($_POST['tipo'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    
    // Validate form data
    if (empty($tipo) || empty($categoria)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            // Check if category name already exists (excluding current category)
            $stmt = $databaseConnection->prepare(
                "SELECT id FROM sistema_imoveis_categorias 
                 WHERE LOWER(categoria) = LOWER(:categoria) AND id != :id LIMIT 1"
            );
            $stmt->bindParam(':categoria', $categoria);
            $stmt->bindParam(':id', $category_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = 'Uma categoria com este nome já existe.';
            } else {
                // Update category
                $stmt = $databaseConnection->prepare(
                    "UPDATE sistema_imoveis_categorias 
                     SET tipo = :tipo, categoria = :categoria
                     WHERE id = :id"
                );
                $stmt->bindParam(':tipo', $tipo);
                $stmt->bindParam(':categoria', $categoria);
                $stmt->bindParam(':id', $category_id);
                $stmt->execute();
                
                // Set success message and redirect
                $_SESSION['alert_message'] = 'Categoria atualizada com sucesso!';
                $_SESSION['alert_type'] = 'success';
                
                header('Location: ' . BASE_URL . '/admin/index.php?page=Category_Admin');
                exit;
            }
        } catch (PDOException $e) {
            logError("Error updating category: " . $e->getMessage());
            $error = 'Ocorreu um erro ao atualizar a categoria. Por favor, tente novamente.';
        }
    }
}
?>

<!-- Update Category Page -->
<div class="admin-page category-update">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Editar Categoria</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Category_Admin" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- Category Form -->
    <form method="POST" action="<?= BASE_URL ?>/admin/index.php?page=Category_Update&id=<?= $category_id ?>" class="admin-form">
        <?php if (!empty($error)): ?>
            <div class="alert-message alert-message--error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo" class="form-control" required>
                    <option value="">Selecione...</option>
                    <option value="Residencial" <?= $tipo === 'Residencial' ? 'selected' : '' ?>>Residencial</option>
                    <option value="Comercial" <?= $tipo === 'Comercial' ? 'selected' : '' ?>>Comercial</option>
                    <option value="Rural" <?= $tipo === 'Rural' ? 'selected' : '' ?>>Rural</option>
                    <option value="Industrial" <?= $tipo === 'Industrial' ? 'selected' : '' ?>>Industrial</option>
                    <option value="Terreno" <?= $tipo === 'Terreno' ? 'selected' : '' ?>>Terreno</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="categoria">Nome da Categoria</label>
                <input type="text" id="categoria" name="categoria" class="form-control" 
                       value="<?= htmlspecialchars($categoria) ?>" required>
                <small class="form-text">Ex: Casa, Apartamento, Sala Comercial, etc.</small>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Category_Admin" class="cancel-button">Cancelar</a>
            <button type="submit" class="primary-button">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
        </div>
    </form>
</div>