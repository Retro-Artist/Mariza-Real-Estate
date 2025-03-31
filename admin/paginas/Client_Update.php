<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

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

// Get client data using our function from admin_functions.php
$formData = getAdminClientById($client_id);

if (!$formData) {
    $_SESSION['alert_message'] = 'Cliente não encontrado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Client_Admin');
    exit;
}

// Get states
$estados = getStates();

// Get cities based on selected state
$cidades = [];
if (!empty($formData['id_estado'])) {
    $cidades = getCitiesByState($formData['id_estado']);
}

// Get neighborhoods based on selected city
$bairros = [];
if (!empty($formData['id_cidade'])) {
    $bairros = getNeighborhoodsByCity($formData['id_cidade']);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $tipo = trim($_POST['tipo'] ?? 'Pessoa Física');
    $nome_completo = trim($_POST['nome_completo'] ?? '');
    $razao_social = trim($_POST['razao_social'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $cnpj = trim($_POST['cnpj'] ?? '');
    $rg = trim($_POST['rg'] ?? '');
    $data_nascimento = $_POST['data_nascimento'] ?? null;
    $profissao = trim($_POST['profissao'] ?? '');
    $telefone1 = trim($_POST['telefone1'] ?? '');
    $telefone2 = trim($_POST['telefone2'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $id_estado = (int)($_POST['id_estado'] ?? 0);
    $id_cidade = (int)($_POST['id_cidade'] ?? 0);
    $id_bairro = (int)($_POST['id_bairro'] ?? 0);
    $observacoes = trim($_POST['observacoes'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $principal = isset($_POST['principal']) && $_POST['principal'] === '1' ? 'Sim' : 'Não';
    
    // Validate form data
    if ($tipo === 'Pessoa Física') {
        if (empty($nome_completo)) {
            $error = 'O nome completo é obrigatório para pessoa física.';
        } elseif (empty($cpf)) {
            $error = 'O CPF é obrigatório para pessoa física.';
        }
    } else {
        if (empty($razao_social)) {
            $error = 'A razão social é obrigatória para pessoa jurídica.';
        } elseif (empty($cnpj)) {
            $error = 'O CNPJ é obrigatório para pessoa jurídica.';
        }
    }
    
    if (empty($telefone1)) {
        $error = 'O telefone é obrigatório.';
    } elseif (empty($email)) {
        $error = 'O email é obrigatório.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, informe um email válido.';
    }
    
    // If no errors, update database
    if (empty($error)) {
        // Prepare client data
        $clientData = [
            'tipo' => $tipo,
            'nome_completo' => $nome_completo,
            'razao_social' => $razao_social,
            'cpf' => $cpf,
            'cnpj' => $cnpj,
            'rg' => $rg,
            'data_nascimento' => $data_nascimento,
            'profissao' => $profissao,
            'telefone1' => $telefone1,
            'telefone2' => $telefone2,
            'email' => $email,
            'endereco' => $endereco,
            'id_estado' => $id_estado,
            'id_cidade' => $id_cidade,
            'id_bairro' => $id_bairro,
            'observacoes' => $observacoes,
            'categoria' => $categoria,
            'principal' => $principal
        ];
        
        // Update client using our function from admin_functions.php
        $result = updateClient($client_id, $clientData);
        
        if ($result) {
            // Set success message and redirect
            $_SESSION['alert_message'] = 'Cliente atualizado com sucesso!';
            $_SESSION['alert_type'] = 'success';
            
            header('Location: ' . BASE_URL . '/admin/index.php?page=Client_Admin');
            exit;
        } else {
            $error = 'Ocorreu um erro ao atualizar o cliente. Por favor, tente novamente.';
        }
    }
    
    // Update formData with POST values for form re-population in case of error
    $formData['tipo'] = $tipo;
    $formData['nome_completo'] = $nome_completo;
    $formData['razao_social'] = $razao_social;
    $formData['cpf'] = $cpf;
    $formData['cnpj'] = $cnpj;
    $formData['rg'] = $rg;
    $formData['data_nascimento'] = $data_nascimento;
    $formData['profissao'] = $profissao;
    $formData['telefone1'] = $telefone1;
    $formData['telefone2'] = $telefone2;
    $formData['email'] = $email;
    $formData['endereco'] = $endereco;
    $formData['id_estado'] = $id_estado;
    $formData['id_cidade'] = $id_cidade;
    $formData['id_bairro'] = $id_bairro;
    $formData['observacoes'] = $observacoes;
    $formData['categoria'] = $categoria;
    $formData['principal'] = $principal;
}
?>

<!-- Update Client Page -->
<div class="client-create-container">
    <h1 class="client-page-title">Atualizar <span>Cliente</span></h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert-message alert-message--error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <!-- Client Form -->
    <form method="POST" action="<?= BASE_URL ?>/admin/index.php?page=Client_Update&id=<?= $client_id ?>" class="client-form">
        <div class="client-form-card">
            <div class="form-section-header">
                <h3>Informações do Cliente</h3>
            </div>
            
            <div class="form-section">
                <!-- Category Dropdown -->
                <div class="form-group">
                    <label for="categoria">Categoria:</label>
                    <select id="categoria" name="categoria" class="form-control">
                        <option value="Cliente" <?= $formData['categoria'] === 'Cliente' ? 'selected' : '' ?>>Cliente</option>
                        <option value="Proprietário" <?= $formData['categoria'] === 'Proprietário' ? 'selected' : '' ?>>Proprietário</option>
                        <option value="Locatário" <?= $formData['categoria'] === 'Locatário' ? 'selected' : '' ?>>Locatário</option>
                        <option value="Locador" <?= $formData['categoria'] === 'Locador' ? 'selected' : '' ?>>Locador</option>
                        <option value="Vendedor" <?= $formData['categoria'] === 'Vendedor' ? 'selected' : '' ?>>Vendedor</option>
                        <option value="Comprador" <?= $formData['categoria'] === 'Comprador' ? 'selected' : '' ?>>Comprador</option>
                        <option value="Fiador" <?= $formData['categoria'] === 'Fiador' ? 'selected' : '' ?>>Fiador</option>
                    </select>
                </div>
                
                <!-- Client Type Radio Buttons -->
                <div class="client-type-container">
                    <div class="client-type-option">
                        <input type="radio" id="fisica" name="tipo" value="Pessoa Física" 
                              <?= $formData['tipo'] === 'Pessoa Física' ? 'checked' : '' ?> 
                              onclick="toggleClientType()">
                        <label for="fisica">Pessoa Física</label>
                    </div>
                    
                    <div class="client-type-option">
                        <input type="radio" id="juridica" name="tipo" value="Pessoa Jurídica" 
                              <?= $formData['tipo'] === 'Pessoa Jurídica' ? 'checked' : '' ?> 
                              onclick="toggleClientType()">
                        <label for="juridica">Pessoa Jurídica</label>
                    </div>
                </div>
            </div>
            
            <!-- Person/Company Information -->
            <div class="form-section form-two-columns">
                <!-- Pessoa Física Fields -->
                <div id="pessoa-fisica-fields" class="form-column" <?= $formData['tipo'] === 'Pessoa Jurídica' ? 'style="display: none;"' : '' ?>>
                    <div class="form-group">
                        <label for="nome_completo">Nome Completo ou Empresa:</label>
                        <input type="text" id="nome_completo" name="nome_completo" class="form-control" 
                              value="<?= htmlspecialchars($formData['nome_completo']) ?>" 
                              placeholder="João da Silva">
                    </div>
                    
                    <div class="form-group">
                        <label for="data_nascimento">Data Nascimento:</label>
                        <input type="date" id="data_nascimento" name="data_nascimento" class="form-control" 
                              value="<?= htmlspecialchars($formData['data_nascimento']) ?>"
                              placeholder="dd/mm/yyyy">
                    </div>
                    
                    <div class="form-group">
                        <label for="cpf">CPF:</label>
                        <input type="text" id="cpf" name="cpf" class="form-control cpf-mask" 
                              value="<?= htmlspecialchars($formData['cpf']) ?>"
                              placeholder="999.999.999-99">
                    </div>
                    
                    <div class="form-group">
                        <label for="profissao">Profissão:</label>
                        <input type="text" id="profissao" name="profissao" class="form-control" 
                              value="<?= htmlspecialchars($formData['profissao']) ?>"
                              placeholder="Profissão">
                    </div>
                </div>
                
                <!-- Pessoa Jurídica Fields -->
                <div id="pessoa-juridica-fields" class="form-column" <?= $formData['tipo'] === 'Pessoa Física' ? 'style="display: none;"' : '' ?>>
                    <div class="form-group">
                        <label for="razao_social">Razão Social:</label>
                        <input type="text" id="razao_social" name="razao_social" class="form-control" 
                              value="<?= htmlspecialchars($formData['razao_social']) ?>"
                              placeholder="Empresa de Teste LTDA.">
                    </div>
                    
                    <div class="form-group">
                        <label for="cnpj">CNPJ:</label>
                        <input type="text" id="cnpj" name="cnpj" class="form-control cnpj-mask" 
                              value="<?= htmlspecialchars($formData['cnpj']) ?>"
                              placeholder="99.999.999/9999-99">
                    </div>
                </div>
                
                <!-- Common Right Column Fields -->
                <div class="form-column">
                    <div class="form-group" id="rg-field" <?= $formData['tipo'] === 'Pessoa Jurídica' ? 'style="display: none;"' : '' ?>>
                        <label for="rg">RG:</label>
                        <input type="text" id="rg" name="rg" class="form-control" 
                              value="<?= htmlspecialchars($formData['rg']) ?>"
                              placeholder="00000000 00">
                    </div>
                    
                    <div class="form-group" id="cnpj-field" <?= $formData['tipo'] === 'Pessoa Física' ? 'style="display: none;"' : '' ?>>
                        <label>&nbsp;</label>
                        <div class="form-spacer"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefone1">Telefone:</label>
                        <input type="text" id="telefone1" name="telefone1" class="form-control telefone-mask" 
                              value="<?= htmlspecialchars($formData['telefone1']) ?>"
                              placeholder="(77) 0000-0000">
                    </div>
                    
                    <div class="form-group">
                        <label for="telefone2">Celular:</label>
                        <input type="text" id="telefone2" name="telefone2" class="form-control telefone-mask" 
                              value="<?= htmlspecialchars($formData['telefone2']) ?>"
                              placeholder="(77) 00000-0000">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-mail:</label>
                        <input type="email" id="email" name="email" class="form-control" 
                              value="<?= htmlspecialchars($formData['email']) ?>"
                              placeholder="contato@exemplo.com.br">
                    </div>
                </div>
            </div>
            
            <!-- Address Information -->
            <div class="form-section form-two-columns">
                <div class="form-column">
                    <div class="form-group">
                        <label for="endereco">Endereço:</label>
                        <input type="text" id="endereco" name="endereco" class="form-control" 
                              value="<?= htmlspecialchars($formData['endereco']) ?>"
                              placeholder="Ex.: Rua Bom Jesus da Lapa Qd. 00, Lt. 00, nº 00">
                    </div>
                </div>
                
                <div class="form-column address-selects">
                    <div class="form-group">
                        <label for="id_estado">Estado:</label>
                        <select id="id_estado" name="id_estado" class="form-control estado-select">
                            <option value="">Selecione</option>
                            <?php foreach ($estados as $estado): ?>
                                <option value="<?= $estado['id'] ?>" <?= $formData['id_estado'] == $estado['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($estado['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_cidade">Cidade:</label>
                        <select id="id_cidade" name="id_cidade" class="form-control cidade-select" <?= empty($formData['id_estado']) ? 'disabled' : '' ?>>
                            <option value="">Selecione</option>
                            <?php foreach ($cidades as $cidade): ?>
                                <option value="<?= $cidade['id'] ?>" <?= $formData['id_cidade'] == $cidade['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cidade['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_bairro">Bairro:</label>
                        <select id="id_bairro" name="id_bairro" class="form-control" <?= empty($formData['id_cidade']) ? 'disabled' : '' ?>>
                            <option value="">Selecione</option>
                            <?php foreach ($bairros as $bairro): ?>
                                <option value="<?= $bairro['id'] ?>" <?= $formData['id_bairro'] == $bairro['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($bairro['bairro']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Observations -->
            <div class="form-section">
                <div class="form-group">
                    <label for="observacoes">Observação:</label>
                    <textarea id="observacoes" name="observacoes" class="form-control" rows="5"
                           placeholder="Observações do Cliente"><?= htmlspecialchars($formData['observacoes']) ?></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin" class="cancel-button">Cancelar</a>
                <button type="submit" class="btn-cadastrar">Atualizar Cliente</button>
            </div>
        </div>
    </form>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle client type fields
    window.toggleClientType = function() {
        const pessoaFisicaRadio = document.getElementById('fisica');
        const pessoaFisicaFields = document.getElementById('pessoa-fisica-fields');
        const pessoaJuridicaFields = document.getElementById('pessoa-juridica-fields');
        const rgField = document.getElementById('rg-field');
        const cnpjField = document.getElementById('cnpj-field');
        
        if (pessoaFisicaRadio.checked) {
            pessoaFisicaFields.style.display = 'block';
            pessoaJuridicaFields.style.display = 'none';
            rgField.style.display = 'block';
            cnpjField.style.display = 'none';
        } else {
            pessoaFisicaFields.style.display = 'none';
            pessoaJuridicaFields.style.display = 'block';
            rgField.style.display = 'none';
            cnpjField.style.display = 'block';
        }
    };
    
    // Initialize estado, cidade and bairro selects
    const estadoSelect = document.querySelector('.estado-select');
    const cidadeSelect = document.querySelector('.cidade-select');
    const bairroSelect = document.getElementById('id_bairro');
    
    // When estado changes, fetch cidades
    if (estadoSelect) {
        estadoSelect.addEventListener('change', function() {
            const estadoId = this.value;
            cidadeSelect.disabled = !estadoId;
            cidadeSelect.innerHTML = '<option value="">Selecione</option>';
            bairroSelect.disabled = true;
            bairroSelect.innerHTML = '<option value="">Selecione</option>';
            
            if (estadoId) {
                fetch(`<?= BASE_URL ?>/admin/ajax/get_cidades.php?id_estado=${estadoId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.cidades && data.cidades.length > 0) {
                            data.cidades.forEach(cidade => {
                                const option = document.createElement('option');
                                option.value = cidade.id;
                                option.textContent = cidade.nome;
                                cidadeSelect.appendChild(option);
                            });
                        }
                        cidadeSelect.disabled = false;
                    })
                    .catch(error => console.error('Error fetching cidades:', error));
            }
        });
    }
    
    // When cidade changes, fetch bairros
    if (cidadeSelect) {
        cidadeSelect.addEventListener('change', function() {
            const cidadeId = this.value;
            bairroSelect.disabled = !cidadeId;
            bairroSelect.innerHTML = '<option value="">Selecione</option>';
            
            if (cidadeId) {
                fetch(`<?= BASE_URL ?>/admin/ajax/get_bairros.php?id_cidade=${cidadeId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.bairros && data.bairros.length > 0) {
                            data.bairros.forEach(bairro => {
                                const option = document.createElement('option');
                                option.value = bairro.id;
                                option.textContent = bairro.bairro;
                                bairroSelect.appendChild(option);
                            });
                        }
                        bairroSelect.disabled = false;
                    })
                    .catch(error => console.error('Error fetching bairros:', error));
            }
        });
    }

});
</script>