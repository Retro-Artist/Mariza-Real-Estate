<?php

// Se já estiver logado, redireciona direto
if (isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =            $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $stmt = $databaseConnection->prepare("
                SELECT id, nome, nivel, senha
                  FROM sistema_usuarios
                 WHERE email = :email
                 LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['senha'])) {
                // Re‑hash automático caso o algoritmo padrão mude no futuro
                if (password_needs_rehash($user['senha'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upd = $databaseConnection->prepare("
                        UPDATE sistema_usuarios
                           SET senha = :hash
                         WHERE id = :id
                    ");
                    $upd->execute([
                        ':hash' => $newHash,
                        ':id'   => $user['id'],
                    ]);
                }

                // Sucesso: grava na sessão e redireciona
                $_SESSION['admin_id']    = $user['id'];
                $_SESSION['admin_name']  = $user['nome'];
                $_SESSION['admin_level'] = $user['nivel'];

                if (defined('MODE') && MODE === 'Development') {
                    error_log("Login success - ID: {$user['id']}, Name: {$user['nome']}, Level: {$user['nivel']}");
                }

                header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
                exit;
            } else {
                $error = 'E-mail ou senha inválidos.';
            }
        } catch (PDOException $e) {
            $error = 'Erro ao processar login. Tente novamente.';
            if (defined('MODE') && MODE === 'Development') {
                error_log("Login error: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrativo – <?= SITE_NAME ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f8fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo img {
            max-width: 180px;
        }
        .login-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .login-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .login-subtitle {
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .login-error {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #333;
            background-color: #fff;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            transition: border-color 0.15s ease-in-out;
        }
        .form-control:focus {
            border-color: #3b82f6;
            outline: 0;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.25);
        }
        .login-button {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 500;
            text-align: center;
            color: #fff;
            background-color: #3b82f6;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.15s ease-in-out;
        }
        .login-button:hover {
            background-color: #2563eb;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
        }
        .back-link:hover {
            color: #3b82f6;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <img src="<?= BASE_URL ?>/assets/img/Logo-White.png" alt="<?= SITE_NAME ?>">
        </div>

        <div class="login-card">
            <h1 class="login-title">Painel Administrativo</h1>
            <p class="login-subtitle">Faça login para acessar o sistema</p>

            <?php if ($error): ?>
                <div class="login-error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>"
                    >
                </div>
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        required
                    >
                </div>
                <button type="submit" class="login-button">Entrar</button>
            </form>

            <a href="<?= BASE_URL ?>" class="back-link">Voltar para o site</a>
        </div>
    </div>
</body>
</html>
