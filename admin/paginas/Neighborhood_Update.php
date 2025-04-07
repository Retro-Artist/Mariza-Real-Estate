<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    // Instead of using header() directly, store the URL for later redirection via JavaScript
    $redirect_url = BASE_URL . '/admin/Admin_Login.php';
    $need_redirect = true;
} else {
    $need_redirect = false;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID do bairro não especificado.';
    $_SESSION['alert_type'] = 'error';
    $redirect_url = BASE_URL . '/admin/index.php?page=Neighborhood_Admin';
    $need_redirect = true;
}

// If security check and ID check pass, proceed with page logic
if (!$need_redirect) {
    $neighborhood_id = (int)$_GET['id'];
    
    // Initialize variables
    $error = '';
    $success_message = '';
    $redirect_after_save = false;
    
    // Get neighborhood data
    try {
        $stmt = $databaseConnection->prepare(
            "SELECT * FROM sistema_bairros WHERE id = :id LIMIT 1"
        );
        $stmt->bindParam(':id', $neighborhood_id);
        $stmt->execute();
        
        $neighborhood = $stmt->fetch();
        
        if (!$neighborhood) {
            $_SESSION['alert_message'] = 'Bairro não encontrado.';
            $_SESSION['alert_type'] = 'error';
            $redirect_url = BASE_URL . '/admin/index.php?page=Neighborhood_Admin';
            $need_redirect = true;
        }
    } catch (PDOException $e) {
        logError("Error fetching neighborhood data: " . $e->getMessage());
        $_SESSION['alert_message'] = 'Erro ao buscar dados do bairro.';
        $_SESSION['alert_type'] = 'error';
        $redirect_url = BASE_URL . '/admin/index.php?page=Neighborhood_Admin';
        $need_redirect = true;
    }
}

// If checks pass and neighborhood data is retrieved, continue with the form
if (!$need_redirect && isset($neighborhood)) {
    // Get all states for the dropdown
    $states = getStates();
    
    // Get cities for the selected state
    $cities = getCitiesByState($neighborhood['id_estado']);
    
    // Initialize form data with current neighborhood values
    $formData = [
        'bairro' => $neighborhood['bairro'],
        'id_estado' => $neighborhood['id_estado'],
        'id_cidade' => $neighborhood['id_cidade']
    ];
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $formData = [
            'bairro' => trim($_POST['bairro'] ?? ''),
            'id_estado' => intval($_POST['id_estado'] ?? 0),
            'id_cidade' => intval($_POST['id_cidade'] ?? 0)
        ];
        
        // Validate form data
        if (empty($formData['bairro'])) {
            $error = 'O nome do bairro é obrigatório.';
        } elseif (empty($formData['id_estado'])) {
            $error = 'Por favor, selecione um estado.';
        } elseif (empty($formData['id_cidade'])) {
            $error = 'Por favor, selecione uma cidade.';
        } else {
            // Check if neighborhood already exists in the selected city (excluding current neighborhood)
            try {
                $stmt = $databaseConnection->prepare(
                    "SELECT COUNT(*) as count FROM sistema_bairros 
                     WHERE LOWER(bairro) = LOWER(:bairro) AND id_cidade = :id_cidade AND id != :id"
                );
                $stmt->bindValue(':bairro', $formData['bairro']);
                $stmt->bindValue(':id_cidade', $formData['id_cidade']);
                $stmt->bindValue(':id', $neighborhood_id);
                $stmt->execute();
                
                $neighborhoodExists = $stmt->fetch()['count'] > 0;
                
                if ($neighborhoodExists) {
                    $error = 'Já existe um bairro com este nome nesta cidade.';
                } else {
                    // Update neighborhood
                    $stmt = $databaseConnection->prepare(
                        "UPDATE sistema_bairros SET 
                         bairro = :bairro, 
                         id_estado = :id_estado, 
                         id_cidade = :id_cidade
                         WHERE id = :id"
                    );
                    
                    $stmt->bindValue(':bairro', $formData['bairro']);
                    $stmt->bindValue(':id_estado', $formData['id_estado']);
                    $stmt->bindValue(':id_cidade', $formData['id_cidade']);
                    $stmt->bindValue(':id', $neighborhood_id);
                    
                    $stmt->execute();
                    
                    // Set success message and prepare for redirect
                    $success_message = 'Bairro atualizado com sucesso!';
                    $_SESSION['alert_message'] = $success_message;
                    $_SESSION['alert_type'] = 'success';
                    
                    $redirect_after_save = true;
                    $redirect_url = BASE_URL . '/admin/index.php?page=Neighborhood_Admin';
                }
            } catch (PDOException $e) {
                logError("Error updating neighborhood: " . $e->getMessage());
                $error = 'Ocorreu um erro ao atualizar o bairro. Por favor, tente novamente.';
            }
        }
    }
}
?>

<?php if (!$need_redirect && isset($neighborhood)): ?>
<div class="admin-page neighborhood-update">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Editar Bairro</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Neighborhood_Admin" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- Neighborhood Form -->
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
            <h3 class="form-section__title">Informações do Bairro</h3>
            
            <div class="form-row">
                <div class="form-group form-group--large">
                    <label for="bairro">Nome do Bairro <span class="required">*</span></label>
                    <input type="text" id="bairro" name="bairro" class="form-control" value="<?= htmlspecialchars($formData['bairro']) ?>" required>
                </div>
            </div>
            
            <div class="form-row">
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
                
                <div class="form-group">
                    <label for="id_cidade">Cidade <span class="required">*</span></label>
                    <select id="id_cidade" name="id_cidade" class="form-control" required <?= empty($formData['id_estado']) ? 'disabled' : '' ?>>
                        <option value="">Selecione uma Cidade</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= $city['id'] ?>" <?= $formData['id_cidade'] == $city['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($city['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Neighborhood_Admin" class="cancel-button">Cancelar</a>
            <button type="submit" class="primary-button">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
        </div>
    </form>
</div>

<!-- Script to handle dynamic city dropdown based on state selection -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stateSelect = document.getElementById('id_estado');
    const citySelect = document.getElementById('id_cidade');
    
    // Function to load cities for the selected state
    function loadCities(stateId) {
        // If no state selected, disable city dropdown
        if (!stateId) {
            citySelect.innerHTML = '<option value="">Selecione uma Cidade</option>';
            citySelect.disabled = true;
            return;
        }
        
        // Enable city dropdown
        citySelect.disabled = true;
        
        // Show loading indicator
        citySelect.innerHTML = '<option value="">Carregando...</option>';
        
        // Make AJAX request to get cities for the selected state
        fetch('<?= BASE_URL ?>/admin/ajax/get_cidades.php?id_estado=' + stateId)
            .then(response => response.json())
            .then(data => {
                // Check if there was an error
                if (data.error) {
                    console.error('Error loading cities:', data.error);
                    citySelect.innerHTML = '<option value="">Erro ao carregar cidades</option>';
                    return;
                }
                
                // Populate city dropdown with the returned cities
                let options = '<option value="">Selecione uma Cidade</option>';
                
                if (data.cidades && data.cidades.length > 0) {
                    data.cidades.forEach(city => {
                        // Check if this city was previously selected
                        const selected = <?= empty($formData['id_cidade']) ? '0' : $formData['id_cidade'] ?> == city.id ? 'selected' : '';
                        options += `<option value="${city.id}" ${selected}>${city.nome}</option>`;
                    });
                    
                    citySelect.innerHTML = options;
                    citySelect.disabled = false;
                } else {
                    citySelect.innerHTML = '<option value="">Nenhuma cidade encontrada</option>';
                    citySelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                citySelect.innerHTML = '<option value="">Erro ao carregar cidades</option>';
                citySelect.disabled = true;
            });
    }
    
    // Event listener for state selection change
    stateSelect.addEventListener('change', function() {
        loadCities(this.value);
    });
    
    // If a state is already selected on page load, load its cities
    if (stateSelect.value) {
        loadCities(stateSelect.value);
    }
});
</script>
<?php endif; ?>

<?php if ($need_redirect): ?>
<script>
    // JavaScript redirect if checks fail
    window.location.href = "<?= $redirect_url ?>";
</script>
<?php endif; ?>

<?php if (isset($redirect_after_save) && $redirect_after_save): ?>
<script>
    // Redirect after a brief delay to show the success message
    setTimeout(function() {
        window.location.href = "<?= $redirect_url ?>";
    }, 1500);
</script>
<?php endif; ?>