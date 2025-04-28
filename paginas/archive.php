<?php
// Add sorting logic based on the 'ordenar' parameter
if (isset($filtros['ordenar'])) {
    if ($filtros['ordenar'] === 'preco_asc') {
        $orderBy = 'ORDER BY preco ASC';
    } elseif ($filtros['ordenar'] === 'preco_desc') {
        $orderBy = 'ORDER BY preco DESC';
    }
}

// Pegar parâmetros de busca da URL
$filtros = [];
$parametros_busca = [
    'tipo',
    'categoria',
    'cidade',
    'bairro',
    'quartos',
    'suites',
    'banheiros',
    'garagem',
    'busca',
    'valor',
    'ordenar'  // Add this line
];

foreach ($parametros_busca as $param) {
    if (isset($_GET[$param]) && $_GET[$param] !== '') {
        $filtros[$param] = $_GET[$param];
    }
}

// Verificar se a página foi acessada com parâmetros de busca
$busca_realizada = !empty($filtros);

// Paginação
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itens_por_pagina = 12;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$filtros['limit'] = $itens_por_pagina;
$filtros['offset'] = $offset;

// Obter categorias para o filtro
$categorias = getAllCategories();

// Obter cidades para o filtro
$cidades = getAllCities();

// Obter bairros para o filtro
$bairros = getAllBairros();

// Buscar imóveis com os filtros aplicados
$imoveis = searchImoveis($filtros);

// Contar total de imóveis para paginação
$total_imoveis = countImoveis($filtros);
$total_paginas = ceil($total_imoveis / $itens_por_pagina);
?>

<!-------------------Advanced Filter Section-------------------->
<section class="filter-section">
    <div class="filter-section__wrapper">
        <form action="<?= BASE_URL ?>/imoveis" method="GET" class="filter-section__form">
            <div class="filter-section__row">
                <div class="filter-section__group">
                    <label for="tipo">Tipo:</label>
                    <select name="tipo" id="tipo" class="form-control">
                        <option value="">Todos</option>
                        <option value="venda" <?= isset($_GET['tipo']) && $_GET['tipo'] == 'venda' ? 'selected' : '' ?>>Comprar</option>
                        <option value="aluguel" <?= isset($_GET['tipo']) && $_GET['tipo'] == 'aluguel' ? 'selected' : '' ?>>Alugar</option>
                    </select>
                </div>

                <div class="filter-section__group">
                    <label for="categoria">Categoria:</label>
                    <select name="categoria" id="categoria" class="form-control">
                        <option value="">Todas Categorias</option>
                        <?php if (!empty($categorias)): ?>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['id'] ?>" <?= isset($_GET['categoria']) && $_GET['categoria'] == $categoria['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['categoria']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="filter-section__group">
                    <label for="cidade">Cidade:</label>
                    <select name="cidade" id="cidade" class="form-control cidade-select">
                        <option value="">Todas Cidades</option>
                        <?php if (!empty($cidades)): ?>
                            <?php foreach ($cidades as $cidade): ?>
                                <option value="<?= $cidade['id'] ?>" <?= isset($_GET['cidade']) && $_GET['cidade'] == $cidade['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cidade['nome']) ?> - <?= $cidade['uf'] ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="filter-section__group">
                    <label for="bairro">Bairro:</label>
                    <select name="bairro" id="bairro" class="form-control">
                        <option value="">Todos Bairros</option>
                        <?php if (!empty($bairros)): ?>
                            <?php foreach ($bairros as $bairro): ?>
                                <option value="<?= $bairro['id'] ?>"
                                    data-cidade="<?= $bairro['id_cidade'] ?>"
                                    <?= isset($_GET['bairro']) && $_GET['bairro'] == $bairro['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($bairro['bairro']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="filter-section__row">
                <div class="filter-section__group">
                    <label for="quartos">Quarto / Dormitório:</label>
                    <select name="quartos" id="quartos" class="form-control">
                        <option value="">Nenhum</option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                        <option value="4" <?= isset($_GET['quartos']) && $_GET['quartos'] == '4' ? 'selected' : '' ?>>4+</option>
                    </select>
                </div>

                <div class="filter-section__group">
                    <label for="suites">Suíte:</label>
                    <select name="suites" id="suites" class="form-control">
                        <option value="">Nenhum</option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                    </select>
                </div>

                <div class="filter-section__group">
                    <label for="banheiros">Banheiro:</label>
                    <select name="banheiros" id="banheiros" class="form-control">
                        <option value="">Nenhum</option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                    </select>
                </div>

                <div class="filter-section__group">
                    <label for="garagem">Vagas na Garagem:</label>
                    <select name="garagem" id="garagem" class="form-control">
                        <option value="">Nenhum</option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                    </select>
                </div>
            </div>

            <div class="filter-section__row">
                <div class="filter-section__group filter-section__group--wide">
                    <label for="busca">Busque por palavra-chave ou Código:</label>
                    <input type="text" name="busca" id="busca" placeholder="Digite sua busca..." class="form-control" value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
                </div>


                <div class="filter-section__group">
                    <label for="valor">Valor R$:</label>
                    <select name="valor" class="form-control">
                        <option value="" <?= (!isset($_GET['valor']) || $_GET['valor'] == "") ? "selected" : "" ?>>Qualquer</option>
                        <option value="500" <?= (isset($_GET['valor']) && $_GET['valor'] == 500) ? "selected" : "" ?>>Até R$ 500</option>
                        <option value="700" <?= (isset($_GET['valor']) && $_GET['valor'] == 700) ? "selected" : "" ?>>Até R$ 700</option>
                        <option value="800" <?= (isset($_GET['valor']) && $_GET['valor'] == 800) ? "selected" : "" ?>>Até R$ 800</option>
                        <option value="900" <?= (isset($_GET['valor']) && $_GET['valor'] == 900) ? "selected" : "" ?>>Até R$ 900</option>
                        <option value="1000" <?= (isset($_GET['valor']) && $_GET['valor'] == 1000) ? "selected" : "" ?>>Até R$ 1.000</option>
                        <option value="1500" <?= (isset($_GET['valor']) && $_GET['valor'] == 1500) ? "selected" : "" ?>>Até R$ 1.500</option>
                        <option value="2000" <?= (isset($_GET['valor']) && $_GET['valor'] == 2000) ? "selected" : "" ?>>Até R$ 2.000</option>
                        <option value="2500" <?= (isset($_GET['valor']) && $_GET['valor'] == 2500) ? "selected" : "" ?>>Até R$ 2.500</option>
                        <option value="3000" <?= (isset($_GET['valor']) && $_GET['valor'] == 3000) ? "selected" : "" ?>>Até R$ 3.000</option>
                        <option value="4000" <?= (isset($_GET['valor']) && $_GET['valor'] == 4000) ? "selected" : "" ?>>Até R$ 4.000</option>
                        <option value="5000" <?= (isset($_GET['valor']) && $_GET['valor'] == 5000) ? "selected" : "" ?>>Até R$ 5.000</option>
                        <option value="7000" <?= (isset($_GET['valor']) && $_GET['valor'] == 7000) ? "selected" : "" ?>>Até R$ 7.000</option>
                        <option value="10000" <?= (isset($_GET['valor']) && $_GET['valor'] == 10000) ? "selected" : "" ?>>Até R$ 10.000</option>
                        <option value="15000" <?= (isset($_GET['valor']) && $_GET['valor'] == 15000) ? "selected" : "" ?>>Até R$ 15.000</option>
                        <option value="20000" <?= (isset($_GET['valor']) && $_GET['valor'] == 20000) ? "selected" : "" ?>>Até R$ 20.000</option>
                        <option value="30000" <?= (isset($_GET['valor']) && $_GET['valor'] == 30000) ? "selected" : "" ?>>Até R$ 30.000</option>
                        <option value="50000" <?= (isset($_GET['valor']) && $_GET['valor'] == 50000) ? "selected" : "" ?>>Até R$ 50.000</option>
                        <option value="100000" <?= (isset($_GET['valor']) && $_GET['valor'] == 100000) ? "selected" : "" ?>>Até R$ 100.000</option>
                        <option value="200000" <?= (isset($_GET['valor']) && $_GET['valor'] == 200000) ? "selected" : "" ?>>Até R$ 200.000</option>
                        <option value="300000" <?= (isset($_GET['valor']) && $_GET['valor'] == 300000) ? "selected" : "" ?>>Até R$ 300.000</option>
                        <option value="500000" <?= (isset($_GET['valor']) && $_GET['valor'] == 500000) ? "selected" : "" ?>>Até R$ 500.000</option>
                        <option value="750000" <?= (isset($_GET['valor']) && $_GET['valor'] == 750000) ? "selected" : "" ?>>Até R$ 750.000</option>
                        <option value="1000000" <?= (isset($_GET['valor']) && $_GET['valor'] == 1000000) ? "selected" : "" ?>>Até R$ 1.000.000</option>
                        <option value="2000000" <?= (isset($_GET['valor']) && $_GET['valor'] == 2000000) ? "selected" : "" ?>>Até R$ 2.000.000</option>
                        <option value="5000000" <?= (isset($_GET['valor']) && $_GET['valor'] == 5000000) ? "selected" : "" ?>>Até R$ 5.000.000</option>
                        <option value="10000000" <?= (isset($_GET['valor']) && $_GET['valor'] == 10000000) ? "selected" : "" ?>>Até R$ 10.000.000</option>
                        <option value="15000000" <?= (isset($_GET['valor']) && $_GET['valor'] == 15000000) ? "selected" : "" ?>>Até R$ 15.000.000</option>
                        <option value="20000000" <?= (isset($_GET['valor']) && $_GET['valor'] == 20000000) ? "selected" : "" ?>>Até R$ 20.000.000</option>
                        <option value="30000000" <?= (isset($_GET['valor']) && $_GET['valor'] == 30000000) ? "selected" : "" ?>>Até R$ 30.000.000</option>
                        <option value="50000000" <?= (isset($_GET['valor']) && $_GET['valor'] == 50000000) ? "selected" : "" ?>>Até R$ 50.000.000</option>
                    </select>
                </div>


                <div class="filter-section__group">
                    <label for="ordenar">Ordenar por:</label>
                    <select name="ordenar" id="ordenar" class="form-control">
                        <option value="">Selecione</option>
                        <option value="preco_asc" <?= isset($_GET['ordenar']) && $_GET['ordenar'] == 'preco_asc' ? 'selected' : '' ?>>Preço: Menor para Maior</option>
                        <option value="preco_desc" <?= isset($_GET['ordenar']) && $_GET['ordenar'] == 'preco_desc' ? 'selected' : '' ?>>Preço: Maior para Menor</option>
                    </select>
                </div>


                <div class="filter-section__group filter-section__group--submit">
                    <button type="submit" class="search-button filter-section__submit-button">Buscar</button>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Lista de Imóveis Section -->
<section class="properties-section section">
    <div class="properties-section__wrapper">
        <h2 class="section__title">Encontre seu imóvel</h2>

        <?php if (empty($imoveis)): ?>
            <div class="properties-section__no-results">
                <p>Nenhum imóvel encontrado com os critérios selecionados.</p>
                <p>Por favor, tente outra combinação de filtros ou entre em contato para ajudarmos na sua busca.</p>
                <a href="<?= BASE_URL ?>/contato" class="primary-button">Entrar em contato</a>
            </div>
        <?php else: ?>
            <div class="properties-section__grid">
                <?php foreach ($imoveis as $imovel): ?>
                    <!-- Property Card -->
                    <div class="property-card">
                        <a href="<?= BASE_URL ?>/imovel/<?= $imovel['id'] ?>">
                            <img src="<?= getPropertyMainImage($imovel) ?>"
                                alt="<?= htmlspecialchars($imovel['titulo'] ?? '') ?>" class="property-card__image">
                        </a>

                        <div class="property-card__content">
                            <span class="property-card__tag">
                                <?= $imovel['para'] === 'venda' ? 'Venda' : 'Aluguel' ?> - <?= htmlspecialchars($imovel['categoria'] ?? '') ?>
                            </span>
                            <h3 class="property-card__title"><?= htmlspecialchars($imovel['titulo'] ?? '') ?></h3>
                            <div class="property-card__price"><?= formatCurrency($imovel['valor']) ?></div>
                            <div class="property-card__location">
                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($imovel['bairro'] ?? '') ?>
                            </div>

                            <div class="property-card__features">
                                <?php if (!empty($imovel['quartos']) && $imovel['quartos'] != "Nenhum"): ?>
                                    <span class="property-card__feature">
                                        <i class="fas fa-bed"></i> <?= $imovel['quartos'] ?> <?= $imovel['quartos'] > 1 ? "Quartos" : "Quarto" ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($imovel['garagem']) && $imovel['garagem'] != "Nenhum"): ?>
                                    <span class="property-card__feature">
                                        <i class="fas fa-car"></i> <?= $imovel['garagem'] ?> <?= $imovel['garagem'] > 1 ? "Garagens" : "Garagem" ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($imovel['area_total'])): ?>
                                    <span class="property-card__feature">
                                        <i class="fas fa-vector-square"></i> <?= $imovel['area_total'] ?> <?= $imovel['und_medida'] ?? 'm²' ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="property-card__agent">
                                <i class="fas fa-city"></i> <?= htmlspecialchars($imovel['cidade'] ?? '') ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
                <div class="pagination">
                    <?php if ($pagina_atual > 1): ?>
                        <a href="<?= BASE_URL ?>/imoveis?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_atual - 1])) ?>" class="pagination__item">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                    <?php else: ?>
                        <span class="pagination__item pagination__item--disabled">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </span>
                    <?php endif; ?>

                    <?php
                    // Definir range de páginas a mostrar
                    $range = 2;
                    $inicio_range = max(1, $pagina_atual - $range);
                    $fim_range = min($total_paginas, $pagina_atual + $range);

                    // Mostrar primeira página se não estiver no range
                    if ($inicio_range > 1): ?>
                        <a href="<?= BASE_URL ?>/imoveis?<?= http_build_query(array_merge($_GET, ['pagina' => 1])) ?>" class="pagination__item">1</a>
                        <?php if ($inicio_range > 2): ?>
                            <span class="pagination__item pagination__item--ellipsis">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Mostrar páginas no range -->
                    <?php for ($i = $inicio_range; $i <= $fim_range; $i++): ?>
                        <?php if ($i == $pagina_atual): ?>
                            <span class="pagination__item pagination__item--active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/imoveis?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>" class="pagination__item"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Mostrar última página se não estiver no range -->
                    <?php if ($fim_range < $total_paginas): ?>
                        <?php if ($fim_range < $total_paginas - 1): ?>
                            <span class="pagination__item pagination__item--ellipsis">...</span>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/imoveis?<?= http_build_query(array_merge($_GET, ['pagina' => $total_paginas])) ?>" class="pagination__item"><?= $total_paginas ?></a>
                    <?php endif; ?>

                    <?php if ($pagina_atual < $total_paginas): ?>
                        <a href="<?= BASE_URL ?>/imoveis?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_atual + 1])) ?>" class="pagination__item">
                            Próxima <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="pagination__item pagination__item--disabled">
                            Próxima <i class="fas fa-chevron-right"></i>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Script para filtro dinâmico de bairros -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cidadeSelect = document.querySelector('.cidade-select');
        const bairroSelect = document.getElementById('bairro');
        const bairroOptions = Array.from(bairroSelect.querySelectorAll('option'));

        // Função para filtrar bairros com base na cidade selecionada
        function filtrarBairros() {
            const cidadeSelecionada = cidadeSelect.value;

            // Remover todas as opções atuais, exceto a primeira (Todos Bairros)
            while (bairroSelect.options.length > 1) {
                bairroSelect.remove(1);
            }

            // Se nenhuma cidade for selecionada, mostrar todos os bairros
            if (!cidadeSelecionada) {
                bairroOptions.forEach(option => {
                    if (option.value) { // Não incluir a opção "Todos Bairros" novamente
                        bairroSelect.appendChild(option.cloneNode(true));
                    }
                });
                return;
            }

            // Filtrar e adicionar apenas os bairros da cidade selecionada
            const bairrosFiltrados = bairroOptions.filter(option => {
                return option.value === '' || option.dataset.cidade === cidadeSelecionada;
            });

            bairrosFiltrados.forEach(option => {
                if (option.value !== '') { // Não incluir a opção "Todos Bairros" novamente
                    bairroSelect.appendChild(option.cloneNode(true));
                }
            });
        }

        // Filtrar bairros ao carregar a página
        filtrarBairros();

        // Adicionar evento de mudança na seleção de cidade
        cidadeSelect.addEventListener('change', filtrarBairros);
    });
</script>