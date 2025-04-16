<?php
// admin/paginas/Atendimento_Delete.php

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