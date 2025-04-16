<?php
// Admin_Login.php
session_start();

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/admin_functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';

    // isAdminUser() should verify email/password and return user row or false
    $user = isAdminUser($email, $password);
    if ($user) {
        $_SESSION['admin_id']   = $user['id'];
        $_SESSION['admin_name'] = $user['nome'];
        header('Location: admin/paginas/Property_Admin.php');
        exit;
    } else {
        $error = 'E‑mail ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <link rel="stylesheet" href="assets/css/admin/login.css">
</head>
<body>
  <div class="login-container">
    <h1>Entrar como Admin</h1>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="form-group">
        <label for="email">E‑mail</label>
        <input type="email" name="email" id="email" required class="form-control">
      </div>
      <div class="form-group">
        <label for="password">Senha</label>
        <input type="password" name="password" id="password" required class="form-control">
      </div>
      <button type="submit" class="btn btn-primary">Entrar</button>
    </form>
  </div>
</body>
</html>
