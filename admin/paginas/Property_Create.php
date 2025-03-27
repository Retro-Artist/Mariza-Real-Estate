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
    'para' => 'venda',
    'id_categoria' => '',
    'id_estado' => '',
    'id_cidade' => '',
    'id_bairro' => '',
    'valor' => '',
    'quartos' => '',
    'suites' => '',
    'banheiros' => '',
    'garagem' => '',
    'area_total' => '',
    'area_construida' => '',
    'und_medida' => 'm²',
    'endereco' => '',
    'descricao' => '',
    'ref' => '',
    'codigo' => '',
    'status' => 'ativo',
    'destaque' => 0
];

// Get categories
try {
    $stmt = $databaseConnection->query("SELECT * FROM sistema_imoveis_categorias ORDER BY categoria ASC");
    $categorias = $stmt->fetchAll();
} catch (PDOException $e) {
    logError("Error fetching categories: " . $e->getMessage());
    $categorias = [];
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
    $formData = [
        'titulo' => trim($_POST['titulo'] ?? ''),
        'para' => trim($_POST['para'] ?? 'venda'),
        'id_categoria' => (int)($_POST['id_categoria'] ?? 0),
        'id_estado' => (int)($_POST['id_estado'] ?? 0),
        'id_cidade' => (int)($_POST['id_cidade'] ?? 0),
        'id_bairro' => (int)($_POST['id_bairro'] ?? 0),
        'valor' => str_replace(['R$', '.', ','], ['', '', '.'], trim($_POST['valor'] ?? '0')),
        'quartos' => trim($_POST['quartos'] ?? ''),
        'suites' => trim($_POST['suites'] ?? ''),
        'banheiros' => trim($_POST['banheiros'] ?? ''),
        'garagem' => trim($_POST['garagem'] ?? ''),
        'area_total' => trim($_POST['area_total'] ?? ''),
        'area_construida' => trim($_POST['area_construida'] ?? ''),
        'und_medida' => trim($_POST['und_medida'] ?? 'm²'),
        'endereco' => trim($_POST['endereco'] ?? ''),
        'descricao' => trim($_POST['descricao'] ?? ''),
        'ref' => trim($_POST['ref'] ?? ''),
        'codigo' => trim($_POST['codigo'] ?? ''),
        'status' => trim($_POST['status'] ?? 'ativo'),
        'destaque' => isset($_POST['destaque']) ? 1 : 0
    ];
    
    // Validate form data
    if (empty($formData['titulo'])) {
        $error = 'O título do imóvel é obrigatório.';
    } elseif (empty($formData['id_categoria'])) {
        $error = 'Selecione uma categoria para o imóvel.';
    } elseif (empty($formData['valor'])) {
        $error = 'Informe o valor do imóvel.';
    } elseif (empty($formData['codigo'])) {
        $error = 'O código do imóvel é obrigatório.';
    } else {
        try {
            // Check if property code already exists
            $stmt = $databaseConnection->prepare(
                "SELECT id FROM sistema_imoveis WHERE codigo = :codigo LIMIT 1"
            );
            $stmt->bindParam(':codigo', $formData['codigo']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = 'Um imóvel com este código já existe.';
            } else {
                // Get current date and time
                $data = date('Y-m-d');
                $hora = date('H:i:s');
                $id_usuario = $_SESSION['admin_id'];
                
                // Prepare keywords for search
                $palavras_chaves = $formData['titulo'] . ' ' . $formData['descricao'];
                
                // Insert new property
                $stmt = $databaseConnection->prepare(
                    "INSERT INTO sistema_imoveis (
                        titulo, para, id_categoria, id_estado, id_cidade, id_bairro, 
                        valor, quartos, suites, banheiros, garagem, area_total, 
                        area_construida, und_medida, endereco, descricao, ref, 
                        codigo, status, data, hora, id_usuario, palavras_chaves, destaque
                    ) VALUES (
                        :titulo, :para, :id_categoria, :id_estado, :id_cidade, :id_bairro, 
                        :valor, :quartos, :suites, :banheiros, :garagem, :area_total, 
                        :area_construida, :und_medida, :endereco, :descricao, :ref, 
                        :codigo, :status, :data, :hora, :id_usuario, :palavras_chaves, :destaque
                    )"
                );
                
                $stmt->bindParam(':titulo', $formData['titulo']);
                $stmt->bindParam(':para', $formData['para']);
                $stmt->bindParam(':id_categoria', $formData['id_categoria']);
                $stmt->bindParam(':id_estado', $formData['id_estado']);
                $stmt->bindParam(':id_cidade', $formData['id_cidade']);
                $stmt->bindParam(':id_bairro', $formData['id_bairro']);
                $stmt->bindParam(':valor', $formData['valor']);
                $stmt->bindParam(':quartos', $formData['quartos']);
                $stmt->bindParam(':suites', $formData['suites']);
                $stmt->bindParam(':banheiros', $formData['banheiros']);
                $stmt->bindParam(':garagem', $formData['garagem']);
                $stmt->bindParam(':area_total', $formData['area_total']);
                $stmt->bindParam(':area_construida', $formData['area_construida']);
                $stmt->bindParam(':und_medida', $formData['und_medida']);
                $stmt->bindParam(':endereco', $formData['endereco']);
                $stmt->bindParam(':descricao', $formData['descricao']);
                $stmt->bindParam(':ref', $formData['ref']);
                $stmt->bindParam(':codigo', $formData['codigo']);
                $stmt->bindParam(':status', $formData['status']);
                $stmt->bindParam(':data', $data);
                $stmt->bindParam(':hora', $hora);
                $stmt->bindParam(':id_usuario', $id_usuario);
                $stmt->bindParam(':palavras_chaves', $palavras_chaves);
                $stmt->bindParam(':destaque', $formData['destaque']);
                
                $stmt->execute();
                $newPropertyId = $databaseConnection->lastInsertId();
                
                // Handle image uploads
                if (isset($_FILES['imagens']) && !empty($_FILES['imagens']['name'][0])) {
                    $uploadDir = __DIR__ . '/../../../uploads/imoveis/';
                    
                    // Create directory if it doesn't exist
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Process each uploaded image
                    $totalFiles = count($_FILES['imagens']['name']);
                    for ($i = 0; $i < $totalFiles; $i++) {
                        if ($_FILES['imagens']['error'][$i] === UPLOAD_ERR_OK) {
                            $tempFile = $_FILES['imagens']['tmp_name'][$i];
                            $imageNumber = str_pad($i + 1, 2, '0', STR_PAD_LEFT); // 01, 02, etc.
                            $fileName = $formData['codigo'] . $imageNumber . '.jpg';
                            $targetFile = $uploadDir . $fileName;
                            
                            // Move the uploaded file
                            move_uploaded_file($tempFile, $targetFile);
                        }
                    }
                }
                
                // Set success message and redirect
                $_SESSION['alert_message'] = 'Imóvel adicionado com sucesso!';
                $_SESSION['alert_type'] = 'success';
                
                header('Location: ' . BASE_URL . '/admin/imoveis');
                exit;
            }
        } catch (PDOException $e) {
            logError("Error creating property: " . $e->getMessage());
            $error = 'Ocorreu um erro ao adicionar o imóvel. Por favor, tente novamente.';
        }
    }
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

<!-- Add Property Page -->
<div class="admin-page property-create">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Adicionar Novo Imóvel</h2>
        <a href="<?= BASE_URL ?>/admin/imoveis" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <!-- Property Form -->
    <form method="POST" action="" class="admin-form" enctype="multipart/form-data">
        <?php if (!empty($error)): ?>
            <div class="alert-message alert-message--error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <!-- Basic Information -->
        <div class="form-section">
            <h3 class="form-section__title">Informações Básicas</h3>
            
            <div class="form-row">
                <div class="form-group form-group--large">
                    <label for="titulo">Título do Imóvel <span class="required">*</span></label>
                    <input type="text" id="titulo" name="titulo" class="form-control" 
                           value="<?= htmlspecialchars($formData['titulo']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="codigo">Código <span class="required">*</span></label>
                    <input type="text" id="codigo" name="codigo" class="form-control" 
                           value="<?= htmlspecialchars($formData['codigo']) ?>" required>
                    <small class="form-text">Código único para identificação do imóvel.</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="para">Tipo de Operação <span class="required">*</span></label>
                    <select id="para" name="para" class="form-control" required>
                        <option value="venda" <?= $formData['para'] === 'venda' ? 'selected' : '' ?>>Venda</option>
                        <option value="aluguel" <?= $formData['para'] === 'aluguel' ? 'selected' : '' ?>>Aluguel</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="id_categoria">Categoria <span class="required">*</span></label>
                    <select id="id_categoria" name="id_categoria" class="form-control" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria['id'] ?>" <?= $formData['id_categoria'] == $categoria['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($categoria['categoria']) ?> (<?= htmlspecialchars($categoria['tipo']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="valor">Valor <span class="required">*</span></label>
                    <input type="text" id="valor" name="valor" class="form-control money-mask" 
                           value="<?= formatCurrency($formData['valor']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="ref">Referência</label>
                    <input type="text" id="ref" name="ref" class="form-control" 
                           value="<?= htmlspecialchars($formData['ref']) ?>">
                    <small class="form-text">Código de referência (opcional).</small>
                </div>
            </div>
        </div>
        
        <!-- Location Information -->
        <div class="form-section">
            <h3 class="form-section__title">Localização</h3>
            
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
            
            <div class="form-row">
                <div class="form-group form-group--large">
                    <label for="endereco">Endereço</label>
                    <input type="text" id="endereco" name="endereco" class="form-control" 
                           value="<?= htmlspecialchars($formData['endereco']) ?>">
                </div>
            </div>
        </div>
        
        <!-- Features -->
        <div class="form-section">
            <h3 class="form-section__title">Características</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="quartos">Quartos</label>
                    <select id="quartos" name="quartos" class="form-control">
                        <option value="">Selecione...</option>
                        <option value="1" <?= $formData['quartos'] === '1' ? 'selected' : '' ?>>1</option>
                        <option value="2" <?= $formData['quartos'] === '2' ? 'selected' : '' ?>>2</option>
                        <option value="3" <?= $formData['quartos'] === '3' ? 'selected' : '' ?>>3</option>
                        <option value="4" <?= $formData['quartos'] === '4' ? 'selected' : '' ?>>4</option>
                        <option value="5+" <?= $formData['quartos'] === '5+' ? 'selected' : '' ?>>5+</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="suites">Suítes</label>
                    <select id="suites" name="suites" class="form-control">
                        <option value="">Selecione...</option>
                        <option value="1" <?= $formData['suites'] === '1' ? 'selected' : '' ?>>1</option>
                        <option value="2" <?= $formData['suites'] === '2' ? 'selected' : '' ?>>2</option>
                        <option value="3" <?= $formData['suites'] === '3' ? 'selected' : '' ?>>3</option>
                        <option value="4" <?= $formData['suites'] === '4' ? 'selected' : '' ?>>4</option>
                        <option value="5+" <?= $formData['suites'] === '5+' ? 'selected' : '' ?>>5+</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="banheiros">Banheiros</label>
                    <select id="banheiros" name="banheiros" class="form-control">
                        <option value="">Selecione...</option>
                        <option value="1" <?= $formData['banheiros'] === '1' ? 'selected' : '' ?>>1</option>
                        <option value="2" <?= $formData['banheiros'] === '2' ? 'selected' : '' ?>>2</option>
                        <option value="3" <?= $formData['banheiros'] === '3' ? 'selected' : '' ?>>3</option>
                        <option value="4" <?= $formData['banheiros'] === '4' ? 'selected' : '' ?>>4</option>
                        <option value="5+" <?= $formData['banheiros'] === '5+' ? 'selected' : '' ?>>5+</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="garagem">Vagas de Garagem</label>
                    <select id="garagem" name="garagem" class="form-control">
                        <option value="">Selecione...</option>
                        <option value="1" <?= $formData['garagem'] === '1' ? 'selected' : '' ?>>1</option>
                        <option value="2" <?= $formData['garagem'] === '2' ? 'selected' : '' ?>>2</option>
                        <option value="3" <?= $formData['garagem'] === '3' ? 'selected' : '' ?>>3</option>
                        <option value="4" <?= $formData['garagem'] === '4' ? 'selected' : '' ?>>4</option>
                        <option value="5+" <?= $formData['garagem'] === '5+' ? 'selected' : '' ?>>5+</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="area_total">Área Total</label>
                    <input type="text" id="area_total" name="area_total" class="form-control" 
                           value="<?= htmlspecialchars($formData['area_total']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="area_construida">Área Construída</label>
                    <input type="text" id="area_construida" name="area_construida" class="form-control" 
                           value="<?= htmlspecialchars($formData['area_construida']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="und_medida">Unidade de Medida</label>
                    <select id="und_medida" name="und_medida" class="form-control">
                        <option value="m²" <?= $formData['und_medida'] === 'm²' ? 'selected' : '' ?>>m²</option>
                        <option value="ha" <?= $formData['und_medida'] === 'ha' ? 'selected' : '' ?>>hectares</option>
                        <option value="km²" <?= $formData['und_medida'] === 'km²' ? 'selected' : '' ?>>km²</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Description -->
        <div class="form-section">
            <h3 class="form-section__title">Descrição</h3>
            
            <div class="form-row">
                <div class="form-group form-group--full">
                    <label for="descricao">Descrição Detalhada</label>
                    <textarea id="descricao" name="descricao" class="form-control" rows="6"><?= htmlspecialchars($formData['descricao']) ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Images -->
        <div class="form-section">
            <h3 class="form-section__title">Imagens</h3>
            
            <div class="form-row">
                <div class="form-group form-group--full">
                    <label for="imagens">Selecione as Imagens</label>
                    <input type="file" id="imagens" name="imagens[]" class="form-control-file" multiple accept="image/*">
                    <small class="form-text">Selecione uma ou mais imagens (JPEG, PNG). A primeira imagem será a principal. As imagens devem ter no máximo 5MB.</small>
                </div>
            </div>
        </div>
        
        <!-- Status and Options -->
        <div class="form-section">
            <h3 class="form-section__title">Status e Opções</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="ativo" <?= $formData['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inativo" <?= $formData['status'] === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
                
                <div class="form-group form-group--checkbox">
                    <div class="checkbox-container">
                        <input type="checkbox" id="destaque" name="destaque" value="1" 
                               <?= $formData['destaque'] ? 'checked' : '' ?>>
                        <label for="destaque">Destaque na Página Principal</label>
                    </div>
                    <small class="form-text">Marque esta opção para mostrar o imóvel na seção de destaques da página inicial.</small>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="<?= BASE_URL ?>/admin/imoveis" class="cancel-button">Cancelar</a>
            <button type="submit" class="primary-button">
                <i class="fas fa-save"></i> Salvar Imóvel
            </button>
        </div>
    </form>
</div>

<style>
/* Additional styles for property form */
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

.form-control-file {
    padding: 8px 0;
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
    // Initialize estado, cidade and bairro selects
    const estadoSelect = document.querySelector('.estado-select');
    const cidadeSelect = document.querySelector('.cidade-select');
    const bairroSelect = document.querySelector('#id_bairro');
    
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
                    })
                    .catch(error => console.error('Error fetching bairros:', error));
            }
        });
    }
    
    // Money mask for valor input
    const valorInput = document.getElementById('valor');
    if (valorInput) {
        valorInput.addEventListener('input', function(e) {
            let value = e.target.value;
            value = value.replace(/\D/g, '');
            value = (parseInt(value) / 100).toFixed(2);
            e.target.value = value.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        });
    }
});
</script>