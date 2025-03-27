<?php
// Processar o formulário de contato
$message = '';
$messageClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');
    
    if (empty($nome) || empty($email) || empty($mensagem)) {
        $message = 'Por favor, preencha todos os campos obrigatórios.';
        $messageClass = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Por favor, informe um email válido.';
        $messageClass = 'error';
    } else {
        // Aqui você implementaria o envio do email
        // Por enquanto, apenas mostrar mensagem de sucesso
        $message = 'Obrigado pelo seu contato! Retornaremos em breve.';
        $messageClass = 'success';
    }
}
?>

<!-------------------Contato Section-------------------->
<section class="contato-section">
    <div class="contato-section__wrapper">
        <h1 class="contato-section__title">Entre em Contato</h1>
        <p class="contato-section__subtitle">Estamos à disposição para atender você</p>
        
        <?php if (isset($message) && $message): ?>
            <div class="message <?= $messageClass ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="contato-section__container">
            <!-- Coluna de informações -->
            <div class="contato-section__info">
                <div class="contato-section__card">
                    <h3 class="contato-section__info-title">Fale diretamente conosco</h3>
                    
                    <div class="contato-section__item">
                        <div class="contato-section__icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="contato-section__content">
                            <h4>Telefone</h4>
                            <p>77 99936-7802</p>
                        </div>
                    </div>
                    
                    <div class="contato-section__item">
                        <div class="contato-section__icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contato-section__content">
                            <h4>Email</h4>
                            <p>marizaimoveis2@gmail.com</p>
                        </div>
                    </div>
                    
                    <div class="contato-section__item">
                        <div class="contato-section__icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contato-section__content">
                            <h4>Endereço</h4>
                            <p>Luis Eduardo Magalhães, BA</p>
                        </div>
                    </div>
                    
                    <div class="contato-section__item">
                        <div class="contato-section__icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contato-section__content">
                            <h4>Horário de Atendimento</h4>
                            <p>Segunda à Sexta: 9h às 18h</p>
                            <p>Sábado: 9h às 13h</p>
                        </div>
                    </div>
                    
                    <div class="contato-section__social">
                        <a href="#" class="contato-section__social-link">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="#" class="contato-section__social-link">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="contato-section__social-link">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Formulário -->
            <div class="contato-section__form-container">
                <form action="<?= BASE_URL ?>/contato" method="POST" class="contato-section__form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome">Seu Nome <span class="required">*</span></label>
                            <input type="text" id="nome" name="nome" required class="form-control" 
                                   value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Seu Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required class="form-control" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefone">Seu Telefone</label>
                        <input type="tel" id="telefone" name="telefone" class="form-control" 
                               value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="mensagem">Sua Mensagem <span class="required">*</span></label>
                        <textarea id="mensagem" name="mensagem" required class="form-control" rows="5"><?= htmlspecialchars($_POST['mensagem'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="primary-button contato-section__submit">
                            <i class="fas fa-paper-plane"></i> Enviar Mensagem
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Mapa -->
        <div class="contato-section__map">
            <h2 class="contato-section__map-title">Nossa Localização</h2>
            <div class="contato-section__map-container">
                <!-- Substitua pelo código de incorporação do Google Maps -->
                <iframe 
                    width="100%" 
                    height="450" 
                    style="border:0" 
                    loading="lazy" 
                    allowfullscreen 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d62690.22888016555!2d-45.7909639317243!3d-12.092868448835095!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9338cfbde0d5c97d%3A0xb3cb9f52d2ced5b!2sLu%C3%ADs%20Eduardo%20Magalh%C3%A3es%2C%20BA%2C%2047850-000!5e0!3m2!1spt-BR!2sbr">
                </iframe>
            </div>
        </div>
    </div>
</section>