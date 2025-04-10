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
  <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/assets/img/favicon.png">
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
      <!-- Logo on far left -->
      <a href="<?= BASE_URL ?>" class="site-header__logo">
        <img src="<?= IMAGES_URL ?>site-logo.webp" alt="<?= SITE_NAME ?>">
      </a>
      <!-- Desktop Navigation (visible on desktop) -->
      <nav class="site-header__nav">
        <a href="<?= BASE_URL ?>" class="site-header__nav-item">HOME</a>
        <a href="<?= BASE_URL ?>/imoveis" class="site-header__nav-item">IMÓVEIS</a>
        <a href="<?= BASE_URL ?>/contato" class="site-header__nav-item">CONTATO</a>
        <a href="https://wa.me/5577999367802?text=Ol%C3%A1%20tenho%20um%20im%C3%B3vel%20que%20pretendo%20vender%20ou%20alugar" class="site-header__nav-item">ANUNCIE SEU IMÓVEL</a>
      </nav>
      <!-- Action Icons: Search and Hamburger (hamburger only shows on mobile) -->
      <div class="site-header__actions">
        <button id="searchToggle" class="site-header__search-toggle" aria-label="Buscar no site">
          <i class="fas fa-search search-open"></i>
          <i class="fas fa-times search-close"></i>
        </button>
        <button id="hamburgerToggle" class="site-header__hamburger-toggle" aria-label="Menu">
          <i class="fas fa-bars"></i>
        </button>
      </div>
    </div>
    <!-- Mobile Navigation Dropdown -->
    <nav class="site-header__nav-mobile">
      <a href="<?= BASE_URL ?>" class="site-header__nav-item">HOME</a>
      <a href="<?= BASE_URL ?>/imoveis" class="site-header__nav-item">IMÓVEIS</a>
      <a href="<?= BASE_URL ?>/contato" class="site-header__nav-item">CONTATO</a>
      <a href="https://wa.me/5577999367802?text=Ol%C3%A1%20tenho%20um%20im%C3%B3vel%20que%20pretendo%20vender%20ou%20alugar" class="site-header__nav-item">ANUNCIE SEU IMÓVEL</a>
    </nav>
    <!-- Search Bar (Hidden by default) -->
    <div class="search-bar" id="searchBar">
      <div class="container">
        <form action="<?= BASE_URL ?>/imoveis" method="GET" class="search-bar__form">
          <input type="text" name="busca" placeholder="Digite sua busca..." class="search-bar__input">
        </form>
      </div>
    </div>
  </header>

  <script>

    // Toggle Mobile Navigation Dropdown
    const hamburgerToggle = document.getElementById('hamburgerToggle');
    const mobileNav = document.querySelector('.site-header__nav-mobile');
    hamburgerToggle.addEventListener('click', function() {
      mobileNav.classList.toggle('active');
    });
  </script>
</body>
</html>
