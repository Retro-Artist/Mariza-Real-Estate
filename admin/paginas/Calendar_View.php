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

    // Get event data using function from admin_functions.php
    $event = getCalendarEventById($event_id);

    if (!$event) {
        $_SESSION['alert_message'] = 'Lembrete não encontrado.';
        $_SESSION['alert_type'] = 'error';
        $redirect_url = BASE_URL . '/admin/index.php?page=Calendar';
        $need_redirect = true;
    }
}

// Variáveis para status de atualização
$success_message = '';
$error_message = '';

if (!$need_redirect) {
    // Format dates for display
    $data_inicio = new DateTime($event['data_inicio']);
    $data_fim = new DateTime($event['data_fim']);

    // Process status update
    if (isset($_POST['update_status'])) {
        $new_status = $_POST['status'];
        
        try {
            $stmt = $databaseConnection->prepare("UPDATE sistema_avisos SET status = :status WHERE id = :id");
            $stmt->bindParam(':status', $new_status);
            $stmt->bindParam(':id', $event_id);
            $stmt->execute();
            
            // Atualizar o objeto $event com o novo status
            $event['status'] = $new_status;
            
            $success_message = 'Status atualizado com sucesso!';
        } catch (PDOException $e) {
            logError("Error updating event status: " . $e->getMessage());
            $error_message = 'Ocorreu um erro ao atualizar o status.';
        }
    }

    // Priority class for visual indication
    $priorityClass = '';
    switch ($event['prioridade']) {
        case 'Urgente':
            $priorityClass = 'priority--urgent';
            break;
        case 'Alta':
            $priorityClass = 'priority--high';
            break;
        case 'Normal':
            $priorityClass = 'priority--normal';
            break;
        case 'Baixa':
            $priorityClass = 'priority--low';
            break;
    }

    // Get status class
    $statusClass = strtolower($event['status']);
}
?>

<?php if (!$need_redirect): ?>
<div class="admin-page event-view">
    <!-- Page Header -->
    <div class="admin-page__header">
        <div class="admin-page__actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar_Update&id=<?= $event_id ?>" class="primary-button">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar" class="cancel-button">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert-message alert-message--success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert-message alert-message--error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>
    
    <!-- Main Event Details Card -->
    <div class="admin-card">
        <div class="event-header">
            <div class="event-title-section">
                <h3 class="event-title"><?= htmlspecialchars($event['titulo']) ?></h3>
                <div class="event-badges">
                    <span class="badge <?= $priorityClass ?>">
                        <i class="fas fa-flag"></i> <?= htmlspecialchars($event['prioridade']) ?>
                    </span>
                    <span class="badge status--<?= $statusClass ?>">
                        <i class="fas <?= $statusClass === 'concluído' ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                        <?= htmlspecialchars($event['status']) ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="event-details">
            <!-- Descrição -->
            <?php if (!empty($event['descricao'])): ?>
                <div class="detail-section">
                    <h4 class="detail-section-title">Descrição</h4>
                    <div class="detail-content description-content">
                        <?= nl2br(htmlspecialchars($event['descricao'])) ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Período -->
            <div class="detail-section">
                <h4 class="detail-section-title">Período</h4>
                <div class="detail-row">
                    <div class="detail-group">
                        <label><i class="fas fa-calendar-day"></i> Início:</label>
                        <div class="detail-value">
                            <strong><?= $data_inicio->format('d/m/Y') ?></strong> às 
                            <strong><?= $data_inicio->format('H:i') ?></strong>
                        </div>
                    </div>
                    
                    <div class="detail-group">
                        <label><i class="fas fa-calendar-check"></i> Término:</label>
                        <div class="detail-value">
                            <strong><?= $data_fim->format('d/m/Y') ?></strong> às 
                            <strong><?= $data_fim->format('H:i') ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Atribuição -->
            <div class="detail-section">
                <h4 class="detail-section-title">Atribuição</h4>
                <div class="detail-row">
                    <div class="detail-group">
                        <label><i class="fas fa-user-edit"></i> Criado por:</label>
                        <div class="detail-value">
                            <?= htmlspecialchars($event['criador_nome'] ?? 'N/A') ?>
                        </div>
                    </div>
                    
                    <div class="detail-group">
                        <label><i class="fas fa-user-check"></i> Atribuído para:</label>
                        <div class="detail-value">
                            <?= htmlspecialchars($event['para']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Status Update Card -->
    <div class="admin-card">
        <h3 class="card-title">Atualizar Status</h3>
        
        <form method="POST" action="" class="status-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status do Lembrete:</label>
                    <select id="status" name="status" class="form-control">
                        <option value="Pendente" <?= $event['status'] === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="Concluído" <?= $event['status'] === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                    </select>
                </div>
                
                <div class="form-group form-group--submit">
                    <button type="submit" name="update_status" value="1" class="primary-button">
                        <i class="fas fa-save"></i> Atualizar Status
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Actions Card -->
    <div class="admin-card">
        <h3 class="card-title">Ações</h3>
        
        <div class="action-buttons-container">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar_Update&id=<?= $event_id ?>" class="action-link">
                <i class="fas fa-edit"></i> Editar Lembrete
            </a>
            
            <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar_Delete&id=<?= $event_id ?>" class="action-link action-link--danger">
                <i class="fas fa-trash"></i> Excluir Lembrete
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