<?php


// If security check passes, proceed with page logic
if (!$need_redirect) {
    // Initialize variables
    $error = '';
    $success_message = '';
    $redirect_after_save = false;
    $redirect_url = '';

    // Get all states for the dropdown
    $states = getStates();

    // Initialize form data with default values
    $formData = [
        'bairro' => '',
        'id_estado' => '',
        'id_cidade' => ''
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
            // Check if neighborhood already exists in the selected city
            try {
                $stmt = $databaseConnection->prepare(
                    "SELECT COUNT(*) as count FROM sistema_bairros 
                     WHERE LOWER(bairro) = LOWER(:bairro) AND id_cidade = :id_cidade"
                );
                $stmt->bindValue(':bairro', $formData['bairro']);
                $stmt->bindValue(':id_cidade', $formData['id_cidade']);
                $stmt->execute();

                $neighborhoodExists = $stmt->fetch()['count'] > 0;

                if ($neighborhoodExists) {
                    $error = 'Já existe um bairro com este nome nesta cidade.';
                } else {
                    // Insert new neighborhood
                    $stmt = $databaseConnection->prepare(
                        "INSERT INTO sistema_bairros (bairro, id_estado, id_cidade) 
                         VALUES (:bairro, :id_estado, :id_cidade)"
                    );

                    $stmt->bindValue(':bairro', $formData['bairro']);
                    $stmt->bindValue(':id_estado', $formData['id_estado']);
                    $stmt->bindValue(':id_cidade', $formData['id_cidade']);

                    $stmt->execute();

                    // Set success message and prepare for redirect
                    $success_message = 'Bairro adicionado com sucesso!';
                    $_SESSION['alert_message'] = $success_message;
                    $_SESSION['alert_type'] = 'success';

                    $redirect_after_save = true;
                    $redirect_url = BASE_URL . '/admin/index.php?page=Neighborhood_Admin';
                }
            } catch (PDOException $e) {
                logError("Error creating neighborhood: " . $e->getMessage());
                $error = 'Ocorreu um erro ao adicionar o bairro. Por favor, tente novamente.';
            }
        }
    }
}
?>

<?php if (!$need_redirect): ?>
    <main class="Location">
        <div class="admin-page neighborhood-create">
            <!-- Page Header -->
            <div class="admin-page__header">
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
                                <?php
                                // If state was selected, get cities for that state
                                if (!empty($formData['id_estado'])) {
                                    $cities = getCitiesByState($formData['id_estado']);
                                    foreach ($cities as $city):
                                ?>
                                        <option value="<?= $city['id'] ?>" <?= $formData['id_cidade'] == $city['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($city['nome']) ?>
                                        </option>
                                <?php
                                    endforeach;
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="<?= BASE_URL ?>/admin/index.php?page=Neighborhood_Admin" class="cancel-button">Cancelar</a>
                    <button type="submit" class="primary-button">
                        <i class="fas fa-save"></i> Salvar Bairro
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Script to handle dynamic city dropdown based on state selection -->
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

            // Direct filtering approach - get cities from existing data
            // This is a fallback approach that doesn't require an AJAX endpoint
            let citiesData = [];
            
            <?php
            // Output all cities as a JavaScript array with their state IDs
            try {
                $allCitiesStmt = $databaseConnection->query("SELECT id, nome, id_estado FROM sistema_cidades ORDER BY nome ASC");
                $allCities = $allCitiesStmt->fetchAll();
                echo "citiesData = " . json_encode($allCities) . ";";
            } catch (PDOException $e) {
                echo "console.error('Error fetching cities: " . addslashes($e->getMessage()) . "');";
                echo "citiesData = [];";
            }
            ?>
            
            // Filter cities by state ID
            const filteredCities = citiesData.filter(city => city.id_estado == stateId);
            
            // Populate city dropdown with filtered cities
            let options = '<option value="">Selecione uma Cidade</option>';
            
            if (filteredCities && filteredCities.length > 0) {
                filteredCities.forEach(city => {
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
        // JavaScript redirect if security check fails
        window.location.href = "<?= $redirect_url ?>";
    </script>
<?php endif; ?>

<?php if ($redirect_after_save): ?>
    <script>
        // Redirect after a brief delay to show the success message
        setTimeout(function() {
            window.location.href = "<?= $redirect_url ?>";
        }, 1500);
    </script>
<?php endif; ?>