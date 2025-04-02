<?php
// Iniciar a sessão
session_start();

// Incluir configurações e funções
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_functions.php';

// Verificar se o administrador está logado
$isLoggedIn = isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);

// Se não estiver logado e não estiver na página de login, redirecionar
if (!$isLoggedIn && !isset($_GET['page']) && $_GET['page'] !== 'login') {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Obter a página atual da URL
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Definir o mapeamento de páginas para arquivos PHP
$pages = [
    // Dashboard (agora é o calendário)
    'dashboard' => 'Admin_Dashboard.php',
    
    // Páginas de Lembretes (antigos calendários)
    'Lembrete_Create' => 'paginas/Lembrete_Create.php',
    'Lembrete_Update' => 'paginas/Lembrete_Update.php',
    'Lembrete_View' => 'paginas/Lembrete_View.php',
    'Lembrete_Delete' => 'paginas/Lembrete_Delete.php',
    
    // Outras páginas
    'Category_Admin' => 'paginas/Category_Admin.php',
    'Category_Create' => 'paginas/Category_Create.php',
    'Category_Update' => 'paginas/Category_Update.php',
    'Category_Delete' => 'paginas/Category_Delete.php',
    
    'Property_Admin' => 'paginas/Property_Admin.php',
    'Property_Create' => 'paginas/Property_Create.php',
    'Property_Update' => 'paginas/Property_Update.php',
    'Property_Delete' => 'paginas/Property_Delete.php',
    
    'Client_Admin' => 'paginas/Client_Admin.php',
    'Client_Create' => 'paginas/Client_Create.php',
    'Client_Update' => 'paginas/Client_Update.php',
    'Client_Delete' => 'paginas/Client_Delete.php',
    'Client_View' => 'paginas/Client_View.php',
    
    'Atendimento_Admin' => 'paginas/Atendimento_Admin.php',
    'Atendimento_Create' => 'paginas/Atendimento_Create.php', 
    'Atendimento_Update' => 'paginas/Atendimento_Update.php',
    'Atendimento_Delete' => 'paginas/Atendimento_Delete.php',
    'Atendimento_View' => 'paginas/Atendimento_View.php'
];

// Verificar se a página solicitada existe no mapeamento
if (!array_key_exists($page, $pages)) {
    // Página não encontrada, redirecionar para o dashboard
    header('Location: ' . BASE_URL . '/admin/index.php?page=dashboard');
    exit;
}

// Incluir cabeçalho se o usuário estiver logado
if ($isLoggedIn) {
    include_once 'Admin_Header.php';
}

// Incluir o arquivo da página
include_once $pages[$page];

// Incluir rodapé se o usuário estiver logado
if ($isLoggedIn) {
    include_once 'Admin_Footer.php';
}
?>