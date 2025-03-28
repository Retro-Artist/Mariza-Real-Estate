<?php
// admin/paginas/Atendimento_View.php

// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID do atendimento não especificado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento_Admin');
    exit;
}

$atendimento_id = (int)$_GET['id'];

// Get atendimento data
try {
    $stmt = $databaseConnection->prepare("SELECT * FROM sistema_interacao WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $atendimento_id);
    $stmt->execute();
    
    $atendimento = $stmt->fetch();
    
    if (!$atendimento) {
        $_SESSION['alert_message'] = 'Atendimento não encontrado.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento_Admin');
        exit;
    }
    
} catch (PDOException $e) {
    logError("Error fetching atendimento details: " . $e->getMessage());
    $_SESSION['alert_message'] = 'Erro ao buscar detalhes do atendimento.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento_Admin');
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
        logError("Error updating atendimento status: " . $e->getMessage());
        $error = 'Ocorreu um erro ao atualizar o status.';
    }
}

// Get status class for badge
$statusClass = '';
switch ($atendimento['status']) {
    case 'Pendente':
        $statusClass = 'badge--pending';
        break;
    case 'Em Andamento':
        $statusClass = 'badge--progress';
        break;
    case 'Concluído':
        $statusClass = 'badge--complete';
        break;
    case 'Cancelado':
        $statusClass = 'badge--canceled';
        break;
}

// Get source class for badge
$sourceClass = 'badge--' . strtolower($atendimento['local']);
?>

<div class="admin-page atendimento-view">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Detalhes do Atendimento</h2>
        <div class="admin-page__actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Update&id=<?= $atendimento_id ?>" class="primary-button">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Admin" class="cancel-button">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
    
    <!-- Atendimento Details Card -->
    <div class="admin-card">
        <div class="atendimento-header">
            <div class="atendimento-meta">
                <div class="atendimento-id">Atendimento #<?= $atendimento['id'] ?></div>
                <div class="atendimento-date">
                    <i class="fas fa-calendar"></i> <?= formatDate($atendimento['data']) ?> às <?= $atendimento['hora'] ?>
                </div>
            </div>
            <div class="atendimento-badges">
                <span class="badge <?= $sourceClass ?>">
                    <?= htmlspecialchars($atendimento['local']) ?>
                </span>
                <span class="badge <?= $statusClass ?>">
                    <?= htmlspecialchars($atendimento['status']) ?>
                </span>
            </div>
        </div>
        
        <div class="atendimento-details">
            <div class="detail-section">
                <h3 class="detail-section-title">Informações do Contato</h3>
                
                <div class="detail-row">
                    <div class="detail-group">
                        <label>Nome:</label>
                        <div class="detail-value"><?= htmlspecialchars($atendimento['nome']) ?></div>
                    </div>
                    
                    <div class="detail-group">
                        <label>Email:</label>
                        <div class="detail-value">
                            <a href="mailto:<?= htmlspecialchars($atendimento['email']) ?>">
                                <?= htmlspecialchars($atendimento['email']) ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-group">
                        <label>Telefone:</label>
                        <div class="detail-value">
                            <?php if (!empty($atendimento['telefone'])): ?>
                                <a href="tel:<?= htmlspecialchars($atendimento['telefone']) ?>">
                                    <?= htmlspecialchars($atendimento['telefone']) ?>
                                </a>
                            <?php else: ?>
                                <span class="no-data">Não informado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="detail-group">
                        <label>WhatsApp:</label>
                        <div class="detail-value">
                            <?php if (!empty($atendimento['telefone'])): ?>
                                <a href="https://api.whatsapp.com/send?phone=<?= preg_replace('/\D/', '', $atendimento['telefone']) ?>" target="_blank">
                                    Enviar mensagem <i class="fab fa-whatsapp"></i>
                                </a>
                            <?php else: ?>
                                <span class="no-data">Não disponível</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h3 class="detail-section-title">Mensagem</h3>
                <div class="message-content">
                    <?= nl2br(htmlspecialchars($atendimento['mensagem'])) ?>
                </div>
            </div>
        </div>
        
        <!-- Status Update Form -->
        <div class="status-update">
            <h3 class="detail-section-title">Atualizar Status</h3>
            <form method="POST" action="" class="status-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status" class="form-control">
                            <option value="Pendente" <?= $atendimento['status'] === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="Em Andamento" <?= $atendimento['status'] === 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="Concluído" <?= $atendimento['status'] === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                            <option value="Cancelado" <?= $atendimento['status'] === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="update_status" value="1" class="primary-button">
                            <i class="fas fa-save"></i> Atualizar Status
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Actions -->
        <div class="atendimento-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Update&id=<?= $atendimento_id ?>" class="btn btn-edit">
                <i class="fas fa-edit"></i> Editar Atendimento
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Delete&id=<?= $atendimento_id ?>" class="btn btn-delete delete-button">
                <i class="fas fa-trash"></i> Excluir Atendimento
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Create" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Converter em Cliente
            </a>
        </div>
    </div>
</div>

<style>
/* Atendimento View Styles */
.atendimento-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--admin-border);
    flex-wrap: wrap;
    gap: 10px;
}

.atendimento-meta {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.atendimento-id {
    font-size: var(--font-lg);
    font-weight: 600;
}

.atendimento-date {
    font-size: var(--font-sm);
    color: #666;
}

.atendimento-badges {
    display: flex;
    gap: 10px;
}

.badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: var(--font-sm);
    font-weight: 500;
}

.detail-section {
    margin-bottom: 30px;
}

.detail-section-title {
    font-size: var(--font-md);
    font-weight: 600;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 1px solid #eee;
}

.detail-row {
    display: flex;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 20px;
}

.detail-group {
    flex: 1;
    min-width: 250px;
}

.detail-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 5px;
    color: #555;
}

.detail-value {
    padding: 8px 0;
}

.detail-value a {
    color: var(--admin-primary);
    text-decoration: none;
}

.detail-value a:hover {
    text-decoration: underline;
}

.message-content {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    min-height: 100px;
}

.status-update {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--admin-border);
}

.status-form {
    display: flex;
    align-items: flex-end;
}

.status-form .form-row {
    display: flex;
    gap: 15px;
    width: 100%;
    align-items: flex-end;
}

.atendimento-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--admin-border);
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    border-radius: 4px;
    font-size: var(--font-base);
    cursor: pointer;
    text-decoration: none;
    transition: background-color 0.2s, transform 0.1s;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-edit {
    background-color: var(--admin-blue);
    color: white;
}

.btn-delete {
    background-color: var(--admin-red);
    color: white;
}

.btn-primary {
    background-color: var(--admin-primary);
    color: white;
}

.no-data {
    color: #999;
    font-style: italic;
}

/* Badge styles for sources and status */
.badge--site {
    background-color: rgba(102, 16, 242, 0.2);
    color: #6610f2;
}

.badge--whatsapp {
    background-color: rgba(37, 211, 102, 0.2);
    color: #25D366;
}

.badge--telefone {
    background-color: rgba(0, 123, 255, 0.2);
    color: #007bff;
}

.badge--pessoal {
    background-color: rgba(108, 117, 125, 0.2);
    color: #6c757d;
}

.badge--pending {
    background-color: rgba(255, 193, 7, 0.2);
    color: #ffc107;
}

.badge--progress {
    background-color: rgba(0, 123, 255, 0.2);
    color: #007bff;
}

.badge--complete {
    background-color: rgba(40, 167, 69, 0.2);
    color: #28a745;
}

.badge--canceled {
    background-color: rgba(220, 53, 69, 0.2);
    color: #dc3545;
}

@media (max-width: 768px) {
    .atendimento-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .status-form .form-row {
        flex-direction: column;
        gap: 10px;
    }
    
    .atendimento-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>