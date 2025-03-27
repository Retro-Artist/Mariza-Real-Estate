<?php
// Security check - already handled in index.php
if (!isset($_SESSION['admin_id'])) {
    return;
}

// Get current page for highlighting active menu item
$current_page = isset($page) ? $page : 'dashboard';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - <?= SITE_NAME ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin/admin.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar__header">
                <div class="admin-sidebar__logo">
                    <img src="<?= BASE_URL ?>/assets/img/site-logo.webp" alt="<?= SITE_NAME ?>">
                </div>
                <h2 class="admin-sidebar__title">Painel Admin</h2>
                <button class="admin-sidebar__toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <nav class="admin-sidebar__nav">
                <ul class="admin-sidebar__menu">
                    <li class="admin-sidebar__item <?= $current_page === 'dashboard' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>/admin/" class="admin-sidebar__link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="admin-sidebar__item <?= $current_page === 'imoveis' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>/admin/imoveis" class="admin-sidebar__link">
                            <i class="fas fa-home"></i>
                            <span>Imóveis</span>
                        </a>
                    </li>
                    
                    <li class="admin-sidebar__item <?= $current_page === 'categorias' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>/admin/categorias" class="admin-sidebar__link">
                            <i class="fas fa-tags"></i>
                            <span>Categorias</span>
                        </a>
                    </li>
                    
                    <li class="admin-sidebar__divider"></li>
                    
                    <li class="admin-sidebar__item">
                        <a href="<?= BASE_URL ?>/admin/Admin_Logout.php" class="admin-sidebar__link">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Sair</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <!-- Top Bar -->
            <header class="admin-topbar">
                <div class="admin-topbar__left">
                    <h1 class="admin-topbar__title">
                        <?php
                        switch ($current_page) {
                            case 'dashboard':
                                echo 'Dashboard';
                                break;
                            case 'imoveis':
                                echo isset($param) ? ucfirst($param) . ' Imóvel' : 'Gerenciar Imóveis';
                                break;
                            case 'categorias':
                                echo isset($param) ? ucfirst($param) . ' Categoria' : 'Gerenciar Categorias';
                                break;
                            default:
                                echo 'Dashboard';
                        }
                        ?>
                    </h1>
                </div>
                
                <div class="admin-topbar__right">
                    <div class="admin-topbar__user">
                        <span class="admin-topbar__username">
                            <i class="fas fa-user"></i>
                            <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Usuário') ?>
                        </span>
                    </div>
                    
                    <a href="<?= BASE_URL ?>/" class="admin-topbar__site-link" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        Ver Site
                    </a>
                </div>
            </header>
            
            <!-- Content Area -->
            <div class="admin-content">