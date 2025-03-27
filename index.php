<?php
// Start session
session_start();

// Include configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// Get the requested page and parse URL segments
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = parse_url(BASE_URL, PHP_URL_PATH) ?: '';

// Remove trailing slash from base_path if it exists
$base_path = rtrim($base_path, '/');

// Remove base path from request URI
if (!empty($base_path) && strpos($request_uri, $base_path) === 0) {
    $request_uri = substr($request_uri, strlen($base_path));
}

// Remove query string if any
$uri_parts = explode('?', $request_uri);
$path = trim($uri_parts[0], '/');

// If path is empty, set it to 'home'
if (empty($path)) {
    $path = 'home';
}

$path_segments = explode('/', $path);

// Determine the page and its parameters
$page = $path_segments[0];
$param = isset($path_segments[1]) ? $path_segments[1] : null;

// Include header
include 'header.php';

// Routing for public pages
switch ($page) {
    case 'home':
        include 'paginas/home.php';
        break;
    case 'imoveis':
        include 'paginas/archive.php';
        break;
    case 'imovel':
        // Garantir que o ID existe e é um número
        $imovel_id = is_numeric($param) ? (int)$param : 0;
        if ($imovel_id <= 0) {
            include 'paginas/404.php';
        } else {
            $_GET['id'] = $imovel_id; // Definir o ID para uso na página single.php
            include 'paginas/single.php';
        }
        break;
    case 'contato':
        include 'paginas/contato.php';
        break;
    case 'sobre':
        include 'paginas/sobre.php';
        break;
    case 'anuncie':
        include 'paginas/anuncie.php';
        break;
    case 'admin':
        // Redirect to admin area
        header('Location: ' . BASE_URL . '/admin/');
        exit;
    default:
        include 'paginas/404.php';
        break;
}

// Include footer
include 'footer.php';