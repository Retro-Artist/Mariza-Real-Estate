<?php
// Include security functions (if not already included)
require_once __DIR__ . '/../includes/security_functions.php';

// Obter o ID do imóvel da URL
$imovel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Se não tiver ID, redirecionar para a página de imóveis
if ($imovel_id <= 0) {
    header('Location: ' . BASE_URL . '/imoveis');
    exit;
}

// Buscar detalhes do imóvel
$imovel = getPropertyById($imovel_id);

// Se o imóvel não existir, mostrar mensagem de erro
if (!$imovel) {
    echo '<div class="container" style="padding: 50px 20px; text-align: center;">
            <h2>Imóvel não encontrado</h2>
            <p>O imóvel que você está procurando não está disponível ou foi removido.</p>
            <a href="' . BASE_URL . '/imoveis" class="primary-button">Ver todos os imóveis</a>
          </div>';
    exit;
}

// Obter imagens do imóvel (função modificada)
$imagens = getPropertyImages($imovel_id);

// Preparar mensagem padrão para o formulário
$mensagem_padrao = "Olá, gostaria de saber mais informações sobre o imóvel: " . 
    ($imovel['para'] === 'venda' ? '- Casa à Venda' : '- Casa para Aluguel') .
    " no Bairro " . htmlspecialchars($imovel['bairro'] ?? 'Não informado') . 
    " no valor de " . formatCurrency($imovel['valor']) . 
    ", que encontrei no seu site. Obrigado!";

// Processar formulário
$message = '';
$messageClass = '';

// Generate CSRF token for the form
$csrfToken = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form with security checks
    $formResult = processSecureForm($_POST, 'property_contact');
    
    if ($formResult['success']) {
        // Get sanitized data
        $sanitizedData = $formResult['data'];
        $nome = $sanitizedData['nome'];
        $telefone = $sanitizedData['telefone'] ?? '';
        $email = $sanitizedData['email'];
        $mensagem = $sanitizedData['mensagem'] ?? $mensagem_padrao;
        
        try {
            // Inserir o contato na tabela sistema_interacao
            $data = date('Y-m-d');
            $hora = date('H:i:s');
            $local = 'Site';
            $status = 'Pendente';
            
            // Adicionar referência ao imóvel na mensagem para facilitar o acompanhamento
            $mensagem = "[Contato sobre imóvel #" . $imovel_id . " - " . htmlspecialchars($imovel['titulo']) . "]\n\n" . $mensagem;
            
            $stmt = $databaseConnection->prepare(
                "INSERT INTO sistema_interacao (
                    nome, email, telefone, mensagem,
                    data, hora, local, status
                ) VALUES (
                    :nome, :email, :telefone, :mensagem,
                    :data, :hora, :local, :status
                )"
            );
            
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telefone', $telefone);
            $stmt->bindParam(':mensagem', $mensagem);
            $stmt->bindParam(':data', $data);
            $stmt->bindParam(':hora', $hora);
            $stmt->bindParam(':local', $local);
            $stmt->bindParam(':status', $status);
            
            $stmt->execute();
            
            // Log successful submission
            logSecurityEvent('PROPERTY_CONTACT', 'Successful property contact form submission', [
                'property_id' => $imovel_id,
                'email' => $email,
                'name' => $nome
            ]);
            
            // Mostrar mensagem de sucesso
            $message = 'Obrigado pelo seu contato! Retornaremos em breve.';
            $messageClass = 'success';
            
            // Reset form fields after successful submission
            $nome = $email = $telefone = '';
            $mensagem = $mensagem_padrao;
        } catch (PDOException $e) {
            logError("Error saving property contact form: " . $e->getMessage());
            logSecurityEvent('ERROR', 'Database error on property contact form', [
                'property_id' => $imovel_id,
                'error' => $e->getMessage()
            ]);
            $message = 'Ocorreu um erro ao enviar sua mensagem. Por favor, tente novamente.';
            $messageClass = 'error';
        }
    } else {
        // Display error message from security validation
        $message = $formResult['message'];
        $messageClass = 'error';
    }
}
?>

<!-------------------Property Detail Section-------------------->
<section class="property-detail">
    <div class="property-detail__wrapper">
        <div class="property-detail__main">
            <!-- Galeria de Fotos -->
            <div class="property-gallery">
                <div class="property-gallery__main">
                    <div class="property-gallery__current">
                        <?php if (!empty($imagens)): ?>
                            <img src="<?= UPLOADS_URL . '/imoveis/' . $imagens[0]['imagem'] ?>" 
                                 alt="<?= htmlspecialchars($imovel['titulo'] ?? '') ?>" id="mainImage">
                        <?php else: ?>
                            <img src="<?= BASE_URL . '/assets/img/placeholder.png' ?>" 
                                 alt="<?= htmlspecialchars($imovel['titulo'] ?? '') ?>" id="mainImage">
                        <?php endif; ?>
                        
                        <!-- Botões de navegação da galeria -->
                        <button class="property-gallery__nav property-gallery__nav--prev" id="prevImage">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="property-gallery__nav property-gallery__nav--next" id="nextImage">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Miniaturas -->
                <div class="property-gallery__thumbs">
                    <?php 
                    if (!empty($imagens)): 
                        foreach ($imagens as $index => $imagem): 
                    ?>
                        <div class="property-gallery__thumb <?= $index === 0 ? 'property-gallery__thumb--active' : '' ?>" 
                             data-image="<?= UPLOADS_URL . '/imoveis/' . $imagem['imagem'] ?>">
                            <img src="<?= UPLOADS_URL . '/imoveis/' . $imagem['imagem'] ?>" 
                                 alt="<?= htmlspecialchars($imovel['titulo'] ?? '') ?> - Imagem <?= $index + 1 ?>">
                        </div>
                    <?php 
                        endforeach; 
                    else: 
                    ?>
                        <div class="property-gallery__thumb property-gallery__thumb--active" 
                             data-image="<?= BASE_URL . '/assets/img/placeholder.png' ?>">
                            <img src="<?= BASE_URL . '/assets/img/placeholder.png' ?>" 
                                 alt="<?= htmlspecialchars($imovel['titulo'] ?? '') ?>">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Informações do Imóvel -->
            <div class="property-info">
                <h1 class="property-info__title"><?= htmlspecialchars($imovel['titulo'] ?? '') ?></h1>
                
                <div class="property-info__meta">
                    <span class="property-info__tag">
                        <?= $imovel['para'] === 'venda' ? 'Venda' : 'Aluguel' ?> - <?= htmlspecialchars($imovel['categoria'] ?? '') ?>
                    </span>
                    <span class="property-info__price"><?= formatCurrency($imovel['valor']) ?></span>
                </div>
                
                <div class="property-info__location">
                    <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($imovel['bairro'] ?? '') ?>, <?= htmlspecialchars($imovel['cidade'] ?? '') ?> - <?= htmlspecialchars($imovel['uf'] ?? '') ?></p>
                </div>
                
                <div class="property-info__ref">
                    <p>Referência do Imóvel - <?= htmlspecialchars($imovel['ref'] ?? '') ?></p>
                </div>
                
                <!-- Características -->
                <div class="property-features">
                    <?php if (!empty($imovel['quartos']) && $imovel['quartos'] != "Nenhum"): ?>
                    <div class="property-features__item">
                        <i class="fas fa-bed"></i>
                        <span><?= $imovel['quartos'] == 1 ? "Um quarto" : $imovel['quartos'] . " Quartos" ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($imovel['suites']) && $imovel['suites'] != "Nenhum"): ?>
                    <div class="property-features__item">
                        <i class="fas fa-bath"></i>
                        <span><?= $imovel['suites'] == 1 ? "Uma suíte" : $imovel['suites'] . " Suítes" ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($imovel['garagem']) && $imovel['garagem'] != "Nenhum"): ?>
                    <div class="property-features__item">
                        <i class="fas fa-car"></i>
                        <span><?= $imovel['garagem'] == 1 ? "Uma vaga" : $imovel['garagem'] . " Vagas" ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($imovel['banheiros']) && $imovel['banheiros'] != "Nenhum"): ?>
                    <div class="property-features__item">
                        <i class="fas fa-shower"></i>
                        <span><?= $imovel['banheiros'] == 1 ? "Um banheiro" : $imovel['banheiros'] . " Banheiros" ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($imovel['area_total'])): ?>
                    <div class="property-features__item">
                        <i class="fas fa-vector-square"></i>
                        <span><?= $imovel['area_total'] ?> <?= $imovel['und_medida'] ?? 'm²' ?> Área Total</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($imovel['area_construida'])): ?>
                    <div class="property-features__item">
                        <i class="fas fa-home"></i>
                        <span><?= $imovel['area_construida'] ?> <?= $imovel['und_medida'] ?? 'm²' ?> Área Construída</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Descrição -->
                <div class="property-description">
                    <h2 class="property-description__title">Descrição</h2>
                    <div class="property-description__content">
                        <?= nl2br(htmlspecialchars($imovel['descricao'] ?? '')) ?>
                    </div>
                </div>
                
                <!-- Mapa -->
                <?php if (!empty($imovel['latitude']) && !empty($imovel['longitude'])): ?>
                <div class="property-map">
                    <h2 class="property-map__title">Mapa</h2>
                    <div class="property-map__container" id="propertyMap">
                        <iframe 
                            width="100%" 
                            height="400" 
                            frameborder="0" 
                            scrolling="no" 
                            marginheight="0" 
                            marginwidth="0" 
                            src="https://maps.google.com/maps?q=<?= $imovel['latitude'] ?>,<?= $imovel['longitude'] ?>&z=15&output=embed"
                        ></iframe>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
       <div class="property-sidebar">
    <!-- Informações do Imóvel (Sidebar) -->
    <div class="property-sidebar__info">
        <h2>Informações do Imóvel</h2>
        <p>Quer receber mais informações deste imóvel? Entre em contato agora mesmo!</p>
        
        <?php if ($message): ?>
            <div class="message <?= $messageClass ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Formulário de Contato -->
        <form action="" method="POST" class="property-contact-form">
            <!-- CSRF Protection -->
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <div class="form-group">
                <input type="text" name="nome" placeholder="Seu Nome" required class="form-control"
                       value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" maxlength="100">
            </div>
            
            <div class="form-group">
                <input type="tel" name="telefone" placeholder="Seu Telefone" required class="form-control"
                       value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>" maxlength="20">
            </div>
            
            <div class="form-group">
                <input type="email" name="email" placeholder="Seu Email" required class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" maxlength="100">
            </div>
            
            <div class="form-group">
                <textarea name="mensagem" placeholder="Sua Mensagem" class="form-control" rows="5" maxlength="1000"><?= htmlspecialchars($_POST['mensagem'] ?? $mensagem_padrao) ?></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" class="primary-button property-contact-form__submit">ENVIAR</button>
            </div>
        </form>
        
        <!-- Contato WhatsApp -->
        <div class="property-whatsapp">
            <h3>Consultar Disponibilidade</h3>
            <a href="https://api.whatsapp.com/send?phone=<?= WHATSAPP_NUMBER ?>&text=<?= urlencode($mensagem_padrao) ?>" 
               class="whatsapp-button" target="_blank">
                <i class="fab fa-whatsapp"></i> CONTATO VIA WHATSAPP
            </a>
        </div>
    </div>
</div>
        
    </div>
</section>

<!-- Lightbox -->
<div class="lightbox" id="propertyLightbox">
    <span class="lightbox__close" id="lightboxClose">&times;</span>
    <img class="lightbox__image" id="lightboxImage">
    
    <a class="lightbox__prev" id="lightboxPrev">&#10094;</a>
    <a class="lightbox__next" id="lightboxNext">&#10095;</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variáveis para galeria
    const mainImage = document.getElementById('mainImage');
    const thumbs = document.querySelectorAll('.property-gallery__thumb');
    const prevButton = document.getElementById('prevImage');
    const nextButton = document.getElementById('nextImage');
    let currentIndex = 0;
    
    // Variáveis para lightbox
    const lightbox = document.getElementById('propertyLightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxClose = document.getElementById('lightboxClose');
    const lightboxPrev = document.getElementById('lightboxPrev');
    const lightboxNext = document.getElementById('lightboxNext');
    
    // Carregar todas as imagens em um array
    const images = Array.from(thumbs).map(thumb => thumb.dataset.image);
    
    // Função para atualizar a imagem principal
    function updateMainImage(index) {
        mainImage.src = images[index];
        
        // Atualizar miniatura ativa
        thumbs.forEach(thumb => thumb.classList.remove('property-gallery__thumb--active'));
        thumbs[index].classList.add('property-gallery__thumb--active');
        
        currentIndex = index;
    }
    
    // Event listeners para miniaturas
    thumbs.forEach((thumb, index) => {
        thumb.addEventListener('click', function() {
            updateMainImage(index);
        });
        
        // Abrir lightbox ao clicar na imagem principal
        thumb.addEventListener('dblclick', function() {
            openLightbox(index);
        });
    });
    
    // Event listeners para botões de navegação
    prevButton.addEventListener('click', function() {
        let newIndex = currentIndex - 1;
        if (newIndex < 0) newIndex = images.length - 1;
        updateMainImage(newIndex);
    });
    
    nextButton.addEventListener('click', function() {
        let newIndex = currentIndex + 1;
        if (newIndex >= images.length) newIndex = 0;
        updateMainImage(newIndex);
    });
    
    // Event listener para imagem principal (abrir lightbox)
    mainImage.addEventListener('click', function() {
        openLightbox(currentIndex);
    });
    
    // Funções do Lightbox
    function openLightbox(index) {
        lightboxImage.src = images[index];
        lightbox.style.display = 'block';
        currentIndex = index;
    }
    
    lightboxClose.addEventListener('click', function() {
        lightbox.style.display = 'none';
    });
    
    lightboxPrev.addEventListener('click', function() {
        let newIndex = currentIndex - 1;
        if (newIndex < 0) newIndex = images.length - 1;
        lightboxImage.src = images[newIndex];
        currentIndex = newIndex;
    });
    
    lightboxNext.addEventListener('click', function() {
        let newIndex = currentIndex + 1;
        if (newIndex >= images.length) newIndex = 0;
        lightboxImage.src = images[newIndex];
        currentIndex = newIndex;
    });
    
    // Fechar lightbox com tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && lightbox.style.display === 'block') {
            lightbox.style.display = 'none';
        }
    });
});
</script>