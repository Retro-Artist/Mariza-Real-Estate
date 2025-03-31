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

/**
 * Script Loader and Event Handler
 * Manages script loading and DOM events for the admin panel
 */

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle functionality
    const sidebarToggle = document.getElementById('sidebarToggle');
    const adminContainer = document.querySelector('.admin-container');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            adminContainer.classList.toggle('sidebar-collapsed');
        });
    }
    
    // Show/hide confirmation dialog for delete actions
    const deleteButtons = document.querySelectorAll('.delete-button');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Tem certeza que deseja excluir este item? Esta ação não pode ser desfeita.')) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide alert messages after 5 seconds
    const alertMessages = document.querySelectorAll('.alert-message');
    
    if (alertMessages.length > 0) {
        setTimeout(function() {
            alertMessages.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display = 'none', 500);
            });
        }, 5000);
    }
    
    // Handle dynamic select functionality for location fields
    initializeLocationSelects();
    
    // Initialize money input masks
    initializeInputMasks();
    
    // Toggle between client types (pessoa física/jurídica)
    initializeClientTypeToggle();
});

/**
 * Initialize location selects (estado, cidade, bairro)
 */
function initializeLocationSelects() {
    const estadoSelect = document.querySelector('.estado-select');
    const cidadeSelect = document.querySelector('.cidade-select');
    const bairroSelect = document.getElementById('id_bairro');
    
    if (!estadoSelect || !cidadeSelect || !bairroSelect) return;
    
    // When estado changes, fetch cidades
    estadoSelect.addEventListener('change', function() {
        const estadoId = this.value;
        cidadeSelect.disabled = !estadoId;
        cidadeSelect.innerHTML = '<option value="">Selecione...</option>';
        bairroSelect.disabled = true;
        bairroSelect.innerHTML = '<option value="">Selecione...</option>';
        
        if (estadoId) {
            fetch(`${window.location.origin}/admin/ajax/get_cidades.php?id_estado=${estadoId}`)
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
                    cidadeSelect.disabled = false;
                })
                .catch(error => console.error('Error fetching cidades:', error));
        }
    });
    
    // When cidade changes, fetch bairros
    cidadeSelect.addEventListener('change', function() {
        const cidadeId = this.value;
        bairroSelect.disabled = !cidadeId;
        bairroSelect.innerHTML = '<option value="">Selecione...</option>';
        
        if (cidadeId) {
            fetch(`${window.location.origin}/admin/ajax/get_bairros.php?id_cidade=${cidadeId}`)
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
                    bairroSelect.disabled = false;
                })
                .catch(error => console.error('Error fetching bairros:', error));
        }
    });
}

/**
 * Initialize input masks for form fields
 */
function initializeInputMasks() {
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
}

/**
 * Initialize client type toggle functionality
 */
function initializeClientTypeToggle() {
    // Toggle client type fields
    const pessoaFisicaRadio = document.getElementById('fisica');
    const pessoaJuridicaRadio = document.getElementById('juridica');
    const pessoaFisicaFields = document.getElementById('pessoa-fisica-fields');
    const pessoaJuridicaFields = document.getElementById('pessoa-juridica-fields');
    const rgField = document.getElementById('rg-field');
    const cnpjField = document.getElementById('cnpj-field');
    
    if (!pessoaFisicaRadio || !pessoaJuridicaRadio) return;
    
    function toggleClientType() {
        if (pessoaFisicaRadio.checked) {
            if (pessoaFisicaFields) pessoaFisicaFields.style.display = 'block';
            if (pessoaJuridicaFields) pessoaJuridicaFields.style.display = 'none';
            if (rgField) rgField.style.display = 'block';
            if (cnpjField) cnpjField.style.display = 'none';
        } else {
            if (pessoaFisicaFields) pessoaFisicaFields.style.display = 'none';
            if (pessoaJuridicaFields) pessoaJuridicaFields.style.display = 'block';
            if (rgField) rgField.style.display = 'none';
            if (cnpjField) cnpjField.style.display = 'block';
        }
    }
    
    // Initial toggle based on checked state
    toggleClientType();
    
    // Add event listeners
    pessoaFisicaRadio.addEventListener('change', toggleClientType);
    pessoaJuridicaRadio.addEventListener('change', toggleClientType);
}