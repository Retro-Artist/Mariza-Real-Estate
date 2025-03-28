<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Initialize variables
$error = '';
$formData = [
    'nome' => '',
    'email' => '',
    'telefone' => '',
    'mensagem' => '',
    'local' => 'Site',
    'status' => 'Pendente'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'nome' => trim($_POST['nome'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'telefone' => trim($_POST['telefone'] ?? ''),
        'mensagem' => trim($_POST['mensagem'] ?? ''),
        'local' => trim($_POST['local'] ?? 'Site'),
        'status' => trim($_POST['status'] ?? 'Pendente')
    ];
    
    // Validate form data
    if (empty($formData['nome'])) {
        $error = 'O nome é obrigatório.';
    } elseif (empty($formData['telefone'])) {
        $error = 'O telefone é obrigatório.';
    } elseif (!empty($formData['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, informe um email válido.';
    } elseif (empty($formData['mensagem'])) {
        $error = 'A mensagem é obrigatória.';
    } else {
        try {
            // Get current date and time
            $data = date('Y-m-d');
            $hora = date('H:i:s');
            
            // Insert new service request
            $stmt = $databaseConnection->prepare(
                "INSERT INTO sistema_interacao (
                    nome, email, telefone, mensagem, 
                    data, hora, local, status
                ) VALUES (
                    :nome, :email, :telefone, :mensagem,
                    :data, :hora, :local, :status
                )"
            );
            
            $stmt->bindParam(':nome', $formData['nome']);
            $stmt->bindParam(':email', $formData['email']);
            $stmt->bindParam(':telefone', $formData['telefone']);
            $stmt->bindParam(':mensagem', $formData['mensagem']);
            $stmt->bindParam(':data', $data);
            $stmt->bindParam(':hora', $hora);
            $stmt->bindParam(':local', $formData['local']);
            $stmt->bindParam(':status', $formData['status']);
            
            $stmt->execute();
            
            // Set success message and redirect
            $_SESSION['alert_message'] = 'Atendimento registrado com sucesso!';
            $_SESSION['alert_type'] = 'success';
            
            header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento');
            exit;
            
        } catch (PDOException $e) {
            logError("Error creating service request: " . $e->getMessage());
            $error = 'Ocorreu um erro ao registrar o atendimento. Por favor, tente novamente.';
        }
    }
}
?>

<!-- Add Service Request Page -->
<div class="admin-page atendimento-create">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Registrar Atendimento</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- Service Request Form -->
    <form method="POST" action="" class="admin-form">
        <?php if (!empty($error)): ?>
            <div class="alert-message alert-message--error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="form-section">
            <h3 class="form-section__title">Informações do Contato</h3>
            
            <div class="form-row">
                <div class="form-group form-group--large">
                    <label for="nome">Nome <span class="required">*</span></label>
                    <input type="text" id="nome" name="nome" class="form-control" 
                           value="<?= htmlspecialchars($formData['nome']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone <span class="required">*</span></label>
                    <input type="text" id="telefone" name="telefone" class="form-control telefone-mask" 
                           value="<?= htmlspecialchars($formData['telefone']) ?>" required
                           placeholder="(99) 99999-9999">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--large">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($formData['email']) ?>"
                           placeholder="email@exemplo.com">
                </div>
                
                <div class="form-group">
                    <label for="local">Origem</label>
                    <select id="local" name="local" class="form-control">
                        <option value="Site" <?= $formData['local'] === 'Site' ? 'selected' : '' ?>>Site</option>
                        <option value="WhatsApp" <?= $formData['local'] === 'WhatsApp' ? 'selected' : '' ?>>WhatsApp</option>
                        <option value="Telefone" <?= $formData['local'] === 'Telefone' ? 'selected' : '' ?>>Telefone</option>
                        <option value="Pessoal" <?= $formData['local'] === 'Pessoal' ? 'selected' : '' ?>>Pessoal</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="form-section__title">Mensagem e Status</h3>
            
            <div class="form-row">
                <div class="form-group form-group--full">
                    <label for="mensagem">Mensagem <span class="required">*</span></label>
                    <textarea id="mensagem" name="mensagem" class="form-control" rows="6" required><?= htmlspecialchars($formData['mensagem']) ?></textarea>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="Pendente" <?= $formData['status'] === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="Em Andamento" <?= $formData['status'] === 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="Concluído" <?= $formData['status'] === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento" class="cancel-button">Cancelar</a>
            <button type="submit" class="primary-button">
                <i class="fas fa-save"></i> Salvar Atendimento
            </button>
        </div>
    </form>
</div>