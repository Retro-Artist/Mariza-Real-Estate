<?php
declare(strict_types=1);

// Configure session before starting
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_secure', '1'); // Use only over HTTPS

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start session
session_start();

// Include necessary files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/database.php';

try {
    // Get the requested page and parse URL segments
    $request_uri = $_SERVER['REQUEST_URI'];
    $base_path = parse_url(BASE_URL, PHP_URL_PATH) ?: '';

    // Remove trailing slash from base_path if it exists
    $base_path = rtrim($base_path, '/');

    // Remove base path from request URI
    if (!empty($base_path) && str_starts_with($request_uri, $base_path)) {
        $request_uri = substr($request_uri, strlen($base_path));
    }

    // Remove query string if any
    $uri_parts = explode('?', $request_uri);
    $path = trim($uri_parts[0], '/');

    // If path is empty, set it to 'home'
    $path = $path ?: 'home';

    $path_segments = explode('/', $path);

    // Determine the page and its parameters
    $page = $path_segments[0] ?? 'home';
    $param = $path_segments[1] ?? null;

    // Basic security checks - use modern PHP sanitization
    $page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page) ?: 'home';
    $param = $param ? preg_replace('/[^a-zA-Z0-9_-]/', '', $param) : null;

    // Include header
    include 'header.php';

    // Routing for public pages
    match ($page) {
        'home' => include 'paginas/home.php',
        'imoveis' => include 'paginas/archive.php',
        'imovel' => handleImovelRoute($param),
        'contato' => include 'paginas/contato.php',
        'sobre' => include 'paginas/sobre.php',
        'anuncie' => include 'paginas/anuncie.php',
        'admin' => handleAdminRoute(),
        default => include 'paginas/404.php'
    };

    // Include footer
    include 'footer.php';

} catch (Throwable $e) {
    // Log any unexpected errors
    logError('Unhandled exception: ' . $e->getMessage());
    
    // Show user-friendly error page
    if (MODE === 'Development') {
        echo "An error occurred: " . $e->getMessage();
    } else {
        include 'paginas/error.php';
    }
    exit;
}

// Helper function to handle imovel route
function handleImovelRoute(?string $param): void {
    // Garantir que o ID existe e é um número
    $imovel_id = $param && is_numeric($param) ? (int)$param : 0;
    if ($imovel_id <= 0) {
        include 'paginas/404.php';
    } else {
        $_GET['id'] = $imovel_id; // Definir o ID para uso na página single.php
        include 'paginas/single.php';
    }
}

// Helper function to handle admin route
function handleAdminRoute(): void {
    // Redirect to admin area
    header('Location: ' . BASE_URL . '/admin/');
    exit;
}