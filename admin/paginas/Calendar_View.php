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

// Get event data using function from admin_functions.php
$event = getCalendarEventById($event_id);

if (!$event) {
    $_SESSION['alert_message'] = 'Lembrete não encontrado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Calendar');
    exit;
}

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
        
        $_SESSION['alert_message'] = 'Status atualizado com sucesso!';
        $_SESSION['alert_type'] = 'success';
        
        // Refresh the page to show updated data
        header('Location: ' . BASE_URL . '/admin/index.php?page=Calendar_View&id=' . $event_id);
        exit;
    } catch (PDOException $e) {
        logError("Error updating event status: " . $e->getMessage());
        $error = 'Ocorreu um erro ao atualizar o status.';
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
?>

<!-- HTML content remains unchanged -->

<div class="admin-page event-view">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Detalhes do Lembrete</h2>
        <div class="admin-page__actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar_Update&id=<?= $event_id ?>" class="primary-button">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar" class="cancel-button">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
    
    <!-- Event Details Card -->
    <div class="admin-card">
        <div class="event-header">
            <h3 class="event-title"><?= htmlspecialchars($event['titulo']) ?></h3>
            <div class="event-meta">
                <div class="event-priority <?= $priorityClass ?>">
                    <?= htmlspecialchars($event['prioridade']) ?>
                </div>
                <div class="event-status status--<?= strtolower($event['status']) ?>">
                    <?= htmlspecialchars($event['status']) ?>
                </div>
            </div>
        </div>
        
        <div class="event-details">
            <div class="detail-group">
                <label>Descrição:</label>
                <div class="detail-content">
                    <?= nl2br(htmlspecialchars($event['descricao'])) ?>
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-group">
                    <label>Data de Início:</label>
                    <div class="detail-content">
                        <?= $data_inicio->format('d/m/Y H:i') ?>
                    </div>
                </div>
                
                <div class="detail-group">
                    <label>Data de Término:</label>
                    <div class="detail-content">
                        <?= $data_fim->format('d/m/Y H:i') ?>
                    </div>
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-group">
                    <label>Criado por:</label>
                    <div class="detail-content">
                        <?= htmlspecialchars($event['criador_nome'] ?? 'N/A') ?>
                    </div>
                </div>
                
                <div class="detail-group">
                    <label>Atribuído para:</label>
                    <div class="detail-content">
                        <?= htmlspecialchars($event['para']) ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Update Form -->
        <div class="event-actions">
            <form method="POST" action="" class="status-form">
                <div class="status-form__group">
                    <label for="status">Atualizar Status:</label>
                    <select id="status" name="status" class="form-control">
                        <option value="Pendente" <?= $event['status'] === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="Concluído" <?= $event['status'] === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                    </select>
                </div>
                
                <button type="submit" name="update_status" value="1" class="primary-button">
                    <i class="fas fa-save"></i> Atualizar Status
                </button>
            </form>
        </div>
    </div>
    
    <!-- Actions Card -->
    <div class="admin-card">
        <div class="action-buttons">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar_Update&id=<?= $event_id ?>" class="action-button">
                <i class="fas fa-edit"></i> Editar Lembrete
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar_Delete&id=<?= $event_id ?>" class="action-button action-button--delete">
                <i class="fas fa-trash"></i> Excluir Lembrete
            </a>
        </div>
    </div>
</div>