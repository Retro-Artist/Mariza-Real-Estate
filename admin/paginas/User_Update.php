<?php


// Initialize variables
$alertMessage = '';
$alertType = '';
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get user data
$user = getAdminUserById($userId);

// Redirect if user not found
if (!$user) {
    $_SESSION['alert_message'] = 'Usuário não encontrado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=User_Admin');
    exit;
}

// Initialize form data with user data
$formData = [
    'nome' => $user['nome'],
    'email' => $user['email'],
    'senha' => '',
    'senha_confirm' => '',
    'nivel' => $user['nivel']
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'nome' => trim($_POST['nome'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'senha' => $_POST['senha'] ?? '',
        'senha_confirm' => $_POST['senha_confirm'] ?? '',
        'nivel' => trim($_POST['nivel'] ?? '')
    ];

    // Validate form data
    $errors = [];

    if (empty($formData['nome'])) {
        $errors[] = 'O nome é obrigatório.';
    }

    if (empty($formData['email'])) {
        $errors[] = 'O email é obrigatório.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Por favor, informe um email válido.';
    }

    // Only validate password if provided (optional on update)
    if (!empty($formData['senha'])) {
        if (strlen($formData['senha']) < 6) {
            $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
        }

        if ($formData['senha'] !== $formData['senha_confirm']) {
            $errors[] = 'As senhas não coincidem.';
        }
    }

    if (empty($formData['nivel'])) {
        $errors[] = 'O nível de acesso é obrigatório.';
    }

    // Prevent changing own user level from Administrator to lower level
    if (
        $userId === $_SESSION['admin_id'] && ($user['nivel'] === 'Administrador' || $user['nivel'] == '1') &&
        $formData['nivel'] !== 'Administrador' && $formData['nivel'] != '1'
    ) {
        $errors[] = 'Você não pode alterar seu próprio nível de acesso de Administrador para um nível inferior.';
    }

    // If no errors, update user
    if (empty($errors)) {
        $userData = [
            'nome' => $formData['nome'],
            'email' => $formData['email'],
            'nivel' => $formData['nivel']
        ];

        // Only include password if provided
        if (!empty($formData['senha'])) {
            $userData['senha'] = $formData['senha'];
        }

        $success = updateUser($userId, $userData);

        if ($success) {
            // User updated successfully
            $_SESSION['alert_message'] = 'Usuário atualizado com sucesso!';
            $_SESSION['alert_type'] = 'success';

            // If updating own user, update session data
            if ($userId === $_SESSION['admin_id']) {
                $_SESSION['admin_name'] = $formData['nome'];
                $_SESSION['admin_level'] = $formData['nivel'];
            }

            header('Location: ' . BASE_URL . '/admin/index.php?page=User_Admin');
            exit;
        } else {
            $alertMessage = 'Erro ao atualizar usuário. O email informado pode já estar em uso.';
            $alertType = 'error';
        }
    } else {
        $alertMessage = implode('<br>', $errors);
        $alertType = 'error';
    }
}
?>
<main class="User">

    <div class="admin-page__header">
        <h2 class="admin-page__title">Editar Usuário</h2>

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

    <div class="admin-form user-update">
        <form method="POST" action="">
            <div class="form-section">
                <h3 class="form-section__title">Informações do Usuário</h3>

                <div class="form-row">
                    <div class="form-group form-group--half">
                        <label for="nome">Nome <span class="required">*</span></label>
                        <input type="text" id="nome" name="nome" class="form-control"
                            value="<?= htmlspecialchars($formData['nome']) ?>" required>
                    </div>

                    <div class="form-group form-group--half">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control"
                            value="<?= htmlspecialchars($formData['email']) ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group form-group--half">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" class="form-control">
                        <div class="form-text">Deixe em branco para manter a senha atual. A nova senha deve ter pelo menos 6 caracteres.</div>
                    </div>

                    <div class="form-group form-group--half">
                        <label for="senha_confirm">Confirmar Senha</label>
                        <input type="password" id="senha_confirm" name="senha_confirm" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nivel">Nível de Acesso <span class="required">*</span></label>
                        <select id="nivel" name="nivel" class="form-control" required
                            <?= ($userId === $_SESSION['admin_id'] && ($user['nivel'] === 'Administrador' || $user['nivel'] == '1')) ? 'disabled' : '' ?>>
                            <option value="0" <?= $formData['nivel'] === '0' || $formData['nivel'] === 'Editor' ? 'selected' : '' ?>>Editor</option>
                            <option value="1" <?= $formData['nivel'] === '1' || $formData['nivel'] === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                        </select>

                        <?php if ($userId === $_SESSION['admin_id'] && ($user['nivel'] === 'Administrador' || $user['nivel'] == '1')): ?>
                            <!-- Hidden field to ensure value is submitted when select is disabled -->
                            <input type="hidden" name="nivel" value="<?= $user['nivel'] == '1' ? '1' : 'Administrador' ?>">
                        <?php endif; ?>

                        <div class="form-text">
                            <strong>Editor:</strong> Acesso limitado ao gerenciamento de imóveis, clientes e calendário.<br>
                            <strong>Administrador:</strong> Acesso completo a todas as funções do sistema, incluindo gerenciamento de usuários.

                            <?php if ($userId === $_SESSION['admin_id'] && $user['nivel'] === 'Administrador'): ?>
                                <br><span class="warning-text">Você não pode alterar seu próprio nível de acesso de Administrador para um nível inferior.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>/admin/index.php?page=User_Admin" class="cancel-button">Cancelar</a>
                <button type="submit" class="primary-button">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</main>