<?php
// This page is included within index.php, which already handles the session check

// Enable detailed error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if property ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = "ID do imóvel não fornecido.";
    $_SESSION['alert_type'] = "error";
    header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
    exit;
}

$property_id = (int)$_GET['id'];

// Get property details
$property = getAdminPropertyById($property_id);

// Check if property exists
if (!$property) {
    $_SESSION['alert_message'] = "Imóvel não encontrado.";
    $_SESSION['alert_type'] = "error";
    header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
    exit;
}

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
$debugInfo = ''; // For capturing debug information

// Default values from the property data
$defaults = [
    'titulo' => $property['titulo'],
    'para' => $property['para'],
    'id_categoria' => $property['id_categoria'],
    'id_estado' => $property['id_estado'],
    'id_cidade' => $property['id_cidade'],
    'id_bairro' => $property['id_bairro'],
    'valor' => number_format($property['valor'], 2, ',', '.'),
    'quartos' => $property['quartos'],
    'suites' => $property['suites'],
    'banheiros' => $property['banheiros'],
    'salas' => $property['salas'],
    'cozinhas' => $property['cozinhas'],
    'garagem' => $property['garagem'],
    'area_servico' => $property['area_servico'],
    'und_medida' => $property['und_medida'],
    'status' => $property['status'],
    'corretor_responsavel' => $property['corretor_responsavel'],
    'area_total' => $property['area_total'],
    'area_construida' => $property['area_construida'],
    'endereco' => $property['endereco'],
    'descricao' => $property['descricao'],
    'palavras_chaves' => $property['palavras_chaves'],
    'ref' => $property['ref'] ?? ('REF-' . $property_id . date('YmdHis') . rand(100, 999)),
    'classificados' => $property['classificados'] ?? 'nao',
    'medida_frente' => $property['medida_frente'] ?? '',
    'medida_fundo' => $property['medida_fundo'] ?? '',
    'medida_laterais' => $property['medida_laterais'] ?? '',
    'nome_anunciante' => $property['nome_anunciante'],
    'telefone_anunciante' => $property['telefone_anunciante'],
    'destaque' => $property['destaque'],
    'quadra_lote' => $property['quadra_lote']
];

// Define limits for numeric fields to prevent DB overflow errors
$fieldLimits = [
    'valor' => ['max' => 999999999.99, 'message' => 'O valor máximo permitido é R$ 999.999.999,99'],
    'area_total' => ['max' => 9999999, 'message' => 'A área total máxima permitida é 9.999.999'],
    'area_construida' => ['max' => 9999999, 'message' => 'A área construída máxima permitida é 9.999.999'],
];

// Function to sanitize and validate numeric inputs
function sanitizeNumericInput($value, $max = null)
{
    // Remove all non-numeric characters except decimal point
    $value = preg_replace('/[^0-9,.]/', '', $value);
    // Convert comma to dot for decimal
    $value = str_replace(',', '.', $value);
    // Ensure it's a valid number
    if (!is_numeric($value)) {
        return 0;
    }
    $value = (float)$value;
    // Apply maximum limit if provided
    if ($max !== null && $value > $max) {
        return $max;
    }
    return $value;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug - Capture all POST data
    $debugInfo .= "POST Data: " . print_r($_POST, true) . "\n";

    // Validate required fields
    if (empty($_POST['titulo'])) {
        $validationErrors['titulo'] = 'Título é obrigatório';
    }

    // Sanitize and validate numeric values
    // Format currency value (remove R$ and convert comma to dot)
    $valorFormatado = $_POST['valor'] ?? '0';
    $valorFormatado = str_replace('R$ ', '', $valorFormatado);
    $valorFormatado = str_replace('.', '', $valorFormatado);
    $valorFormatado = str_replace(',', '.', $valorFormatado);
    $valorFormatado = sanitizeNumericInput($valorFormatado, $fieldLimits['valor']['max']);

    if ($valorFormatado <= 0) {
        $validationErrors['valor'] = 'Valor deve ser maior que zero';
    } else if ($valorFormatado >= $fieldLimits['valor']['max']) {
        $validationErrors['valor'] = $fieldLimits['valor']['message'];
    }

    // Validate area fields
    if (!empty($_POST['area_total'])) {
        $_POST['area_total'] = sanitizeNumericInput($_POST['area_total'], $fieldLimits['area_total']['max']);
        if ($_POST['area_total'] >= $fieldLimits['area_total']['max']) {
            $validationErrors['area_total'] = $fieldLimits['area_total']['message'];
        }
    }

    if (!empty($_POST['area_construida'])) {
        $_POST['area_construida'] = sanitizeNumericInput($_POST['area_construida'], $fieldLimits['area_construida']['max']);
        if ($_POST['area_construida'] >= $fieldLimits['area_construida']['max']) {
            $validationErrors['area_construida'] = $fieldLimits['area_construida']['message'];
        }
    }

    // Validate other required fields
    if (empty($_POST['id_categoria'])) {
        $validationErrors['id_categoria'] = 'Categoria é obrigatória';
    }

    if (empty($_POST['id_estado'])) {
        $validationErrors['id_estado'] = 'Estado é obrigatório';
    }

    if (empty($_POST['id_cidade'])) {
        $validationErrors['id_cidade'] = 'Cidade é obrigatória';
    }

    if (empty($_POST['id_bairro'])) {
        $validationErrors['id_bairro'] = 'Bairro é obrigatório';
    }

    // Validate and sanitize phone number
    if (!empty($_POST['telefone_anunciante'])) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $_POST['telefone_anunciante']);
        // Validate phone length
        if (strlen($phone) < 10 || strlen($phone) > 11) {
            $validationErrors['telefone_anunciante'] = 'Telefone inválido. Use o formato (XX) XXXXX-XXXX';
        } else {
            // Reformat phone to standard format
            $_POST['telefone_anunciante'] = '(' . substr($phone, 0, 2) . ') ' .
                (strlen($phone) == 11 ?
                    substr($phone, 2, 5) . '-' . substr($phone, 7) :
                    substr($phone, 2, 4) . '-' . substr($phone, 6));
        }
    }

    // Collect form data
    $propertyData = [
        'titulo'               => trim($_POST['titulo'] ?? $defaults['titulo']),
        'para'                 => strtolower($_POST['para'] ?? $defaults['para']),
        'id_categoria'         => (int)($_POST['id_categoria'] ?? $defaults['id_categoria']),
        'id_estado'            => (int)($_POST['id_estado'] ?? $defaults['id_estado']),
        'id_cidade'            => (int)($_POST['id_cidade'] ?? $defaults['id_cidade']),
        'id_bairro'            => (int)($_POST['id_bairro'] ?? $defaults['id_bairro']),
        'valor'                => $valorFormatado,
        'quartos'              => (int)($_POST['quartos'] ?? $defaults['quartos']),
        'suites'               => (int)($_POST['suites'] ?? $defaults['suites']),
        'banheiros'            => (int)($_POST['banheiros'] ?? $defaults['banheiros']),
        'salas'                => (int)($_POST['salas'] ?? $defaults['salas']),
        'cozinhas'             => (int)($_POST['cozinhas'] ?? $defaults['cozinhas']),
        'garagem'              => (int)($_POST['garagem'] ?? $defaults['garagem']),
        'area_servico'         => $_POST['area_servico'] ?? $defaults['area_servico'],
        'area_total'           => sanitizeNumericInput($_POST['area_total'] ?? $defaults['area_total']),
        'area_construida'      => sanitizeNumericInput($_POST['area_construida'] ?? $defaults['area_construida']),
        'und_medida'           => $_POST['und_medida'] ?? $defaults['und_medida'],
        'endereco'             => trim($_POST['endereco'] ?? $defaults['endereco']),
        'descricao'            => trim($_POST['descricao'] ?? $defaults['descricao']),
        'palavras_chaves'      => trim($_POST['palavras_chaves'] ?? $defaults['palavras_chaves']),
        'codigo'               => $property['codigo'], // Keep the original code
        'ref'                  => trim($_POST['ref'] ?? $defaults['ref']),
        'status'               => strtolower($_POST['status'] ?? $defaults['status']),
        'destaque'             => isset($_POST['destaque']) ? 1 : 0,
        'classificados'        => $_POST['classificados'] ?? $defaults['classificados'],
        'quadra_lote'          => trim($_POST['quadra_lote'] ?? $defaults['quadra_lote']),
        'medida_frente'        => trim($_POST['medida_frente'] ?? $defaults['medida_frente']),
        'medida_fundo'         => trim($_POST['medida_fundo'] ?? $defaults['medida_fundo']),
        'medida_laterais'      => trim($_POST['medida_laterais'] ?? $defaults['medida_laterais']),
        'nome_anunciante'      => trim($_POST['nome_anunciante'] ?? $defaults['nome_anunciante']),
        'telefone_anunciante'  => trim($_POST['telefone_anunciante'] ?? $defaults['telefone_anunciante']),
        'corretor_responsavel' => (int)($_POST['corretor_responsavel'] ?? $defaults['corretor_responsavel'])
    ];

    // Debug - Capture processed property data
    $debugInfo .= "Property Data: " . print_r($propertyData, true) . "\n";

    // If there are no validation errors, proceed with updating
    if (empty($validationErrors)) {
        try {
            // Update property using the function from admin_functions.php
            $updateResult = updateProperty($property_id, $propertyData);

            // Debug - Capture the result
            $debugInfo .= "Result of updateProperty(): " . ($updateResult ? "Success" : "Failed") . "\n";

            if ($updateResult) {
                $code = $property['codigo']; // Use existing property code
                $uploadDir = __DIR__ . '/../../uploads/imoveis/';
                
                // Process image uploads if provided
                $uploadedImages = 0;
                $uploadErrors = [];

                // Process main image first (replace 01.jpg if provided)
                if (!empty($_FILES['main_image']['name'])) {
                    $mainImageTmp = $_FILES['main_image']['tmp_name'];
                    $mainImageName = $_FILES['main_image']['name'];

                    if ($_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                        $filename = $code . '01.jpg'; // Always save as 01.jpg

                        // Process the main image
                        $result = processImageUpload($mainImageTmp, $uploadDir . $filename);
                        if ($result) {
                            $uploadedImages++;
                            $debugInfo .= "Updated main image as $filename\n";
                        } else {
                            $uploadErrors[] = "Erro ao processar imagem principal: $mainImageName";
                        }
                    } else if ($_FILES['main_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $uploadErrors[] = "Erro no upload da imagem principal: " . $_FILES['main_image']['error'];
                    }
                }

                // Now process additional images if provided
                if (!empty($_FILES['images']['name'][0])) {
                    foreach ($_FILES['images']['name'] as $i => $origName) {
                        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                            $tmp = $_FILES['images']['tmp_name'][$i];
                            $imageNumber = $i + 2; // Start from 02 since 01 is main image

                            // Only process up to 11 additional images (total 12 with main image)
                            if ($imageNumber <= 12) {
                                $filename = $code . sprintf('%02d', $imageNumber) . '.jpg';

                                // Process the additional image
                                $result = processImageUpload($tmp, $uploadDir . $filename);
                                if ($result) {
                                    $uploadedImages++;
                                } else {
                                    $uploadErrors[] = "Erro ao processar imagem $imageNumber: $origName";
                                }
                            }
                        } else if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                            $uploadErrors[] = "Erro no upload da imagem $i: " . $_FILES['images']['error'][$i];
                        }
                    }
                }

                // Debug - Capture image upload results
                $debugInfo .= "Uploaded/Updated total of $uploadedImages images\n";
                if (!empty($uploadErrors)) {
                    $debugInfo .= "Upload Errors: " . implode(", ", $uploadErrors) . "\n";
                }

                // Set success message
                $_SESSION['alert_message'] = "Imóvel atualizado com sucesso!";
                $_SESSION['alert_type'] = "success";

                // Log debug info if there was any
                if (!empty($debugInfo)) {
                    logError("Property Update Debug Info: $debugInfo", 'DEBUG');
                }

                // Redirect to property admin
                header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
                exit;
            } else {
                // Check if we can get a more specific error from database
                $databaseError = $databaseConnection->errorInfo();
                $error = "Erro ao atualizar imóvel. ";
                if (!empty($databaseError[2])) {
                    $error .= "Erro do banco de dados: " . $databaseError[2];
                } else {
                    $error .= "Verifique os dados e tente novamente.";
                }

                // Log debug info on error
                logError("Property Update Error: $error\nDebug Info: $debugInfo", 'ERROR');
            }
        } catch (Exception $e) {
            $error = "Exceção ao atualizar imóvel: " . $e->getMessage();
            logError("Property Update Exception: " . $e->getMessage() . "\nDebug Info: $debugInfo", 'ERROR');
        }
    } else {
        $error = "Por favor, corrija os erros no formulário antes de continuar.";
        // Log validation errors
        logError("Property Update Validation Errors: " . print_r($validationErrors, true) . "\nDebug Info: $debugInfo", 'WARN');
    }
}

// Helper function to process image uploads - same as in Property_Create.php
function processImageUpload($sourcePath, $destPath)
{
    try {
        // Get image information
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            logError("Failed to get image information for: $sourcePath", 'ERROR');
            return false;
        }

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
                // Unsupported image type, try direct copy
                logError("Unsupported image type for: $sourcePath, trying direct copy", 'WARN');
                return move_uploaded_file($sourcePath, $destPath);
        }

        if (!$sourceImage) {
            logError("Failed to create image resource for: $sourcePath", 'ERROR');
            return false;
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

            if (!$newImage) {
                logError("Failed to create resized image canvas for: $sourcePath", 'ERROR');
                return false;
            }

            // Handle transparency for PNG
            if ($type == IMAGETYPE_PNG) {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            // Resize the image
            $result = imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            if (!$result) {
                logError("Failed to resize image for: $sourcePath", 'ERROR');
                imagedestroy($sourceImage);
                imagedestroy($newImage);
                return false;
            }

            // Save the image
            $result = imagejpeg($newImage, $destPath, 90);

            // Free up memory
            imagedestroy($newImage);
            imagedestroy($sourceImage);

            return $result;
        } else {
            // Save as JPEG without resizing
            $result = imagejpeg($sourceImage, $destPath, 90);

            // Free up memory
            imagedestroy($sourceImage);

            return $result;
        }
    } catch (Exception $e) {
        logError("Image processing exception: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

// Helper function to display validation error
function showValidationError($field)
{
    global $validationErrors;
    if (isset($validationErrors[$field])) {
        return '<div class="validation-error">' . htmlspecialchars($validationErrors[$field]) . '</div>';
    }
    return '';
}

// Helper function to add error class to form fields
function errorClass($field)
{
    global $validationErrors;
    return isset($validationErrors[$field]) ? ' form-control--error' : '';
}

// Helper to get field value combining POST, property data, and defaults
function getFieldValue($field, $default = '')
{
    global $defaults, $property;
    
    if (isset($_POST[$field])) {
        return htmlspecialchars($_POST[$field]);
    } else if (isset($defaults[$field])) {
        return htmlspecialchars($defaults[$field]);
    }
    return $default;
}

// Get existing property images
function getExistingImages($propertyCode) {
    $uploadDir = __DIR__ . '/../../uploads/imoveis/';
    $images = [];
    
    for ($i = 1; $i <= 12; $i++) {
        $number = str_pad($i, 2, '0', STR_PAD_LEFT);
        $filename = $propertyCode . $number . '.jpg';
        $filepath = $uploadDir . $filename;
        
        if (file_exists($filepath)) {
            $images[] = [
                'number' => $i,
                'filename' => $filename,
                'url' => BASE_URL . '/uploads/imoveis/' . $filename,
                'isPrimary' => ($i === 1)
            ];
        }
    }
    
    return $images;
}

// Get existing property images
$existingImages = getExistingImages($property['codigo']);
?>

<!-- Property Update Page -->
<div class="admin-page property-update">
    <div class="admin-page__header">
        <h2 class="admin-page__title">Atualizar Imóvel</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Admin" class="admin-page__back-link">
            <i class="fas fa-arrow-left"></i> Voltar para Lista
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert-message alert-message--error">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php if (MODE === 'Development' && !empty($debugInfo)): ?>
            <div class="debug-info">
                <h3>Informações de Depuração</h3>
                <pre><?= htmlspecialchars($debugInfo) ?></pre>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="admin-card">
        <form action="" method="POST" enctype="multipart/form-data" class="property-form">
            <!-- Hidden field for property ID -->
            <input type="hidden" name="property_id" value="<?= $property_id ?>">

            <!-- Hidden fields for required values that might be missing -->
            <input type="hidden" name="ref" value="<?= getFieldValue('ref') ?>">
            <input type="hidden" name="medida_frente" value="<?= getFieldValue('medida_frente', '0') ?>">
            <input type="hidden" name="medida_fundo" value="<?= getFieldValue('medida_fundo', '0') ?>">
            <input type="hidden" name="medida_laterais" value="<?= getFieldValue('medida_laterais', '0') ?>">
            <input type="hidden" name="classificados" value="<?= getFieldValue('classificados', 'nao') ?>">

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
                                <option value="venda" <?= getFieldValue('para') == 'venda' ? 'selected' : '' ?>>Venda</option>
                                <option value="aluguel" <?= getFieldValue('para') == 'aluguel' ? 'selected' : '' ?>>Aluguel</option>
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
                            <?= showValidationError('id_categoria') ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="valor">Valor <span class="required">*</span></label>
                            <input type="text" id="valor" name="valor" class="form-control money-mask<?= errorClass('valor') ?>" required value="R$ <?= getFieldValue('valor') ?>">
                            <?= showValidationError('valor') ?>
                        </div>

                        <div class="form-group">
                            <label for="status">Status <span class="required">*</span></label>
                            <select id="status" name="status" class="form-control<?= errorClass('status') ?>" required>
                                <option value="ativo" <?= getFieldValue('status') == 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                <option value="inativo" <?= getFieldValue('status') == 'inativo' ? 'selected' : '' ?>>Inativo</option>
                                <option value="vendido" <?= getFieldValue('status') == 'vendido' ? 'selected' : '' ?>>Vendido</option>
                                <option value="alugado" <?= getFieldValue('status') == 'alugado' ? 'selected' : '' ?>>Alugado</option>
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
                            <input type="checkbox" id="destaque" name="destaque" value="1" <?= (getFieldValue('destaque') == 1) ? 'checked' : '' ?>>
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
                                <option value="0" <?= getFieldValue('quartos') == '0' ? 'selected' : '' ?>>Nenhum</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>" <?= getFieldValue('quartos') == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="suites">Suítes</label>
                            <select id="suites" name="suites" class="form-control">
                                <option value="0" <?= getFieldValue('suites') == '0' ? 'selected' : '' ?>>Nenhum</option>
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
                                <option value="0" <?= getFieldValue('banheiros') == '0' ? 'selected' : '' ?>>Nenhum</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= getFieldValue('banheiros') == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="salas">Salas</label>
                            <select id="salas" name="salas" class="form-control">
                                <option value="0" <?= getFieldValue('salas') == '0' ? 'selected' : '' ?>>Nenhum</option>
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
                                <option value="0" <?= getFieldValue('cozinhas') == '0' ? 'selected' : '' ?>>Nenhum</option>
                                <?php for ($i = 1; $i <= 3; $i++): ?>
                                    <option value="<?= $i ?>" <?= getFieldValue('cozinhas') == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="garagem">Vagas de Garagem</label>
                            <select id="garagem" name="garagem" class="form-control">
                                <option value="0" <?= getFieldValue('garagem') == '0' ? 'selected' : '' ?>>Nenhum</option>
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
                            <input type="text" id="area_total" name="area_total" class="form-control numeric-only<?= errorClass('area_total') ?>" value="<?= getFieldValue('area_total') ?>">
                            <?= showValidationError('area_total') ?>
                        </div>

                        <div class="form-group">
                            <label for="area_construida">Área Construída</label>
                            <input type="text" id="area_construida" name="area_construida" class="form-control numeric-only<?= errorClass('area_construida') ?>" value="<?= getFieldValue('area_construida') ?>">
                            <?= showValidationError('area_construida') ?>
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
                            <label for="id_bairro">Bairro <span class="required">*</span></label>
                            <select id="id_bairro" name="id_bairro" class="form-control<?= errorClass('id_bairro') ?>" required>
                                <option value="">Selecione o Bairro</option>
                                <?php foreach ($neighborhoods as $bairro): ?>
                                    <option value="<?= $bairro['id'] ?>"
                                        data-city="<?= $bairro['id_cidade'] ?>"
                                        <?= getFieldValue('id_bairro') == $bairro['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($bairro['bairro']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= showValidationError('id_bairro') ?>
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
                            <input type="text" id="telefone_anunciante" name="telefone_anunciante" class="form-control phone-mask<?= errorClass('telefone_anunciante') ?>" value="<?= getFieldValue('telefone_anunciante') ?>">
                            <?= showValidationError('telefone_anunciante') ?>
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
                    <p class="form-section__desc">Substitua ou adicione fotos ao imóvel</p>

                    <!-- Display existing images section -->
                    <?php if (!empty($existingImages)): ?>
                    <div class="current-images">
                        <h4>Imagens Atuais do Imóvel</h4>
                        <p class="form-help">Para substituir uma imagem, utilize os campos abaixo para enviar novas imagens.</p>
                        
                        <div class="image-gallery">
                            <?php foreach ($existingImages as $image): ?>
                                <div class="image-preview<?= $image['isPrimary'] ? ' image-preview--primary' : '' ?>">
                                    <img src="<?= $image['url'] ?>" alt="Imagem <?= $image['number'] ?> do imóvel">
                                    <div class="image-number"><?= $image['number'] ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                        <div class="alert-message alert-message--warning">
                            Este imóvel não possui imagens. Por favor, adicione pelo menos uma imagem principal.
                        </div>
                    <?php endif; ?>

                    <!-- New separate field for main image -->
                    <div class="form-row" style="margin-top: 20px;">
                        <div class="form-group form-group--large">
                            <label for="main_image">Substituir Imagem Principal</label>
                            <input type="file" id="main_image" name="main_image" class="form-control-file<?= errorClass('main_image') ?>" accept="image/*">
                            <?= showValidationError('main_image') ?>
                            <div class="form-help">
                                <p>Esta imagem será usada como miniatura e como primeira imagem na galeria.</p>
                                <p>Recomendamos uma imagem de boa qualidade, de preferência na horizontal.</p>
                                <p>Tamanho máximo: 5MB.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Main image preview container -->
                    <div class="main-image-preview" id="mainImagePreview" style="margin-bottom: 20px;"></div>

                    <!-- Existing field for additional images -->
                    <div class="form-row" style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                        <div class="form-group form-group--large">
                            <label for="images">Adicionar/Substituir Imagens Adicionais</label>
                            <input type="file" id="images" name="images[]" class="form-control-file" multiple accept="image/*">
                            <div class="form-help">
                                <p>São permitidas até 11 imagens adicionais no formato JPG, PNG ou GIF.</p>
                                <p>As novas imagens serão adicionadas em sequência após as existentes.</p>
                                <p>Tamanho máximo por arquivo: 5MB.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Additional images preview container -->
                    <div class="image-preview" id="imagePreview"></div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-button primary-button--large">
                    <i class="fas fa-save"></i> Atualizar Imóvel
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

    // Field validation functions
    window.validators = {
        // Validate currency value
        valor: function(value) {
            // Remove formatting
            let numericValue = value.replace(/[^\d,]/g, '').replace(',', '.');
            if (parseFloat(numericValue) <= 0) {
                return 'Valor deve ser maior que zero';
            }
            if (parseFloat(numericValue) > 999999999.99) {
                return 'O valor máximo permitido é R$ 999.999.999,99';
            }
            return null;
        },

        // Validate numeric area fields
        area_total: function(value) {
            if (value !== '' && isNaN(value.replace(',', '.')) || parseFloat(value.replace(',', '.')) < 0) {
                return 'Por favor, informe um valor numérico válido';
            }
            if (parseFloat(value.replace(',', '.')) > 9999999) {
                return 'A área máxima permitida é 9.999.999';
            }
            return null;
        },

        area_construida: function(value) {
            if (value !== '' && (isNaN(value.replace(',', '.')) || parseFloat(value.replace(',', '.')) < 0)) {
                return 'Por favor, informe um valor numérico válido';
            }
            if (parseFloat(value.replace(',', '.')) > 9999999) {
                return 'A área máxima permitida é 9.999.999';
            }
            return null;
        },

        // Validate phone number
        telefone_anunciante: function(value) {
            if (value === '') return null;

            // Remove all non-numeric characters
            const phone = value.replace(/\D/g, '');
            if (phone.length < 10 || phone.length > 11) {
                return 'Telefone inválido. Use o formato (XX) XXXXX-XXXX';
            }
            return null;
        }
    };

    // Apply validation to form fields
    // Fields to validate on input
    const fieldsToValidate = [
        'valor', 'area_total', 'area_construida', 'telefone_anunciante'
    ];

    fieldsToValidate.forEach(field => {
        const element = document.getElementById(field);
        if (element) {
            element.addEventListener('input', function() {
                validateField(field, this.value);
            });

            // Also validate on blur for good measure
            element.addEventListener('blur', function() {
                validateField(field, this.value);
            });
        }
    });

    // Function to validate a field and show/hide error message
    function validateField(fieldName, value) {
        const element = document.getElementById(fieldName);
        if (!element) return;

        // Validate using appropriate validator
        if (window.validators[fieldName]) {
            const errorMessage = window.validators[fieldName](value);

            // Get or create error element
            let errorElement = element.parentNode.querySelector('.validation-error');
            if (!errorElement && errorMessage) {
                errorElement = document.createElement('div');
                errorElement.className = 'validation-error';
                element.parentNode.appendChild(errorElement);
            }

            // Show or hide error
            if (errorMessage) {
                element.classList.add('form-control--error');
                if (errorElement) {
                    errorElement.textContent = errorMessage;
                }
            } else {
                element.classList.remove('form-control--error');
                if (errorElement) {
                    errorElement.remove();
                }
            }
        }
    }

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
            'area_total': 'details',
            'area_construida': 'details',
            'telefone_anunciante': 'attributes',
            'corretor_responsavel': 'attributes',
            'main_image': 'images'
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

    // Main image preview functionality
    const mainImageInput = document.getElementById('main_image');
    const mainImagePreview = document.getElementById('mainImagePreview');

    if (mainImageInput) {
        mainImageInput.addEventListener('change', function() {
            mainImagePreview.innerHTML = '';

            if (this.files && this.files[0]) {
                const file = this.files[0];

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        const previewContainer = document.createElement('div');
                        previewContainer.className = 'main-image-container';
                        previewContainer.innerHTML = `
    <h4 class="preview-title">Nova Imagem Principal</h4>
    <div class="main-image-preview__item">
        <img src="${e.target.result}" alt="Preview da Imagem Principal" class="main-image-preview__img">
        <div class="main-image-preview__filename">${file.name}</div>
    </div>
`;
                        mainImagePreview.appendChild(previewContainer);
                    };

                    reader.readAsDataURL(file);
                }
            }
        });
    }

    // Additional images preview
    const additionalImagesInput = document.getElementById('images');
    const additionalImagesPreview = document.getElementById('imagePreview');

    if (additionalImagesInput) {
        additionalImagesInput.addEventListener('change', function() {
            additionalImagesPreview.innerHTML = '';

            if (this.files.length > 0) {
                // Create header
                const header = document.createElement('div');
                header.className = 'image-preview__header';
                header.innerHTML = `<h4>Preview das Novas Imagens Adicionais (${Math.min(this.files.length, 11)} de 11 máximo)</h4>`;
                additionalImagesPreview.appendChild(header);

                // Create container for images
                const container = document.createElement('div');
                container.className = 'image-preview__grid';
                additionalImagesPreview.appendChild(container);

                for (let i = 0; i < Math.min(this.files.length, 11); i++) {
                    const file = this.files[i];

                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        const imgContainer = document.createElement('div');
                        imgContainer.className = 'image-preview__item';

                        reader.onload = function(e) {
                            imgContainer.innerHTML = `
        <div class="image-preview__number">${i + 2}</div>
        <img src="${e.target.result}" alt="Preview" class="image-preview__img">
        <div class="image-preview__filename">${file.name}</div>
    `;
                        };

                        reader.readAsDataURL(file);
                        container.appendChild(imgContainer);
                    }
                }
            }
        });
    }

    // Improved money mask for price input
    const moneyInput = document.querySelector('.money-mask');
    if (moneyInput) {
        // Function to format a number as Brazilian currency
        function formatCurrency(value) {
            // Convert to string and ensure it's only digits
            value = String(value).replace(/\D/g, '');
            
            // Handle empty value
            if (value === '') return 'R$ 0,00';
            
            // Convert to number (in cents)
            const numericValue = parseInt(value, 10);
            
            // Format as currency with 2 decimal places
            const formattedValue = (numericValue / 100).toFixed(2);
            
            // Replace dot with comma for decimal separator
            let currencyValue = formattedValue.replace('.', ',');
            
            // Add thousands separators
            currencyValue = currencyValue.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            
            // Add currency symbol
            return `R$ ${currencyValue}`;
        }

        // Handle input event
        moneyInput.addEventListener('input', function() {
            const rawValue = this.value.replace(/\D/g, '');
            
            // Prevent exceeding maximum value (11 digits)
            if (rawValue.length > 11) {
                this.value = formatCurrency(rawValue.substring(0, 11));
            } else {
                this.value = formatCurrency(rawValue);
            }
        });

        // Handle focus event - select all text when focused
        moneyInput.addEventListener('focus', function() {
            this.select();
        });

        // Handle blur event - ensure proper formatting when leaving the field
        moneyInput.addEventListener('blur', function() {
            if (this.value === '' || this.value === 'R$ ' || this.value === 'R$ 0,00') {
                this.value = 'R$ 0,00';
            } else {
                // Make sure the value is properly formatted when leaving the field
                const rawValue = this.value.replace(/\D/g, '');
                this.value = formatCurrency(rawValue);
            }
        });

        // Initial formatting if value exists
        if (moneyInput.value && !moneyInput.value.startsWith('R$ ')) {
            moneyInput.value = 'R$ ' + moneyInput.value;
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

    // Add numeric fields validation
    const numericInputs = document.querySelectorAll('.numeric-only');
    if (numericInputs.length > 0) {
        numericInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                // Allow only numbers and comma
                this.value = this.value.replace(/[^0-9,]/g, '');

                // Ensure only one comma
                const commaCount = (this.value.match(/,/g) || []).length;
                if (commaCount > 1) {
                    this.value = this.value.replace(/,/g, function(match, index, string) {
                        return index === string.indexOf(',') ? ',' : '';
                    });
                }

                // Validate the field
                if (this.id && window.validators[this.id]) {
                    validateField(this.id, this.value);
                }
            });
        });
    }

    // Form Validation
    const form = document.querySelector('.property-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let hasErrors = false;

            // Validate all fields that have validators
            Object.keys(window.validators).forEach(fieldName => {
                const element = document.getElementById(fieldName);
                if (element) {
                    const errorMessage = window.validators[fieldName](element.value);
                    if (errorMessage) {
                        hasErrors = true;
                        element.classList.add('form-control--error');

                        // Get or create error element
                        let errorElement = element.parentNode.querySelector('.validation-error');
                        if (!errorElement) {
                            errorElement = document.createElement('div');
                            errorElement.className = 'validation-error';
                            element.parentNode.appendChild(errorElement);
                        }

                        errorElement.textContent = errorMessage;
                    }
                }
            });

            // Validate required fields
            const requiredFields = [{
                    id: 'titulo',
                    message: 'Título é obrigatório'
                },
                {
                    id: 'valor',
                    message: 'Valor deve ser preenchido'
                },
                {
                    id: 'id_estado',
                    message: 'Estado é obrigatório'
                },
                {
                    id: 'id_cidade',
                    message: 'Cidade é obrigatória'
                },
                {
                    id: 'id_bairro',
                    message: 'Bairro é obrigatório'
                }
            ];

            requiredFields.forEach(field => {
                const element = document.getElementById(field.id);
                if (element && !element.value) {
                    hasErrors = true;
                    element.classList.add('form-control--error');

                    // Get or create error element
                    let errorElement = element.parentNode.querySelector('.validation-error');
                    if (!errorElement) {
                        errorElement = document.createElement('div');
                        errorElement.className = 'validation-error';
                        element.parentNode.appendChild(errorElement);
                    }

                    errorElement.textContent = field.message;
                }
            });

            if (hasErrors) {
                e.preventDefault();

                // Show alert message at the top of the form
                const alertMessage = document.createElement('div');
                alertMessage.className = 'alert-message alert-message--error';
                alertMessage.textContent = 'Por favor, corrija os erros no formulário antes de continuar.';

                // Insert at the top of the form
                const firstElement = form.firstChild;
                form.insertBefore(alertMessage, firstElement);

                // Scroll to the top of the form
                alertMessage.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                // Remove the alert after 5 seconds
                setTimeout(() => {
                    alertMessage.remove();
                }, 5000);

                // Scroll to the first error
                const firstError = document.querySelector('.form-control--error');
                if (firstError) {
                    // Find which tab contains the error
                    let section = firstError.closest('.form-section');
                    if (section) {
                        let tabId = section.getAttribute('data-section');
                        activateTab(tabId);

                        // Wait a bit for tab to activate then scroll to error
                        setTimeout(() => { firstError.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                            firstError.focus();
                        }, 100);
                    }
                }
            }
        });
    }

    // Filter cities based on selected state
    const stateSelect = document.getElementById('id_estado');
    const citySelect = document.getElementById('id_cidade');
    const bairroSelect = document.getElementById('id_bairro');

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

    // Trigger state change on page load to populate dropdowns
    if (stateSelect && stateSelect.value) {
        stateSelect.dispatchEvent(new Event('change'));
    }
});
</script>