<?php


// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID do lembrete não especificado.';
    $_SESSION['alert_type'] = 'error';
    $redirect_url = BASE_URL . '/admin/index.php?page=Calendar';
    $need_redirect = true;
}

if (!$need_redirect) {
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
            $redirect_url = BASE_URL . '/admin/index.php?page=Calendar';
            $need_redirect = true;
        }
    }
    // If confirmed, process deletion
    else {
        // Delete event using function from admin_functions.php
        $result = deleteCalendarEvent($event_id);
        
        if ($result) {
            // Set success message and prepare for redirect
            $_SESSION['alert_message'] = 'Lembrete excluído com sucesso!';
            $_SESSION['alert_type'] = 'success';
            
            $redirect_url = BASE_URL . '/admin/index.php?page=Calendar';
            $need_redirect = true;
        } else {
            $_SESSION['alert_message'] = 'Ocorreu um erro ao excluir o lembrete.';
            $_SESSION['alert_type'] = 'error';
            $redirect_url = BASE_URL . '/admin/index.php?page=Calendar';
            $need_redirect = true;
        }
    }
}
?>

<?php if (!$need_redirect && isset($event) && $event): ?>
<!-- Delete Event Confirmation Page -->
<div class="admin-page event-delete">
    <!-- Page Header -->
    <div class="admin-page__header">
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
<?php endif; ?>

<?php if ($need_redirect): ?>
<script>
    // Redirecionamento via JavaScript
    window.location.href = "<?= $redirect_url ?>";
</script>
<?php endif; ?>