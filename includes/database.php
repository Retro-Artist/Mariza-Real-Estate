<?php
// Certifique-se que o config.php foi incluído
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config/config.php';
}

// Database connection using PDO
function connectDatabase() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        return $pdo;
    } catch (PDOException $e) {

        // For development, you might want to see the actual error
        if (MODE === 'Development') {
            echo "Connection failed: " . $e->getMessage();
        } else {
            // In production, show a friendly error message
            echo "We're experiencing technical difficulties. Please try again later.";
        }
        exit;
    }
}

// Get database connection
$databaseConnection = connectDatabase();