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

// Get event data
try {
    $stmt = $databaseConnection->prepare(
        "SELECT a.*, u.nome as criador_nome 
         FROM sistema_avisos a
         LEFT JOIN sistema_usuarios u ON a.id_usuario = u.id
         WHERE a.id = :id LIMIT 1"
    );
    $stmt->bindParam(':id', $event_id);
    $stmt->execute();
    
    $event = $stmt->fetch();
    
    if (!$event) {
        $_SESSION['alert_message'] = 'Lembrete não encontrado.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=Calendar');
        exit;
    }
    
    // Format dates for display
    $data_inicio = new DateTime($event['data_inicio']);
    $data_fim = new DateTime($event['data_fim']);
    
} catch (PDOException $e) {
    logError("Error fetching event details: " . $e->getMessage());
    $_SESSION['alert_message'] = 'Erro ao buscar detalhes do lembrete.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Calendar');
    exit;
}

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

<style>
/* Event View Styles */
.event-header {
    border-bottom: 1px solid var(--admin-border);
    padding-bottom: 15px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 10px;
}

.event-title {
    font-size: var(--font-xl);
    margin: 0;
    font-family: var(--font-secondary);
    flex: 1;
}

.event-meta {
    display: flex;
    gap: 10px;
}

.event-priority,
.event-status {
    padding: 5px 10px;
    border-radius: 3px;
    font-size: var(--font-sm);
    font-weight: 600;
    color: white;
}

.priority--urgent {
    background-color: #dc3545;
}

.priority--high {
    background-color: #fd7e14;
}

.priority--normal {
    background-color: #007bff;
}

.priority--low {
    background-color: #6c757d;
}

.status--pendente {
    background-color: #ffc107;
    color: #212529;
}

.status--concluído {
    background-color: #28a745;
}

.event-details {
    margin-bottom: 20px;
}

.detail-row {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.detail-group {
    flex: 1;
    min-width: 250px;
    margin-bottom: 15px;
}

.detail-group label {
    font-weight: 600;
    color: var(--admin-text);
    margin-bottom: 5px;
    display: block;
}

.detail-content {
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 3px;
    min-height: 24px;
}

.event-actions {
    border-top: 1px solid var(--admin-border);
    padding-top: 20px;
    margin-top: 20px;
}

.status-form {
    display: flex;
    align-items: flex-end;
    gap: 15px;
    flex-wrap: wrap;
}

.status-form__group {
    flex: 1;
    min-width: 200px;
}

.status-form__group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.action-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    background-color: var(--admin-blue);
    color: white;
    border-radius: var(--border-radius);
    font-size: var(--font-base);
    text-decoration: none;
    transition: var(--transition);
}

.action-button--delete {
    background-color: var(--admin-red);
}

.action-button:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .admin-page__header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .admin-page__actions {
        display: flex;
        gap: 10px;
        width: 100%;
    }
    
    .admin-page__actions a {
        flex: 1;
        text-align: center;
    }
    
    .detail-group {
        width: 100%;
    }
}
</style>