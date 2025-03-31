<?php
// admin/paginas/Atendimento_Create.php

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
    'local' => 'Telefone',
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
        'local' => trim($_POST['local'] ?? 'Telefone'),
        'status' => trim($_POST['status'] ?? 'Pendente')
    ];
    
    // Validate form data
    if (empty($formData['nome'])) {
        $error = 'O nome é obrigatório.';
    } elseif (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, informe um email válido.';
    } elseif (empty($formData['mensagem'])) {
        $error = 'A mensagem é obrigatória.';
    } else {
        // Create service request using function from admin_functions.php
        $newRequestId = createServiceRequest($formData);
        
        if ($newRequestId) {
            // Set success message and redirect
            $_SESSION['alert_message'] = 'Atendimento adicionado com sucesso!';
            $_SESSION['alert_type'] = 'success';
            
            header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento_View&id=' . $newRequestId);
            exit;
        } else {
            $error = 'Ocorreu um erro ao adicionar o atendimento. Por favor, tente novamente.';
        }
    }
}
?>

<div class="admin-page atendimento-create">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Novo Atendimento</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Admin" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- Atendimento Form -->
    <form method="POST" action="" class="admin-form">
        <?php if (!empty($error)): ?>
            <div class="alert-message alert-message--error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="form-section">
            <h3 class="form-section__title">Informações do Atendimento</h3>
            
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
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" class="form-control" 
                           value="<?= htmlspecialchars($formData['telefone']) ?>">
                </div>
                
                <div class="form-group form-group--half">
                    <label for="local">Origem <span class="required">*</span></label>
                    <select id="local" name="local" class="form-control" required>
                        <option value="Telefone" <?= $formData['local'] === 'Telefone' ? 'selected' : '' ?>>Telefone</option>
                        <option value="WhatsApp" <?= $formData['local'] === 'WhatsApp' ? 'selected' : '' ?>>WhatsApp</option>
                        <option value="Pessoal" <?= $formData['local'] === 'Pessoal' ? 'selected' : '' ?>>Pessoal</option>
                        <option value="Site" <?= $formData['local'] === 'Site' ? 'selected' : '' ?>>Site</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--full">
                    <label for="mensagem">Mensagem <span class="required">*</span></label>
                    <textarea id="mensagem" name="mensagem" class="form-control" rows="6" required><?= htmlspecialchars($formData['mensagem']) ?></textarea>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--half">
                    <label for="status">Status <span class="required">*</span></label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="Pendente" <?= $formData['status'] === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="Em Andamento" <?= $formData['status'] === 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="Concluído" <?= $formData['status'] === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                        <option value="Cancelado" <?= $formData['status'] === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Admin" class="cancel-button">Cancelar</a>
            <button type="submit" class="primary-button">
                <i class="fas fa-save"></i> Cadastrar Atendimento
            </button>
        </div>
    </form>
</div>