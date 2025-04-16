<?php


// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Armazenar a mensagem de erro e URL para redirecionamento posterior
    $_SESSION['alert_message'] = 'ID do lembrete não especificado.';
    $_SESSION['alert_type'] = 'error';
    $redirect_url = BASE_URL . '/admin/index.php?page=Calendar';
    $need_redirect = true;
}

if (!$need_redirect) {
    $event_id = (int)$_GET['id'];

    // Initialize variables
    $error = '';

    // Get event data using function from admin_functions.php
    $event = getCalendarEventById($event_id);

    if (!$event) {
        $_SESSION['alert_message'] = 'Lembrete não encontrado.';
        $_SESSION['alert_type'] = 'error';
        $redirect_url = BASE_URL . '/admin/index.php?page=Calendar';
        $need_redirect = true;
    }
}

// Variável para armazenar mensagens de sucesso e redirecionamento
$success_message = '';
$redirect_after_save = false;

if (!$need_redirect) {
    // Format dates for form inputs
    $data_inicio = new DateTime($event['data_inicio']);
    $data_fim = new DateTime($event['data_fim']);

    $formData = [
        'titulo' => $event['titulo'],
        'descricao' => $event['descricao'],
        'para' => $event['para'],
        'prioridade' => $event['prioridade'],
        'data_inicio' => $data_inicio->format('Y-m-d'),
        'hora_inicio' => $data_inicio->format('H:i'),
        'data_fim' => $data_fim->format('Y-m-d'),
        'hora_fim' => $data_fim->format('H:i'),
        'status' => $event['status']
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
            // Update calendar event using function from admin_functions.php
            $result = updateCalendarEvent($event_id, $formData);
            
            if ($result) {
                // Preparar redirecionamento para depois que a página for renderizada
                $success_message = 'Lembrete atualizado com sucesso!';
                $_SESSION['alert_message'] = $success_message;
                $_SESSION['alert_type'] = 'success';
                
                $redirect_after_save = true;
                $redirect_url = BASE_URL . '/admin/index.php?page=Calendar_View&id=' . $event_id;
            } else {
                $error = 'Ocorreu um erro ao atualizar o lembrete. Por favor, tente novamente.';
            }
        }
    }
}
?>

<?php if (!$need_redirect): ?>
<!-- HTML content remains unchanged -->

<!-- Update Event Page -->
<div class="admin-page event-update">
    <!-- Page Header -->
    <div class="admin-page__header">
        <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar_View&id=<?= $event_id ?>" class="cancel-button">
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
        
        <?php if (!empty($success_message)): ?>
            <div class="alert-message alert-message--success">
                <?= htmlspecialchars($success_message) ?>
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
            <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar_View&id=<?= $event_id ?>" class="cancel-button">Cancelar</a>
            <button type="submit" class="primary-button">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if ($need_redirect): ?>
<script>
    // Redirecionamento via JavaScript se a verificação de segurança falhar
    window.location.href = "<?= $redirect_url ?>";
</script>
<?php endif; ?>

<?php if ($redirect_after_save): ?>
<script>
    // Redirecionar após um breve intervalo para que o usuário possa ver a mensagem de sucesso
    setTimeout(function() {
        window.location.href = "<?= $redirect_url ?>";
    }, 1500);
</script>
<?php endif; ?>