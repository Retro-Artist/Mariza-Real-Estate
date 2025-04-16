<?php


// Initialize variables
$alertMessage = '';
$alertType = '';
$formData = [
    'nome' => '',
    'email' => '',
    'senha' => '',
    'senha_confirm' => '',
    'nivel' => 'Editor'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'nome' => trim($_POST['nome'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'senha' => $_POST['senha'] ?? '',
        'senha_confirm' => $_POST['senha_confirm'] ?? '',
        'nivel' => trim($_POST['nivel'] ?? 'Editor')
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
    
    if (empty($formData['senha'])) {
        $errors[] = 'A senha é obrigatória.';
    } elseif (strlen($formData['senha']) < 6) {
        $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
    }
    
    if ($formData['senha'] !== $formData['senha_confirm']) {
        $errors[] = 'As senhas não coincidem.';
    }
    
    if (empty($formData['nivel'])) {
        $errors[] = 'O nível de acesso é obrigatório.';
    }
    
    // If no errors, create user
    if (empty($errors)) {
        $userData = [
            'nome' => $formData['nome'],
            'email' => $formData['email'],
            'senha' => $formData['senha'],
            'nivel' => $formData['nivel']
        ];
        
        $userId = createUser($userData);
        
        if ($userId) {
            // User created successfully
            $_SESSION['alert_message'] = 'Usuário criado com sucesso!';
            $_SESSION['alert_type'] = 'success';
            header('Location: ' . BASE_URL . '/admin/index.php?page=User_Admin');
            exit;
        } else {
            $alertMessage = 'Erro ao criar usuário. O email informado pode já estar em uso.';
            $alertType = 'error';
        }
    } else {
        $alertMessage = implode('<br>', $errors);
        $alertType = 'error';
    }
}
?>

<div class="admin-page__header"> 
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

<div class="admin-form user-create">
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
                    <label for="senha">Senha <span class="required">*</span></label>
                    <input type="password" id="senha" name="senha" class="form-control" required>
                    <div class="form-text">A senha deve ter pelo menos 6 caracteres</div>
                </div>
                
                <div class="form-group form-group--half">
                    <label for="senha_confirm">Confirmar Senha <span class="required">*</span></label>
                    <input type="password" id="senha_confirm" name="senha_confirm" class="form-control" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nivel">Nível de Acesso <span class="required">*</span></label>
                    <select id="nivel" name="nivel" class="form-control" required>
                        <option value="Editor" <?= $formData['nivel'] === 'Editor' ? 'selected' : '' ?>>Editor</option>
                        <option value="Administrador" <?= $formData['nivel'] === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                    </select>
                    <div class="form-text">
                        <strong>Editor:</strong> Acesso limitado ao gerenciamento de imóveis, clientes e calendário.<br>
                        <strong>Administrador:</strong> Acesso completo a todas as funções do sistema, incluindo gerenciamento de usuários.
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