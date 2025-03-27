<?php
// ===================================
// Site Configuration
// ===================================
define('MODE', 'Development');
define('SITE_NAME', 'Mariza Corretora de Imóveis');
define('BASE_URL', 'http://localhost:8888');
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
    define('DB_HOST', 'production.host.com');
    define('DB_NAME', 'casadeba_site');
    define('DB_USER', 'prod_user');
    define('DB_PASS', 'secure_password');
}