<?php
// Start session
session_start();

// Include configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id']) && basename($_SERVER['PHP_SELF']) != 'Admin_Login.php') {
    // Redirect to login page if not logged in
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Get the requested page and parse URL segments
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = parse_url(BASE_URL, PHP_URL_PATH) ?: '';
$base_path = rtrim($base_path, '/'); // Remove trailing slash if any

// Build admin path with or without base path
$admin_path = $base_path . '/admin';

// Remove admin path from request URI to get the relative path
if (strpos($request_uri, $admin_path) === 0) {
    $path = substr($request_uri, strlen($admin_path));
} else {
    $path = $request_uri;
}

// Remove query string if any
$uri_parts = explode('?', $path);
$path = trim($uri_parts[0], '/');

// If path is empty, set default page
if (empty($path)) {
    $path = 'dashboard';
}

$path_segments = explode('/', $path);

// Determine the page and its parameters
$page = !empty($path_segments[0]) ? $path_segments[0] : 'dashboard';
$param = isset($path_segments[1]) ? $path_segments[1] : null;

// Include header
include 'Admin_Header.php';

// Routing for admin pages
switch ($page) {
    case 'dashboard':
        include 'paginas/Admin_Dashboard.php';
        break;
    case 'categorias':
        if ($param == 'adicionar') {
            include 'paginas/Category_Create.php';
        } elseif ($param == 'editar' && isset($_GET['id'])) {
            include 'paginas/Category_Update.php';
        } elseif ($param == 'excluir' && isset($_GET['id'])) {
            include 'paginas/Category_Delete.php';
        } else {
            include 'paginas/Category_Admin.php';
        }
        break;
    case 'imoveis':
        if ($param == 'adicionar') {
            include 'paginas/Property_Create.php';
        } elseif ($param == 'editar' && isset($_GET['id'])) {
            include 'paginas/Property_Update.php';
        } elseif ($param == 'excluir' && isset($_GET['id'])) {
            include 'paginas/Property_Delete.php';
        } else {
            include 'paginas/Property_Admin.php';
        }
        break;
    default:
        include 'paginas/Admin_Dashboard.php';
        break;
}

// Include footer
include 'Admin_Footer.php';