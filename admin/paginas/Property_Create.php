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
    'ref' => '',
    'codigo' => '',
    'id_estado' => '',
    'id_cidade' => '',
    'id_bairro' => '',
    'endereco' => '',
    'quartos' => '',
    'suites' => '',
    'banheiros' => '',
    'salas' => '',
    'cozinhas' => '',
    'garagem' => '',
    'area_servico' => '',
    'area_total' => '',
    'area_construida' => '',
    'und_medida' => 'm²',
    'valor' => '',
    'quadra_lote' => '',
    'descricao' => '',
    'palavras_chaves' => '',
    'corretor_responsavel' => '',
    'nome_anunciante' => '',
    'telefone_anunciante' => '',
    'destaque' => 0,
    'classificados' => 'Não'
];

// Get lists for select boxes
$categorias = getAdminCategories();
$estados = getStates();
$cidades = [];
$bairros = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'titulo' => trim($_POST['titulo'] ?? ''),
        'para' => trim($_POST['para'] ?? 'venda'),
        'id_categoria' => trim($_POST['id_categoria'] ?? ''),
        'ref' => trim($_POST['ref'] ?? ''),
        'codigo' => trim($_POST['codigo'] ?? ''),
        'id_estado' => trim($_POST['id_estado'] ?? ''),
        'id_cidade' => trim($_POST['id_cidade'] ?? ''),
        'id_bairro' => trim($_POST['id_bairro'] ?? ''),
        'endereco' => trim($_POST['endereco'] ?? ''),
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
        'valor' => trim($_POST['valor'] ?? ''),
        'quadra_lote' => trim($_POST['quadra_lote'] ?? ''),
        'descricao' => trim($_POST['descricao'] ?? ''),
        'palavras_chaves' => trim($_POST['palavras_chaves'] ?? ''),
        'corretor_responsavel' => trim($_POST['corretor_responsavel'] ?? ''),
        'nome_anunciante' => trim($_POST['nome_anunciante'] ?? ''),
        'telefone_anunciante' => trim($_POST['telefone_anunciante'] ?? ''),
        'destaque' => isset($_POST['destaque']) ? 1 : 0,
        'classificados' => trim($_POST['classificados'] ?? 'Não'),
        'status' => 'ativo'
    ];
    
    // Preprocess the data
    $formData['valor'] = str_replace(['R$', '.', ','], ['', '', '.'], $formData['valor']);
    
    // Validate form data
    if (empty($formData['titulo'])) {
        $error = 'O título do imóvel é obrigatório.';
    } elseif (empty($formData['codigo'])) {
        $error = 'O código do imóvel é obrigatório.';
    } elseif (empty($formData['id_categoria'])) {
        $error = 'A categoria do imóvel é obrigatória.';
    } elseif (empty($formData['valor'])) {
        $error = 'O valor do imóvel é obrigatório.';
    } else {
        // Create property using function from admin_functions.php
        $newPropertyId = createProperty($formData);
        
        if ($newPropertyId) {
            // Process image uploads
            $uploadDir = __DIR__ . '/../../uploads/imoveis/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Process uploaded images
            for ($i = 1; $i <= 12; $i++) {
                $uploadFile = $_FILES['imagem'.$i] ?? null;
                
                if ($uploadFile && $uploadFile['error'] === 0) {
                    $imageNumber = str_pad($i, 2, '0', STR_PAD_LEFT); // 01, 02, etc.
                    $fileName = $formData['codigo'] . $imageNumber . '.jpg';
                    $filePath = $uploadDir . $fileName;
                    
                    // Handle image upload based on type
                    $imageType = exif_imagetype($uploadFile['tmp_name']);
                    
                    if ($imageType === IMAGETYPE_JPEG) {
                        // If it's already a JPEG, just move it
                        move_uploaded_file($uploadFile['tmp_name'], $filePath);
                    } else {
                        // If it's another format, convert to JPEG
                        $image = null;
                        
                        switch ($imageType) {
                            case IMAGETYPE_GIF:
                                $image = imagecreatefromgif($uploadFile['tmp_name']);
                                break;
                            case IMAGETYPE_PNG:
                                $image = imagecreatefrompng($uploadFile['tmp_name']);
                                break;
                        }
                        
                        if ($image) {
                            $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
                            imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                            imagealphablending($bg, true);
                            imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                            imagedestroy($image);
                            imagejpeg($bg, $filePath, 90);
                            imagedestroy($bg);
                        }
                    }
                }
            }
            
            // Set success message and redirect
            $_SESSION['alert_message'] = 'Imóvel cadastrado com sucesso!';
            $_SESSION['alert_type'] = 'success';
            
            header('Location: ' . BASE_URL . '/admin/index.php?page=Property_Admin');
            exit;
        } else {
            $error = 'Ocorreu um erro ao cadastrar o imóvel. Verifique se o código já não está sendo utilizado.';
        }
    }
    
    // If there's an error, get city and neighborhood lists for form repopulation
    if (!empty($formData['id_estado'])) {
        $cidades = getCitiesByState($formData['id_estado']);
    }
    
    if (!empty($formData['id_cidade'])) {
        $bairros = getNeighborhoodsByCity($formData['id_cidade']);
    }
}
?>

<div class="admin-page property-create">
    <div class="admin-page__header">
        <h2 class="admin-page__title">Cadastrar Novo Imóvel</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Admin" class="cancel-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert-message alert-message--error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" class="admin-form">
        <!-- Informações Principais -->
        <div class="form-section">
            <h3 class="form-section__title">Informações Principais</h3>
            
            <div class="form-row">
                <div class="form-group form-group--half">
                    <label for="titulo">Título <span class="required">*</span></label>
                    <input type="text" id="titulo" name="titulo" class="form-control" required
                           value="<?= htmlspecialchars($formData['titulo']) ?>">
                </div>
                
                <div class="form-group form-group--half">
                    <label for="codigo">Código <span class="required">*</span></label>
                    <input type="text" id="codigo" name="codigo" class="form-control" required
                           value="<?= htmlspecialchars($formData['codigo']) ?>">
                    <small class="form-text">Código único para identificação do imóvel.</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--half">
                    <label for="ref">Referência</label>
                    <input type="text" id="ref" name="ref" class="form-control"
                           value="<?= htmlspecialchars($formData['ref']) ?>">
                </div>
                
                <div class="form-group form-group--half">
                    <label for="id_categoria">Categoria <span class="required">*</span></label>
                    <select id="id_categoria" name="id_categoria" class="form-control" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria['id'] ?>" <?= $formData['id_categoria'] == $categoria['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($categoria['categoria']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--half">
                    <label>Tipo <span class="required">*</span></label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="para" value="venda" <?= $formData['para'] === 'venda' ? 'checked' : '' ?>>
                            Venda
                        </label>
                        <label>
                            <input type="radio" name="para" value="aluguel" <?= $formData['para'] === 'aluguel' ? 'checked' : '' ?>>
                            Aluguel
                        </label>
                    </div>
                </div>
                
                <div class="form-group form-group--half">
                    <label for="valor">Valor <span class="required">*</span></label>
                    <input type="text" id="valor" name="valor" class="form-control money-mask" required
                           value="<?= htmlspecialchars($formData['valor']) ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--half">
                    <label for="classificados">Anúncio Classificados?</label>
                    <select id="classificados" name="classificados" class="form-control">
                        <option value="Não" <?= $formData['classificados'] === 'Não' ? 'selected' : '' ?>>Não</option>
                        <option value="Sim" <?= $formData['classificados'] === 'Sim' ? 'selected' : '' ?>>Sim</option>
                    </select>
                </div>
                
                <div class="form-group form-group--half">
                    <label for="destaque">Destacar no Site?</label>
                    <div class="checkbox-container">
                        <input type="checkbox" id="destaque" name="destaque" value="1" 
                               <?= $formData['destaque'] ? 'checked' : '' ?>>
                        <span>Sim, destacar este imóvel no site</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Localização -->
        <div class="form-section">
            <h3 class="form-section__title">Localização</h3>
            
            <div class="form-row">
                <div class="form-group form-group--half">
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
                
                <div class="form-group form-group--half">
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
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--half">
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
                
                <div class="form-group form-group--half">
                    <label for="endereco">Endereço</label>
                    <input type="text" id="endereco" name="endereco" class="form-control"
                           value="<?= htmlspecialchars($formData['endereco']) ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--half">
                    <label for="quadra_lote">Quadra/Lote</label>
                    <input type="text" id="quadra_lote" name="quadra_lote" class="form-control"
                           value="<?= htmlspecialchars($formData['quadra_lote']) ?>">
                </div>
            </div>
        </div>
        
        <!-- Características -->
        <div class="form-section">
            <h3 class="form-section__title">Características</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="quartos">Quartos</label>
                    <select id="quartos" name="quartos" class="form-control">
                        <option value="Nenhum" <?= $formData['quartos'] === 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= $formData['quartos'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="suites">Suítes</label>
                    <select id="suites" name="suites" class="form-control">
                        <option value="Nenhum" <?= $formData['suites'] === 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= $formData['suites'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="banheiros">Banheiros</label>
                    <select id="banheiros" name="banheiros" class="form-control">
                        <option value="Nenhum" <?= $formData['banheiros'] === 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= $formData['banheiros'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="salas">Salas</label>
                    <select id="salas" name="salas" class="form-control">
                        <option value="Nenhum" <?= $formData['salas'] === 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= $formData['salas'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="cozinhas">Cozinhas</label>
                    <select id="cozinhas" name="cozinhas" class="form-control">
                        <option value="Nenhum" <?= $formData['cozinhas'] === 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= $formData['cozinhas'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="garagem">Vagas de Garagem</label>
                    <select id="garagem" name="garagem" class="form-control">
                        <option value="Nenhum" <?= $formData['garagem'] === 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= $formData['garagem'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="area_servico">Área de Serviço</label>
                    <select id="area_servico" name="area_servico" class="form-control">
                        <option value="Nenhum" <?= $formData['area_servico'] === 'Nenhum' ? 'selected' : '' ?>>Nenhum</option>
                        <option value="Sim" <?= $formData['area_servico'] === 'Sim' ? 'selected' : '' ?>>Sim</option>
                    </select>
                </div>
                
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
                        <option value="ha" <?= $formData['und_medida'] === 'ha' ? 'selected' : '' ?>>ha</option>
                        <option value="alq" <?= $formData['und_medida'] === 'alq' ? 'selected' : '' ?>>alq</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Descrição -->
        <div class="form-section">
            <h3 class="form-section__title">Descrição</h3>
            
            <div class="form-row">
                <div class="form-group form-group--full">
                    <label for="descricao">Descrição do Imóvel</label>
                    <textarea id="descricao" name="descricao" class="form-control" rows="6"><?= htmlspecialchars($formData['descricao']) ?></textarea>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--full">
                    <label for="palavras_chaves">Palavras-chave</label>
                    <input type="text" id="palavras_chaves" name="palavras_chaves" class="form-control"
                           value="<?= htmlspecialchars($formData['palavras_chaves']) ?>">
                    <small class="form-text">Palavras-chave separadas por vírgula (opcional).</small>
                </div>
            </div>
        </div>
        
        <!-- Corretor/Anunciante -->
        <div class="form-section">
            <h3 class="form-section__title">Corretor/Anunciante</h3>
            
            <div class="form-row">
                <div class="form-group form-group--half">
                    <label for="corretor_responsavel">Corretor Responsável</label>
                    <input type="text" id="corretor_responsavel" name="corretor_responsavel" class="form-control"
                           value="<?= htmlspecialchars($formData['corretor_responsavel']) ?>">
                </div>
                
                <div class="form-group form-group--half">
                    <label for="nome_anunciante">Nome do Anunciante</label>
                    <input type="text" id="nome_anunciante" name="nome_anunciante" class="form-control"
                           value="<?= htmlspecialchars($formData['nome_anunciante']) ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--half">
                    <label for="telefone_anunciante">Telefone do Anunciante</label>
                    <input type="text" id="telefone_anunciante" name="telefone_anunciante" class="form-control"
                           value="<?= htmlspecialchars($formData['telefone_anunciante']) ?>">
                </div>
            </div>
        </div>
        
        <!-- Imagens -->
        <div class="form-section">
            <h3 class="form-section__title">Imagens</h3>
            <p class="form-text">As imagens serão redimensionadas para um padrão adequado. A primeira imagem será usada como capa.</p>
            
            <div class="form-row">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <div class="form-group">
                        <label for="imagem<?= $i ?>">Imagem <?= $i ?></label>
                        <input type="file" id="imagem<?= $i ?>" name="imagem<?= $i ?>" class="form-control form-control-file" accept="image/*">
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Admin" class="cancel-button">Cancelar</a>
            <button type="submit" class="primary-button">
                <i class="fas fa-save"></i> Cadastrar Imóvel
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
</script>