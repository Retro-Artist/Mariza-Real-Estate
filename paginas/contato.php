<?php
// Include security functions
require_once __DIR__ . '/../includes/security_functions.php';

// Processar o formulário de contato
$message = '';
$messageClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form with security checks
    $formResult = processSecureForm($_POST, 'contato');
    
    if ($formResult['success']) {
        // Get sanitized data
        $sanitizedData = $formResult['data'];
        $nome = $sanitizedData['nome'];
        $email = $sanitizedData['email'];
        $telefone = $sanitizedData['telefone'] ?? '';
        $mensagem = $sanitizedData['mensagem'] ?? '';
        
        try {
            // Inserir o contato na tabela sistema_interacao
            $data = date('Y-m-d');
            $hora = date('H:i:s');
            $local = 'Site';
            $status = 'Pendente';
            
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
            
            // Mostrar mensagem de sucesso
            $message = 'Obrigado pelo seu contato! Retornaremos em breve.';
            $messageClass = 'success';
            
            // Log successful submission
            logSecurityEvent('CONTACT', 'Successful contact form submission', [
                'email' => $email,
                'name' => $nome
            ]);
            
            // Reset form fields after successful submission
            $nome = $email = $telefone = $mensagem = '';
        } catch (PDOException $e) {
            logError("Error saving contact form: " . $e->getMessage());
            logSecurityEvent('ERROR', 'Database error on contact form', [
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

// Generate CSRF token for the form
$csrfToken = generateCSRFToken();
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
                    <!-- CSRF Protection -->
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome">Seu Nome <span class="required">*</span></label>
                            <input type="text" id="nome" name="nome" required class="form-control" 
                                   value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" maxlength="100">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Seu Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required class="form-control" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" maxlength="100">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefone">Seu Telefone</label>
                        <input type="tel" id="telefone" name="telefone" class="form-control" 
                               value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>" maxlength="20">
                    </div>
                    
                    <div class="form-group">
                        <label for="mensagem">Sua Mensagem <span class="required">*</span></label>
                        <textarea id="mensagem" name="mensagem" required class="form-control" rows="5" maxlength="1000"><?= htmlspecialchars($_POST['mensagem'] ?? '') ?></textarea>
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