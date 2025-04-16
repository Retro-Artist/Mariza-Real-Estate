<?php


// Initialize variables
$alertMessage = '';
$alertType = '';
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Prevent deleting own account
if ($userId === $_SESSION['admin_id']) {
    $_SESSION['alert_message'] = 'Você não pode excluir sua própria conta.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=User_Admin');
    exit;
}

// Get user data
$user = getAdminUserById($userId);

// Redirect if user not found
if (!$user) {
    $_SESSION['alert_message'] = 'Usuário não encontrado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=User_Admin');
    exit;
}

// Process deletion request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $success = deleteUser($userId);
    
    if ($success) {
        // User deleted successfully
        $_SESSION['alert_message'] = 'Usuário excluído com sucesso!';
        $_SESSION['alert_type'] = 'success';
        header('Location: ' . BASE_URL . '/admin/index.php?page=User_Admin');
        exit;
    } else {
        // Error deleting user - likely the last admin
        $alertMessage = 'Não é possível excluir o último usuário administrador do sistema.';
        $alertType = 'error';
    }
}
?>

<div class="admin-page__header">
    <h2 class="admin-page__title">Excluir Usuário</h2>
    
    <div class="admin-page__actions">
        <a href="<?= BASE_URL ?>/admin/index.php?page=User_Admin" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<?php if (!empty($alertMessage)): ?>
    <div class="alert-message alert-message--<?= $alertType ?>">
        <?= $alertMessage ?>
    </div>
<?php endif; ?>

<div class="admin-form user-delete">
    <div class="confirmation-message">
        <div class="confirmation-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h3>Tem certeza que deseja excluir este usuário?</h3>
        
        <p>Você está prestes a excluir o usuário <strong><?= htmlspecialchars($user['nome']) ?></strong>.</p>
        <p>Email: <strong><?= htmlspecialchars($user['email']) ?></strong></p>
        <p>Nível de Acesso: <strong><?= htmlspecialchars($user['nivel']) ?></strong></p>
        
        <p class="warning-text">Esta ação não pode ser desfeita!</p>
        
        <div class="confirmation-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=User_Admin" class="cancel-button">
                <i class="fas fa-times"></i> Cancelar
            </a>
            
            <form method="POST" action="" style="display: inline;">
                <input type="hidden" name="confirm_delete" value="1">
                <button type="submit" class="primary-button delete-button">
                    <i class="fas fa-trash"></i> Confirmar Exclusão
                </button>
            </form>
        </div>
    </div>
</div>