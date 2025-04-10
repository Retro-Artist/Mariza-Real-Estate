<?php
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- Header -->
    <header class="site-header">
        <div class="site-header__top">
            <div class="container">
                <!-- Accent bar on top -->
            </div>
        </div>
        <div class="site-header__wrapper">
            <!-- Logo -->
            <a href="<?= BASE_URL ?>" class="site-header__logo">
                <img src="<?= IMAGES_URL ?>site-logo.webp" alt="<?= SITE_NAME ?>">
            </a>

            <!-- Navigation -->
            <nav class="site-header__nav">
                <a href="<?= BASE_URL ?>" class="site-header__nav-item">HOME</a>
                <a href="<?= BASE_URL ?>/imoveis" class="site-header__nav-item">IMÓVEIS</a>
                <a href="<?= BASE_URL ?>/contato" class="site-header__nav-item">CONTATO</a>
                <a href="https://wa.me/5577999367802?text=Ol%C3%A1%20tenho%20um%20im%C3%B3vel%20que%20pretendo%20vender%20ou%20alugar" class="site-header__nav-item">ANUNCIE SEU IMÓVEL</a>
                <button id="searchToggle" class="site-header__search-toggle" aria-label="Buscar no site">
                    <i class="fas fa-search search-open"></i>
                    <i class="fas fa-times search-close"></i>
                </button>
            </nav>
        </div>
            <!-- Search Bar (Hidden by default) -->
    <div class="search-bar" id="searchBar">
        <div class="container">
            <form action="<?= BASE_URL ?>/imoveis" method="GET" class="search-bar__form">
                <input type="text" name="busca" placeholder="Digite sua busca..." class="search-bar__input">
            </form>
        </div>
    </div>
    </header>

