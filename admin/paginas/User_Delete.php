<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Check if user has administrator privileges
if ($_SESSION['admin_level'] !== 'Administrador') {
    $_SESSION['alert_message'] = 'Você não tem permissão para acessar esta página.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/');
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID do usuário não especificado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=User_Admin');
    exit;
}

$user_id = (int)$_GET['id'];

// Prevent users from deleting their own account
if ((int)$_SESSION['admin_id'] === $user_id) {
    $_SESSION['alert_message'] = 'Você não pode excluir sua própria conta.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=User_Admin');
    exit;
}

// Initialize variables
$error = '';
$confirmDelete = isset($_GET['confirm']) && $_GET['confirm'] === '1';

// If not confirmed, get user data for confirmation page
if (!$confirmDelete) {
    // Get user data
    $user = getAdminUserById($user_id);
    
    if (!$user) {
        $_SESSION['alert_message'] = 'Usuário não encontrado.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=User_Admin');
        exit;
    }
}
// If confirmed, process deletion
else {
    // Delete user using our function from admin_functions.php
    $result = deleteUser($user_id);
    
    if ($result) {
        // Set success message and redirect
        $_SESSION['alert_message'] = 'Usuário excluído com sucesso!';
        $_SESSION['alert_type'] = 'success';
        
        header('Location: ' . BASE_URL . '/admin/index.php?page=User_Admin');
        exit;
    } else {
        $_SESSION['alert_message'] = 'Não é possível excluir o último administrador do sistema.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=User_Admin');
        exit;
    }
}
?>

<!-- Delete User Confirmation Page -->
<div class="admin-page user-delete">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Excluir Usuário</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=User_Admin" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- Confirmation Card -->
    <div class="admin-card">
        <div class="confirmation-message">
            <i class="fas fa-exclamation-triangle confirmation-icon"></i>
            <h3>Tem certeza que deseja excluir este usuário?</h3>
            <p>Você está prestes a excluir o usuário <strong><?= htmlspecialchars($user['nome']) ?></strong>.</p>
            <p>Esta ação não pode ser desfeita.</p>
            <?php if ($user['nivel'] === 'Administrador'): ?>
                <p class="warning-text">Atenção: Este usuário é um administrador. Certifique-se de que existem outros administradores no sistema.</p>
            <?php endif; ?>
        </div>
        
        <div class="confirmation-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=User_Admin" class="cancel-button">
                Cancelar
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=User_Delete&id=<?= $user_id ?>&confirm=1" class="delete-button">
                <i class="fas fa-trash"></i> Sim, Excluir Usuário
            </a>
        </div>
    </div>
</div>