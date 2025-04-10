<?php
// ===================================
// Site Configuration
// ===================================
define('MODE', 'Development');
define('SITE_NAME', 'Mariza Corretora de Imóveis');
define('BASE_URL', 'https://marizamarquezanimoveis.com'); // Update with your actual domain
define('UPLOADS_URL', BASE_URL . '/uploads/');
define('IMAGES_URL', BASE_URL . '/assets/img/');
define('WHATSAPP_NUMBER', '5511999999999');

// ===================================
// Database Configuration
// ===================================
if (MODE === 'Development') {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'casadeba_site');
    define('DB_USER', 'root');
    define('DB_PASS', 'root');
} else {
    define('DB_HOST', 'localhost'); // Or your production host
    define('DB_NAME', 'casadeba_site');
    define('DB_USER', 'casadeba_ubi');
    define('DB_PASS', 'Mariza@2023');
}

// Note: Session settings should be set before session_start()