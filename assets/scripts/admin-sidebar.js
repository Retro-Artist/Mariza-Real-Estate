document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar navigation on mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
        });
    }
    
    // Toggle submenu items
    const submenuToggles = document.querySelectorAll('.admin-sidebar__link--toggle');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            parent.classList.toggle('expanded');
        });
    });
    
    // Auto-expand submenu if any of its children are active
    const activeSubmenuItems = document.querySelectorAll('.admin-sidebar__submenu-item.active');
    activeSubmenuItems.forEach(item => {
        const parentSubmenu = item.closest('.admin-sidebar__item--has-submenu');
        if (parentSubmenu) {
            parentSubmenu.classList.add('expanded');
        }
    });
});