<!-- Footer -->
<footer class="site-footer">
    <div class="site-footer__wrapper">
        <!-- Column 1: Logo -->
        <div class="site-footer__column">
            <img src="<?= BASE_URL ?>/assets/img/site-logo.webp" alt="<?= SITE_NAME ?>" class="site-footer__logo">
        </div>
        
        <!-- Column 2: Navegação -->
        <div class="site-footer__column">
            <h3 class="site-footer__title">Navegação</h3>
            <a href="<?= BASE_URL ?>/imoveis" class="site-footer__link">Imóveis</a>
            <a href="<?= BASE_URL ?>/contato" class="site-footer__link">Contato</a>
            <a href="<?= BASE_URL ?>/anunciar" class="site-footer__link">Anunciar</a>
        </div>
        
        <!-- Column 3: Fale Conosco -->
        <div class="site-footer__column">
            <h3 class="site-footer__title">Fale Conosco</h3>
            <p class="site-footer__contact">77 99936-7802</p>
            <p class="site-footer__contact">marizaimoveis2@gmail.com</p>
        </div>
        
        <!-- Column 4: Redes Sociais -->
        <div class="site-footer__column">
            <h3 class="site-footer__title">Redes Sociais</h3>
            <div class="site-footer__social">
                <a href="#" class="site-footer__social-link">
                    <i class="fab fa-instagram"></i> instagram
                </a>
                <a href="#" class="site-footer__social-link">
                    <i class="fab fa-facebook-f"></i> facebook
                </a>
                <a href="#" class="site-footer__social-link">
                    <i class="fab fa-whatsapp"></i> whatsapp
                </a>
            </div>
        </div>
    </div>
    
    <!-- Copyright -->
    <div class="site-footer__copyright">
        <p>Mariza Marquezan Imóveis © <?= date('Y') ?> - Todos os Direitos Reservados</p>
    </div>
    
    <!-- WhatsApp Floating Button -->
    <?php
    // Verificar se estamos na página de detalhe de imóvel
    $is_property_page = isset($imovel) && !empty($imovel);
    
    // Preparar mensagem contextual para página de imóvel
    $whatsapp_message = "Olá! Gostaria de mais informações sobre os imóveis.";
    
    if ($is_property_page) {
        // Construir mensagem com informações do imóvel
        $property_url = BASE_URL . "/imovel/" . $imovel['id'];
        $property_type = $imovel['para'] === 'venda' ? 'Casa à Venda' : 'Casa para Aluguel';
        $property_neighborhood = htmlspecialchars($imovel['bairro'] ?? 'Não informado');
        $property_price = formatCurrency($imovel['valor']);
        $property_title = htmlspecialchars($imovel['titulo'] ?? '');
        
        $whatsapp_message = "Olá, gostaria de saber mais informações sobre o imóvel: {$property_title} - {$property_type} no Bairro {$property_neighborhood} no valor de {$property_price}, que encontrei no seu site ({$property_url}). Obrigado!";
    }
    ?>
    
    <div class="whatsapp-float">
        <a href="https://api.whatsapp.com/send?phone=<?= WHATSAPP_NUMBER ?>&text=<?= urlencode($whatsapp_message) ?>" 
           class="whatsapp-float__button" target="_blank" rel="noopener noreferrer">
            <i class="fab fa-whatsapp"></i>
        </a>
        <div class="whatsapp-float__tooltip">
            <?= $is_property_page ? 'Pergunte sobre este imóvel' : 'Fale conosco pelo WhatsApp' ?>
        </div>
    </div>
    
    <!-- Scripts -->

    <script src="<?= BASE_URL ?>/assets/scripts/script_loader.js"></script>
    <script src="<?= BASE_URL ?>/assets/scripts/counter-animation.js"></script>
</body>
</html>