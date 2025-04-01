<?php
// Include configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/admin_functions.php';

// Set header to JSON
header('Content-Type: application/json');

// Check for proper session
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Check if event IDs are provided
if (!isset($_GET['ids']) || empty($_GET['ids'])) {
    echo json_encode(['error' => 'No event IDs provided']);
    exit;
}

// Parse event IDs
$eventIds = explode(',', $_GET['ids']);
$eventIds = array_map('intval', $eventIds);

// Validate if we have valid IDs
if (empty($eventIds)) {
    echo json_encode(['error' => 'Invalid event IDs']);
    exit;
}

// Get events data
$events = [];
try {
    // Create placeholders for the IN clause
    $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
    
    // Prepare and execute the query
    $stmt = $databaseConnection->prepare(
        "SELECT a.*, u.nome as criador_nome 
         FROM sistema_avisos a
         LEFT JOIN sistema_usuarios u ON a.id_usuario = u.id
         WHERE a.id IN ($placeholders)"
    );
    
    // Bind the event ID parameters
    foreach ($eventIds as $index => $id) {
        $stmt->bindValue($index + 1, $id);
    }
    
    $stmt->execute();
    
    // Fetch results
    $results = $stmt->fetchAll();
    
    // Format the results for JSON response
    foreach ($results as $row) {
        $dataInicio = new DateTime($row['data_inicio']);
        $dataFim = new DateTime($row['data_fim']);
        
        $events[] = [
            'id' => $row['id'],
            'titulo' => $row['titulo'],
            'descricao' => $row['descricao'],
            'para' => $row['para'],
            'prioridade' => $row['prioridade'],
            'data_inicio' => $dataInicio->format('d/m/Y'),
            'hora_inicio' => $dataInicio->format('H:i'),
            'data_fim' => $dataFim->format('d/m/Y'),
            'hora_fim' => $dataFim->format('H:i'),
            'status' => $row['status'],
            'criador' => $row['criador_nome'] ?? 'Sistema'
        ];
    }
    
    // Return the events data
    echo json_encode(['events' => $events]);
} catch (PDOException $e) {
    // Log error
    logError("AJAX error fetching events: " . $e->getMessage());
    
    // Return error
    echo json_encode(['error' => 'Erro ao buscar lembretes']);
}