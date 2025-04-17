<?php
// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID do cliente não especificado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Client_Admin');
    exit;
}

$client_id = (int)$_GET['id'];

// Initialize variables
$error = '';
$success_message = '';
$redirect_after_save = false;
$redirect_url = '';

// Get data for dropdowns
$states = getStates();
$categories = ['Cliente', 'Proprietario', 'Corretor'];

// Get all cities for dropdown
$allCities = [];
try {
    $citiesStmt = $databaseConnection->query("SELECT * FROM sistema_cidades ORDER BY nome ASC");
    $allCities = $citiesStmt->fetchAll();
} catch (PDOException $e) {
    logError("Error fetching cities: " . $e->getMessage());
}

// Get all neighborhoods for dropdown
$allNeighborhoods = [];
try {
    $neighborhoodsStmt = $databaseConnection->query("SELECT * FROM sistema_bairros ORDER BY bairro ASC");
    $allNeighborhoods = $neighborhoodsStmt->fetchAll();
} catch (PDOException $e) {
    logError("Error fetching neighborhoods: " . $e->getMessage());
}

// Get client data
try {
    $stmt = $databaseConnection->prepare(
        "SELECT * FROM sistema_clientes WHERE id = :id LIMIT 1"
    );
    $stmt->bindParam(':id', $client_id);
    $stmt->execute();

    $client = $stmt->fetch();

    if (!$client) {
        $_SESSION['alert_message'] = 'Cliente não encontrado.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/index.php?page=Client_Admin');
        exit;
    }
} catch (PDOException $e) {
    logError("Error fetching client data: " . $e->getMessage());
    $_SESSION['alert_message'] = 'Erro ao buscar dados do cliente.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Client_Admin');
    exit;
}

// Initialize form data with client values
$formData = [
    'tipo' => $client['tipo'],
    'nome_completo' => $client['nome_completo'],
    'razao_social' => $client['razao_social'],
    'cpf' => $client['cpf'],
    'cnpj' => $client['cnpj'],
    'rg' => $client['rg'],
    'data_nascimento' => $client['data_nascimento'] ? date('Y-m-d', strtotime($client['data_nascimento'])) : '',
    'profissao' => $client['profissao'],
    'telefone1' => $client['telefone1'],
    'telefone2' => $client['telefone2'],
    'email' => $client['email'],
    'endereco' => $client['endereco'],
    'id_estado' => $client['id_estado'],
    'id_cidade' => $client['id_cidade'],
    'id_bairro' => $client['id_bairro'],
    'observacoes' => $client['observacoes'],
    'categoria' => $client['categoria'],
    'principal' => $client['principal']
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'tipo' => trim($_POST['tipo'] ?? 'PF'),
        'nome_completo' => trim($_POST['nome_completo'] ?? ''),
        'razao_social' => trim($_POST['razao_social'] ?? ''),
        'cpf' => trim($_POST['cpf'] ?? ''),
        'cnpj' => trim($_POST['cnpj'] ?? ''),
        'rg' => trim($_POST['rg'] ?? ''),
        'data_nascimento' => trim($_POST['data_nascimento'] ?? ''),
        'profissao' => trim($_POST['profissao'] ?? ''),
        'telefone1' => trim($_POST['telefone1'] ?? ''),
        'telefone2' => trim($_POST['telefone2'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'endereco' => trim($_POST['endereco'] ?? ''),
        'id_estado' => intval($_POST['id_estado'] ?? 0),
        'id_cidade' => intval($_POST['id_cidade'] ?? 0),
        'id_bairro' => intval($_POST['id_bairro'] ?? 0),
        'observacoes' => trim($_POST['observacoes'] ?? ''),
        'categoria' => trim($_POST['categoria'] ?? ''),
        'principal' => isset($_POST['principal']) ? 'Sim' : 'Não'
    ];

    // Validate form data
    if ($formData['tipo'] === 'PF' && empty($formData['nome_completo'])) {
        $error = 'O nome completo é obrigatório para Pessoa Física.';
    } elseif ($formData['tipo'] === 'PJ' && empty($formData['razao_social'])) {
        $error = 'A razão social é obrigatória para Pessoa Jurídica.';
    } elseif (empty($formData['categoria'])) {
        $error = 'A categoria do cliente é obrigatória.';
    } elseif (empty($formData['telefone1'])) {
        $error = 'Pelo menos um telefone de contato é obrigatório.';
    } else {
        try {
            // Format date to MySQL format if not empty
            $data_nascimento = !empty($formData['data_nascimento']) ? date('Y-m-d', strtotime($formData['data_nascimento'])) : null;

            // Update client
            $stmt = $databaseConnection->prepare(
                "UPDATE sistema_clientes SET
                    tipo = :tipo,
                    nome_completo = :nome_completo,
                    razao_social = :razao_social,
                    cpf = :cpf,
                    cnpj = :cnpj,
                    rg = :rg,
                    data_nascimento = :data_nascimento,
                    profissao = :profissao,
                    telefone1 = :telefone1,
                    telefone2 = :telefone2,
                    email = :email,
                    endereco = :endereco,
                    id_estado = :id_estado,
                    id_cidade = :id_cidade,
                    id_bairro = :id_bairro,
                    observacoes = :observacoes,
                    categoria = :categoria,
                    principal = :principal
                WHERE id = :id"
            );

            // Bind parameters
            $stmt->bindValue(':tipo', $formData['tipo']);
            $stmt->bindValue(':nome_completo', $formData['nome_completo']);
            $stmt->bindValue(':razao_social', $formData['razao_social']);
            $stmt->bindValue(':cpf', $formData['cpf']);
            $stmt->bindValue(':cnpj', $formData['cnpj']);
            $stmt->bindValue(':rg', $formData['rg']);
            $stmt->bindValue(':data_nascimento', $data_nascimento);
            $stmt->bindValue(':profissao', $formData['profissao']);
            $stmt->bindValue(':telefone1', $formData['telefone1']);
            $stmt->bindValue(':telefone2', $formData['telefone2']);
            $stmt->bindValue(':email', $formData['email']);
            $stmt->bindValue(':endereco', $formData['endereco']);
            $stmt->bindValue(':id_estado', $formData['id_estado'] ?: null, PDO::PARAM_INT);
            $stmt->bindValue(':id_cidade', $formData['id_cidade'] ?: null, PDO::PARAM_INT);
            $stmt->bindValue(':id_bairro', $formData['id_bairro'] ?: null, PDO::PARAM_INT);
            $stmt->bindValue(':observacoes', $formData['observacoes']);
            $stmt->bindValue(':categoria', $formData['categoria']);
            $stmt->bindValue(':principal', $formData['principal']);
            $stmt->bindValue(':id', $client_id);

            $result = $stmt->execute();

            if ($result) {
                // Set success message and prepare for redirect
                $success_message = 'Cliente atualizado com sucesso!';
                $_SESSION['alert_message'] = $success_message;
                $_SESSION['alert_type'] = 'success';

                $redirect_after_save = true;
                $redirect_url = BASE_URL . '/admin/index.php?page=Client_Admin';
            } else {
                $error = 'Erro ao salvar os dados. Por favor, tente novamente.';
            }
        } catch (PDOException $e) {
            logError("Error updating client: " . $e->getMessage());
            $error = 'Ocorreu um erro ao atualizar o cliente. Por favor, tente novamente.';
        }
    }
}
?>

<main class="Client">
    <div class="admin-page client-update">
        <!-- Page Header -->
        <div class="admin-page__header">
            <h2 class="admin-page__title">Editar Cliente</h2>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin" class="admin-page__back-link">
                <i class="fas fa-arrow-left"></i> Voltar para Lista
            </a>
        </div>

        <!-- Client Form -->
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

            <!-- Tipo de Cliente Section -->
            <div class="form-section">
                <h3 class="form-section__title">Tipo de Cliente</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label>Tipo de Pessoa</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="tipo" id="fisica" value="PF" <?= $formData['tipo'] === 'PF' ? 'checked' : '' ?>>
                                Pessoa Física
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="tipo" id="juridica" value="PJ" <?= $formData['tipo'] === 'PJ' ? 'checked' : '' ?>>
                                Pessoa Jurídica
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="categoria">Categoria <span class="required">*</span></label>
                        <select id="categoria" name="categoria" class="form-control" required>
                            <option value="">Selecione</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category ?>" <?= $formData['categoria'] == $category ? 'selected' : '' ?>>
                                    <?= $category ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group--checkbox">
                        <label>
                            <input type="checkbox" name="principal" value="1" <?= $formData['principal'] === 'Sim' ? 'checked' : '' ?>>
                            Cliente Principal
                        </label>
                    </div>
                </div>
            </div>

            <!-- Pessoa Física Section -->
            <div id="pessoa-fisica-fields" class="form-section" <?= $formData['tipo'] === 'PJ' ? 'style="display: none;"' : '' ?>>
                <h3 class="form-section__title">Dados Pessoais</h3>

                <div class="form-row">
                    <div class="form-group form-group--large">
                        <label for="nome_completo">Nome Completo <span class="required">*</span></label>
                        <input type="text" id="nome_completo" name="nome_completo" class="form-control" value="<?= htmlspecialchars($formData['nome_completo']) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cpf">CPF</label>
                        <input type="text" id="cpf" name="cpf" class="form-control" value="<?= htmlspecialchars($formData['cpf']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="rg">RG</label>
                        <input type="text" id="rg" name="rg" class="form-control" value="<?= htmlspecialchars($formData['rg']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="data_nascimento">Data de Nascimento</label>
                        <input type="date" id="data_nascimento" name="data_nascimento" class="form-control" value="<?= htmlspecialchars($formData['data_nascimento']) ?>">
                    </div>
                </div>
            </div>

            <!-- Pessoa Jurídica Section -->
            <div id="pessoa-juridica-fields" class="form-section" <?= $formData['tipo'] === 'PF' ? 'style="display: none;"' : '' ?>>
                <h3 class="form-section__title">Dados da Empresa</h3>

                <div class="form-row">
                    <div class="form-group form-group--large">
                        <label for="razao_social">Razão Social <span class="required">*</span></label>
                        <input type="text" id="razao_social" name="razao_social" class="form-control" value="<?= htmlspecialchars($formData['razao_social']) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cnpj">CNPJ</label>
                        <input type="text" id="cnpj" name="cnpj" class="form-control" value="<?= htmlspecialchars($formData['cnpj']) ?>">
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="form-section">
                <h3 class="form-section__title">Informações de Contato</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="profissao">Profissão</label>
                        <input type="text" id="profissao" name="profissao" class="form-control" value="<?= htmlspecialchars($formData['profissao']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="telefone1">Telefone Principal <span class="required">*</span></label>
                        <input type="text" id="telefone1" name="telefone1" class="form-control phone-mask" value="<?= htmlspecialchars($formData['telefone1']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="telefone2">Telefone Secundário</label>
                        <input type="text" id="telefone2" name="telefone2" class="form-control phone-mask" value="<?= htmlspecialchars($formData['telefone2']) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group form-group--large">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($formData['email']) ?>">
                    </div>
                </div>
            </div>

            <!-- Address Section -->
            <div class="form-section">
                <h3 class="form-section__title">Endereço</h3>

                <div class="form-row">
                    <div class="form-group form-group--large">
                        <label for="endereco">Endereço Completo</label>
                        <input type="text" id="endereco" name="endereco" class="form-control" value="<?= htmlspecialchars($formData['endereco']) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="id_estado">Estado</label>
                        <select id="id_estado" name="id_estado" class="form-control">
                            <option value="">Selecione</option>
                            <?php foreach ($states as $state): ?>
                                <option value="<?= $state['id'] ?>" <?= $formData['id_estado'] == $state['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($state['nome']) ?> (<?= htmlspecialchars($state['uf']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_cidade">Cidade</label>
                        <select id="id_cidade" name="id_cidade" class="form-control" <?= empty($formData['id_estado']) ? 'disabled' : '' ?>>
                            <option value="">Selecione</option>
                            <?php foreach ($allCities as $city): ?>
                                <option value="<?= $city['id'] ?>" 
                                        data-state="<?= $city['id_estado'] ?>" 
                                        <?= $formData['id_cidade'] == $city['id'] ? 'selected' : '' ?>
                                        <?= $formData['id_estado'] && $city['id_estado'] != $formData['id_estado'] ? 'style="display: none;"' : '' ?>>
                                    <?= htmlspecialchars($city['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_bairro">Bairro</label>
                        <select id="id_bairro" name="id_bairro" class="form-control" <?= empty($formData['id_cidade']) ? 'disabled' : '' ?>>
                            <option value="">Selecione</option>
                            <?php foreach ($allNeighborhoods as $neighborhood): ?>
                                <option value="<?= $neighborhood['id'] ?>" 
                                        data-city="<?= $neighborhood['id_cidade'] ?>" 
                                        <?= $formData['id_bairro'] == $neighborhood['id'] ? 'selected' : '' ?>
                                        <?= $formData['id_cidade'] && $neighborhood['id_cidade'] != $formData['id_cidade'] ? 'style="display: none;"' : '' ?>>
                                    <?= htmlspecialchars($neighborhood['bairro']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="form-section">
                <h3 class="form-section__title">Observações</h3>

                <div class="form-row">
                    <div class="form-group form-group--large">
                        <textarea id="observacoes" name="observacoes" class="form-control" rows="4"><?= htmlspecialchars($formData['observacoes']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin" class="cancel-button">Cancelar</a>
                <button type="submit" class="primary-button">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle client type toggle
        const pessoaFisicaRadio = document.getElementById('fisica');
        const pessoaJuridicaRadio = document.getElementById('juridica');
        const pessoaFisicaFields = document.getElementById('pessoa-fisica-fields');
        const pessoaJuridicaFields = document.getElementById('pessoa-juridica-fields');

        function toggleClientType() {
            if (pessoaFisicaRadio.checked) {
                pessoaFisicaFields.style.display = 'block';
                pessoaJuridicaFields.style.display = 'none';
            } else {
                pessoaFisicaFields.style.display = 'none';
                pessoaJuridicaFields.style.display = 'block';
            }
        }

        pessoaFisicaRadio.addEventListener('change', toggleClientType);
        pessoaJuridicaRadio.addEventListener('change', toggleClientType);

        // Location selectors functionality
        const estadoSelect = document.getElementById('id_estado');
        const cidadeSelect = document.getElementById('id_cidade');
        const bairroSelect = document.getElementById('id_bairro');

        if (estadoSelect && cidadeSelect && bairroSelect) {
            // When state changes, filter cities
            estadoSelect.addEventListener('change', function() {
                const selectedState = this.value;
                
                // Reset city dropdown
                cidadeSelect.disabled = !selectedState;
                
                // Reset neighborhood dropdown
                bairroSelect.disabled = true;
                
                // Show/hide city options based on selected state
                const cityOptions = cidadeSelect.querySelectorAll('option:not(:first-child)');
                let hasVisibleCity = false;
                
                cityOptions.forEach(option => {
                    if (!selectedState || option.dataset.state === selectedState) {
                        option.style.display = '';
                        hasVisibleCity = true;
                    } else {
                        option.style.display = 'none';
                        if (option.selected) {
                            cidadeSelect.value = '';
                        }
                    }
                });
                
                // Enable/disable city dropdown
                cidadeSelect.disabled = !hasVisibleCity && !selectedState;
            });
            
            // When city changes, filter neighborhoods
            cidadeSelect.addEventListener('change', function() {
                const selectedCity = this.value;
                
                // Reset neighborhood dropdown
                bairroSelect.disabled = !selectedCity;
                
                // Show/hide neighborhood options based on selected city
                const neighborhoodOptions = bairroSelect.querySelectorAll('option:not(:first-child)');
                let hasVisibleNeighborhood = false;
                
                neighborhoodOptions.forEach(option => {
                    if (!selectedCity || option.dataset.city === selectedCity) {
                        option.style.display = '';
                        hasVisibleNeighborhood = true;
                    } else {
                        option.style.display = 'none';
                        if (option.selected) {
                            bairroSelect.value = '';
                        }
                    }
                });
                
                // Enable/disable neighborhood dropdown
                bairroSelect.disabled = !hasVisibleNeighborhood && !selectedCity;
            });
            
            // Initialize selectors based on initial values
            if (estadoSelect.value) {
                estadoSelect.dispatchEvent(new Event('change'));
                
                if (cidadeSelect.value) {
                    cidadeSelect.dispatchEvent(new Event('change'));
                }
            }
        }

        // Phone mask
        const phoneMasks = document.querySelectorAll('.phone-mask');
        if (phoneMasks.length > 0) {
            phoneMasks.forEach(input => {
                input.addEventListener('input', function(e) {
                    let value = this.value.replace(/\D/g, '');
                    
                    if (value.length > 11) value = value.substring(0, 11);
                    
                    if (value.length > 6) {
                        if (value.length > 10) { // Celular com 9 dígitos
                            this.value = `(${value.substring(0, 2)}) ${value.substring(2, 7)}-${value.substring(7)}`;
                        } else { // Telefone fixo
                            this.value = `(${value.substring(0, 2)}) ${value.substring(2, 6)}-${value.substring(6)}`;
                        }
                    } else if (value.length > 2) {
                        this.value = `(${value.substring(0, 2)}) ${value.substring(2)}`;
                    } else if (value.length > 0) {
                        this.value = `(${value}`;
                    } else {
                        this.value = '';
                    }
                });
            });
        }

        // CPF mask
        const cpfInput = document.getElementById('cpf');
        if (cpfInput) {
            cpfInput.addEventListener('input', function(e) {
                let value = this.value.replace(/\D/g, '');
                
                if (value.length > 11) value = value.substring(0, 11);
                
                if (value.length > 9) {
                    this.value = `${value.substring(0, 3)}.${value.substring(3, 6)}.${value.substring(6, 9)}-${value.substring(9)}`;
                } else if (value.length > 6) {
                    this.value = `${value.substring(0, 3)}.${value.substring(3, 6)}.${value.substring(6)}`;
                } else if (value.length > 3) {
                    this.value = `${value.substring(0, 3)}.${value.substring(3)}`;
                } else {
                    this.value = value;
                }
            });
        }

        // CNPJ mask
        const cnpjInput = document.getElementById('cnpj');
        if (cnpjInput) {
            cnpjInput.addEventListener('input', function(e) {
                let value = this.value.replace(/\D/g, '');
                
                if (value.length > 14) value = value.substring(0, 14);
                
                if (value.length > 12) {
                    this.value = `${value.substring(0, 2)}.${value.substring(2, 5)}.${value.substring(5, 8)}/${value.substring(8, 12)}-${value.substring(12)}`;
                } else if (value.length > 8) {
                    this.value = `${value.substring(0, 2)}.${value.substring(2, 5)}.${value.substring(5, 8)}/${value.substring(8)}`;
                } else if (value.length > 5) {
                    this.value = `${value.substring(0, 2)}.${value.substring(2, 5)}.${value.substring(5)}`;
                } else if (value.length > 2) {
                    this.value = `${value.substring(0, 2)}.${value.substring(2)}`;
                } else {
                    this.value = value;
                }
            });
        }
    });

    <?php if ($redirect_after_save): ?>
    // Redirect after save with a slight delay to show success message
    setTimeout(function() {
        window.location.href = "<?= $redirect_url ?>";
    }, 1500);
    <?php endif; ?>
</script>