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
    // Get event data using function from admin_functions.php
    $event = getCalendarEventById($event_id);
    
    if (!$event) {
        $_SESSION['alert_message'] = 'Lembrete não encontrado.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=Calendar');
        exit;
    }
}
// If confirmed, process deletion
else {
    // Delete event using function from admin_functions.php
    $result = deleteCalendarEvent($event_id);
    
    if ($result) {
        // Set success message and redirect
        $_SESSION['alert_message'] = 'Lembrete excluído com sucesso!';
        $_SESSION['alert_type'] = 'success';
        
        header('Location: ' . BASE_URL . '/admin/index.php?page=Calendar');
        exit;
    } else {
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