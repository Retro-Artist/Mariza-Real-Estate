<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID do imóvel não especificado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/imoveis');
    exit;
}

$property_id = (int)$_GET['id'];

// Initialize variables
$error = '';
$formData = [];

// Get property data
try {
    $stmt = $databaseConnection->prepare(
        "SELECT * FROM sistema_imoveis WHERE id = :id LIMIT 1"
    );
    $stmt->bindParam(':id', $property_id);
    $stmt->execute();
    
    $formData = $stmt->fetch();
    
    if (!$formData) {
        $_SESSION['alert_message'] = 'Imóvel não encontrado.';
        $_SESSION['alert_type'] = 'error';
        header('Location: ' . BASE_URL . '/admin/imoveis');
        exit;
    }
} catch (PDOException $e) {
    logError("Error fetching property data: " . $e->getMessage());
    $_SESSION['alert_message'] = 'Erro ao buscar dados do imóvel.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/imoveis');
    exit;
}

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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $titulo = trim($_POST['titulo'] ?? '');
    $para = trim($_POST['para'] ?? 'venda');
    $id_categoria = (int)($_POST['id_categoria'] ?? 0);
    $id_estado = (int)($_POST['id_estado'] ?? 0);
    $id_cidade = (int)($_POST['id_cidade'] ?? 0);
    $id_bairro = (int)($_POST['id_bairro'] ?? 0);
    $valor = str_replace(['R$', '.', ','], ['', '', '.'], trim($_POST['valor'] ?? '0'));
    $quartos = trim($_POST['quartos'] ?? '');
    $suites = trim($_POST['suites'] ?? '');
    $banheiros = trim($_POST['banheiros'] ?? '');
    $garagem = trim($_POST['garagem'] ?? '');
    $area_total = trim($_POST['area_total'] ?? '');
    $area_construida = trim($_POST['area_construida'] ?? '');
    $und_medida = trim($_POST['und_medida'] ?? 'm²');
    $endereco = trim($_POST['endereco'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $ref = trim($_POST['ref'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');
    $status = trim($_POST['status'] ?? 'ativo');
    $destaque = isset($_POST['destaque']) ? 1 : 0;
    
    // Validate form data
    if (empty($titulo)) {
        $error = 'O título do imóvel é obrigatório.';
    } elseif (empty($id_categoria)) {
        $error = 'Selecione uma categoria para o imóvel.';
    } elseif (empty($valor)) {
        $error = 'Informe o valor do imóvel.';
    } elseif (empty($codigo)) {
        $error = 'O código do imóvel é obrigatório.';
    } else {
        try {
            // Check if property code already exists (except for this property)
            $stmt = $databaseConnection->prepare(
                "SELECT id FROM sistema_imoveis WHERE codigo = :codigo AND id != :id LIMIT 1"
            );
            $stmt->bindParam(':codigo', $codigo);
            $stmt->bindParam(':id', $property_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = 'Um imóvel com este código já existe.';
            } else {
                // Update property
                $stmt = $databaseConnection->prepare(
                    "UPDATE sistema_imoveis SET 
                        titulo = :titulo, 
                        para = :para, 
                        id_categoria = :id_categoria, 
                        id_estado = :id_estado, 
                        id_cidade = :id_cidade, 
                        id_bairro = :id_bairro, 
                        valor = :valor, 
                        quartos = :quartos, 
                        suites = :suites, 
                        banheiros = :banheiros, 
                        garagem = :garagem, 
                        area_total = :area_total, 
                        area_construida = :area_construida, 
                        und_medida = :und_medida, 
                        endereco = :endereco, 
                        descricao = :descricao, 
                        ref = :ref, 
                        codigo = :codigo, 
                        status = :status,
                        destaque = :destaque
                     WHERE id = :id"
                );
                
                $stmt->bindParam(':titulo', $titulo);
                $stmt->bindParam(':para', $para);
                $stmt->bindParam(':id_categoria', $id_categoria);
                $stmt->bindParam(':id_estado', $id_estado);
                $stmt->bindParam(':id_cidade', $id_cidade);
                $stmt->bindParam(':id_bairro', $id_bairro);
                $stmt->bindParam(':valor', $valor);
                $stmt->bindParam(':quartos', $quartos);
                $stmt->bindParam(':suites', $suites);
                $stmt->bindParam(':banheiros', $banheiros);
                $stmt->bindParam(':garagem', $garagem);
                $stmt->bindParam(':area_total', $area_total);
                $stmt->bindParam(':area_construida', $area_construida);
                $stmt->bindParam(':und_medida', $und_medida);
                $stmt->bindParam(':endereco', $endereco);
                $stmt->bindParam(':descricao', $descricao);
                $stmt->bindParam(':ref', $ref);
                $stmt->bindParam(':codigo', $codigo);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':destaque', $destaque);
                $stmt->bindParam(':id', $property_id);
                
                $stmt->execute();
                
                // Prepare keywords for search
                $palavras_chaves = $titulo . ' ' . $descricao;
                
                // Update keywords separately
                $stmt = $databaseConnection->prepare(
                    "UPDATE sistema_imoveis SET palavras_chaves = :palavras_chaves WHERE id = :id"
                );
                $stmt->bindParam(':palavras_chaves', $palavras_chaves);
                $stmt->bindParam(':id', $property_id);
                $stmt->execute();
                
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
                            $fileName = $codigo . $imageNumber . '.jpg';
                            $targetFile = $uploadDir . $fileName;
                            
                            // Move the uploaded file
                            move_uploaded_file($tempFile, $targetFile);
                        }
                    }
                }
                
                // Set success message and redirect
                $_SESSION['alert_message'] = 'Imóvel atualizado com sucesso!';
                $_SESSION['alert_type'] = 'success';
                
                header('Location: ' . BASE_URL . '/admin/imoveis');
                exit;
            }
        } catch (PDOException $e) {
            logError("Error updating property: " . $e->getMessage());
            $error = 'Ocorreu um erro ao atualizar o imóvel. Por favor, tente novamente.';
        }
    }
    
    // Update formData with POST values for form re-population in case of error
    $formData['titulo'] = $titulo;
    $formData['para'] = $para;
    $formData['id_categoria'] = $id_categoria;
    $formData['id_estado'] = $id_estado;
    $formData['id_cidade'] = $id_cidade;
    $formData['id_bairro'] = $id_bairro;
    $formData['valor'] = $valor;
    $formData['quartos'] = $quartos;
    $formData['suites'] = $suites;
    $formData['banheiros'] = $banheiros;
    $formData['garagem'] = $garagem;
    $formData['area_total'] = $area_total;
    $formData['area_construida'] = $area_construida;
    $formData['und_medida'] = $und_medida;
    $formData['endereco'] = $endereco;
    $formData['descricao'] = $descricao;
    $formData['ref'] = $ref;
    $formData['codigo'] = $codigo;
    $formData['status'] = $status;
    $formData['destaque'] = $destaque;
}
?>

<!-- Update Property Page -->
<div class="admin-page property-update">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Editar Imóvel</h2>
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
            
            <!-- Current Images -->
            <div class="current-images">
                <h4>Imagens Atuais</h4>
                <div class="image-gallery">
                    <?php
                    $hasImages = false;
                    $uploadDir = BASE_URL . '/uploads/imoveis/';
                    for ($i = 1; $i <= 12; $i++) {
                        $imageNumber = str_pad($i, 2, '0', STR_PAD_LEFT); // 01, 02, etc.
                        $fileName = $formData['codigo'] . $imageNumber . '.jpg';
                        $filePath = __DIR__ . '/../../../uploads/imoveis/' . $fileName;
                        
                        if (file_exists($filePath)) {
                            $hasImages = true;
                            echo '<div class="image-preview">';
                            echo '<img src="' . $uploadDir . $fileName . '" alt="Imagem ' . $i . '">';
                            echo '<div class="image-number">Imagem ' . $i . '</div>';
                            echo '</div>';
                        }
                    }
                    
                    if (!$hasImages) {
                        echo '<p>Nenhuma imagem encontrada para este imóvel.</p>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--full">
                    <label for="imagens">Atualizar Imagens</label>
                    <input type="file" id="imagens" name="imagens[]" class="form-control-file" multiple accept="image/*">
                    <small class="form-text">
                        Selecione novas imagens para substituir as atuais (JPEG, PNG). As imagens serão renomeadas 
                        seguindo o padrão do código do imóvel (ex: CODIGO01.jpg, CODIGO02.jpg, etc).
                        A ordem de seleção determinará a numeração das imagens.
                    </small>
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
                <i class="fas fa-save"></i> Salvar Alterações
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

.current-images h4 {
    margin-top: 0;
    margin-bottom: 15px;
    font-family: var(--font-secondary);
    font-size: var(--font-md);
}

.image-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.image-preview {
    border: 1px solid var(--admin-border);
    border-radius: var(--border-radius);
    overflow: hidden;
    position: relative;
}

.image-preview img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    display: block;
}

.image-number {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 5px;
    font-size: var(--font-xs);
    text-align: center;
}

@media (max-width: 768px) {
    .form-group,
    .form-group--large {
        flex: 1 0 100%;
    }
    
    .image-gallery {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    }
    
    .image-preview img {
        height: 100px;
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
            if (value === '') {
                e.target.value = '';
                return;
            }
            value = (parseInt(value) / 100).toFixed(2);
            e.target.value = value.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        });
    }
});
</script>