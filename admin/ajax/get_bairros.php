<?php
// Include configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Set header to JSON
header('Content-Type: application/json');

// Check if the ID parameter is set
if (!isset($_GET['id_cidade']) || empty($_GET['id_cidade'])) {
    echo json_encode(['error' => 'ID da cidade não fornecido']);
    exit;
}

$id_cidade = (int)$_GET['id_cidade'];

// Get neighborhoods for the given city
try {
    $stmt = $databaseConnection->prepare(
        "SELECT id, bairro FROM sistema_bairros 
         WHERE id_cidade = :id_cidade 
         ORDER BY bairro ASC"
    );
    $stmt->bindParam(':id_cidade', $id_cidade);
    $stmt->execute();
    
    $bairros = $stmt->fetchAll();
    
    echo json_encode(['bairros' => $bairros]);
} catch (PDOException $e) {
    // Log error
    logError("AJAX error fetching neighborhoods: " . $e->getMessage());
    
    // Return error
    echo json_encode(['error' => 'Erro ao buscar bairros']);
}