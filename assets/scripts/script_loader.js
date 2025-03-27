// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Search toggle functionality
    const searchToggle = document.getElementById('searchToggle');
    const searchBar = document.getElementById('searchBar');
    
    if (searchToggle && searchBar) {
        searchToggle.addEventListener('click', function() {
            searchBar.classList.toggle('active');
            document.body.classList.toggle('search-active');
        });
    }
    
    // Sticky header functionality
    const header = document.querySelector('.site-header');
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('site-header--sticky');
            } else {
                header.classList.remove('site-header--sticky');
            }
        });
    }
    
    // WhatsApp floating button functionality
    const whatsappFloat = document.querySelector('.whatsapp-float');
    
    if (whatsappFloat) {
        // Verificar se estamos em uma página de imóvel específico
        // Isso verifica se a URL contém "/imovel/" seguido por números
        if (/\/imovel\/\d+/.test(window.location.pathname)) {
            // Adicionar classe de animação
            whatsappFloat.classList.add('whatsapp-float--shaking');
            
            // Parar animação quando passar o mouse
            const whatsappButton = whatsappFloat.querySelector('.whatsapp-float__button');
            if (whatsappButton) {
                whatsappButton.addEventListener('mouseenter', function() {
                    this.style.animation = 'none';
                });
                
                // Retomar animação ao retirar o mouse
                whatsappButton.addEventListener('mouseleave', function() {
                    this.style.animation = '';
                });
            }
        }
    }
});