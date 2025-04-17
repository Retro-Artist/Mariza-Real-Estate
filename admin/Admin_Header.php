<?php
// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default redirect logic if not set by the page
if (!isset($need_redirect)) {
    $need_redirect = false;
}

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Get current page for highlighting active menu item
$current_page = isset($page) ? $page : 'Calendar';
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="admin-body">
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar__header">
                <div class="admin-sidebar__logo">
                    <a href="/admin">
                        <img src="<?= BASE_URL ?>/assets/img/Logo-White.png" alt="<?= SITE_NAME ?>">
                    </a>
                </div>
                <button class="admin-sidebar__toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <nav class="admin-sidebar__nav">
                <ul class="admin-sidebar__menu">
                    <!-- Calendário como item primário do menu -->
                    <li class="admin-sidebar__item <?= $current_page === 'Calendar' || strpos($current_page, 'Calendar_') === 0 ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar" class="admin-sidebar__link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Meus Lembretes</span>
                        </a>
                    </li>

                    <li class="admin-sidebar__item <?= strpos($current_page, 'Property_') === 0 ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Admin" class="admin-sidebar__link">
                            <i class="fas fa-home"></i>
                            <span>Cadastrar Imóveis</span>
                        </a>
                    </li>

                    <li class="admin-sidebar__item <?= strpos($current_page, 'Client_') === 0 ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin" class="admin-sidebar__link">
                            <i class="fas fa-users"></i>
                            <span>Gerenciar Clientes</span>
                        </a>
                    </li>

                    <li class="admin-sidebar__item <?= strpos($current_page, 'Atendimento') === 0 ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Admin" class="admin-sidebar__link">
                            <i class="fas fa-headset"></i>
                            <span>Atender Clientes</span>
                        </a>
                    </li>

                    <li class="admin-sidebar__item admin-sidebar__item--has-submenu <?= strpos($current_page, 'State_') === 0 || strpos($current_page, 'City_') === 0 || strpos($current_page, 'Neighborhood_') === 0 || strpos($current_page, 'Category_') === 0 || strpos($current_page, 'User_') === 0 ? 'active' : '' ?>">
                        <a href="#" class="admin-sidebar__link admin-sidebar__link--toggle">
                            <i class="fas fa-cogs"></i>
                            <span>Configurações</span>
                            <i class="fas fa-chevron-down admin-sidebar__submenu-icon"></i>
                        </a>
                        <ul class="admin-sidebar__submenu">
                            <?php if (isset($_SESSION['admin_level']) && ($_SESSION['admin_level'] == '1' || $_SESSION['admin_level'] == 1)): ?>
                                <li class="admin-sidebar__submenu-item <?= strpos($current_page, 'User_') === 0 ? 'active' : '' ?>">
                                    <a href="<?= BASE_URL ?>/admin/index.php?page=User_Admin" class="admin-sidebar__submenu-link">
                                        <i class="fas fa-users-cog"></i>
                                        <span>Usuários do Site</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li class="admin-sidebar__submenu-item <?= strpos($current_page, 'Category_') === 0 ? 'active' : '' ?>">
                                <a href="<?= BASE_URL ?>/admin/index.php?page=Category_Admin" class="admin-sidebar__submenu-link">
                                    <i class="fas fa-tags"></i>
                                    <span>Minhas Categorias</span>
                                </a>
                            </li>
                            <li class="admin-sidebar__submenu-item <?= strpos($current_page, 'State_') === 0 ? 'active' : '' ?>">
                                <a href="<?= BASE_URL ?>/admin/index.php?page=State_Admin" class="admin-sidebar__submenu-link">
                                    <i class="fas fa-map"></i>
                                    <span>Meus Estados</span>
                                </a>
                            </li>
                            <li class="admin-sidebar__submenu-item <?= strpos($current_page, 'City_') === 0 ? 'active' : '' ?>">
                                <a href="<?= BASE_URL ?>/admin/index.php?page=City_Admin" class="admin-sidebar__submenu-link">
                                    <i class="fas fa-city"></i>
                                    <span>Minhas Cidades</span>
                                </a>
                            </li>
                            <li class="admin-sidebar__submenu-item <?= strpos($current_page, 'Neighborhood_') === 0 ? 'active' : '' ?>">
                                <a href="<?= BASE_URL ?>/admin/index.php?page=Neighborhood_Admin" class="admin-sidebar__submenu-link">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>Meus Bairros</span>
                                </a>
                            </li>

                        </ul>
                    </li>

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
                <h1 class="admin-topbar__title"> <?= SITE_NAME ?> </h1>

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