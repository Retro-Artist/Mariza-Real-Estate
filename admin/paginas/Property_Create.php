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
$validationErrors = [];

// Default values for required fields
$defaults = [
    'titulo' => 'Imóvel à venda',
    'para' => 'Venda',
    'id_categoria' => $categories[0]['id'] ?? 1,
    'id_estado' => $states[0]['id'] ?? 1,
    'id_cidade' => $cities[0]['id'] ?? 1,
    'id_bairro' => $neighborhoods[0]['id'] ?? 0,
    'valor' => 'R$ 0,00',
    'quartos' => '0',
    'suites' => '0',
    'banheiros' => '0',
    'salas' => '0',
    'cozinhas' => '0',
    'garagem' => '0',
    'area_servico' => 'Não',
    'und_medida' => 'M²',
    'status' => 'Ativo',
    'corretor_responsavel' => $_SESSION['admin_id'] ?? 1
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (empty($_POST['titulo'])) {
        $validationErrors['titulo'] = 'Título é obrigatório';
    }
    
    // Format currency value (remove R$ and convert comma to dot)
    $valorFormatado = $_POST['valor'] ?? '0';
    $valorFormatado = str_replace('R$ ', '', $valorFormatado);
    $valorFormatado = str_replace('.', '', $valorFormatado);
    $valorFormatado = str_replace(',', '.', $valorFormatado);
    $valorFormatado = (float)$valorFormatado;
    
    if ($valorFormatado <= 0) {
        $validationErrors['valor'] = 'Valor deve ser maior que zero';
    }

    // Generate a unique code for the property
    $codigo = md5(uniqid(rand(), true));
    
    // Current date and time
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i:s');

    // Collect form data
    $propertyData = [
        'titulo'               => $_POST['titulo'] ?? $defaults['titulo'],
        'para'                 => $_POST['para'] ?? $defaults['para'],
        'id_categoria'         => $_POST['id_categoria'] ?? $defaults['id_categoria'],
        'id_estado'            => $_POST['id_estado'] ?? $defaults['id_estado'],
        'id_cidade'            => $_POST['id_cidade'] ?? $defaults['id_cidade'],
        'id_bairro'            => $_POST['id_bairro'] ?? $defaults['id_bairro'],
        'valor'                => $valorFormatado,
        'quartos'              => $_POST['quartos'] ?? $defaults['quartos'],
        'suites'               => $_POST['suites'] ?? $defaults['suites'],
        'banheiros'            => $_POST['banheiros'] ?? $defaults['banheiros'],
        'salas'                => $_POST['salas'] ?? $defaults['salas'],
        'cozinhas'             => $_POST['cozinhas'] ?? $defaults['cozinhas'],
        'garagem'              => $_POST['garagem'] ?? $defaults['garagem'],
        'area_servico'         => $_POST['area_servico'] ?? $defaults['area_servico'],
        'area_total'           => $_POST['area_total'] ?? '',
        'area_construida'      => $_POST['area_construida'] ?? '',
        'und_medida'           => $_POST['und_medida'] ?? $defaults['und_medida'],
        'endereco'             => $_POST['endereco'] ?? '',
        'descricao'            => $_POST['descricao'] ?? '',
        'palavras_chaves'      => $_POST['palavras_chaves'] ?? '',
        'codigo'               => $codigo,
        'data'                 => $currentDate,
        'hora'                 => $currentTime,
        'id_usuario'           => $_SESSION['admin_id'],
        'status'               => $_POST['status'] ?? $defaults['status'],
        'destaque'             => isset($_POST['destaque']) ? 1 : 0,
        'quadra_lote'          => $_POST['quadra_lote'] ?? '',
        'nome_anunciante'      => $_POST['nome_anunciante'] ?? '',
        'telefone_anunciante'  => $_POST['telefone_anunciante'] ?? '',
        'corretor_responsavel' => $_POST['corretor_responsavel'] ?? $defaults['corretor_responsavel']
    ];

    // If there are no validation errors, proceed with saving
    if (empty($validationErrors)) {
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
            $error = "Erro ao cadastrar imóvel. Por favor, verifique os dados e tente novamente.";
        }
    } else {
        $error = "Por favor, corrija os erros no formulário antes de continuar.";
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

// Helper function to display validation error
function showValidationError($field) {
    global $validationErrors;
    if (isset($validationErrors[$field])) {
        return '<div class="validation-error">' . htmlspecialchars($validationErrors[$field]) . '</div>';
    }
    return '';
}

// Helper function to add error class to form fields
function errorClass($field) {
    global $validationErrors;
    return isset($validationErrors[$field]) ? ' form-control--error' : '';
}

// Helper to get field value or default
function getFieldValue($field, $default = '') {
    global $defaults;
    if (isset($_POST[$field])) {
        return htmlspecialchars($_POST[$field]);
    } else if (isset($defaults[$field])) {
        return htmlspecialchars($defaults[$field]);
    }
    return $default;
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
        <!-- Quick Fill Form -->
        <div class="quick-fill">
            <h3>Preencher Rapidamente</h3>
            <p>Preencha o formulário automaticamente com os valores padrão.</p>
            <button type="button" id="quickFillButton" class="secondary-button">
                <i class="fas fa-magic"></i> Preencher Automaticamente
            </button>
        </div>

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
                    <p class="form-section__desc">Os campos marcados com <span class="required">*</span> são obrigatórios.</p>

                    <div class="form-row">
                        <div class="form-group form-group--large">
                            <label for="titulo">Título do Imóvel <span class="required">*</span></label>
                            <input type="text" id="titulo" name="titulo" class="form-control<?= errorClass('titulo') ?>" required value="<?= getFieldValue('titulo') ?>">
                            <?= showValidationError('titulo') ?>
                            <div class="form-help">Ex: Casa com 3 quartos em Jardim das Acácias</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="para">Anúncio Para <span class="required">*</span></label>
                            <select id="para" name="para" class="form-control<?= errorClass('para') ?>" required>
                                <option value="Venda" <?= getFieldValue('para') == 'Venda' ? 'selected' : '' ?>>Venda</option>
                                <option value="Aluguel" <?= getFieldValue('para') == 'Aluguel' ? 'selected' : '' ?>>Aluguel</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="id_categoria">Categoria <span class="required">*</span></label>
                            <select id="id_categoria" name="id_categoria" class="form-control<?= errorClass('id_categoria') ?>" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= getFieldValue('id_categoria') == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['categoria']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="valor">Valor <span class="required">*</span></label>
                            <input type="text" id="valor" name="valor" class="form-control money-mask<?= errorClass('valor') ?>" required value="<?= getFieldValue('valor') ?>">
                            <?= showValidationError('valor') ?>
                        </div>

                        <div class="form-group">
                            <label for="status">Status <span class="required">*</span></label>
                            <select id="status" name="status" class="form-control<?= errorClass('status') ?>" required>
                                <option value="Ativo" <?= getFieldValue('status') == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                <option value="Inativo" <?= getFieldValue('status') == 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                                <option value="Vendido" <?= getFieldValue('status') == 'Vendido' ? 'selected' : '' ?>>Vendido</option>
                                <option value="Alugado" <?= getFieldValue('status') == 'Alugado' ? 'selected' : '' ?>>Alugado</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group form-group--large">
                            <label for="descricao">Descrição</label>
                            <textarea id="descricao" name="descricao" class="form-control" rows="6"><?= getFieldValue('descricao') ?></textarea>
                            <div class="form-help">Descreva as características do imóvel em detalhes</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group form-group--large">
                            <label for="palavras_chaves">Palavras-chave</label>
                            <input type="text" id="palavras_chaves" name="palavras_chaves" class="form-control" value="<?= getFieldValue('palavras_chaves') ?>">
                            <div class="form-help">Palavras-chave que ajudem na busca deste imóvel (separadas por vírgula)</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group form-group--checkbox">
                            <input type="checkbox" id="destaque" name="destaque" value="1" <?= isset($_POST['destaque']) ? 'checked' : '' ?>>
                            <label for="destaque">Imóvel em Destaque</label>
                            <div class="form-help">Marque esta opção para que o imóvel apareça na seção de destaque na página inicial</div>
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
                                <option value="Nenhum" <?= getFieldValue('quartos') == 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>" <?= getFieldValue('quartos') == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="suites">Suítes</label>
                            <select id="suites" name="suites" class="form-control">
                                <option value="Nenhum" <?= getFieldValue('suites') == 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= getFieldValue('suites') == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="banheiros">Banheiros</label>
                            <select id="banheiros" name="banheiros" class="form-control">
                                <option value="Nenhum" <?= getFieldValue('banheiros') == 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= getFieldValue('banheiros') == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="salas">Salas</label>
                            <select id="salas" name="salas" class="form-control">
                                <option value="Nenhum" <?= getFieldValue('salas') == 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= getFieldValue('salas') == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cozinhas">Cozinhas</label>
                            <select id="cozinhas" name="cozinhas" class="form-control">
                                <option value="Nenhum" <?= getFieldValue('cozinhas') == 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                                <?php for ($i = 1; $i <= 3; $i++): ?>
                                    <option value="<?= $i ?>" <?= getFieldValue('cozinhas') == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="garagem">Vagas de Garagem</label>
                            <select id="garagem" name="garagem" class="form-control">
                                <option value="Nenhum" <?= getFieldValue('garagem') == 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>" <?= getFieldValue('garagem') == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="area_servico">Área de Serviço</label>
                            <select id="area_servico" name="area_servico" class="form-control">
                                <option value="Não" <?= getFieldValue('area_servico') == 'Não' ? 'selected' : '' ?>>Não</option>
                                <option value="Sim" <?= getFieldValue('area_servico') == 'Sim' ? 'selected' : '' ?>>Sim</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="und_medida">Unidade de Medida</label>
                            <select id="und_medida" name="und_medida" class="form-control">
                                <option value="M²" <?= getFieldValue('und_medida') == 'M²' ? 'selected' : '' ?>>m²</option>
                                <option value="ha" <?= getFieldValue('und_medida') == 'ha' ? 'selected' : '' ?>>ha</option>
                                <option value="alq" <?= getFieldValue('und_medida') == 'alq' ? 'selected' : '' ?>>alq</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="area_total">Área Total</label>
                            <input type="text" id="area_total" name="area_total" class="form-control" value="<?= getFieldValue('area_total') ?>">
                        </div>

                        <div class="form-group">
                            <label for="area_construida">Área Construída</label>
                            <input type="text" id="area_construida" name="area_construida" class="form-control" value="<?= getFieldValue('area_construida') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="quadra_lote">Quadra/Lote</label>
                            <input type="text" id="quadra_lote" name="quadra_lote" class="form-control" value="<?= getFieldValue('quadra_lote') ?>">
                        </div>
                    </div>
                </div>

                <!-- Location Section -->
                <div class="form-section" data-section="location">
                    <h3 class="form-section__title">Localização</h3>
                    <p class="form-section__desc">Informações sobre a localização do imóvel</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="id_estado">Estado <span class="required">*</span></label>
                            <select id="id_estado" name="id_estado" class="form-control<?= errorClass('id_estado') ?>" required>
                                <option value="">Selecione o Estado</option>
                                <?php foreach ($states as $state): ?>
                                    <option value="<?= $state['id'] ?>" <?= getFieldValue('id_estado') == $state['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($state['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= showValidationError('id_estado') ?>
                        </div>

                        <div class="form-group">
                            <label for="id_cidade">Cidade <span class="required">*</span></label>
                            <select id="id_cidade" name="id_cidade" class="form-control<?= errorClass('id_cidade') ?>" required>
                                <option value="">Selecione a Cidade</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city['id'] ?>" 
                                            data-state="<?= $city['id_estado'] ?>" 
                                            <?= getFieldValue('id_cidade') == $city['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($city['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= showValidationError('id_cidade') ?>
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
                                            <?= getFieldValue('id_bairro') == $bairro['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($bairro['bairro']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group form-group--large">
                            <label for="endereco">Endereço Completo</label>
                            <input type="text" id="endereco" name="endereco" class="form-control" value="<?= getFieldValue('endereco') ?>">
                            <div class="form-help">Ex: Rua das Flores, 123 - Centro</div>
                        </div>
                    </div>
                </div>

                <!-- Attributes Section -->
                <div class="form-section" data-section="attributes">
                    <h3 class="form-section__title">Informações Adicionais</h3>
                    <p class="form-section__desc">Dados do anunciante e responsável pelo imóvel</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome_anunciante">Nome do Anunciante</label>
                            <input type="text" id="nome_anunciante" name="nome_anunciante" class="form-control" value="<?= getFieldValue('nome_anunciante') ?>">
                        </div>

                        <div class="form-group">
                            <label for="telefone_anunciante">Telefone do Anunciante</label>
                            <input type="text" id="telefone_anunciante" name="telefone_anunciante" class="form-control phone-mask" value="<?= getFieldValue('telefone_anunciante') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="corretor_responsavel">Corretor Responsável <span class="required">*</span></label>
                            <select id="corretor_responsavel" name="corretor_responsavel" class="form-control<?= errorClass('corretor_responsavel') ?>" required>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= getFieldValue('corretor_responsavel') == $user['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= showValidationError('corretor_responsavel') ?>
                        </div>
                    </div>
                </div>

                <!-- Images Section -->
                <div class="form-section" data-section="images">
                    <h3 class="form-section__title">Fotos do Imóvel</h3>
                    <p class="form-section__desc">Adicione até 12 fotos do imóvel</p>

                    <div class="form-row">
                        <div class="form-group form-group--large">
                            <label for="images">Selecione as Fotos</label>
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

            <div class="form-actions">
                <button type="submit" class="primary-button primary-button--large">
                    <i class="fas fa-save"></i> Salvar Imóvel
                </button>
                <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Admin" class="cancel-button">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<style>
/* Additional styling for form validation */
.validation-error {
    color: #e74c3c;
    font-size: 12px;
    margin-top: 5px;
    display: block;
}

.form-control--error {
    border-color: #e74c3c !important;
    background-color: #fff8f8;
}

.required {
    color: #e74c3c;
    font-weight: bold;
}

.form-section__desc {
    color: #777;
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
}

.quick-fill {
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 20px;
    border-left: 4px solid #4CAF50;
}

.quick-fill h3 {
    margin-top: 0;
    font-size: 1.1rem;
    color: #333;
}

.quick-fill p {
    color: #666;
    margin-bottom: 10px;
    font-size: 0.9rem;
}

.secondary-button {
    background-color: #6c757d;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s;
    font-size: 0.9rem;
}

.secondary-button:hover {
    background-color: #5a6268;
}

.form-help {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.primary-button--large {
    padding: 12px 24px;
    font-size: 1.1rem;
}

.form-actions {
    display: flex;
    justify-content: space-between;
    padding-top: 20px;
    border-top: 1px solid #eee;
    margin-top: 20px;
}
</style>

<script>
// Tab navigation
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.form-tab');
    const sections = document.querySelectorAll('.form-section');
    
    // Check for validation errors and show appropriate tab
    const hasValidationErrors = <?= !empty($validationErrors) ? 'true' : 'false' ?>;
    const validationErrorFields = <?= json_encode(array_keys($validationErrors ?? [])) ?>;
    
    if (hasValidationErrors) {
        // Find which tab contains the first error
        const fieldToTabMap = {
            'titulo': 'basic',
            'valor': 'basic',
            'para': 'basic',
            'status': 'basic',
            'id_estado': 'location',
            'id_cidade': 'location',
            'id_bairro': 'location',
            'corretor_responsavel': 'attributes'
        };
        
        // Get the first error field
        const firstErrorField = validationErrorFields[0];
        const tabToActivate = fieldToTabMap[firstErrorField] || 'basic';
        
        // Activate the appropriate tab
        activateTab(tabToActivate);
        
        // Focus on the first field with error
        if (document.getElementById(firstErrorField)) {
            document.getElementById(firstErrorField).focus();
        }
    }
    
    // Tab click handler
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            activateTab(targetTab);
        });
    });
    
    // Function to activate a tab
    function activateTab(tabName) {
        // Update active tab
        tabs.forEach(t => {
            if (t.getAttribute('data-tab') === tabName) {
                t.classList.add('form-tab--active');
            } else {
                t.classList.remove('form-tab--active');
            }
        });
        
        // Show corresponding section
        sections.forEach(section => {
            if (section.getAttribute('data-section') === tabName) {
                section.classList.add('form-section--active');
            } else {
                section.classList.remove('form-section--active');
            }
        });
    }
    
    // Quick Fill Button
    const quickFillButton = document.getElementById('quickFillButton');
    if (quickFillButton) {
        quickFillButton.addEventListener('click', function() {
            // Fill basic fields
            document.getElementById('titulo').value = 'Imóvel à venda';
            document.getElementById('valor').value = 'R$ 100.000,00';
            
            // Set selections
            document.getElementById('para').value = 'Venda';
            document.getElementById('status').value = 'Ativo';
            
            // Fill details fields with minimum values
            document.getElementById('quartos').value = '2';
            document.getElementById('banheiros').value = '1';
            document.getElementById('area_total').value = '200';
            document.getElementById('area_construida').value = '100';
            
            // Trigger state selection to populate city and neighborhood
            const stateSelect = document.getElementById('id_estado');
            if (stateSelect.options.length > 0) {
                stateSelect.value = stateSelect.options[0].value;
                stateSelect.dispatchEvent(new Event('change'));
            }
            
            alert('Campos preenchidos automaticamente! Revise as informações antes de salvar.');
        });
    }
    
    // Filter cities based on selected state
    const stateSelect = document.getElementById('id_estado');
    const citySelect = document.getElementById('id_cidade');
    const bairroSelect = document.getElementById('id_bairro');
    
    // Set initial default values if not already set
    if (!stateSelect.value && stateSelect.options.length > 0) {
        stateSelect.value = stateSelect.options[0].value;
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
    
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            imagePreview.innerHTML = '';
            
            if (this.files.length > 0) {
                // Create header
                const header = document.createElement('div');
                header.className = 'image-preview__header';
                header.innerHTML = `<h4>Preview das Imagens Selecionadas (${Math.min(this.files.length, 12)} de 12 máximo)</h4>`;
                imagePreview.appendChild(header);
                
                // Create container for images
                const container = document.createElement('div');
                container.className = 'image-preview__grid';
                imagePreview.appendChild(container);
                
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
                        container.appendChild(imgContainer);
                    }
                }
            }
        });
    }
    
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
    
    // Form Validation
    const form = document.querySelector('.property-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let hasErrors = false;
            
            // Validate required fields
            const requiredFields = [
                { id: 'titulo', message: 'Título é obrigatório' },
                { id: 'valor', message: 'Valor deve ser preenchido' },
                { id: 'id_estado', message: 'Estado é obrigatório' },
                { id: 'id_cidade', message: 'Cidade é obrigatória' }
            ];
            
            requiredFields.forEach(field => {
                const element = document.getElementById(field.id);
                const errorDiv = element.parentNode.querySelector('.validation-error') || 
                                 document.createElement('div');
                errorDiv.className = 'validation-error';
                
                if (!element.value) {
                    hasErrors = true;
                    element.classList.add('form-control--error');
                    errorDiv.textContent = field.message;
                    
                    // Add error message if not already there
                    if (!element.parentNode.querySelector('.validation-error')) {
                        element.parentNode.appendChild(errorDiv);
                    }
                } else {
                    element.classList.remove('form-control--error');
                    if (element.parentNode.contains(errorDiv)) {
                        element.parentNode.removeChild(errorDiv);
                    }
                }
            });
            
            // Validate valor is greater than zero
            const valorInput = document.getElementById('valor');
            if (valorInput) {
                let valorValue = valorInput.value.replace(/\D/g, '');
                valorValue = parseInt(valorValue);
                
                if (valorValue <= 0) {
                    hasErrors = true;
                    valorInput.classList.add('form-control--error');
                    
                    const errorDiv = valorInput.parentNode.querySelector('.validation-error') || 
                                    document.createElement('div');
                    errorDiv.className = 'validation-error';
                    errorDiv.textContent = 'Valor deve ser maior que zero';
                    
                    if (!valorInput.parentNode.querySelector('.validation-error')) {
                        valorInput.parentNode.appendChild(errorDiv);
                    }
                }
            }
            
            if (hasErrors) {
                e.preventDefault();
                // Scroll to the first error
                const firstError = document.querySelector('.form-control--error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });
    }
    
    // Trigger state change on page load to populate dropdowns
    if (stateSelect && stateSelect.value) {
        stateSelect.dispatchEvent(new Event('change'));
    }
});
</script>