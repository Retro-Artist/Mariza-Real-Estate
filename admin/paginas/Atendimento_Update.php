<?php
// admin/paginas/Atendimento_Update.php



// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID do atendimento não especificado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento_Admin');
    exit;
}

$atendimento_id = (int)$_GET['id'];

// Initialize variables
$error = '';

// Get service request data using function from admin_functions.php
$formData = getServiceRequestById($atendimento_id);

if (!$formData) {
    $_SESSION['alert_message'] = 'Atendimento não encontrado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento_Admin');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');
    $local = trim($_POST['local'] ?? 'Site');
    $status = trim($_POST['status'] ?? 'Pendente');
    
    // Validate form data
    if (empty($nome)) {
        $error = 'O nome é obrigatório.';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, informe um email válido.';
    } elseif (empty($mensagem)) {
        $error = 'A mensagem é obrigatória.';
    } else {
        // Prepare service request data
        $requestData = [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'mensagem' => $mensagem,
            'local' => $local,
            'status' => $status
        ];
        
        // Update service request using function from admin_functions.php
        $result = updateServiceRequest($atendimento_id, $requestData);
        
        if ($result) {
            // Set success message and redirect
            $_SESSION['alert_message'] = 'Atendimento atualizado com sucesso!';
            $_SESSION['alert_type'] = 'success';
            
            header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento_View&id=' . $atendimento_id);
            exit;
        } else {
            $error = 'Ocorreu um erro ao atualizar o atendimento. Por favor, tente novamente.';
        }
    }
    
    // Update formData with POST values for form re-population in case of error
    $formData['nome'] = $nome;
    $formData['email'] = $email;
    $formData['telefone'] = $telefone;
    $formData['mensagem'] = $mensagem;
    $formData['local'] = $local;
    $formData['status'] = $status;
}
?>
<main class="Atendimento">
<div class="admin-page atendimento-update">
    <!-- Page Header -->
    <div class="admin-page__header">
        <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_View&id=<?= $atendimento_id ?>" class="cancel-button">
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
                        <option value="Site" <?= $formData['local'] === 'Site' ? 'selected' : '' ?>>Site</option>
                        <option value="WhatsApp" <?= $formData['local'] === 'WhatsApp' ? 'selected' : '' ?>>WhatsApp</option>
                        <option value="Telefone" <?= $formData['local'] === 'Telefone' ? 'selected' : '' ?>>Telefone</option>
                        <option value="Pessoal" <?= $formData['local'] === 'Pessoal' ? 'selected' : '' ?>>Pessoal</option>
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
                
                <div class="form-group form-group--half">
                    <label>Data do Atendimento</label>
                    <div class="read-only-value">
                        <?= formatDate($formData['data']) ?> às <?= $formData['hora'] ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_View&id=<?= $atendimento_id ?>" class="cancel-button">Cancelar</a>
            <button type="submit" class="primary-button">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
        </div>
    </form>
</div>
</main>