<?php

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID do cliente não especificado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Client_Admin');
    exit;
}

$client_id = (int)$_GET['id'];

// Initialize variables
$error = '';
$confirmDelete = isset($_GET['confirm']) && $_GET['confirm'] === '1';

// If not confirmed, get client data for confirmation page
if (!$confirmDelete) {
    // Get client data using our function from admin_functions.php
    $client = getAdminClientById($client_id);
    
    if (!$client) {
        $_SESSION['alert_message'] = 'Cliente não encontrado.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=Client_Admin');
        exit;
    }
}
// If confirmed, process deletion
else {
    // Delete client using our function from admin_functions.php
    $result = deleteClient($client_id);
    
    if ($result) {
        // Set success message and redirect
        $_SESSION['alert_message'] = 'Cliente excluído com sucesso!';
        $_SESSION['alert_type'] = 'success';
        
        header('Location: ' . BASE_URL . '/admin/index.php?page=Client_Admin');
        exit;
    } else {
        $_SESSION['alert_message'] = 'Ocorreu um erro ao excluir o cliente.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=Client_Admin');
        exit;
    }
}
?>

<!-- HTML content remains unchanged -->

<!-- Delete Client Confirmation Page -->
<div class="admin-page client-delete">
    <!-- Page Header -->
    <div class="admin-page__header">
        <a href="<?= BASE_URL ?>/admin/Client_Admin.php" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- Confirmation Card -->
    <div class="admin-card">
        <div class="confirmation-message">
            <i class="fas fa-exclamation-triangle confirmation-icon"></i>
            <h3>Tem certeza que deseja excluir este cliente?</h3>
            
            <div class="client-info">
                <?php if ($client['tipo'] === 'Pessoa Física'): ?>
                    <p>Nome: <strong><?= htmlspecialchars($client['nome_completo']) ?></strong></p>
                    <p>CPF: <strong><?= htmlspecialchars($client['cpf']) ?></strong></p>
                <?php else: ?>
                    <p>Razão Social: <strong><?= htmlspecialchars($client['razao_social']) ?></strong></p>
                    <p>CNPJ: <strong><?= htmlspecialchars($client['cnpj']) ?></strong></p>
                <?php endif; ?>
                <p>Email: <strong><?= htmlspecialchars($client['email']) ?></strong></p>
                <p>Telefone: <strong><?= htmlspecialchars($client['telefone1']) ?></strong></p>
                <p>Categoria: <strong><?= htmlspecialchars($client['categoria']) ?></strong></p>
            </div>
            
            <p class="warning-text">Esta ação não pode ser desfeita.</p>
        </div>
        
        <div class="confirmation-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin" class="cancel-button">
                Cancelar
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Delete&id=<?= $client_id ?>&confirm=1" class="delete-button">
                <i class="fas fa-trash"></i> Sim, Excluir Cliente
            </a>
        </div>
    </div>
</div>
