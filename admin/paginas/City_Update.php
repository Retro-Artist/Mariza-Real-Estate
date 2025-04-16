<?php

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID da cidade não especificado.';
    $_SESSION['alert_type'] = 'error';
    $redirect_url = BASE_URL . '/admin/index.php?page=City_Admin';
    $need_redirect = true;
}

// If security check and ID check pass, proceed with page logic

$city_id = (int)$_GET['id'];

// Initialize variables
$error = '';
$success_message = '';
$redirect_after_save = false;

// Get city data
try {
    $stmt = $databaseConnection->prepare(
        "SELECT * FROM sistema_cidades WHERE id = :id LIMIT 1"
    );
    $stmt->bindParam(':id', $city_id);
    $stmt->execute();

    $city = $stmt->fetch();

    if (!$city) {
        $_SESSION['alert_message'] = 'Cidade não encontrada.';
        $_SESSION['alert_type'] = 'error';
        $redirect_url = BASE_URL . '/admin/index.php?page=City_Admin';
        $need_redirect = true;
    }
} catch (PDOException $e) {
    logError("Error fetching city data: " . $e->getMessage());
    $_SESSION['alert_message'] = 'Erro ao buscar dados da cidade.';
    $_SESSION['alert_type'] = 'error';
    $redirect_url = BASE_URL . '/admin/index.php?page=City_Admin';
    $need_redirect = true;
}

// If checks pass and city data is retrieved, continue with the form
if (isset($city)) {
    // Get all states for the dropdown
    $states = getStates();

    // Initialize form data with current city values
    $formData = [
        'nome' => $city['nome'],
        'id_estado' => $city['id_estado'],
        'cep' => $city['cep']
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
            // Check if city already exists in the selected state (excluding current city)
            try {
                $stmt = $databaseConnection->prepare(
                    "SELECT COUNT(*) as count FROM sistema_cidades 
                     WHERE LOWER(nome) = LOWER(:nome) AND id_estado = :id_estado AND id != :id"
                );
                $stmt->bindValue(':nome', $formData['nome']);
                $stmt->bindValue(':id_estado', $formData['id_estado']);
                $stmt->bindValue(':id', $city_id);
                $stmt->execute();

                $cityExists = $stmt->fetch()['count'] > 0;

                if ($cityExists) {
                    $error = 'Já existe uma cidade com este nome neste estado.';
                } else {
                    // Update city
                    $stmt = $databaseConnection->prepare(
                        "UPDATE sistema_cidades SET 
                         nome = :nome, 
                         id_estado = :id_estado, 
                         cep = :cep
                         WHERE id = :id"
                    );

                    $stmt->bindValue(':nome', $formData['nome']);
                    $stmt->bindValue(':id_estado', $formData['id_estado']);
                    $stmt->bindValue(':cep', $formData['cep']);
                    $stmt->bindValue(':id', $city_id);

                    $stmt->execute();

                    // Set success message and prepare for redirect
                    $success_message = 'Cidade atualizada com sucesso!';
                    $_SESSION['alert_message'] = $success_message;
                    $_SESSION['alert_type'] = 'success';

                    $redirect_after_save = true;
                    $redirect_url = BASE_URL . '/admin/index.php?page=City_Admin';
                }
            } catch (PDOException $e) {
                logError("Error updating city: " . $e->getMessage());
                $error = 'Ocorreu um erro ao atualizar a cidade. Por favor, tente novamente.';
            }
        }
    }
}
?>

<?php if (isset($city)): ?>
    <div class="admin-page city-update">
        <!-- Page Header -->
        <div class="admin-page__header">
            <h2 class="admin-page__title">Editar Cidade</h2>
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
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>