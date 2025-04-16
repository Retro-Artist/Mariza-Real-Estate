<?php

// If security check passes, proceed with page logic

    // Initialize variables
    $error = '';
    $success_message = '';
    $redirect_after_save = false;
    $redirect_url = '';
    
    // Get all states for the dropdown
    $states = getStates();
    
    // Initialize form data with default values
    $formData = [
        'nome' => '',
        'id_estado' => '',
        'cep' => ''
    ];
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $formData = [
            'nome' => trim($_POST['nome'] ?? ''),
            'id_estado' => intval($_POST['id_estado'] ?? 0),
            'cep' => trim($_POST['cep'] ?? '')
        ];
        
        // Validate form data
        if (empty($formData['nome'])) {
            $error = 'O nome da cidade é obrigatório.';
        } elseif (empty($formData['id_estado'])) {
            $error = 'Por favor, selecione um estado.';
        } else {
            // Check if city already exists in the selected state
            try {
                $stmt = $databaseConnection->prepare(
                    "SELECT COUNT(*) as count FROM sistema_cidades 
                     WHERE LOWER(nome) = LOWER(:nome) AND id_estado = :id_estado"
                );
                $stmt->bindValue(':nome', $formData['nome']);
                $stmt->bindValue(':id_estado', $formData['id_estado']);
                $stmt->execute();
                
                $cityExists = $stmt->fetch()['count'] > 0;
                
                if ($cityExists) {
                    $error = 'Já existe uma cidade com este nome neste estado.';
                } else {
                    // Insert new city
                    $stmt = $databaseConnection->prepare(
                        "INSERT INTO sistema_cidades (nome, id_estado, cep) 
                         VALUES (:nome, :id_estado, :cep)"
                    );
                    
                    $stmt->bindValue(':nome', $formData['nome']);
                    $stmt->bindValue(':id_estado', $formData['id_estado']);
                    $stmt->bindValue(':cep', $formData['cep']);
                    
                    $stmt->execute();
                    
                    // Set success message and prepare for redirect
                    $success_message = 'Cidade adicionada com sucesso!';
                    $_SESSION['alert_message'] = $success_message;
                    $_SESSION['alert_type'] = 'success';
                    
                    $redirect_after_save = true;
                    $redirect_url = BASE_URL . '/admin/index.php?page=City_Admin';
                }
            } catch (PDOException $e) {
                logError("Error creating city: " . $e->getMessage());
                $error = 'Ocorreu um erro ao adicionar a cidade. Por favor, tente novamente.';
            }
        }
    }
?>


<div class="admin-page city-create">
    <!-- Page Header -->
    <div class="admin-page__header">
        <a href="<?= BASE_URL ?>/admin/index.php?page=City_Admin" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- City Form -->
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
            <h3 class="form-section__title">Informações da Cidade</h3>
            
            <div class="form-row">
                <div class="form-group form-group--large">
                    <label for="nome">Nome da Cidade <span class="required">*</span></label>
                    <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($formData['nome']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="id_estado">Estado <span class="required">*</span></label>
                    <select id="id_estado" name="id_estado" class="form-control" required>
                        <option value="">Selecione um Estado</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?= $state['id'] ?>" <?= $formData['id_estado'] == $state['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($state['nome']) ?> (<?= htmlspecialchars($state['uf']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="cep">CEP</label>
                    <input type="text" id="cep" name="cep" class="form-control" value="<?= htmlspecialchars($formData['cep']) ?>">
                    <div class="form-text">Formato: 00000-000</div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=City_Admin" class="cancel-button">Cancelar</a>
            <button type="submit" class="primary-button">
                <i class="fas fa-save"></i> Salvar Cidade
            </button>
        </div>
    </form>
</div>