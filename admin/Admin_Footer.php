</div><!-- End of admin-content -->
        </main><!-- End of admin-main -->
    </div><!-- End of admin-container -->
    
        <!-- Scripts -->
        <script>
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
        
        // Fix links to main site
        const mainSiteLinks = document.querySelectorAll('a[href^="<?= BASE_URL ?>"]');
        mainSiteLinks.forEach(link => {
            const href = link.getAttribute('href');
            // If the link is to the main site but doesn't have a trailing slash after BASE_URL
            if (href === '<?= BASE_URL ?>' || href.match(/^<?= BASE_URL ?>\?/)) {
                // Add a trailing slash
                link.setAttribute('href', href.replace(/^(<?= BASE_URL ?>)/, '$1/'));
            }
        });
    });
    </script>
</body>
</html>