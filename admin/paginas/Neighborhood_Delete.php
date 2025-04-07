<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    // Instead of using header() directly, store the URL for later redirection via JavaScript
    $redirect_url = BASE_URL . '/admin/Admin_Login.php';
    $need_redirect = true;
} else {
    $need_redirect = false;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID do bairro não especificado.';
    $_SESSION['alert_type'] = 'error';
    $redirect_url = BASE_URL . '/admin/index.php?page=Neighborhood_Admin';
    $need_redirect = true;
}

if (!$need_redirect) {
    $neighborhood_id = (int)$_GET['id'];

    // Initialize variables
    $neighborhood = [];
    $error = '';
    $confirmDelete = isset($_GET['confirm']) && $_GET['confirm'] === '1';

    // If not confirmed, get neighborhood data for confirmation page
    if (!$confirmDelete) {
        try {
            $stmt = $databaseConnection->prepare(
                "SELECT b.*, c.nome as cidade_nome, e.nome as estado_nome, e.uf
                 FROM sistema_bairros b
                 LEFT JOIN sistema_cidades c ON b.id_cidade = c.id
                 LEFT JOIN sistema_estados e ON b.id_estado = e.id
                 WHERE b.id = :id LIMIT 1"
            );
            $stmt->bindParam(':id', $neighborhood_id);
            $stmt->execute();
            
            $neighborhood = $stmt->fetch();
            
            if (!$neighborhood) {
                $_SESSION['alert_message'] = 'Bairro não encontrado.';
                $_SESSION['alert_type'] = 'error';
                $redirect_url = BASE_URL . '/admin/index.php?page=Neighborhood_Admin';
                $need_redirect = true;
            }

            // Check if neighborhood has properties associated with it
            $stmt = $databaseConnection->prepare(
                "SELECT COUNT(*) as count FROM sistema_imoveis WHERE id_bairro = :id_bairro"
            );
            $stmt->bindParam(':id_bairro', $neighborhood_id);
            $stmt->execute();
            
            $propertiesCount = $stmt->fetch()['count'];
            
            // If neighborhood is in use, set error
            if ($propertiesCount > 0) {
                $error = 'Este bairro não pode ser excluído porque está associado a ' . $propertiesCount . ' imóvel(is).';
            }
        } catch (PDOException $e) {
            logError("Error checking neighborhood usage: " . $e->getMessage());
            $error = 'Ocorreu um erro ao verificar se o bairro pode ser excluído.';
        }
    } 
    // If confirmed, process deletion
    else {
        try {
            // First check if neighborhood has properties associated with it
            $stmt = $databaseConnection->prepare(
                "SELECT COUNT(*) as count FROM sistema_imoveis WHERE id_bairro = :id_bairro"
            );
            $stmt->bindParam(':id_bairro', $neighborhood_id);
            $stmt->execute();
            
            $propertiesCount = $stmt->fetch()['count'];
            
            if ($propertiesCount > 0) {
                $_SESSION['alert_message'] = 'Este bairro não pode ser excluído porque está em uso.';
                $_SESSION['alert_type'] = 'error';
                $redirect_url = BASE_URL . '/admin/index.php?page=Neighborhood_Admin';
                $need_redirect = true;
            } else {
                // Delete neighborhood
                $stmt = $databaseConnection->prepare("DELETE FROM sistema_bairros WHERE id = :id");
                $stmt->bindParam(':id', $neighborhood_id);
                $stmt->execute();
                
                $_SESSION['alert_message'] = 'Bairro excluído com sucesso!';
                $_SESSION['alert_type'] = 'success';
                $redirect_url = BASE_URL . '/admin/index.php?page=Neighborhood_Admin';
                $need_redirect = true;
            }
        } catch (PDOException $e) {
            logError("Error deleting neighborhood: " . $e->getMessage());
            $_SESSION['alert_message'] = 'Ocorreu um erro ao excluir o bairro.';
            $_SESSION['alert_type'] = 'error';
            $redirect_url = BASE_URL . '/admin/index.php?page=Neighborhood_Admin';
            $need_redirect = true;
        }
    }
}
?>

<?php if (!$need_redirect && isset($neighborhood) && $neighborhood): ?>
<!-- Delete Neighborhood Confirmation Page -->
<div class="admin-page neighborhood-delete">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Excluir Bairro</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Neighborhood_Admin" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- Confirmation Card -->
    <div class="admin-card">
        <?php if (!empty($error)): ?>
            <div class="alert-message alert-message--error">
                <?= htmlspecialchars($error) ?>
            </div>
            
            <div class="form-actions mt-4">
                <a href="<?= BASE_URL ?>/admin/index.php?page=Neighborhood_Admin" class="primary-button">
                    <i class="fas fa-arrow-left"></i> Voltar para Lista de Bairros
                </a>
            </div>
        <?php else: ?>
            <div class="confirmation-message">
                <i class="fas fa-exclamation-triangle confirmation-icon"></i>
                <h3>Tem certeza que deseja excluir este bairro?</h3>
                <p>Você está prestes a excluir o bairro "<strong><?= htmlspecialchars($neighborhood['bairro']) ?></strong>" da cidade de <strong><?= htmlspecialchars($neighborhood['cidade_nome']) ?></strong> no estado de <strong><?= htmlspecialchars($neighborhood['estado_nome']) ?> (<?= htmlspecialchars($neighborhood['uf']) ?>)</strong>.</p>
                <p>Esta ação não pode ser desfeita.</p>
                
                <div class="warning-text">
                    <i class="fas fa-info-circle"></i> Esta ação só será possível se o bairro não estiver associado a nenhum imóvel.
                </div>
            </div>
            
            <div class="confirmation-actions">
                <a href="<?= BASE_URL ?>/admin/index.php?page=Neighborhood_Admin" class="cancel-button">
                    Cancelar
                </a>
                <a href="<?= BASE_URL ?>/admin/index.php?page=Neighborhood_Delete&id=<?= $neighborhood_id ?>&confirm=1" class="delete-button">
                    <i class="fas fa-trash"></i> Sim, Excluir Bairro
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($need_redirect): ?>
<script>
    // JavaScript redirect if checks fail
    window.location.href = "<?= $redirect_url ?>";
</script>
<?php endif; ?>