<?php
// ===================================
// Site Configuration
// ===================================
define('MODE', 'Development');
define('SITE_NAME', 'Mariza Marquezan - Corretora');

// ===================================
// Database Configuration
// ===================================
if (MODE === 'Development') {
    define('BASE_URL', 'http://localhost:8888');
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'casadeba_site');
    define('DB_USER', 'root');
    define('DB_PASS', 'root');
} else {
    define('BASE_URL', 'https://marizamarquezanimoveis.com');
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'casadeba_site');
    define('DB_USER', 'casadeba_ubi');
    define('DB_PASS', 'qH3&]Qv=l!bT');
}

define('UPLOADS_URL', BASE_URL . '/uploads/');
define('IMAGES_URL', BASE_URL . '/assets/img/');
define('WHATSAPP_NUMBER', '5511999999999');