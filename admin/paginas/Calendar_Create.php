<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Initialize variables
$error = '';
$formData = [
    'titulo' => '',
    'descricao' => '',
    'para' => '',
    'prioridade' => 'Normal',
    'data_inicio' => date('Y-m-d'),
    'hora_inicio' => date('H:i'),
    'data_fim' => date('Y-m-d'),
    'hora_fim' => date('H:i'),
    'status' => 'Pendente'
];

// Get available users for assignment
try {
    $stmt = $databaseConnection->query("SELECT id, nome FROM sistema_usuarios ORDER BY nome ASC");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    logError("Error fetching users: " . $e->getMessage());
    $usuarios = [];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'titulo' => trim($_POST['titulo'] ?? ''),
        'descricao' => trim($_POST['descricao'] ?? ''),
        'para' => trim($_POST['para'] ?? ''),
        'prioridade' => trim($_POST['prioridade'] ?? 'Normal'),
        'data_inicio' => trim($_POST['data_inicio'] ?? ''),
        'hora_inicio' => trim($_POST['hora_inicio'] ?? ''),
        'data_fim' => trim($_POST['data_fim'] ?? ''),
        'hora_fim' => trim($_POST['hora_fim'] ?? ''),
        'status' => trim($_POST['status'] ?? 'Pendente')
    ];
    
    // Validate form data
    if (empty($formData['titulo'])) {
        $error = 'O título do lembrete é obrigatório.';
    } elseif (empty($formData['data_inicio']) || empty($formData['data_fim'])) {
        $error = 'As datas de início e fim são obrigatórias.';
    } else {
        // Create calendar event using function from admin_functions.php
        $newEventId = createCalendarEvent($formData);
        
        if ($newEventId) {
            // Set success message and redirect
            $_SESSION['alert_message'] = 'Lembrete adicionado com sucesso!';
            $_SESSION['alert_type'] = 'success';
            
            header('Location: ' . BASE_URL . '/admin/index.php?page=Calendar');
            exit;
        } else {
            $error = 'Ocorreu um erro ao adicionar o lembrete. Por favor, tente novamente.';
        }
    }
}
?>

<!-- HTML content remains unchanged -->

<!-- Add Event Page -->
<div class="admin-page event-create">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Adicionar Lembrete</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- Event Form -->
    <form method="POST" action="" class="admin-form">
        <?php if (!empty($error)): ?>
            <div class="alert-message alert-message--error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="form-section">
            <h3 class="form-section__title">Informações do Lembrete</h3>
            
            <div class="form-row">
                <div class="form-group form-group--full">
                    <label for="titulo">Título <span class="required">*</span></label>
                    <input type="text" id="titulo" name="titulo" class="form-control" 
                           value="<?= htmlspecialchars($formData['titulo']) ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--full">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" class="form-control" rows="4"><?= htmlspecialchars($formData['descricao']) ?></textarea>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="para">Para</label>
                    <select id="para" name="para" class="form-control">
                        <option value="Todos" <?= $formData['para'] === 'Todos' ? 'selected' : '' ?>>Todos os Usuários</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?= htmlspecialchars($usuario['nome']) ?>" <?= $formData['para'] === $usuario['nome'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($usuario['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="prioridade">Prioridade</label>
                    <select id="prioridade" name="prioridade" class="form-control">
                        <option value="Baixa" <?= $formData['prioridade'] === 'Baixa' ? 'selected' : '' ?>>Baixa</option>
                        <option value="Normal" <?= $formData['prioridade'] === 'Normal' ? 'selected' : '' ?>>Normal</option>
                        <option value="Alta" <?= $formData['prioridade'] === 'Alta' ? 'selected' : '' ?>>Alta</option>
                        <option value="Urgente" <?= $formData['prioridade'] === 'Urgente' ? 'selected' : '' ?>>Urgente</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="Pendente" <?= $formData['status'] === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="Concluído" <?= $formData['status'] === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="data_inicio">Data de Início <span class="required">*</span></label>
                    <input type="date" id="data_inicio" name="data_inicio" class="form-control" 
                           value="<?= htmlspecialchars($formData['data_inicio']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="hora_inicio">Hora de Início</label>
                    <input type="time" id="hora_inicio" name="hora_inicio" class="form-control" 
                           value="<?= htmlspecialchars($formData['hora_inicio']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="data_fim">Data de Término <span class="required">*</span></label>
                    <input type="date" id="data_fim" name="data_fim" class="form-control" 
                           value="<?= htmlspecialchars($formData['data_fim']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="hora_fim">Hora de Término</label>
                    <input type="time" id="hora_fim" name="hora_fim" class="form-control" 
                           value="<?= htmlspecialchars($formData['hora_fim']) ?>">
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar" class="cancel-button">Cancelar</a>
            <button type="submit" class="primary-button">
                <i class="fas fa-save"></i> Salvar Lembrete
            </button>
        </div>
    </form>
</div>