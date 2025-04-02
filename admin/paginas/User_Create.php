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

// Initialize variables
$error = '';
$formData = [
    'nome' => '',
    'email' => '',
    'senha' => '',
    'nivel' => 'Editor'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'nome' => trim($_POST['nome'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'senha' => trim($_POST['senha'] ?? ''),
        'nivel' => trim($_POST['nivel'] ?? 'Editor')
    ];
    
    // Validate form data
    if (empty($formData['nome'])) {
        $error = 'O nome é obrigatório.';
    } elseif (empty($formData['email'])) {
        $error = 'O email é obrigatório.';
    } elseif (empty($formData['senha'])) {
        $error = 'A senha é obrigatória.';
    } elseif (strlen($formData['senha']) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, informe um email válido.';
    } else {
        // Create user using our function from admin_functions.php
        $userId = createUser($formData);
        
        if ($userId) {
            // Set success message and redirect
            $_SESSION['alert_message'] = 'Usuário adicionado com sucesso!';
            $_SESSION['alert_type'] = 'success';
            
            header('Location: ' . BASE_URL . '/admin/index.php?page=User_Admin');
            exit;
        } else {
            $error = 'Um usuário com este email já existe.';
        }
    }
}
?>

<div class="admin-page user-create">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Adicionar Novo Usuário</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=User_Admin" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- User Form -->
    <form method="POST" action="" class="admin-form">
        <?php if (!empty($error)): ?>
            <div class="alert-message alert-message--error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="form-section">
            <h3 class="form-section__title">Informações do Usuário</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome <span class="required">*</span></label>
                    <input type="text" id="nome" name="nome" class="form-control" 
                           value="<?= htmlspecialchars($formData['nome']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($formData['email']) ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="senha">Senha <span class="required">*</span></label>
                    <input type="password" id="senha" name="senha" class="form-control" required>
                    <small class="form-text">A senha deve ter pelo menos 6 caracteres.</small>
                </div>
                
                <div class="form-group">
                    <label for="nivel">Nível de Acesso <span class="required">*</span></label>
                    <select id="nivel" name="nivel" class="form-control" required>
                        <option value="Administrador" <?= $formData['nivel'] === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                        <option value="Editor" <?= $formData['nivel'] === 'Editor' ? 'selected' : '' ?>>Editor</option>
                        <option value="Corretor" <?= $formData['nivel'] === 'Corretor' ? 'selected' : '' ?>>Corretor</option>
                    </select>
                    <small class="form-text">Administradores têm acesso completo ao sistema.</small>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=User_Admin" class="cancel-button">Cancelar</a>
            <button type="submit" class="primary-button">
                <i class="fas fa-save"></i> Salvar Usuário
            </button>
        </div>
    </form>
</div>