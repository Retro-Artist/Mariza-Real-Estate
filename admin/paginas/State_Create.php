<?php


// If security check passes, proceed with page logic

// Initialize variables
$error = '';
$success_message = '';
$redirect_after_save = false;
$redirect_url = '';

// Initialize form data with default values
$formData = [
    'nome' => '',
    'uf' => ''
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'nome' => trim($_POST['nome'] ?? ''),
        'uf' => strtoupper(trim($_POST['uf'] ?? ''))
    ];

    // Validate form data
    if (empty($formData['nome'])) {
        $error = 'O nome do estado é obrigatório.';
    } elseif (empty($formData['uf'])) {
        $error = 'A sigla UF do estado é obrigatória.';
    } elseif (strlen($formData['uf']) != 2) {
        $error = 'A sigla UF deve conter exatamente 2 caracteres.';
    } else {
        // Check if state already exists
        try {
            $stmt = $databaseConnection->prepare(
                "SELECT COUNT(*) as count FROM sistema_estados 
                     WHERE LOWER(nome) = LOWER(:nome) OR UPPER(uf) = UPPER(:uf)"
            );
            $stmt->bindValue(':nome', $formData['nome']);
            $stmt->bindValue(':uf', $formData['uf']);
            $stmt->execute();

            $stateExists = $stmt->fetch()['count'] > 0;

            if ($stateExists) {
                $error = 'Já existe um estado com este nome ou sigla UF.';
            } else {
                // Insert new state
                $stmt = $databaseConnection->prepare(
                    "INSERT INTO sistema_estados (nome, uf) 
                         VALUES (:nome, :uf)"
                );

                $stmt->bindValue(':nome', $formData['nome']);
                $stmt->bindValue(':uf', $formData['uf']);

                $stmt->execute();

                // Set success message and prepare for redirect
                $success_message = 'Estado adicionado com sucesso!';
                $_SESSION['alert_message'] = $success_message;
                $_SESSION['alert_type'] = 'success';

                $redirect_after_save = true;
                $redirect_url = BASE_URL . '/admin/index.php?page=State_Admin';
            }
        } catch (PDOException $e) {
            logError("Error creating state: " . $e->getMessage());
            $error = 'Ocorreu um erro ao adicionar o estado. Por favor, tente novamente.';
        }
    }
}

?>


<div class="admin-page state-create">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Adicionar Novo Estado</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=State_Admin" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- State Form -->
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
            <h3 class="form-section__title">Informações do Estado</h3>

            <div class="form-row">
                <div class="form-group form-group--large">
                    <label for="nome">Nome do Estado <span class="required">*</span></label>
                    <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($formData['nome']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="uf">UF <span class="required">*</span></label>
                    <input type="text" id="uf" name="uf" class="form-control" value="<?= htmlspecialchars($formData['uf']) ?>" maxlength="2" style="text-transform: uppercase;" required>
                    <div class="form-text">Sigla de 2 letras (ex: SP)</div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=State_Admin" class="cancel-button">Cancelar</a>
            <button type="submit" class="primary-button">
                <i class="fas fa-save"></i> Salvar Estado
            </button>
        </div>
    </form>
</div>