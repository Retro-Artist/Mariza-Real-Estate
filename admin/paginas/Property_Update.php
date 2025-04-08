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
    header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
    exit;
}

$property_id = (int)$_GET['id'];

// Initialize variables
$error = '';

// Get property data using function from admin_functions.php
$formData = getAdminPropertyById($property_id);

if (!$formData) {
    $_SESSION['alert_message'] = 'Imóvel não encontrado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
    exit;
}

// Get categories
$categorias = getAdminCategories();

// Get states
$estados = getStates();

// Get available corretores (real estate agents)
try {
    $stmt = $databaseConnection->query("SELECT id, nome FROM sistema_usuarios WHERE nivel = 'Corretor' OR nivel = 'Administrador' ORDER BY nome ASC");
    $corretores = $stmt->fetchAll();
} catch (PDOException $e) {
    logError("Error fetching corretores: " . $e->getMessage());
    $corretores = [];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $postData = [
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
        'salas' => trim($_POST['salas'] ?? ''),
        'cozinhas' => trim($_POST['cozinhas'] ?? ''),
        'garagem' => trim($_POST['garagem'] ?? ''),
        'area_servico' => trim($_POST['area_servico'] ?? ''),
        'area_total' => trim($_POST['area_total'] ?? ''),
        'area_construida' => trim($_POST['area_construida'] ?? ''),
        'und_medida' => trim($_POST['und_medida'] ?? 'm²'),
        'endereco' => trim($_POST['endereco'] ?? ''),
        'descricao' => trim($_POST['descricao'] ?? ''),
        'ref' => trim($_POST['ref'] ?? ''),
        'codigo' => trim($_POST['codigo'] ?? ''),
        'status' => trim($_POST['status'] ?? 'ativo'),
        'destaque' => isset($_POST['destaque']) ? 1 : 0,
        'classificados' => trim($_POST['classificados'] ?? ''),
        'quadra_lote' => trim($_POST['quadra_lote'] ?? ''),
        'medida_frente' => trim($_POST['medida_frente'] ?? ''),
        'medida_fundo' => trim($_POST['medida_fundo'] ?? ''),
        'medida_laterais' => trim($_POST['medida_laterais'] ?? ''),
        'corretor_responsavel' => (int)($_POST['corretor_responsavel'] ?? 0),
        'nome_anunciante' => trim($_POST['nome_anunciante'] ?? ''),
        'telefone_anunciante' => trim($_POST['telefone_anunciante'] ?? ''),
        'palavras_chaves' => trim($_POST['palavras_chaves'] ?? '')
    ];
    
    // Validate form data
    if (empty($postData['titulo'])) {
        $error = 'O título do imóvel é obrigatório.';
    } elseif (empty($postData['id_categoria'])) {
        $error = 'Selecione uma categoria para o imóvel.';
    } elseif (empty($postData['valor'])) {
        $error = 'Informe o valor do imóvel.';
    } elseif (empty($postData['codigo'])) {
        $error = 'O código do imóvel é obrigatório.';
    } else {
        // Update property using function from admin_functions.php
        $result = updateProperty($property_id, $postData);
        
        if ($result) {
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
                        $fileName = $postData['codigo'] . $imageNumber . '.jpg';
                        $targetFile = $uploadDir . $fileName;
                        
                        // Move the uploaded file
                        move_uploaded_file($tempFile, $targetFile);
                    }
                }
            }
            
            // Set success message and redirect
            $_SESSION['alert_message'] = 'Imóvel atualizado com sucesso!';
            $_SESSION['alert_type'] = 'success';
            
            header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
            exit;
        } else {
            $error = 'Um imóvel com este código já existe.';
        }
    }
    
    // Update formData with POST values for form re-population in case of error
    $formData = array_merge($formData, $postData);
}

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
?>

<!-- Update Property Page -->
<div class="admin-page property-update">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Editar Imóvel</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Admin" class="cancel-button">
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
                <div class="form-group form-group--full">
                    <label for="titulo">Título do Imóvel <span class="required">*</span></label>
                    <input type="text" id="titulo" name="titulo" class="form-control" 
                           value="<?= htmlspecialchars($formData['titulo']) ?>" required
                           placeholder="Ex: Casa a venda no bairro jardim paraíso">
                </div>
                
                <!-- Campo para código do imóvel -->
                <input type="hidden" id="codigo" name="codigo" value="<?= htmlspecialchars($formData['codigo']) ?>">
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
                    <label for="classificados">Classificados</label>
                    <select id="classificados" name="classificados" class="form-control">
                        <option value="">Selecione...</option>
                        <option value="Sim" <?= $formData['classificados'] === 'Sim' ? 'selected' : '' ?>>Sim</option>
                        <option value="Não" <?= $formData['classificados'] === 'Não' ? 'selected' : '' ?>>Não</option>
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
                
                <input type="hidden" id="ref" name="ref" value="<?= htmlspecialchars($formData['ref']) ?>">
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
                           value="<?= htmlspecialchars($formData['endereco']) ?>"
                           placeholder="Informe o endereço (Ex.: Rua Jorge Amado, n 354, Luis Eduardo Magalhães)">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="quadra_lote">Quadra e Lote</label>
                    <input type="text" id="quadra_lote" name="quadra_lote" class="form-control" 
                           value="<?= htmlspecialchars($formData['quadra_lote']) ?>"
                           placeholder="Q. 00, Lt. 00">
                </div>
                
                <div class="form-group">
                    <label for="medida_frente">Medida da Frente</label>
                    <input type="text" id="medida_frente" name="medida_frente" class="form-control" 
                           value="<?= htmlspecialchars($formData['medida_frente']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="medida_fundo">Medida do Fundo</label>
                    <input type="text" id="medida_fundo" name="medida_fundo" class="form-control" 
                           value="<?= htmlspecialchars($formData['medida_fundo']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="medida_laterais">Medidas Laterais</label>
                    <input type="text" id="medida_laterais" name="medida_laterais" class="form-control" 
                           value="<?= htmlspecialchars($formData['medida_laterais']) ?>">
                </div>
            </div>
        </div>
        
        <!-- Features -->
        <div class="form-section">
            <h3 class="form-section__title">Características</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="quartos">Quartos/Dormitórios</label>
                    <select id="quartos" name="quartos" class="form-control">
                        <option value="">Nenhum</option>
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
                        <option value="">Nenhum</option>
                        <option value="1" <?= $formData['suites'] === '1' ? 'selected' : '' ?>>1</option>
                        <option value="2" <?= $formData['suites'] === '2' ? 'selected' : '' ?>>2</option>
                        <option value="3" <?= $formData['suites'] === '3' ? 'selected' : '' ?>>3</option>
                        <option value="4" <?= $formData['suites'] === '4' ? 'selected' : '' ?>>4</option>
                        <option value="5+" <?= $formData['suites'] === '5+' ? 'selected' : '' ?>>5+</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="cozinhas">Cozinhas</label>
                    <select id="cozinhas" name="cozinhas" class="form-control">
                        <option value="">Nenhum</option>
                        <option value="1" <?= $formData['cozinhas'] === '1' ? 'selected' : '' ?>>1</option>
                        <option value="2" <?= $formData['cozinhas'] === '2' ? 'selected' : '' ?>>2</option>
                        <option value="3" <?= $formData['cozinhas'] === '3' ? 'selected' : '' ?>>3</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="salas">Salas</label>
                    <select id="salas" name="salas" class="form-control">
                        <option value="">Nenhum</option>
                        <option value="1" <?= $formData['salas'] === '1' ? 'selected' : '' ?>>1</option>
                        <option value="2" <?= $formData['salas'] === '2' ? 'selected' : '' ?>>2</option>
                        <option value="3" <?= $formData['salas'] === '3' ? 'selected' : '' ?>>3</option>
                        <option value="4" <?= $formData['salas'] === '4' ? 'selected' : '' ?>>4</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="banheiros">Banheiros</label>
                    <select id="banheiros" name="banheiros" class="form-control">
                        <option value="">Nenhum</option>
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
                        <option value="">Nenhum</option>
                        <option value="1" <?= $formData['garagem'] === '1' ? 'selected' : '' ?>>1</option>
                        <option value="2" <?= $formData['garagem'] === '2' ? 'selected' : '' ?>>2</option>
                        <option value="3" <?= $formData['garagem'] === '3' ? 'selected' : '' ?>>3</option>
                        <option value="4" <?= $formData['garagem'] === '4' ? 'selected' : '' ?>>4</option>
                        <option value="5+" <?= $formData['garagem'] === '5+' ? 'selected' : '' ?>>5+</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="area_servico">Área de Serviço</label>
                    <select id="area_servico" name="area_servico" class="form-control">
                        <option value="">Nenhum</option>
                        <option value="Sim" <?= $formData['area_servico'] === 'Sim' ? 'selected' : '' ?>>Sim</option>
                        <option value="Não" <?= $formData['area_servico'] === 'Não' ? 'selected' : '' ?>>Não</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="und_medida">Unidade de Medida</label>
                    <select id="und_medida" name="und_medida" class="form-control">
                        <option value="m²" <?= $formData['und_medida'] === 'm²' ? 'selected' : '' ?>>m²</option>
                        <option value="ha" <?= $formData['und_medida'] === 'ha' ? 'selected' : '' ?>>hectares</option>
                        <option value="km²" <?= $formData['und_medida'] === 'km²' ? 'selected' : '' ?>>km²</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="area_total">Área Total</label>
                    <input type="text" id="area_total" name="area_total" class="form-control" 
                           value="<?= htmlspecialchars($formData['area_total']) ?>"
                           placeholder="Somente Números">
                </div>
                
                <div class="form-group">
                    <label for="area_construida">Área Construída</label>
                    <input type="text" id="area_construida" name="area_construida" class="form-control" 
                           value="<?= htmlspecialchars($formData['area_construida']) ?>"
                           placeholder="Somente Números">
                </div>
            </div>
        </div>
        
        <!-- Additional Information -->
        <div class="form-section">
            <h3 class="form-section__title">Informações Adicionais</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="corretor_responsavel">Corretor Responsável</label>
                    <select id="corretor_responsavel" name="corretor_responsavel" class="form-control">
                        <option value="">Selecione...</option>
                        <?php foreach ($corretores as $corretor): ?>
                            <option value="<?= $corretor['id'] ?>" <?= $formData['corretor_responsavel'] == $corretor['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($corretor['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="nome_anunciante">Proprietário do Imóvel</label>
                    <input type="text" id="nome_anunciante" name="nome_anunciante" class="form-control" 
                           value="<?= htmlspecialchars($formData['nome_anunciante']) ?>"
                           placeholder="Nome do Proprietário">
                </div>
                
                <div class="form-group">
                    <label for="telefone_anunciante">Telefones do Proprietário</label>
                    <input type="text" id="telefone_anunciante" name="telefone_anunciante" class="form-control" 
                           value="<?= htmlspecialchars($formData['telefone_anunciante']) ?>"
                           placeholder="Digite os Telefones do Proprietário">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="palavras_chaves">Palavras-chave</label>
                    <input type="text" id="palavras_chaves" name="palavras_chaves" class="form-control" 
                           value="<?= htmlspecialchars($formData['palavras_chaves']) ?>">
                    <small class="form-text">Palavras-chave separadas por vírgula para melhorar a busca (Opcional).</small>
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
            <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Admin" class="cancel-button">Cancelar</a>
            <button type="submit" class="primary-button">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
        </div>
    </form>
</div>

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