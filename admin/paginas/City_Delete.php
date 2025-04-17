<?php

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID da cidade não especificado.';
    $_SESSION['alert_type'] = 'error';
    $redirect_url = BASE_URL . '/admin/index.php?page=City_Admin';
    $need_redirect = true;
}


$city_id = (int)$_GET['id'];

// Initialize variables
$city = [];
$error = '';
$confirmDelete = isset($_GET['confirm']) && $_GET['confirm'] === '1';

// If not confirmed, get city data for confirmation page
if (!$confirmDelete) {
    try {
        $stmt = $databaseConnection->prepare(
            "SELECT c.*, e.nome as estado_nome, e.uf
                 FROM sistema_cidades c
                 LEFT JOIN sistema_estados e ON c.id_estado = e.id
                 WHERE c.id = :id LIMIT 1"
        );
        $stmt->bindParam(':id', $city_id);
        $stmt->execute();

        $city = $stmt->fetch();

        if (!$city) {
            $_SESSION['alert_message'] = 'Cidade não encontrada.';
            $_SESSION['alert_type'] = 'error';
            $redirect_url = BASE_URL . '/admin/index.php?page=City_Admin';
            $need_redirect = true;
        }

        // Check if city has neighborhoods associated with it
        $stmt = $databaseConnection->prepare(
            "SELECT COUNT(*) as count FROM sistema_bairros WHERE id_cidade = :id_cidade"
        );
        $stmt->bindParam(':id_cidade', $city_id);
        $stmt->execute();

        $neighborhoodsCount = $stmt->fetch()['count'];

        // Check if city has properties associated with it
        $stmt = $databaseConnection->prepare(
            "SELECT COUNT(*) as count FROM sistema_imoveis WHERE id_cidade = :id_cidade"
        );
        $stmt->bindParam(':id_cidade', $city_id);
        $stmt->execute();

        $propertiesCount = $stmt->fetch()['count'];

        // If city is in use, set error
        if ($neighborhoodsCount > 0 || $propertiesCount > 0) {
            $error = 'Esta cidade não pode ser excluída porque está associada a ';

            if ($neighborhoodsCount > 0 && $propertiesCount > 0) {
                $error .= $neighborhoodsCount . ' bairro(s) e ' . $propertiesCount . ' imóvel(is).';
            } elseif ($neighborhoodsCount > 0) {
                $error .= $neighborhoodsCount . ' bairro(s).';
            } else {
                $error .= $propertiesCount . ' imóvel(is).';
            }
        }
    } catch (PDOException $e) {
        logError("Error checking city usage: " . $e->getMessage());
        $error = 'Ocorreu um erro ao verificar se a cidade pode ser excluída.';
    }
}
// If confirmed, process deletion
else {
    try {
        // First check if city has neighborhoods or properties associated with it
        $stmt = $databaseConnection->prepare(
            "SELECT 
                (SELECT COUNT(*) FROM sistema_bairros WHERE id_cidade = :id) as neighborhoods_count,
                (SELECT COUNT(*) FROM sistema_imoveis WHERE id_cidade = :id) as properties_count"
        );
        $stmt->bindParam(':id', $city_id);
        $stmt->execute();

        $result = $stmt->fetch();

        if ($result['neighborhoods_count'] > 0 || $result['properties_count'] > 0) {
            $_SESSION['alert_message'] = 'Esta cidade não pode ser excluída porque está em uso.';
            $_SESSION['alert_type'] = 'error';
            $redirect_url = BASE_URL . '/admin/index.php?page=City_Admin';
            $need_redirect = true;
        } else {
            // Delete city
            $stmt = $databaseConnection->prepare("DELETE FROM sistema_cidades WHERE id = :id");
            $stmt->bindParam(':id', $city_id);
            $stmt->execute();

            $_SESSION['alert_message'] = 'Cidade excluída com sucesso!';
            $_SESSION['alert_type'] = 'success';
            $redirect_url = BASE_URL . '/admin/index.php?page=City_Admin';
            $need_redirect = true;
        }
    } catch (PDOException $e) {
        logError("Error deleting city: " . $e->getMessage());
        $_SESSION['alert_message'] = 'Ocorreu um erro ao excluir a cidade.';
        $_SESSION['alert_type'] = 'error';
        $redirect_url = BASE_URL . '/admin/index.php?page=City_Admin';
        $need_redirect = true;
    }
}

?>

<?php if (isset($city) && $city): ?>
    <main class="Location">
        <!-- Delete City Confirmation Page -->
        <div class="admin-page city-delete">
            <!-- Page Header -->
            <div class="admin-page__header">
                <a href="<?= BASE_URL ?>/admin/index.php?page=City_Admin" class="cancel-button">
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
                        <a href="<?= BASE_URL ?>/admin/index.php?page=City_Admin" class="primary-button">
                            <i class="fas fa-arrow-left"></i> Voltar para Lista de Cidades
                        </a>
                    </div>
                <?php else: ?>
                    <div class="confirmation-message">
                        <i class="fas fa-exclamation-triangle confirmation-icon"></i>
                        <h3>Tem certeza que deseja excluir esta cidade?</h3>
                        <p>Você está prestes a excluir a cidade "<strong><?= htmlspecialchars($city['nome']) ?></strong>" do estado de <strong><?= htmlspecialchars($city['estado_nome']) ?> (<?= htmlspecialchars($city['uf']) ?>)</strong>.</p>
                        <p>Esta ação não pode ser desfeita.</p>

                        <div class="warning-text">
                            <i class="fas fa-info-circle"></i> Esta ação só será possível se a cidade não estiver associada a nenhum bairro ou imóvel.
                        </div>
                    </div>

                    <div class="confirmation-actions">
                        <a href="<?= BASE_URL ?>/admin/index.php?page=City_Admin" class="cancel-button">
                            Cancelar
                        </a>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=City_Delete&id=<?= $city_id ?>&confirm=1" class="delete-button">
                            <i class="fas fa-trash"></i> Sim, Excluir Cidade
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
<?php endif; ?>