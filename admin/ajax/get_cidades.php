<?php
// Include configuration files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
// At the beginning of get_cidades.php, add this for debugging:
    try {
        if (!isset($databaseConnection) || $databaseConnection === null) {
            // Try to reconnect if connection isn't available
            $databaseConnection = connectDatabase();
            if (!$databaseConnection) {
                throw new Exception("Failed to establish database connection");
            }
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database connection error: ' . $e->getMessage()]);
        exit;
    }
// Set header to JSON
header('Content-Type: application/json');

// Check if the ID parameter is set
if (!isset($_GET['id_estado']) || empty($_GET['id_estado'])) {
    echo json_encode(['error' => 'ID do estado não fornecido']);
    exit;
}

$id_estado = (int)$_GET['id_estado'];

// Get cities for the given state
try {
    $stmt = $databaseConnection->prepare(
        "SELECT id, nome FROM sistema_cidades 
         WHERE id_estado = :id_estado 
         ORDER BY nome ASC"
    );
    $stmt->bindParam(':id_estado', $id_estado);
    $stmt->execute();
    
    $cidades = $stmt->fetchAll();
    
    // Return cities as JSON
    echo json_encode(['cidades' => $cidades]);
    exit;
} catch (PDOException $e) {
    // Log error
    logError("AJAX error fetching cities: " . $e->getMessage());
    
    // Return error
    echo json_encode(['error' => 'Erro ao buscar cidades: ' . $e->getMessage()]);
    exit;
}