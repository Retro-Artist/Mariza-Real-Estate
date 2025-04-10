<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Check if property ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID do imóvel não especificado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
    exit;
}

$property_id = (int)$_GET['id'];

// Initialize variables
$error = '';
$success = false;

// Get property data
$property = getAdminPropertyById($property_id);

if (!$property) {
    $_SESSION['alert_message'] = 'Imóvel não encontrado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
    exit;
}

// Get all categories, states, cities, neighborhoods for form selects
$categories = getAdminCategories();
$states = getStates();
$cities = [];
$neighborhoods = [];

// Get cities and neighborhoods if state/city is selected
if (!empty($property['id_estado'])) {
    $cities = getCitiesByState($property['id_estado']);
}

if (!empty($property['id_cidade'])) {
    $neighborhoods = getNeighborhoodsByCity($property['id_cidade']);
}

// Get all users for corretor_responsavel select
$usersList = getAdminUsers();
$users = $usersList['users'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (empty($_POST['titulo']) || empty($_POST['para']) || empty($_POST['id_categoria'])) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        // Prepare property data
        $valor = str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor']);
        
        $propertyData = [
            'titulo' => $_POST['titulo'],
            'para' => $_POST['para'],
            'id_categoria' => $_POST['id_categoria'],
            'id_estado' => $_POST['id_estado'] ? $_POST['id_estado'] : null,
            'id_cidade' => $_POST['id_cidade'] ? $_POST['id_cidade'] : null,
            'id_bairro' => $_POST['id_bairro'] ? $_POST['id_bairro'] : null,
            'valor' => (float)$valor,
            'quartos' => $_POST['quartos'],
            'suites' => $_POST['suites'],
            'salas' => $_POST['salas'],
            'cozinhas' => $_POST['cozinhas'],
            'banheiros' => $_POST['banheiros'],
            'garagem' => $_POST['garagem'],
            'area_servico' => $_POST['area_servico'],
            'area_total' => $_POST['area_total'],
            'area_construida' => $_POST['area_construida'],
            'und_medida' => $_POST['und_medida'],
            'endereco' => $_POST['endereco'],
            'descricao' => $_POST['descricao'],
            'destaque' => isset($_POST['destaque']) ? 1 : 0,
            'status' => $_POST['status'],
            'quadra_lote' => $_POST['quadra_lote'] ?? '',
            'corretor_responsavel' => $_POST['corretor_responsavel'] ? $_POST['corretor_responsavel'] : 0,
            'nome_anunciante' => $_POST['nome_anunciante'] ?? '',
            'telefone_anunciante' => $_POST['telefone_anunciante'] ?? '',
            'palavras_chaves' => $_POST['palavras_chaves'] ?? ''
        ];

        // Update property
        $result = updateProperty($property_id, $propertyData);

        if ($result) {
            // Handle image uploads if files were submitted
            if (!empty($_FILES['property_images']['name'][0])) {
                $uploadDir = __DIR__ . '/../../uploads/imoveis/';
                
                // Make sure the directory exists
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Process each uploaded file
                foreach ($_FILES['property_images']['name'] as $key => $name) {
                    if ($_FILES['property_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES['property_images']['tmp_name'][$key];
                        $image_number = str_pad($key + 1, 2, '0', STR_PAD_LEFT); // 01, 02, etc.
                        $filename = $_POST['codigo'] . $image_number . '.jpg';
                        
                        // Move and process the uploaded image
                        if (move_uploaded_file($tmp_name, $uploadDir . $filename)) {
                            // Resize image if needed (optional)
                        }
                    }
                }
            }
            
            $success = true;
            $_SESSION['alert_message'] = 'Imóvel atualizado com sucesso!';
            $_SESSION['alert_type'] = 'success';
            
            // Redirect to property admin page
            header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
            exit;
        } else {
            $error = 'Ocorreu um erro ao atualizar o imóvel. Verifique se o código não está sendo usado por outro imóvel.';
        }
    }
    
    // If there was an error, update the property data with POST values for re-populating the form
    if (!empty($error)) {
        foreach ($_POST as $key => $value) {
            if (isset($property[$key])) {
                $property[$key] = $value;
            }
        }
    }
}
?>

<div class="admin-page property-update">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Editar Imóvel</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Admin" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert-message alert-message--error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <!-- Property Form -->
    <form method="POST" action="" class="admin-form" enctype="multipart/form-data">
        <!-- Basic Information -->
        <div class="form-section">
            <h3 class="form-section__title">Informações Básicas</h3>
            
            <div class="form-row">
                <div class="form-group form-group--half">
                    <label for="titulo">Título do Imóvel <span class="required">*</span></label>
                    <input type="text" id="titulo" name="titulo" class="form-control" 
                           value="<?= htmlspecialchars($property['titulo']) ?>" required>
                </div>
                
                <div class="form-group form-group--half">
                    <label for="para">Tipo de Anúncio <span class="required">*</span></label>
                    <select id="para" name="para" class="form-control" required>
                        <option value="venda" <?= $property['para'] === 'venda' ? 'selected' : '' ?>>Venda</option>
                        <option value="aluguel" <?= $property['para'] === 'aluguel' ? 'selected' : '' ?>>Aluguel</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--half">
                    <label for="id_categoria">Categoria <span class="required">*</span></label>
                    <select id="id_categoria" name="id_categoria" class="form-control" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= $property['id_categoria'] == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['categoria']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group form-group--half">
                    <label for="valor">Valor <span class="required">*</span></label>
                    <input type="text" id="valor" name="valor" class="form-control" 
                           value="<?= formatCurrency($property['valor']) ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--half">
                    <label for="corretor_responsavel">Corretor Responsável</label>
                    <select id="corretor_responsavel" name="corretor_responsavel" class="form-control">
                        <option value="">Selecione...</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= $property['corretor_responsavel'] == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group form-group--half">
                    <label for="status">Status <span class="required">*</span></label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="ativo" <?= $property['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inativo" <?= $property['status'] === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                        <option value="vendido" <?= $property['status'] === 'vendido' ? 'selected' : '' ?>>Vendido</option>
                        <option value="alugado" <?= $property['status'] === 'alugado' ? 'selected' : '' ?>>Alugado</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--half">
                    <label for="nome_anunciante">Nome do Anunciante</label>
                    <input type="text" id="nome_anunciante" name="nome_anunciante" class="form-control" 
                           value="<?= htmlspecialchars($property['nome_anunciante']) ?>">
                </div>
                
                <div class="form-group form-group--half">
                    <label for="telefone_anunciante">Telefone do Anunciante</label>
                    <input type="text" id="telefone_anunciante" name="telefone_anunciante" class="form-control" 
                           value="<?= htmlspecialchars($property['telefone_anunciante']) ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--half">
                    <div class="checkbox-container">
                        <input type="checkbox" id="destaque" name="destaque" value="1" <?= $property['destaque'] ? 'checked' : '' ?>>
                        <label for="destaque">Destacar na Página Inicial</label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Location Information -->
        <div class="form-section">
            <h3 class="form-section__title">Localização</h3>
            
            <div class="form-row">
                <div class="form-group form-group--half">
                    <label for="id_estado">Estado</label>
                    <select id="id_estado" name="id_estado" class="form-control estado-select">
                        <option value="">Selecione...</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?= $state['id'] ?>" <?= $property['id_estado'] == $state['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($state['nome']) ?> (<?= $state['uf'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group form-group--half">
                    <label for="id_cidade">Cidade</label>
                    <select id="id_cidade" name="id_cidade" class="form-control cidade-select" <?= empty($property['id_estado']) ? 'disabled' : '' ?>>
                        <option value="">Selecione...</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= $city['id'] ?>" <?= $property['id_cidade'] == $city['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($city['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--half">
                    <label for="id_bairro">Bairro</label>
                    <select id="id_bairro" name="id_bairro" class="form-control" <?= empty($property['id_cidade']) ? 'disabled' : '' ?>>
                        <option value="">Selecione...</option>
                        <?php foreach ($neighborhoods as $neighborhood): ?>
                            <option value="<?= $neighborhood['id'] ?>" <?= $property['id_bairro'] == $neighborhood['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($neighborhood['bairro']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group form-group--half">
                    <label for="endereco">Endereço</label>
                    <input type="text" id="endereco" name="endereco" class="form-control" 
                           value="<?= htmlspecialchars($property['endereco']) ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--half">
                    <label for="quadra_lote">Quadra/Lote</label>
                    <input type="text" id="quadra_lote" name="quadra_lote" class="form-control" 
                           value="<?= htmlspecialchars($property['quadra_lote']) ?>">
                </div>
            </div>
        </div>
        
        <!-- Property Details -->
        <div class="form-section">
            <h3 class="form-section__title">Detalhes do Imóvel</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="quartos">Quartos</label>
                    <select id="quartos" name="quartos" class="form-control">
                        <option value="Nenhum" <?= $property['quartos'] === 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= $property['quartos'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="suites">Suítes</label>
                    <select id="suites" name="suites" class="form-control">
                        <option value="Nenhum" <?= $property['suites'] === 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= $property['suites'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="banheiros">Banheiros</label>
                    <select id="banheiros" name="banheiros" class="form-control">
                        <option value="Nenhum" <?= $property['banheiros'] === 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= $property['banheiros'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="salas">Salas</label>
                    <select id="salas" name="salas" class="form-control">
                        <option value="Nenhum" <?= $property['salas'] === 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= $property['salas'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="cozinhas">Cozinhas</label>
                    <select id="cozinhas" name="cozinhas" class="form-control">
                        <option value="Nenhum" <?= $property['cozinhas'] === 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= $property['cozinhas'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="garagem">Vagas de Garagem</label>
                    <select id="garagem" name="garagem" class="form-control">
                        <option value="Nenhum" <?= $property['garagem'] === 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= $property['garagem'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="area_servico">Área de Serviço</label>
                    <select id="area_servico" name="area_servico" class="form-control">
                        <option value="Não" <?= $property['area_servico'] === 'Não' ? 'selected' : '' ?>>Não</option>
                        <option value="Sim" <?= $property['area_servico'] === 'Sim' ? 'selected' : '' ?>>Sim</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="area_total">Área Total</label>
                    <input type="text" id="area_total" name="area_total" class="form-control" 
                           value="<?= htmlspecialchars($property['area_total']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="area_construida">Área Construída</label>
                    <input type="text" id="area_construida" name="area_construida" class="form-control" 
                           value="<?= htmlspecialchars($property['area_construida']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="und_medida">Unidade de Medida</label>
                    <select id="und_medida" name="und_medida" class="form-control">
                        <option value="m²" <?= $property['und_medida'] === 'm²' ? 'selected' : '' ?>>m²</option>
                        <option value="ha" <?= $property['und_medida'] === 'ha' ? 'selected' : '' ?>>ha</option>
                        <option value="alqueire" <?= $property['und_medida'] === 'alqueire' ? 'selected' : '' ?>>alqueire</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Property Description -->
        <div class="form-section">
            <h3 class="form-section__title">Descrição e Palavras-chave</h3>
            
            <div class="form-row">
                <div class="form-group form-group--full">
                    <label for="descricao">Descrição Detalhada</label>
                    <textarea id="descricao" name="descricao" class="form-control" rows="8"><?= htmlspecialchars($property['descricao']) ?></textarea>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--full">
                    <label for="palavras_chaves">Palavras-chave</label>
                    <textarea id="palavras_chaves" name="palavras_chaves" class="form-control" rows="2"><?= htmlspecialchars($property['palavras_chaves']) ?></textarea>
                    <small class="form-text">Palavras-chave para melhorar a busca (separadas por vírgula)</small>
                </div>
            </div>
        </div>
        
        <!-- Property Images -->
        <div class="form-section">
            <h3 class="form-section__title">Imagens do Imóvel</h3>
            
            <!-- Current Images (if any) -->
            <div class="current-images">
                <h4>Imagens Atuais</h4>
                <p class="form-text">Para substituir as imagens atuais, adicione novas imagens abaixo.</p>
                
                <div class="image-gallery">
                    <?php 
                    $uploadDir = BASE_URL . '/uploads/imoveis/';
                    $hasImages = false;
                    
                    for ($i = 1; $i <= 12; $i++): 
                        $number = str_pad($i, 2, '0', STR_PAD_LEFT);
                        $imageName = $property['codigo'] . $number . '.jpg';
                        $imagePath = __DIR__ . '/../../uploads/imoveis/' . $imageName;
                        
                        if (file_exists($imagePath)):
                            $hasImages = true;
                    ?>
                        <div class="image-preview">
                            <img src="<?= $uploadDir . $imageName ?>?v=<?= time() ?>" alt="Imagem <?= $i ?>">
                            <div class="image-number"><?= $i ?></div>
                        </div>
                    <?php 
                        endif;
                    endfor; 
                    
                    if (!$hasImages):
                    ?>
                        <p>Nenhuma imagem encontrada para este imóvel.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Upload New Images -->
            <div class="form-row">
                <div class="form-group form-group--full">
                    <label for="property_images">Adicionar/Substituir Imagens</label>
                    <input type="file" id="property_images" name="property_images[]" class="form-control-file" multiple accept="image/*">
                    <small class="form-text">
                        Você pode selecionar até 12 imagens. A ordem das imagens será mantida.
                        A primeira imagem será usada como imagem principal na listagem.
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
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