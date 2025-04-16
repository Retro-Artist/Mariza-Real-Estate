<?php


// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID do estado não especificado.';
    $_SESSION['alert_type'] = 'error';
    $redirect_url = BASE_URL . '/admin/index.php?page=State_Admin';
    $need_redirect = true;
}


$state_id = (int)$_GET['id'];

// Initialize variables
$state = [];
$error = '';
$confirmDelete = isset($_GET['confirm']) && $_GET['confirm'] === '1';

// If not confirmed, get state data for confirmation page
if (!$confirmDelete) {
    try {
        $stmt = $databaseConnection->prepare(
            "SELECT * FROM sistema_estados WHERE id = :id LIMIT 1"
        );
        $stmt->bindParam(':id', $state_id);
        $stmt->execute();

        $state = $stmt->fetch();

        if (!$state) {
            $_SESSION['alert_message'] = 'Estado não encontrado.';
            $_SESSION['alert_type'] = 'error';
            $redirect_url = BASE_URL . '/admin/index.php?page=State_Admin';
            $need_redirect = true;
        }

        // Check if state has cities associated with it
        $stmt = $databaseConnection->prepare(
            "SELECT COUNT(*) as count FROM sistema_cidades WHERE id_estado = :id_estado"
        );
        $stmt->bindParam(':id_estado', $state_id);
        $stmt->execute();

        $citiesCount = $stmt->fetch()['count'];

        // Check if state has properties directly associated with it
        $stmt = $databaseConnection->prepare(
            "SELECT COUNT(*) as count FROM sistema_imoveis WHERE id_estado = :id_estado"
        );
        $stmt->bindParam(':id_estado', $state_id);
        $stmt->execute();

        $propertiesCount = $stmt->fetch()['count'];

        // Check if state has neighborhoods associated with it
        $stmt = $databaseConnection->prepare(
            "SELECT COUNT(*) as count FROM sistema_bairros WHERE id_estado = :id_estado"
        );
        $stmt->bindParam(':id_estado', $state_id);
        $stmt->execute();

        $neighborhoodsCount = $stmt->fetch()['count'];

        // If state is in use, set error
        if ($citiesCount > 0 || $propertiesCount > 0 || $neighborhoodsCount > 0) {
            $error = 'Este estado não pode ser excluído porque está associado a ';

            $associations = [];
            if ($citiesCount > 0) {
                $associations[] = $citiesCount . ' cidade(s)';
            }
            if ($neighborhoodsCount > 0) {
                $associations[] = $neighborhoodsCount . ' bairro(s)';
            }
            if ($propertiesCount > 0) {
                $associations[] = $propertiesCount . ' imóvel(is)';
            }

            $error .= implode(', ', $associations) . '.';
        }
    } catch (PDOException $e) {
        logError("Error checking state usage: " . $e->getMessage());
        $error = 'Ocorreu um erro ao verificar se o estado pode ser excluído.';
    }
}
// If confirmed, process deletion
else {
    try {
        // First check if state has cities, neighborhoods or properties associated with it
        $stmt = $databaseConnection->prepare(
            "SELECT 
                (SELECT COUNT(*) FROM sistema_cidades WHERE id_estado = :id) as cities_count,
                (SELECT COUNT(*) FROM sistema_bairros WHERE id_estado = :id) as neighborhoods_count,
                (SELECT COUNT(*) FROM sistema_imoveis WHERE id_estado = :id) as properties_count"
        );
        $stmt->bindParam(':id', $state_id);
        $stmt->execute();

        $result = $stmt->fetch();

        if ($result['cities_count'] > 0 || $result['neighborhoods_count'] > 0 || $result['properties_count'] > 0) {
            $_SESSION['alert_message'] = 'Este estado não pode ser excluído porque está em uso.';
            $_SESSION['alert_type'] = 'error';
            $redirect_url = BASE_URL . '/admin/index.php?page=State_Admin';
            $need_redirect = true;
        } else {
            // Delete state
            $stmt = $databaseConnection->prepare("DELETE FROM sistema_estados WHERE id = :id");
            $stmt->bindParam(':id', $state_id);
            $stmt->execute();

            $_SESSION['alert_message'] = 'Estado excluído com sucesso!';
            $_SESSION['alert_type'] = 'success';
            $redirect_url = BASE_URL . '/admin/index.php?page=State_Admin';
            $need_redirect = true;
        }
    } catch (PDOException $e) {
        logError("Error deleting state: " . $e->getMessage());
        $_SESSION['alert_message'] = 'Ocorreu um erro ao excluir o estado.';
        $_SESSION['alert_type'] = 'error';
        $redirect_url = BASE_URL . '/admin/index.php?page=State_Admin';
        $need_redirect = true;
    }
}

?>

<?php if (isset($state) && $state): ?>
    <!-- Delete State Confirmation Page -->
    <div class="admin-page state-delete">
        <!-- Page Header -->
        <div class="admin-page__header">
            <h2 class="admin-page__title">Excluir Estado</h2>
            <a href="<?= BASE_URL ?>/admin/index.php?page=State_Admin" class="cancel-button">
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
                    <a href="<?= BASE_URL ?>/admin/index.php?page=State_Admin" class="primary-button">
                        <i class="fas fa-arrow-left"></i> Voltar para Lista de Estados
                    </a>
                </div>
            <?php else: ?>
                <div class="confirmation-message">
                    <i class="fas fa-exclamation-triangle confirmation-icon"></i>
                    <h3>Tem certeza que deseja excluir este estado?</h3>
                    <p>Você está prestes a excluir o estado "<strong><?= htmlspecialchars($state['nome']) ?></strong>" (<?= htmlspecialchars($state['uf']) ?>).</p>
                    <p>Esta ação não pode ser desfeita.</p>

                    <div class="warning-text">
                        <i class="fas fa-info-circle"></i> Esta ação só será possível se o estado não estiver associado a nenhuma cidade, bairro ou imóvel.
                    </div>
                </div>

                <div class="confirmation-actions">
                    <a href="<?= BASE_URL ?>/admin/index.php?page=State_Admin" class="cancel-button">
                        Cancelar
                    </a>
                    <a href="<?= BASE_URL ?>/admin/index.php?page=State_Delete&id=<?= $state_id ?>&confirm=1" class="delete-button">
                        <i class="fas fa-trash"></i> Sim, Excluir Estado
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
