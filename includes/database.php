<?php
// Certifique-se que o config.php foi incluído
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config/config.php';
}

// Verificar se a função já existe antes de definir
if (!function_exists('logError')) {
    // Função para registrar erros
    function logError($message) {
        // Log to a file in the logs directory
        $logDir = __DIR__ . '/../logs/';
        
        // Criar o diretório de logs se não existir
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Nome do arquivo de log baseado na data
        $logFile = $logDir . 'error_' . date('Y-m-d') . '.log';
        
        // Formatar a mensagem de log
        $logMessage = date('Y-m-d H:i:s') . " - " . $message . PHP_EOL;
        
        // Adicionar ao arquivo de log
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        // Opcional: também pode enviar email ou notificação para o administrador
        if (defined('MODE') && MODE !== 'Development') {
            error_log($message, 1, 'admin@yourdomain.com');
        }
    }
}

// Database connection using PDO
function connectDatabase() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        // Log error
        logError("Database connection failed: " . $e->getMessage());
        
        // For development, you might want to see the actual error
        if (defined('MODE') && MODE === 'Development') {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
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