<?php
// This page is included within index.php, which already handles the session check

// Fetch data for selects
$categories        = getAdminCategories();
$statesStmt        = $databaseConnection->query("SELECT * FROM sistema_estados ORDER BY nome");
$states            = $statesStmt->fetchAll();
$cities            = getAllCities();
$neighborhoods     = getAllBairros();
$usersResult       = getAdminUsers([], 1, PHP_INT_MAX);
$users             = $usersResult['users'];

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Format currency value (remove R$ and convert comma to dot)
    $valorFormatado = $_POST['valor'] ?? '0';
    $valorFormatado = str_replace('R$ ', '', $valorFormatado);
    $valorFormatado = str_replace('.', '', $valorFormatado);
    $valorFormatado = str_replace(',', '.', $valorFormatado);
    $valorFormatado = (float)$valorFormatado;

    // Generate a unique code for the property
    $codigo = md5(uniqid(rand(), true));
    
    // Current date and time
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i:s');

    // Collect form data
    $propertyData = [
        'titulo'               => $_POST['titulo'] ?? '',
        'para'                 => $_POST['para'] ?? 'Venda',
        'id_categoria'         => $_POST['id_categoria'] ?? 1,
        'id_estado'            => $_POST['id_estado'] ?? 1,
        'id_cidade'            => $_POST['id_cidade'] ?? 1,
        'id_bairro'            => $_POST['id_bairro'] ?? 0,
        'valor'                => $valorFormatado,
        'quartos'              => $_POST['quartos'] ?? '0',
        'suites'               => $_POST['suites'] ?? '0',
        'banheiros'            => $_POST['banheiros'] ?? '0',
        'salas'                => $_POST['salas'] ?? '0',
        'cozinhas'             => $_POST['cozinhas'] ?? '0',
        'garagem'              => $_POST['garagem'] ?? '0',
        'area_servico'         => $_POST['area_servico'] ?? '',
        'area_total'           => $_POST['area_total'] ?? '',
        'area_construida'      => $_POST['area_construida'] ?? '',
        'und_medida'           => $_POST['und_medida'] ?? 'M²',
        'endereco'             => $_POST['endereco'] ?? '',
        'descricao'            => $_POST['descricao'] ?? '',
        'palavras_chaves'      => $_POST['palavras_chaves'] ?? '',
        'codigo'               => $codigo,
        'data'                 => $currentDate,
        'hora'                 => $currentTime,
        'id_usuario'           => $_SESSION['admin_id'],
        'status'               => $_POST['status'] ?? 'Ativo',
        'destaque'             => isset($_POST['destaque']) ? 1 : 0,
        'quadra_lote'          => $_POST['quadra_lote'] ?? '',
        'nome_anunciante'      => $_POST['nome_anunciante'] ?? '',
        'telefone_anunciante'  => $_POST['telefone_anunciante'] ?? '',
        'corretor_responsavel' => $_POST['corretor_responsavel'] ?? 1
    ];

    // Basic validation
    if (empty($propertyData['titulo'])) {
        $error = "Título é um campo obrigatório.";
    } else {
        // Create property using the function from admin_functions.php
        $newPropertyId = createProperty($propertyData);
    
        if ($newPropertyId) {
            // Handle image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $uploadDir = __DIR__ . '/../../uploads/imoveis/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $code = $propertyData['codigo'];
                foreach ($_FILES['images']['name'] as $i => $origName) {
                    if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                        $tmp = $_FILES['images']['tmp_name'][$i];
                        $ext = 'jpg'; // Forcing jpg extension as per system design
                        $filename = $code . sprintf('%02d', $i + 1) . '.' . $ext;
                        
                        // Process the image - resize if needed
                        processImageUpload($tmp, $uploadDir . $filename);
                    }
                }
            }
            
            // Set success message
            $_SESSION['alert_message'] = "Imóvel cadastrado com sucesso!";
            $_SESSION['alert_type'] = "success";
            
            // Redirect to property admin
            header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
            exit;
        } else {
            $error = "Erro ao cadastrar imóvel.";
        }
    }
}

// Helper function to process image uploads
function processImageUpload($sourcePath, $destPath) {
    // Get image information
    $imageInfo = getimagesize($sourcePath);
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $type = $imageInfo[2];
    
    // Create image resource based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        default:
            // Unsupported image type
            move_uploaded_file($sourcePath, $destPath);
            return;
    }
    
    // Check if need to resize
    $maxWidth = 1200;
    $maxHeight = 800;
    
    if ($width > $maxWidth || $height > $maxHeight) {
        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        // Create a new image with new dimensions
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Handle transparency for PNG
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize the image
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Save the image
        imagejpeg($newImage, $destPath, 90);
        
        // Free up memory
        imagedestroy($newImage);
    } else {
        // Save as JPEG without resizing
        imagejpeg($sourceImage, $destPath, 90);
    }
    
    // Free up memory
    imagedestroy($sourceImage);
}
?>

<!-- Property Create Page -->
<div class="admin-page property-create">
    <div class="admin-page__header">
        <h2 class="admin-page__title">Adicionar Novo Imóvel</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Admin" class="admin-page__back-link">
            <i class="fas fa-arrow-left"></i> Voltar para Lista
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert-message alert-message--error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="admin-card">
        <form action="" method="POST" enctype="multipart/form-data" class="property-form">
            <!-- Tabs for better form navigation -->
            <div class="form-tabs">
                <button type="button" class="form-tab form-tab--active" data-tab="basic">Informações Básicas</button>
                <button type="button" class="form-tab" data-tab="details">Detalhes</button>
                <button type="button" class="form-tab" data-tab="location">Localização</button>
                <button type="button" class="form-tab" data-tab="attributes">Características</button>
                <button type="button" class="form-tab" data-tab="images">Imagens</button>
            </div>

            <div class="form-sections">
                <!-- Basic Information Section -->
                <div class="form-section form-section--active" data-section="basic">
                    <h3 class="form-section__title">Informações Básicas</h3>

                    <div class="form-row">
                        <div class="form-group form-group--large">
                            <label for="titulo">Título do Imóvel <span class="required">*</span></label>
                            <input type="text" id="titulo" name="titulo" class="form-control" required value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Código field removed since it's auto-generated -->

                    <div class="form-row">
                        <div class="form-group">
                            <label for="para">Anúncio Para</label>
                            <select id="para" name="para" class="form-control">
                                <option value="Venda" <?= isset($_POST['para']) && $_POST['para'] == 'Venda' ? 'selected' : '' ?>>Venda</option>
                                <option value="Aluguel" <?= isset($_POST['para']) && $_POST['para'] == 'Aluguel' ? 'selected' : '' ?>>Aluguel</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="id_categoria">Categoria</label>
                            <select id="id_categoria" name="id_categoria" class="form-control">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= isset($_POST['id_categoria']) && $_POST['id_categoria'] == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['categoria']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="valor">Valor <span class="required">*</span></label>
                            <input type="text" id="valor" name="valor" class="form-control money-mask" required value="<?= htmlspecialchars($_POST['valor'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="Ativo" <?= isset($_POST['status']) && $_POST['status'] == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                <option value="Inativo" <?= isset($_POST['status']) && $_POST['status'] == 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                                <option value="Vendido" <?= isset($_POST['status']) && $_POST['status'] == 'Vendido' ? 'selected' : '' ?>>Vendido</option>
                                <option value="Alugado" <?= isset($_POST['status']) && $_POST['status'] == 'Alugado' ? 'selected' : '' ?>>Alugado</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group form-group--large">
                            <label for="descricao">Descrição</label>
                            <textarea id="descricao" name="descricao" class="form-control" rows="6"><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group form-group--large">
                            <label for="palavras_chaves">Palavras-chave</label>
                            <input type="text" id="palavras_chaves" name="palavras_chaves" class="form-control" value="<?= htmlspecialchars($_POST['palavras_chaves'] ?? '') ?>">
                            <div class="form-help">Palavras-chave que ajudem na busca deste imóvel (separadas por vírgula)</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group form-group--checkbox">
                            <input type="checkbox" id="destaque" name="destaque" value="1" <?= isset($_POST['destaque']) ? 'checked' : '' ?>>
                            <label for="destaque">Imóvel em Destaque</label>
                        </div>
                    </div>
                </div>

                <!-- Details Section -->
                <div class="form-section" data-section="details">
                    <h3 class="form-section__title">Detalhes do Imóvel</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="quartos">Quartos</label>
                            <select id="quartos" name="quartos" class="form-control">
                                <option value="Nenhum" <?= isset($_POST['quartos']) && $_POST['quartos'] == 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>" <?= isset($_POST['quartos']) && $_POST['quartos'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="suites">Suítes</label>
                            <select id="suites" name="suites" class="form-control">
                                <option value="Nenhum" <?= isset($_POST['suites']) && $_POST['suites'] == 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= isset($_POST['suites']) && $_POST['suites'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="banheiros">Banheiros</label>
                            <select id="banheiros" name="banheiros" class="form-control">
                                <option value="Nenhum" <?= isset($_POST['banheiros']) && $_POST['banheiros'] == 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= isset($_POST['banheiros']) && $_POST['banheiros'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="salas">Salas</label>
                            <select id="salas" name="salas" class="form-control">
                                <option value="Nenhum" <?= isset($_POST['salas']) && $_POST['salas'] == 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= isset($_POST['salas']) && $_POST['salas'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cozinhas">Cozinhas</label>
                            <select id="cozinhas" name="cozinhas" class="form-control">
                                <option value="Nenhum" <?= isset($_POST['cozinhas']) && $_POST['cozinhas'] == 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                                <?php for ($i = 1; $i <= 3; $i++): ?>
                                    <option value="<?= $i ?>" <?= isset($_POST['cozinhas']) && $_POST['cozinhas'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="garagem">Vagas de Garagem</label>
                            <select id="garagem" name="garagem" class="form-control">
                                <option value="Nenhum" <?= isset($_POST['garagem']) && $_POST['garagem'] == 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>" <?= isset($_POST['garagem']) && $_POST['garagem'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="area_servico">Área de Serviço</label>
                            <select id="area_servico" name="area_servico" class="form-control">
                                <option value="Não" <?= isset($_POST['area_servico']) && $_POST['area_servico'] == 'Não' ? 'selected' : '' ?>>Não</option>
                                <option value="Sim" <?= isset($_POST['area_servico']) && $_POST['area_servico'] == 'Sim' ? 'selected' : '' ?>>Sim</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="und_medida">Unidade de Medida</label>
                            <select id="und_medida" name="und_medida" class="form-control">
                                <option value="M²" <?= isset($_POST['und_medida']) && $_POST['und_medida'] == 'M²' ? 'selected' : '' ?>>m²</option>
                                <option value="ha" <?= isset($_POST['und_medida']) && $_POST['und_medida'] == 'ha' ? 'selected' : '' ?>>ha</option>
                                <option value="alq" <?= isset($_POST['und_medida']) && $_POST['und_medida'] == 'alq' ? 'selected' : '' ?>>alq</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="area_total">Área Total</label>
                            <input type="text" id="area_total" name="area_total" class="form-control" value="<?= htmlspecialchars($_POST['area_total'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="area_construida">Área Construída</label>
                            <input type="text" id="area_construida" name="area_construida" class="form-control" value="<?= htmlspecialchars($_POST['area_construida'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="quadra_lote">Quadra/Lote</label>
                            <input type="text" id="quadra_lote" name="quadra_lote" class="form-control" value="<?= htmlspecialchars($_POST['quadra_lote'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Location Section -->
                <div class="form-section" data-section="location">
                    <h3 class="form-section__title">Localização</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="id_estado">Estado</label>
                            <select id="id_estado" name="id_estado" class="form-control">
                                <option value="">Selecione o Estado</option>
                                <?php foreach ($states as $state): ?>
                                    <option value="<?= $state['id'] ?>" <?= isset($_POST['id_estado']) && $_POST['id_estado'] == $state['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($state['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="id_cidade">Cidade</label>
                            <select id="id_cidade" name="id_cidade" class="form-control">
                                <option value="">Selecione a Cidade</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city['id'] ?>" 
                                            data-state="<?= $city['id_estado'] ?>" 
                                            <?= isset($_POST['id_cidade']) && $_POST['id_cidade'] == $city['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($city['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="id_bairro">Bairro</label>
                            <select id="id_bairro" name="id_bairro" class="form-control">
                                <option value="">Selecione o Bairro</option>
                                <?php foreach ($neighborhoods as $bairro): ?>
                                    <option value="<?= $bairro['id'] ?>" 
                                            data-city="<?= $bairro['id_cidade'] ?>"
                                            <?= isset($_POST['id_bairro']) && $_POST['id_bairro'] == $bairro['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($bairro['bairro']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group form-group--large">
                            <label for="endereco">Endereço Completo</label>
                            <input type="text" id="endereco" name="endereco" class="form-control" value="<?= htmlspecialchars($_POST['endereco'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Attributes Section -->
                <div class="form-section" data-section="attributes">
                    <h3 class="form-section__title">Informações Adicionais</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome_anunciante">Nome do Anunciante</label>
                            <input type="text" id="nome_anunciante" name="nome_anunciante" class="form-control" value="<?= htmlspecialchars($_POST['nome_anunciante'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="telefone_anunciante">Telefone do Anunciante</label>
                            <input type="text" id="telefone_anunciante" name="telefone_anunciante" class="form-control phone-mask" value="<?= htmlspecialchars($_POST['telefone_anunciante'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="corretor_responsavel">Corretor Responsável</label>
                            <select id="corretor_responsavel" name="corretor_responsavel" class="form-control">
                                <option value="1">Selecione o Corretor</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= isset($_POST['corretor_responsavel']) && $_POST['corretor_responsavel'] == $user['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Images Section -->
                <div class="form-section" data-section="images">
                    <h3 class="form-section__title">Fotos do Imóvel</h3>

                    <div class="form-row">
                        <div class="form-group form-group--large">
                            <label for="images">Selecione as Fotos (até 12 imagens)</label>
                            <input type="file" id="images" name="images[]" class="form-control-file" multiple accept="image/*">
                            <div class="form-help">
                                <p>São permitidas até 12 imagens no formato JPG, PNG ou GIF.</p>
                                <p>A primeira imagem selecionada será a imagem principal do imóvel.</p>
                                <p>Tamanho máximo por arquivo: 5MB.</p>
                            </div>
                        </div>
                    </div>

                    <div class="image-preview" id="imagePreview"></div>
                </div>
            </div>

            <div class="form-buttons">
                <button type="submit" class="primary-button">
                    <i class="fas fa-save"></i> Salvar Imóvel
                </button>
                <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Admin" class="cancel-button">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Tab navigation
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.form-tab');
    const sections = document.querySelectorAll('.form-section');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Update active tab
            tabs.forEach(t => t.classList.remove('form-tab--active'));
            this.classList.add('form-tab--active');
            
            // Show corresponding section
            sections.forEach(section => {
                if (section.getAttribute('data-section') === targetTab) {
                    section.classList.add('form-section--active');
                } else {
                    section.classList.remove('form-section--active');
                }
            });
        });
    });
    
    // Filter cities based on selected state
    const stateSelect = document.getElementById('id_estado');
    const citySelect = document.getElementById('id_cidade');
    const bairroSelect = document.getElementById('id_bairro');
    
    // Set initial default values
    if (!stateSelect.value) {
        stateSelect.value = "1"; // Default to first state
        // Trigger the change event to update cities
        stateSelect.dispatchEvent(new Event('change'));
    }
    
    stateSelect.addEventListener('change', function() {
        const selectedState = this.value;
        
        // Filter cities
        Array.from(citySelect.options).forEach(option => {
            if (option.value === '' || option.dataset.state === selectedState) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
        
        // Find first visible city option
        const firstVisibleCity = Array.from(citySelect.options).find(option => 
            option.style.display !== 'none' && option.value !== '');
        
        // Select first visible city
        if (firstVisibleCity) {
            citySelect.value = firstVisibleCity.value;
        } else {
            citySelect.value = '';
        }
        
        // Trigger city change to update neighborhoods
        citySelect.dispatchEvent(new Event('change'));
    });
    
    // Filter neighborhoods based on selected city
    citySelect.addEventListener('change', function() {
        const selectedCity = this.value;
        
        // Filter neighborhoods
        Array.from(bairroSelect.options).forEach(option => {
            if (option.value === '' || option.dataset.city === selectedCity) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
        
        // Find first visible neighborhood option
        const firstVisibleBairro = Array.from(bairroSelect.options).find(option => 
            option.style.display !== 'none' && option.value !== '');
        
        // Select first visible neighborhood
        if (firstVisibleBairro) {
            bairroSelect.value = firstVisibleBairro.value;
        } else {
            bairroSelect.value = '';
        }
    });
    
    // Image preview
    const imageInput = document.getElementById('images');
    const imagePreview = document.getElementById('imagePreview');
    
    imageInput.addEventListener('change', function() {
        imagePreview.innerHTML = '';
        
        if (this.files.length > 0) {
            for (let i = 0; i < Math.min(this.files.length, 12); i++) {
                const file = this.files[i];
                
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'image-preview__item';
                    
                    reader.onload = function(e) {
                        imgContainer.innerHTML = `
                            <div class="image-preview__number">${i + 1}</div>
                            <img src="${e.target.result}" alt="Preview" class="image-preview__img">
                            <div class="image-preview__filename">${file.name}</div>
                        `;
                        
                        if (i === 0) {
                            imgContainer.classList.add('image-preview__item--primary');
                            imgContainer.innerHTML += '<div class="image-preview__primary">Imagem Principal</div>';
                        }
                    };
                    
                    reader.readAsDataURL(file);
                    imagePreview.appendChild(imgContainer);
                }
            }
        }
    });
    
    // Set default value for price input
    const moneyInput = document.querySelector('.money-mask');
    if (moneyInput && !moneyInput.value) {
        moneyInput.value = 'R$ 0,00';
    }
    
    // Money mask for price input
    if (moneyInput) {
        moneyInput.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value === '') value = '0';
            value = (parseInt(value) / 100).toFixed(2);
            value = value.replace('.', ',');
            value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            this.value = 'R$ ' + value;
        });
        
        // Initial formatting if value exists
        if (moneyInput.value) {
            let value = moneyInput.value.replace(/\D/g, '');
            if (value) {
                value = (parseInt(value) / 100).toFixed(2);
                value = value.replace('.', ',');
                value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                moneyInput.value = 'R$ ' + value;
            }
        }
    }
    
    // Set default values for dropdowns to ensure they have selections
    document.querySelectorAll('select').forEach(select => {
        if (!select.value && select.options.length > 1) {
            select.value = select.options[1].value; // Select first non-empty option
        }
    });
    
    // Phone mask
    const phoneInput = document.querySelector('.phone-mask');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 11) value = value.substring(0, 11);
            
            if (value.length > 6) {
                this.value = `(${value.substring(0, 2)}) ${value.substring(2, 7)}-${value.substring(7)}`;
            } else if (value.length > 2) {
                this.value = `(${value.substring(0, 2)}) ${value.substring(2)}`;
            } else if (value.length > 0) {
                this.value = `(${value}`;
            }
        });
    }
    
    // Set default status to "Ativo"
    const statusSelect = document.getElementById('status');
    if (statusSelect && !statusSelect.value) {
        statusSelect.value = 'Ativo';
    }
    
    // Trigger state change on page load to populate dropdowns
    if (stateSelect) {
        stateSelect.dispatchEvent(new Event('change'));
    }
});