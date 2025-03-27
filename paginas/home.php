<?php
$imoveis_destaque = getFeaturedProperties(6); // Limitar a 6 imóveis em destaque
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-section__wrapper">
        <div class="hero-section__content">
            <h1 class="hero-section__title">Nós vamos encontrar o lugar perfeito para você.</h1>
            <p class="hero-section__subtitle">Com a Mariza Corretora você terá os melhores serviços. Com uma trajetória sólida e reconhecida no mercado, somos referência quando se trata de transações imobiliárias de qualidade e confiança. Venha anunciar seu imóvel conosco!</p>
            <a href="imoveis.php" class="primary-button">Ver imóveis</a>
        </div>
        <div class="hero-section__image">
            <img src="assets/img/mockup_casa.webp" alt="Casa moderna">
        </div>
    </div>
</section>

<!-- Search Section -->
<section class="search-section">
    <div class="search-section__wrapper">
        <form action="<?= BASE_URL ?>/imoveis" method="GET" class="search-section__form">
            
            <select name="tipo" class="form-control search-section__input">
                <option value="">Tipo de Operação</option>
                <option value="venda">Comprar</option>
                <option value="aluguel">Alugar</option>
            </select>

            <select name="categoria" class="form-control search-section__input">
                <option value="">Todas Categorias</option>
                <?php 
                $categorias = getAllCategories();
                if (!empty($categorias)):
                    foreach ($categorias as $categoria): 
                ?>
                    <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['categoria']) ?></option>
                <?php 
                    endforeach;
                endif;
                ?>
            </select>
            
            <select name="cidade" class="form-control search-section__input">
                <option value="">Todas as Cidades</option>
                <?php 
                $cidades = getAllCities();
                if (!empty($cidades)):
                    foreach ($cidades as $cidade): 
                ?>
                    <option value="<?= $cidade['id'] ?>"><?= htmlspecialchars($cidade['nome']) ?> - <?= $cidade['uf'] ?></option>
                <?php 
                    endforeach;
                endif;
                ?>
            </select>
            
            <button type="submit" class="search-button search-section__button">Realizar busca</button>
        </form>
    </div>
</section>

<!-- Featured Properties Section -->
<section class="featured-properties section">
    <div class="featured-properties__wrapper">
        <h2 class="section__title">Imóveis em Destaque</h2>
        
        <?php if (empty($imoveis_destaque)): ?>
            <p class="text-center">Nenhum imóvel em destaque no momento.</p>
        <?php else: ?>
            <div class="properties-section__grid">
                <?php foreach ($imoveis_destaque as $imovel): ?>
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
                                    <i class="fas fa-bed"></i> <?= $imovel['quartos'] ?>
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($imovel['garagem']) && $imovel['garagem'] != "Nenhum"): ?>
                                <span class="property-card__feature">
                                    <i class="fas fa-car"></i> <?= $imovel['garagem'] ?>
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
            
            <div class="text-center mt-4">
                <a href="<?= BASE_URL ?>/imoveis" class="primary-button">Ver todos os imóveis</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- About Section -->
<section class="about-section">
    <div class="about-section__wrapper">
        <div class="about-section__image">
            <img src="assets/img/mockup_companhia.webp" alt="Sobre nós">
        </div>
        
        <div class="about-section__content">
            <h2 class="about-section__title">Sobre Nós</h2>
            <div class="about-section__text">
                <p>Operando em Luis Eduardo Magalhães-BA, nós oferecemos uma ampla carteira de imóveis, que abrange desde residências elegantes e apartamentos modernos até terrenos promissores para investimentos.</p>
                <p>Nossa equipe de profissionais altamente capacitados está pronta para auxiliá-lo na busca pelo imóvel ideal ou na venda do seu patrimônio com agilidade e eficiência.</p>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="stats-section__wrapper">
        <div class="stats-section__item">
            <div class="stats-section__number">900 +</div>
            <div class="stats-section__text">Imóveis vendidos</div>
        </div>
        
        <div class="stats-section__item">
            <div class="stats-section__number">900 +</div>
            <div class="stats-section__text">Imóveis negociados</div>
        </div>
        
        <div class="stats-section__item">
            <div class="stats-section__number">900 +</div>
            <div class="stats-section__text">Clientes satisfeitos</div>
        </div>
    </div>
</section>