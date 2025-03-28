<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID do atendimento não especificado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento');
    exit;
}

$atendimento_id = (int)$_GET['id'];

// Get service request data
try {
    $stmt = $databaseConnection->prepare(
        "SELECT * FROM sistema_interacao WHERE id = :id LIMIT 1"
    );
    $stmt->bindParam(':id', $atendimento_id);
    $stmt->execute();
    
    $atendimento = $stmt->fetch();
    
    if (!$atendimento) {
        $_SESSION['alert_message'] = 'Atendimento não encontrado.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento');
        exit;
    }
    
} catch (PDOException $e) {
    logError("Error fetching service request details: " . $e->getMessage());
    $_SESSION['alert_message'] = 'Erro ao buscar detalhes do atendimento.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento');
    exit;
}

// Process status update
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    
    try {
        $stmt = $databaseConnection->prepare("UPDATE sistema_interacao SET status = :status WHERE id = :id");
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':id', $atendimento_id);
        $stmt->execute();
        
        $_SESSION['alert_message'] = 'Status atualizado com sucesso!';
        $_SESSION['alert_type'] = 'success';
        
        // Refresh the page to show updated data
        header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento_View&id=' . $atendimento_id);
        exit;
    } catch (PDOException $e) {
        logError("Error updating service request status: " . $e->getMessage());
        $error = 'Ocorreu um erro ao atualizar o status.';
    }
}
?>

<div class="admin-page atendimento-view">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Detalhes do Atendimento</h2>
        <div class="admin-page__actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Update&id=<?= $atendimento_id ?>" class="primary-button">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento" class="cancel-button">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
    
    <!-- Service Request Details Card -->
    <div class="admin-card">
        <div class="atendimento-header">
            <div class="atendimento-meta">
                <div class="meta-item">
                    <label>Status:</label>
                    <span class="badge badge--<?= strtolower(str_replace(' ', '-', $atendimento['status'])) ?>">
                        <?= htmlspecialchars($atendimento['status']) ?>
                    </span>
                </div>
                <div class="meta-item">
                    <label>Origem:</label>
                    <span class="badge badge--<?= strtolower($atendimento['local']) ?>">
                        <?= htmlspecialchars($atendimento['local']) ?>
                    </span>
                </div>
                <div class="meta-item">
                    <label>Data:</label>
                    <span><?= formatDate($atendimento['data']) ?> às <?= substr($atendimento['hora'], 0, 5) ?></span>
                </div>
            </div>
        </div>
        
        <div class="atendimento-details">
            <div class="detail-section">
                <h3 class="detail-section__title">Informações do Contato</h3>
                
                <div class="detail-row">
                    <div class="detail-group">
                        <label>Nome:</label>
                        <div class="detail-content">
                            <?= htmlspecialchars($atendimento['nome']) ?>
                        </div>
                    </div>
                    
                    <div class="detail-group">
                        <label>Telefone:</label>
                        <div class="detail-content">
                            <?= htmlspecialchars($atendimento['telefone']) ?>
                        </div>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-group">
                        <label>Email:</label>
                        <div class="detail-content">
                            <?= !empty($atendimento['email']) ? htmlspecialchars($atendimento['email']) : '-' ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h3 class="detail-section__title">Mensagem</h3>
                
                <div class="detail-content detail-content--message">
                    <?= nl2br(htmlspecialchars($atendimento['mensagem'])) ?>
                </div>
            </div>
        </div>
        
        <!-- Status Update Form -->
        <div class="atendimento-actions">
            <form method="POST" action="" class="status-form">
                <div class="status-form__group">
                    <label for="status">Atualizar Status:</label>
                    <select id="status" name="status" class="form-control">
                        <option value="Pendente" <?= $atendimento['status'] === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="Em Andamento" <?= $atendimento['status'] === 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="Concluído" <?= $atendimento['status'] === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
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
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Update&id=<?= $atendimento_id ?>" class="action-button">
                <i class="fas fa-edit"></i> Editar Atendimento
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Delete&id=<?= $atendimento_id ?>" class="action-button action-button--delete">
                <i class="fas fa-trash"></i> Excluir Atendimento
            </a>
        </div>
    </div>
</div>

<style>
/* Service Request View Styles */
.atendimento-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--admin-border);
}

.atendimento-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.meta-item label {
    font-weight: 600;
    color: var(--admin-text);
}

.detail-section {
    margin-bottom: 30px;
}

.detail-section__title {
    font-size: var(--font-lg);
    margin-bottom: 15px;
    font-family: var(--font-secondary);
    color: var(--admin-text);
}

.detail-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.detail-group {
    flex: 1;
    min-width: 250px;
}

.detail-group label {
    font-weight: 600;
    color: var(--admin-text);
    margin-bottom: 5px;
    display: block;
}

.detail-content {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 3px;
    min-height: 24px;
}

.detail-content--message {
    padding: 15px;
    min-height: 100px;
}

.atendimento-actions {
    padding-top: 20px;
    margin-top: 20px;
    border-top: 1px solid var(--admin-border);
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