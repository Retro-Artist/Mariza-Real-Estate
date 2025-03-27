<?php
// Include configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/database.php';

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
    
    echo json_encode(['cidades' => $cidades]);
} catch (PDOException $e) {
    // Log error
    logError("AJAX error fetching cities: " . $e->getMessage());
    
    // Return error
    echo json_encode(['error' => 'Erro ao buscar cidades']);
}