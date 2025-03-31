<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID do imóvel não especificado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
    exit;
}

$property_id = (int)$_GET['id'];

// Initialize variables
$imovel = [];
$error = '';
$confirmDelete = isset($_GET['confirm']) && $_GET['confirm'] === '1';

// If not confirmed, get property data for confirmation page
if (!$confirmDelete) {
    try {
        $stmt = $databaseConnection->prepare(
            "SELECT i.*, c.categoria 
             FROM sistema_imoveis i
             LEFT JOIN sistema_imoveis_categorias c ON i.id_categoria = c.id
             WHERE i.id = :id LIMIT 1"
        );
        $stmt->bindParam(':id', $property_id);
        $stmt->execute();
        
        $imovel = $stmt->fetch();
        
        if (!$imovel) {
            $_SESSION['alert_message'] = 'Imóvel não encontrado.';
            $_SESSION['alert_type'] = 'error';
            header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
            exit;
        }
        
    } catch (PDOException $e) {
        logError("Error fetching property data: " . $e->getMessage());
        $_SESSION['alert_message'] = 'Erro ao buscar dados do imóvel.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
        exit;
    }
}
// If confirmed, process deletion
else {
    // Delete property using function from admin_functions.php
    $result = deleteProperty($property_id);
    
    if ($result) {
        // Set success message and redirect
        $_SESSION['alert_message'] = 'Imóvel excluído com sucesso!';
        $_SESSION['alert_type'] = 'success';
        
        header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
        exit;
    } else {
        $_SESSION['alert_message'] = 'Ocorreu um erro ao excluir o imóvel.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
        exit;
    }
}
?>

<!-- Delete Property Confirmation Page -->
<div class="admin-page property-delete">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Excluir Imóvel</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Admin" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- Confirmation Card -->
    <div class="admin-card">
        <div class="confirmation-message">
            <i class="fas fa-exclamation-triangle confirmation-icon"></i>
            <h3>Tem certeza que deseja excluir este imóvel?</h3>
            <p>Você está prestes a excluir o imóvel <strong><?= htmlspecialchars($imovel['titulo']) ?></strong>.</p>
            <p>Código: <strong><?= htmlspecialchars($imovel['codigo']) ?></strong></p>
            <p>Categoria: <strong><?= htmlspecialchars($imovel['categoria'] ?? 'N/A') ?></strong></p>
            <p>Valor: <strong><?= formatCurrency($imovel['valor']) ?></strong></p>
            <p>Esta ação não pode ser desfeita e todas as imagens associadas serão excluídas permanentemente.</p>
        </div>
        
        <div class="confirmation-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Admin" class="cancel-button">
                Cancelar
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Delete&id=<?= $property_id ?>&confirm=1" class="delete-button">
                <i class="fas fa-trash"></i> Sim, Excluir Imóvel
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