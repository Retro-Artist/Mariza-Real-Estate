<?php
// Start session
session_start();

// Iniciar output buffering - adicione esta linha no topo de index.php
ob_start();

// Include configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_functions.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id']) && basename($_SERVER['PHP_SELF']) != 'Admin_Login.php') {
    // Redirect to login page if not logged in
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Process quick reminder creation (from modal)
if (isset($_POST['action']) && $_POST['action'] === 'quick_create_reminder') {
    $formData = [
        'titulo' => trim($_POST['titulo'] ?? ''),
        'descricao' => trim($_POST['descricao'] ?? ''),
        'para' => trim($_POST['para'] ?? ''),
        'prioridade' => trim($_POST['prioridade'] ?? 'Normal'),
        'data_inicio' => trim($_POST['selected_date'] ?? ''),
        'hora_inicio' => trim($_POST['hora_inicio'] ?? ''),
        'data_fim' => trim($_POST['data_fim'] ?? ''),
        'hora_fim' => trim($_POST['hora_fim'] ?? ''),
        'status' => 'Pendente'
    ];
    
    // Basic validation
    if (!empty($formData['titulo']) && !empty($formData['data_inicio']) && !empty($formData['data_fim'])) {
        // Create calendar event
        $newEventId = createCalendarEvent($formData);
        
        if ($newEventId) {
            // Set success message
            $_SESSION['alert_message'] = 'Lembrete adicionado com sucesso!';
            $_SESSION['alert_type'] = 'success';
        } else {
            // Set error message
            $_SESSION['alert_message'] = 'Ocorreu um erro ao adicionar o lembrete.';
            $_SESSION['alert_type'] = 'error';
        }
    } else {
        // Set validation error message
        $_SESSION['alert_message'] = 'Por favor, preencha todos os campos obrigatórios.';
        $_SESSION['alert_type'] = 'error';
    }
    
    // Get the current month/year from the selected date
    $selectedDate = new DateTime($formData['data_inicio']);
    $month = $selectedDate->format('n');
    $year = $selectedDate->format('Y');
    
    // Redirect back to calendar with appropriate month/year
    header('Location: ' . BASE_URL . '/admin/index.php?page=Calendar&month=' . $month . '&year=' . $year);
    exit;
}

// Get the requested page from URL or set default to calendar (instead of dashboard)
$page = isset($_GET['page']) ? $_GET['page'] : 'Calendar';

// Include header
include 'Admin_Header.php';

// Routing for admin pages
switch ($page) {
    // Calendar pages (now primary)
    case 'Calendar':
        include 'paginas/Calendar_Admin.php';
        break;
    case 'Calendar_Create':
        include 'paginas/Calendar_Create.php';
        break;
    case 'Calendar_Update':
        include 'paginas/Calendar_Update.php';
        break;
    case 'Calendar_View':
        include 'paginas/Calendar_View.php';
        break;
    case 'Calendar_Delete':
        include 'paginas/Calendar_Delete.php';
        break;

    // Category pages
    case 'Category_Admin':
        include 'paginas/Category_Admin.php';
        break;
    case 'Category_Create':
        include 'paginas/Category_Create.php';
        break;
    case 'Category_Update':
        include 'paginas/Category_Update.php';
        break;
    case 'Category_Delete':
        include 'paginas/Category_Delete.php';
        break;

    // User pages
    case 'User_Admin':
        include 'paginas/User_Admin.php';
        break;
    case 'User_Create':
        include 'paginas/User_Create.php';
        break;
    case 'User_Update':
        include 'paginas/User_Update.php';
        break;
    case 'User_Delete':
        include 'paginas/User_Delete.php';
        break;

    // Property pages
    case 'Property_Admin':
        include 'paginas/Property_Admin.php';
        break;
    case 'Property_Create':
        include 'paginas/Property_Create.php';
        break;
    case 'Property_Update':
        include 'paginas/Property_Update.php';
        break;
    case 'Property_Delete':
        include 'paginas/Property_Delete.php';
        break;

    // Client pages
    case 'Client_Admin':
        include 'paginas/Client_Admin.php';
        break;
    case 'Client_Create':
        include 'paginas/Client_Create.php';
        break;
    case 'Client_Update':
        include 'paginas/Client_Update.php';
        break;
    case 'Client_Delete':
        include 'paginas/Client_Delete.php';
        break;
    case 'Client_View':
        include 'paginas/Client_View.php';
        break;

    // Service Request Management pages
    case 'Atendimento_Admin':
        include 'paginas/Atendimento_Admin.php';
        break;
    case 'Atendimento_Create':
        include 'paginas/Atendimento_Create.php';
        break;
    case 'Atendimento_View':
        include 'paginas/Atendimento_View.php';
        break;
    case 'Atendimento_Update':
        include 'paginas/Atendimento_Update.php';
        break;
    case 'Atendimento_Delete':
        include 'paginas/Atendimento_Delete.php';
        break;

    // For backwards compatibility, redirect dashboard to calendar
    case 'dashboard':
        include 'paginas/Calendar_Admin.php';
        break;

    // Default case
    default:
        include 'paginas/Calendar_Admin.php';
        break;
}
// Include footer
include 'Admin_Footer.php';

// Liberar o output buffer no final do script
ob_end_flush();
