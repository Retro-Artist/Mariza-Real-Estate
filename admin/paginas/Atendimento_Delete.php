<?php
// admin/paginas/Atendimento_Delete.php

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

// Initialize variables
$atendimento = [];
$error = '';
$confirmDelete = isset($_GET['confirm']) && $_GET['confirm'] === '1';

// If not confirmed, get service request data for confirmation page
if (!$confirmDelete) {
    // Get service request using function from admin_functions.php
    $atendimento = getServiceRequestById($atendimento_id);
    
    if (!$atendimento) {
        $_SESSION['alert_message'] = 'Atendimento não encontrado.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento_Admin');
        exit;
    }
}
// If confirmed, process deletion
else {
    // Delete service request using function from admin_functions.php
    $result = deleteServiceRequest($atendimento_id);
    
    if ($result) {
        // Set success message and redirect
        $_SESSION['alert_message'] = 'Atendimento excluído com sucesso!';
        $_SESSION['alert_type'] = 'success';
        
        header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento_Admin');
        exit;
    } else {
        $_SESSION['alert_message'] = 'Ocorreu um erro ao excluir o atendimento.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento_Admin');
        exit;
    }
}
?>

<!-- Delete Atendimento Confirmation Page -->
<div class="admin-page atendimento-delete">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Excluir Atendimento</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_View&id=<?= $atendimento_id ?>" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- Confirmation Card -->
    <div class="admin-card">
        <div class="confirmation-message">
            <i class="fas fa-exclamation-triangle confirmation-icon"></i>
            <h3>Tem certeza que deseja excluir este atendimento?</h3>
            <p>Você está prestes a excluir o atendimento de <strong><?= htmlspecialchars($atendimento['nome']) ?></strong>.</p>
            <p>Data: <strong><?= formatDate($atendimento['data']) ?> às <?= $atendimento['hora'] ?></strong></p>
            <p>Esta ação não pode ser desfeita.</p>
        </div>
        
        <div class="confirmation-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_View&id=<?= $atendimento_id ?>" class="cancel-button">
                Cancelar
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Delete&id=<?= $atendimento_id ?>&confirm=1" class="delete-button">
                <i class="fas fa-trash"></i> Sim, Excluir Atendimento
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