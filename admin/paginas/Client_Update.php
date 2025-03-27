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
    header('Location: ' . BASE_URL . '/admin/Client_Admin.php');
    exit;
}

$client_id = (int)$_GET['id'];

// Initialize variables
$error = '';
$formData = [];

// Get client data
try {
    $stmt = $databaseConnection->prepare(
        "SELECT * FROM sistema_clientes WHERE id = :id LIMIT 1"
    );
    $stmt->bindParam(':id', $client_id);
    $stmt->execute();
    
    $formData = $stmt->fetch();
    
    if (!$formData) {
        $_SESSION['alert_message'] = 'Cliente não encontrado.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/Client_Admin.php');
        exit;
    }
} catch (PDOException $e) {
    logError("Error fetching client data: " . $e->getMessage());
    $_SESSION['alert_message'] = 'Erro ao buscar dados do cliente.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/Client_Admin.php');
    exit;
}

// Get states
try {
    $stmt = $databaseConnection->query("SELECT * FROM sistema_estados ORDER BY nome ASC");
    $estados = $stmt->fetchAll();
} catch (PDOException $e) {
    logError("Error fetching states: " . $e->getMessage());
    $estados = [];
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
        try {
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
            
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':nome_completo', $nome_completo);
            $stmt->bindParam(':razao_social', $razao_social);
            $stmt->bindParam(':cpf', $cpf);
            $stmt->bindParam(':cnpj', $cnpj);
            $stmt->bindParam(':rg', $rg);
            $stmt->bindParam(':data_nascimento', $data_nascimento ? $data_nascimento : null);
            $stmt->bindParam(':profissao', $profissao);
            $stmt->bindParam(':telefone1', $telefone1);
            $stmt->bindParam(':telefone2', $telefone2);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':endereco', $endereco);
            $stmt->bindParam(':id_estado', $id_estado ? $id_estado : null, PDO::PARAM_INT);
            $stmt->bindParam(':id_cidade', $id_cidade ? $id_cidade : null, PDO::PARAM_INT);
            $stmt->bindParam(':id_bairro', $id_bairro ? $id_bairro : null, PDO::PARAM_INT);
            $stmt->bindParam(':observacoes', $observacoes);
            $stmt->bindParam(':categoria', $categoria);
            $stmt->bindParam(':principal', $principal);
            $stmt->bindParam(':id', $client_id);
            
            $stmt->execute();
            
            // Set success message and redirect
            $_SESSION['alert_message'] = 'Cliente atualizado com sucesso!';
            $_SESSION['alert_type'] = 'success';
            
            header('Location: ' . BASE_URL . '/admin/Client_Admin.php');
            exit;
        } catch (PDOException $e) {
            logError("Error updating client: " . $e->getMessage());
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

// Get cities based on selected state
$cidades = [];
if (!empty($formData['id_estado'])) {
    try {
        $stmt = $databaseConnection->prepare("SELECT * FROM sistema_cidades WHERE id_estado = :id_estado ORDER BY nome ASC");
        $stmt->bindParam(':id_estado', $formData['id_estado']);
        $stmt->execute();
        $cidades = $stmt->fetchAll();
    } catch (PDOException $e) {
        logError("Error fetching cities: " . $e->getMessage());
    }
}

// Get neighborhoods based on selected city
$bairros = [];
if (!empty($formData['id_cidade'])) {
    try {
        $stmt = $databaseConnection->prepare("SELECT * FROM sistema_bairros WHERE id_cidade = :id_cidade ORDER BY bairro ASC");
        $stmt->bindParam(':id_cidade', $formData['id_cidade']);
        $stmt->execute();
        $bairros = $stmt->fetchAll();
    } catch (PDOException $e) {
        logError("Error fetching neighborhoods: " . $e->getMessage());
    }
}
?>

<!-- Update Client Page -->
<div class="admin-page client-update">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Editar Cliente</h2>
        <a href="<?= BASE_URL ?>/admin/Client_Admin.php" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- Client Form -->
    <form method="POST" action="<?= BASE_URL ?>/admin/index.php?page=Client_Update&id=<?= $client_id ?>" class="admin-form">
        <?php if (!empty($error)): ?>
            <div class="alert-message alert-message--error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <!-- Client Type Selection -->
        <div class="form-section">
            <h3 class="form-section__title">Tipo de Cliente</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="tipo">Tipo de Cliente <span class="required">*</span></label>
                    <select id="tipo" name="tipo" class="form-control" required onchange="toggleClientType()">
                        <option value="Pessoa Física" <?= $formData['tipo'] === 'Pessoa Física' ? 'selected' : '' ?>>Pessoa Física</option>
                        <option value="Pessoa Jurídica" <?= $formData['tipo'] === 'Pessoa Jurídica' ? 'selected' : '' ?>>Pessoa Jurídica</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="categoria">Categoria</label>
                    <input type="text" id="categoria" name="categoria" class="form-control" 
                           value="<?= htmlspecialchars($formData['categoria']) ?>" list="categorias">
                    <datalist id="categorias">
                        <option value="Proprietário">
                        <option value="Locador">
                        <option value="Locatário">
                        <option value="Comprador">
                        <option value="Vendedor">
                        <option value="Fiador">
                    </datalist>
                </div>
                
                <div class="form-group form-group--checkbox">
                    <div class="checkbox-container">
                        <input type="checkbox" id="principal" name="principal" value="1" 
                               <?= $formData['principal'] === 'Sim' ? 'checked' : '' ?>>
                        <label for="principal">Cliente Principal</label>
                    </div>
                    <small class="form-text">Marque esta opção para destacar este cliente como principal.</small>
                </div>
            </div>
        </div>
        
        <!-- Basic Information -->
        <div class="form-section">
            <h3 class="form-section__title">Informações Básicas</h3>
            
            <!-- Pessoa Física Fields -->
            <div id="pessoa-fisica-fields" style="<?= $formData['tipo'] === 'Pessoa Jurídica' ? 'display: none;' : '' ?>">
                <div class="form-row">
                    <div class="form-group form-group--large">
                        <label for="nome_completo">Nome Completo <span class="required">*</span></label>
                        <input type="text" id="nome_completo" name="nome_completo" class="form-control" 
                               value="<?= htmlspecialchars($formData['nome_completo']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="cpf">CPF <span class="required">*</span></label>
                        <input type="text" id="cpf" name="cpf" class="form-control cpf-mask" 
                               value="<?= htmlspecialchars($formData['cpf']) ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="rg">RG</label>
                        <input type="text" id="rg" name="rg" class="form-control" 
                               value="<?= htmlspecialchars($formData['rg']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="data_nascimento">Data de Nascimento</label>
                        <input type="date" id="data_nascimento" name="data_nascimento" class="form-control" 
                               value="<?= htmlspecialchars($formData['data_nascimento']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="profissao">Profissão</label>
                        <input type="text" id="profissao" name="profissao" class="form-control" 
                               value="<?= htmlspecialchars($formData['profissao']) ?>">
                    </div>
                </div>
            </div>
            
            <!-- Pessoa Jurídica Fields -->
            <div id="pessoa-juridica-fields" style="<?= $formData['tipo'] === 'Pessoa Física' ? 'display: none;' : '' ?>">
                <div class="form-row">
                    <div class="form-group form-group--large">
                        <label for="razao_social">Razão Social <span class="required">*</span></label>
                        <input type="text" id="razao_social" name="razao_social" class="form-control" 
                               value="<?= htmlspecialchars($formData['razao_social']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="cnpj">CNPJ <span class="required">*</span></label>
                        <input type="text" id="cnpj" name="cnpj" class="form-control cnpj-mask" 
                               value="<?= htmlspecialchars($formData['cnpj']) ?>">
                    </div>
                </div>
            </div>
            
            <!-- Common Fields -->
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($formData['email']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="telefone1">Telefone Principal <span class="required">*</span></label>
                    <input type="text" id="telefone1" name="telefone1" class="form-control telefone-mask" 
                           value="<?= htmlspecialchars($formData['telefone1']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="telefone2">Telefone Secundário</label>
                    <input type="text" id="telefone2" name="telefone2" class="form-control telefone-mask" 
                           value="<?= htmlspecialchars($formData['telefone2']) ?>">
                </div>
            </div>
        </div>
        
        <!-- Location Information -->
        <div class="form-section">
            <h3 class="form-section__title">Endereço</h3>
            
            <div class="form-row">
                <div class="form-group form-group--large">
                    <label for="endereco">Endereço Completo</label>
                    <input type="text" id="endereco" name="endereco" class="form-control" 
                           value="<?= htmlspecialchars($formData['endereco']) ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="id_estado">Estado</label>
                    <select id="id_estado" name="id_estado" class="form-control estado-select">
                        <option value="">Selecione...</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?= $estado['id'] ?>" <?= $formData['id_estado'] == $estado['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($estado['nome']) ?> (<?= htmlspecialchars($estado['uf']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="id_cidade">Cidade</label>
                    <select id="id_cidade" name="id_cidade" class="form-control cidade-select" <?= empty($formData['id_estado']) ? 'disabled' : '' ?>>
                        <option value="">Selecione...</option>
                        <?php foreach ($cidades as $cidade): ?>
                            <option value="<?= $cidade['id'] ?>" <?= $formData['id_cidade'] == $cidade['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cidade['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="id_bairro">Bairro</label>
                    <select id="id_bairro" name="id_bairro" class="form-control" <?= empty($formData['id_cidade']) ? 'disabled' : '' ?>>
                        <option value="">Selecione...</option>
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
            <h3 class="form-section__title">Observações</h3>
            
            <div class="form-row">
                <div class="form-group form-group--full">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" class="form-control" rows="5"><?= htmlspecialchars($formData['observacoes']) ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="<?= BASE_URL ?>/admin/Client_Admin.php" class="cancel-button">Cancelar</a>
            <button type="submit" class="primary-button">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
        </div>
    </form>
</div>

<style>
/* Additional styles for client form */
.form-section {
    background-color: var(--admin-card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 20px;
    margin-bottom: 20px;
}

.form-section__title {
    font-size: var(--font-lg);
    margin-top: 0;
    margin-bottom: 20px;
    font-family: var(--font-secondary);
}

.form-group--large {
    flex: 2;
}

.form-group--full {
    flex: 1 0 100%;
}

.form-group--checkbox {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.checkbox-container {
    display: flex;
    align-items: center;
    gap: 8px;
}

.checkbox-container input[type="checkbox"] {
    width: auto;
}

.checkbox-container label {
    margin-bottom: 0;
    cursor: pointer;
}

.required {
    color: var(--admin-red);
}

.form-text {
    font-size: var(--font-xs);
    color: var(--admin-text);
    opacity: 0.7;
    margin-top: 5px;
    display: block;
}

@media (max-width: 768px) {
    .form-group,
    .form-group--large {
        flex: 1 0 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle client type fields
    window.toggleClientType = function() {
        const tipoSelect = document.getElementById('tipo');
        const pessoaFisicaFields = document.getElementById('pessoa-fisica-fields');
        const pessoaJuridicaFields = document.getElementById('pessoa-juridica-fields');
        
        if (tipoSelect.value === 'Pessoa Física') {
            pessoaFisicaFields.style.display = 'block';
            pessoaJuridicaFields.style.display = 'none';
        } else {
            pessoaFisicaFields.style.display = 'none';
            pessoaJuridicaFields.style.display = 'block';
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
            cidadeSelect.innerHTML = '<option value="">Selecione...</option>';
            bairroSelect.disabled = true;
            bairroSelect.innerHTML = '<option value="">Selecione...</option>';
            
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
            bairroSelect.innerHTML = '<option value="">Selecione...</option>';
            
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