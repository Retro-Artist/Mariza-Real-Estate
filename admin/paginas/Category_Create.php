<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Initialize variables
$tipo = '';
$categoria = '';
$error = '';

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
            // Check if category already exists
            $stmt = $databaseConnection->prepare(
                "SELECT id FROM sistema_imoveis_categorias 
                 WHERE LOWER(categoria) = LOWER(:categoria) LIMIT 1"
            );
            $stmt->bindParam(':categoria', $categoria);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = 'Uma categoria com este nome já existe.';
            } else {
                // Insert new category
                $stmt = $databaseConnection->prepare(
                    "INSERT INTO sistema_imoveis_categorias (tipo, categoria) 
                     VALUES (:tipo, :categoria)"
                );
                $stmt->bindParam(':tipo', $tipo);
                $stmt->bindParam(':categoria', $categoria);
                $stmt->execute();
                
                // Set success message and redirect
                $_SESSION['alert_message'] = 'Categoria adicionada com sucesso!';
                $_SESSION['alert_type'] = 'success';
                
                header('Location: ' . BASE_URL . '/admin/index.php?page=Category_Admin');
                exit;
            }
        } catch (PDOException $e) {
            logError("Error creating category: " . $e->getMessage());
            $error = 'Ocorreu um erro ao adicionar a categoria. Por favor, tente novamente.';
        }
    }
}
?>

<!-- Add Category Page -->
<div class="admin-page category-create">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Adicionar Nova Categoria</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Category_Admin" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- Category Form -->
    <form method="POST" action="<?= BASE_URL ?>/admin/index.php?page=Category_Create" class="admin-form">
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
                <i class="fas fa-save"></i> Salvar Categoria
            </button>
        </div>
    </form>
</div>

<style>
/* Additional styles for form */
.form-text {
    font-size: var(--font-xs);
    color: var(--admin-text);
    opacity: 0.7;
    margin-top: 5px;
    display: block;
}
</style>