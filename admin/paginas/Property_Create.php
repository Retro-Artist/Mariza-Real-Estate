<?php


// Fetch data for selects
$clientsResult     = getAdminClients([], 1, PHP_INT_MAX);
$clients           = $clientsResult['clients'];
$categories        = getAdminCategories();
$statesStmt        = $databaseConnection->query("SELECT * FROM sistema_estados ORDER BY nome");
$states            = $statesStmt->fetchAll();
$cities            = getAllCities();
$neighborhoods     = getAllBairros();
$usersResult       = getAdminUsers([], 1, PHP_INT_MAX);
$users             = $usersResult['users'];

$error = null;

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    // Collect form data
    $propertyData = [
        'id_estado'            => $_POST['id_estado'] ?? null,
        'id_cidade'            => $_POST['id_cidade'] ?? null,
        'id_bairro'            => $_POST['id_bairro'] ?? null,
        'para'                 => $_POST['para'] ?? '',
        'id_categoria'         => $_POST['id_categoria'] ?? null,
        'quartos'              => $_POST['quartos'] ?? 0,
        'suites'               => $_POST['suites'] ?? 0,
        'cozinhas'             => $_POST['cozinhas'] ?? 0,
        'salas'                => $_POST['salas'] ?? 0,
        'banheiros'            => $_POST['banheiros'] ?? 0,
        'garagem'              => $_POST['garagem'] ?? 0,
        'area_servico'         => $_POST['area_servico'] ?? '',
        'area_total'           => $_POST['area_total'] ?? '',
        'area_construida'      => $_POST['area_construida'] ?? '',
        'und_medida'           => $_POST['und_medida'] ?? '',
        'valor'                => $_POST['valor'] ?? 0,
        'descricao'            => $_POST['descricao'] ?? '',
        'palavras_chaves'      => $_POST['palavras_chaves'] ?? '',
        'titulo'               => $_POST['titulo'] ?? '',
        'endereco'             => $_POST['endereco'] ?? '',
        'codigo'               => $_POST['codigo'] ?? '',
        'nome_anunciante'      => $_POST['nome_anunciante'] ?? '',
        'telefone_anunciante'  => $_POST['telefone_anunciante'] ?? '',
        'corretor_responsavel' => $_POST['corretor_responsavel'] ?? null,
        'status'               => $_POST['status'] ?? '',
        'quadra_lote'          => $_POST['quadra_lote'] ?? '',
        'destaque'             => isset($_POST['destaque']) ? 1 : 0,
        'ref'                  => $_POST['ref'] ?? ''
    ];

    // Create property
    $newId = createProperty($propertyData);
    if ($newId !== false) {
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
                    $ext = pathinfo($origName, PATHINFO_EXTENSION);
                    $filename = $code . sprintf('%02d', $i + 1) . '.' . $ext;
                    move_uploaded_file($tmp, $uploadDir . $filename);
                }
            }
        }
        header('Location: Property_View.php?id=' . $newId . '&success=1');
        exit;
    } else {
        $error = 'Erro ao criar imóvel. Verifique se o código já existe.';
    }
}

include __DIR__ . '/../Admin_Header.php';
?>

<div class="container">
    <h1>Criar Novo Imóvel</h1>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <!-- Cliente -->
        <div class="form-group">
            <label for="id_cliente">Cliente</label>
            <select name="id_cliente" id="id_cliente" class="form-control">
                <?php foreach ($clients as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome_completo']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Localização -->
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="id_estado">Estado</label>
                <select name="id_estado" id="id_estado" class="form-control">
                    <option value="">Selecione...</option>
                    <?php foreach ($states as $st): ?>
                        <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="id_cidade">Cidade</label>
                <select name="id_cidade" id="id_cidade" class="form-control">
                    <option value="">Selecione...</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= $city['id'] ?>" data-state="<?= $city['id_estado'] ?>"><?= htmlspecialchars($city['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="id_bairro">Bairro</label>
                <select name="id_bairro" id="id_bairro" class="form-control">
                    <option value="">Selecione...</option>
                    <?php foreach ($neighborhoods as $bn): ?>
                        <option value="<?= $bn['id'] ?>" data-city="<?= $bn['id_cidade'] ?>"><?= htmlspecialchars($bn['bairro']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <!-- Tipo do anúncio -->
        <div class="form-group">
            <label for="para">Anúncio para</label>
            <select name="para" id="para" class="form-control">
                <option value="venda">Venda</option>
                <option value="aluguel">Aluguel</option>
            </select>
        </div>
        <!-- Categoria -->
        <div class="form-group">
            <label for="id_categoria">Categoria</label>
            <select name="id_categoria" id="id_categoria" class="form-control">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['categoria']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Características -->
        <div class="form-row">
            <div class="form-group col-md-2">
                <label for="quartos">Quartos</label>
                <input type="number" name="quartos" id="quartos" class="form-control" value="0">
            </div>
            <div class="form-group col-md-2">
                <label for="suites">Suítes</label>
                <input type="number" name="suites" id="suites" class="form-control" value="0">
            </div>
            <div class="form-group col-md-2">
                <label for="banheiros">Banheiros</label>
                <input type="number" name="banheiros" id="banheiros" class="form-control" value="0">
            </div>
            <div class="form-group col-md-2">
                <label for="salas">Salas</label>
                <input type="number" name="salas" id="salas" class="form-control" value="0">
            </div>
            <div class="form-group col-md-2">
                <label for="cozinhas">Cozinhas</label>
                <input type="number" name="cozinhas" id="cozinhas" class="form-control" value="0">
            </div>
            <div class="form-group col-md-2">
                <label for="garagem">Garagem</label>
                <input type="number" name="garagem" id="garagem" class="form-control" value="0">
            </div>
        </div>
        <!-- Áreas e valores -->
        <div class="form-row">
            <div class="form-group col-md-3">
                <label for="area_servico">Área Serviço (m²)</label>
                <input type="text" name="area_servico" id="area_servico" class="form-control">
            </div>
            <div class="form-group col-md-3">
                <label for="area_total">Área Total (m²)</label>
                <input type="text" name="area_total" id="area_total" class="form-control">
            </div>
            <div class="form-group col-md-3">
                <label for="area_construida">Área Construída (m²)</label>
                <input type="text" name="area_construida" id="area_construida" class="form-control">
            </div>
            <div class="form-group col-md-3">
                <label for="und_medida">Unidade de Medida</label>
                <input type="text" name="und_medida" id="und_medida" class="form-control">
            </div>
        </div>
        <div class="form-group">
            <label for="valor">Valor</label>
            <input type="text" name="valor" id="valor" class="form-control" required>
        </div>
        <!-- Título, Código e Endereço -->
        <div class="form-group">
            <label for="titulo">Título</label>
            <input type="text" name="titulo" id="titulo" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="codigo">Código</label>
            <input type="text" name="codigo" id="codigo" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="endereco">Endereço</label>
            <input type="text" name="endereco" id="endereco" class="form-control" required>
        </div>
        <!-- Descrição e Palavras-chave -->
        <div class="form-group">
            <label for="descricao">Descrição</label>
            <textarea name="descricao" id="descricao" class="form-control" rows="4"></textarea>
        </div>
        <div class="form-group">
            <label for="palavras_chaves">Palavras-chave</label>
            <input type="text" name="palavras_chaves" id="palavras_chaves" class="form-control">
        </div>
        <!-- Anunciante e Vendedor -->
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="nome_anunciante">Nome Anunciante</label>
                <input type="text" name="nome_anunciante" id="nome_anunciante" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label for="telefone_anunciante">Telefone Anunciante</label>
                <input type="text" name="telefone_anunciante" id="telefone_anunciante" class="form-control">
            </div>
        </div>
        <div class="form-group">
            <label for="corretor_responsavel">Corretor Responsável</label>
            <select name="corretor_responsavel" id="corretor_responsavel" class="form-control">
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Status, Quadra/Lote e Destaque -->
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="status">Status</label>
                <input type="text" name="status" id="status" class="form-control">
            </div>
            <div class="form-group col-md-4">
                <label for="quadra_lote">Quadra/Lote</label>
                <input type="text" name="quadra_lote" id="quadra_lote" class="form-control">
            </div>
            <div class="form-group col-md-4">
                <label for="destaque">Destaque</label>
                <input type="checkbox" name="destaque" id="destaque" value="1">
            </div>
        </div>
        <!-- Referência Interna -->
        <div class="form-group">
            <label for="ref">Referência</label>
            <input type="text" name="ref" id="ref" class="form-control">
        </div>
        <!-- Upload de Imagens -->
        <div class="form-group">
            <label for="images">Imagens (múltiplas)</label>
            <input type="file" name="images[]" id="images" accept="image/*" multiple class="form-control-file">
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="Property_Admin.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<script>
// Filtrar cidades e bairros
(function(){
    var stateSel = document.getElementById('id_estado');
    var citySel  = document.getElementById('id_cidade');
    var bairroSel= document.getElementById('id_bairro');
    stateSel.addEventListener('change', function(){
        var st = this.value;
        Array.from(citySel.options).forEach(function(opt){ opt.hidden = opt.dataset.state !== st; });
        citySel.value = '';
        citySel.dispatchEvent(new Event('change'));
    });
    citySel.addEventListener('change', function(){
        var ct = this.value;
        Array.from(bairroSel.options).forEach(function(opt){ opt.hidden = opt.dataset.city !== ct; });
        bairroSel.value = '';
    });
})();
</script>