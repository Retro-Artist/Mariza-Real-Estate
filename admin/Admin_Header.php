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
                <button class="admin-sidebar__toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <nav class="admin-sidebar__nav">
                <ul class="admin-sidebar__menu">
                    <li class="admin-sidebar__item <?= $current_page === 'dashboard' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>/admin/index.php?page=dashboard" class="admin-sidebar__link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Calendário</span>
                        </a>
                    </li>

                    <li class="admin-sidebar__item <?= strpos($current_page, 'Lembrete_') === 0 || strpos($current_page, 'Calendar_') === 0 ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Lembrete_Create" class="admin-sidebar__link">
                            <i class="fas fa-sticky-note"></i>
                            <span>Lembretes</span>
                        </a>
                    </li>

                    <li class="admin-sidebar__item <?= strpos($current_page, 'Property_') === 0 ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Admin" class="admin-sidebar__link">
                            <i class="fas fa-home"></i>
                            <span>Imóveis</span>
                        </a>
                    </li>

                    <li class="admin-sidebar__item <?= strpos($current_page, 'Category_') === 0 ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Category_Admin" class="admin-sidebar__link">
                            <i class="fas fa-tags"></i>
                            <span>Categorias</span>
                        </a>
                    </li>

                    <li class="admin-sidebar__item <?= strpos($current_page, 'Client_') === 0 ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin" class="admin-sidebar__link">
                            <i class="fas fa-users"></i>
                            <span>Clientes</span>
                        </a>
                    </li>

                    <li class="admin-sidebar__item <?= strpos($current_page, 'Atendimento') === 0 ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Admin" class="admin-sidebar__link">
                            <i class="fas fa-headset"></i>
                            <span>Atendimentos</span>
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
                <h1 class="admin-topbar__title"> Logo </h1>

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