<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID do lembrete não especificado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Calendar');
    exit;
}

$event_id = (int)$_GET['id'];

// Initialize variables
$event = [];
$error = '';
$confirmDelete = isset($_GET['confirm']) && $_GET['confirm'] === '1';

// If not confirmed, get event data for confirmation page
if (!$confirmDelete) {
    try {
        $stmt = $databaseConnection->prepare("SELECT * FROM sistema_avisos WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $event_id);
        $stmt->execute();
        
        $event = $stmt->fetch();
        
        if (!$event) {
            $_SESSION['alert_message'] = 'Lembrete não encontrado.';
            $_SESSION['alert_type'] = 'error';
            header('Location: ' . BASE_URL . '/admin/index.php?page=Calendar');
            exit;
        }
        
    } catch (PDOException $e) {
        logError("Error fetching event data: " . $e->getMessage());
        $_SESSION['alert_message'] = 'Erro ao buscar dados do lembrete.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=Calendar');
        exit;
    }
}
// If confirmed, process deletion
else {
    try {
        // Delete event
        $stmt = $databaseConnection->prepare("DELETE FROM sistema_avisos WHERE id = :id");
        $stmt->bindParam(':id', $event_id);
        $stmt->execute();
        
        // Set success message and redirect
        $_SESSION['alert_message'] = 'Lembrete excluído com sucesso!';
        $_SESSION['alert_type'] = 'success';
        
        header('Location: ' . BASE_URL . '/admin/index.php?page=Calendar');
        exit;
        
    } catch (PDOException $e) {
        logError("Error deleting event: " . $e->getMessage());
        $_SESSION['alert_message'] = 'Ocorreu um erro ao excluir o lembrete.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=Calendar');
        exit;
    }
}
?>

<!-- Delete Event Confirmation Page -->
<div class="admin-page event-delete">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Excluir Lembrete</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- Confirmation Card -->
    <div class="admin-card">
        <div class="confirmation-message">
            <i class="fas fa-exclamation-triangle confirmation-icon"></i>
            <h3>Tem certeza que deseja excluir este lembrete?</h3>
            <p>Você está prestes a excluir o lembrete "<strong><?= htmlspecialchars($event['titulo']) ?></strong>".</p>
            <p>Esta ação não pode ser desfeita.</p>
        </div>
        
        <div class="confirmation-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar" class="cancel-button">
                Cancelar
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar_Delete&id=<?= $event_id ?>&confirm=1" class="delete-button">
                <i class="fas fa-trash"></i> Sim, Excluir Lembrete
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