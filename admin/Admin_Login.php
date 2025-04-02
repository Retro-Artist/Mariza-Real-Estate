<?php
// Start session
session_start();

// Include configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_functions.php';

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    // Redirecionar para o calendário em vez de dashboard
    header('Location: ' . BASE_URL . '/admin/index.php?page=Calendar');
    exit;
}

// Initialize variables
$error = '';
$login_email = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_email = trim($_POST['email'] ?? '');
    $login_password = trim($_POST['senha'] ?? '');
    
    // Validate form inputs
    if (empty($login_email) || empty($login_password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        // Verify login credentials using our function from admin_functions.php
        $user = authenticateAdmin($login_email, $login_password);
        
        if ($user) {
            // Login successful - create session
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['nome'];
            $_SESSION['admin_level'] = $user['nivel'];
            
            // Redirect to calendar (foi dashboard)
            header('Location: ' . BASE_URL . '/admin/index.php?page=Calendar');
            exit;
        } else {
            // Login failed
            $error = 'Email ou senha inválidos. Por favor, tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - <?= SITE_NAME ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin/admin.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-login-body">
    <div class="admin-login">
        <div class="admin-login__container">
            <div class="admin-login__logo">
                <img src="<?= BASE_URL ?>/assets/img/site-logo.webp" alt="<?= SITE_NAME ?>">
            </div>
            
            <h1 class="admin-login__title">Painel Administrativo</h1>
            
            <?php if (!empty($error)): ?>
                <div class="admin-login__error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="admin-login__form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($login_email) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="primary-button admin-login__button">
                        <i class="fas fa-sign-in-alt"></i> Entrar
                    </button>
                </div>
            </form>
            
            <div class="admin-login__footer">
                <a href="<?= BASE_URL ?>/" class="admin-login__link">
                    <i class="fas fa-arrow-left"></i> Voltar para o site
                </a>
            </div>
        </div>
    </div>
</body>
</html>