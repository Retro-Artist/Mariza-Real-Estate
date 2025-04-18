<?php
/**
 * Enhanced security functions for form handling
 * Place this in includes/security_functions.php
 */

/**
 * Sanitize user input to prevent XSS attacks
 * 
 * @param string $input The user input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
        return $input;
    }
    
    // First, remove any HTML tags
    $input = strip_tags($input);
    
    // Convert special characters to HTML entities
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    return $input;
}

/**
 * Validate email address
 * 
 * @param string $email The email to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (basic format check)
 * 
 * @param string $phone The phone number to validate
 * @return bool True if valid, false otherwise
 */
function validatePhone($phone) {
    // Remove non-digit characters for validation
    $digitsOnly = preg_replace('/\D/', '', $phone);
    
    // Check if we have a reasonable number of digits (8-15 is common worldwide)
    return strlen($digitsOnly) >= 8 && strlen($digitsOnly) <= 15;
}

/**
 * Check for suspicious content in form submission
 * 
 * @param array $data Form data to check
 * @return bool|string False if clean, error message if suspicious
 */
function checkSuspiciousContent($data) {
    // List of suspicious patterns to check for
    $suspiciousPatterns = [
        '/<.*>/',                 // HTML tags that might have survived stripping
        '/https?:\/\//',          // URLs
        '/\[url/',                // BBCode URLs
        '/javascript:/',          // JavaScript protocol
        '/document\./',           // JavaScript DOM manipulation
        '/eval\(/',               // JavaScript eval function
        '/exec\(/',               // Function execution attempt
        '/SELECT.*FROM/',         // SQL queries
        '/UNION.*SELECT/',        // SQL injection
        '/INSERT.*INTO/',         // SQL injection
        '/UPDATE.*SET/',          // SQL injection
        '/DELETE.*FROM/',         // SQL injection
        '/HAVING/',               // SQL injection
        '/SLEEP\(/',              // SQL timing attacks
        '/base64_/',              // PHP encoding functions
    ];
    
    // Convert data to string if it's an array
    if (is_array($data)) {
        $content = implode(' ', $data);
    } else {
        $content = (string) $data;
    }
    
    // Check each pattern
    foreach ($suspiciousPatterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return 'Conteúdo suspeito detectado. Por favor, remova quaisquer links, códigos ou comandos da sua mensagem.';
        }
    }
    
    // Check for extremely long inputs that might be DoS attempts
    if (strlen($content) > 5000) {
        return 'Mensagem muito longa. Por favor, limite sua mensagem a 5000 caracteres.';
    }
    
    return false;
}

/**
 * Rate limit form submissions to prevent spam
 * 
 * @param string $type The type of form being submitted
 * @return bool|string False if allowed, error message if rate limited
 */
function checkRateLimit($type) {
    // Initialize session if not started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $now = time();
    $limitKey = 'form_submission_' . $type;
    $limitWindowKey = 'form_submission_window_' . $type;
    $limitCount = 3; // Maximum number of submissions allowed
    $limitWindow = 3600; // Time window in seconds (1 hour)
    
    // Initialize or update the submission window
    if (!isset($_SESSION[$limitWindowKey]) || $now - $_SESSION[$limitWindowKey] > $limitWindow) {
        $_SESSION[$limitWindowKey] = $now;
        $_SESSION[$limitKey] = 1;
        return false;
    }
    
    // Check if submission limit is reached
    if (isset($_SESSION[$limitKey]) && $_SESSION[$limitKey] >= $limitCount) {
        return 'Você atingiu o limite de envios. Por favor, tente novamente mais tarde.';
    }
    
    // Increment submission count
    $_SESSION[$limitKey] = isset($_SESSION[$limitKey]) ? $_SESSION[$limitKey] + 1 : 1;
    
    return false;
}

/**
 * Log security events
 * 
 * @param string $type Event type
 * @param string $message Event details
 * @param array $context Additional context
 */
function logSecurityEvent($type, $message, $context = []) {
    // Get client IP address
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Format the log entry
    $logEntry = date('Y-m-d H:i:s') . ' | ' . $ip . ' | ' . $type . ' | ' . $message;
    
    // Add context data if available
    if (!empty($context)) {
        $logEntry .= ' | ' . json_encode($context);
    }
    
    // Write to log file
    $logFile = __DIR__ . '/../logs/security_' . date('Y-m-d') . '.log';
    
    // Ensure logs directory exists
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    // Append to log file
    file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND);
}

/**
 * Generate a CSRF token for form protection
 * 
 * @return string The CSRF token
 */
function generateCSRFToken() {
    // Initialize session if not started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Generate a new token if it doesn't exist
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from form submission
 * 
 * @param string $token The token to verify
 * @return bool True if valid, false otherwise
 */
function verifyCSRFToken($token) {
    // Initialize session if not started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if token exists and matches
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    
    return true;
}

/**
 * Secure form processing wrapper
 * 
 * @param array $formData The form data to process
 * @param string $formType Type of form for rate limiting
 * @return array Result with status, message, and sanitized data
 */
function processSecureForm($formData, $formType = 'contact') {
    $result = [
        'success' => false,
        'message' => '',
        'data' => []
    ];
    
    // Check CSRF token
    if (!isset($formData['csrf_token']) || !verifyCSRFToken($formData['csrf_token'])) {
        $result['message'] = 'Erro de validação do formulário. Por favor, tente novamente.';
        logSecurityEvent('CSRF', 'Invalid CSRF token', [
            'form' => $formType,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        return $result;
    }
    
    // Check rate limiting
    $rateLimited = checkRateLimit($formType);
    if ($rateLimited !== false) {
        $result['message'] = $rateLimited;
        logSecurityEvent('RATE_LIMIT', 'Rate limit exceeded', [
            'form' => $formType,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        return $result;
    }
    
    // Remove CSRF token from data to be processed
    unset($formData['csrf_token']);
    
    // Sanitize all inputs
    $sanitizedData = sanitizeInput($formData);
    
    // Check for suspicious content
    $suspiciousContent = checkSuspiciousContent($sanitizedData);
    if ($suspiciousContent !== false) {
        $result['message'] = $suspiciousContent;
        logSecurityEvent('SUSPICIOUS', 'Suspicious content detected', [
            'form' => $formType,
            'content' => json_encode($formData)
        ]);
        return $result;
    }
    
    // Validate required fields
    $requiredFields = ['nome', 'email'];
    foreach ($requiredFields as $field) {
        if (empty($sanitizedData[$field])) {
            $result['message'] = 'Por favor, preencha todos os campos obrigatórios.';
            return $result;
        }
    }
    
    // Validate email
    if (isset($sanitizedData['email']) && !validateEmail($sanitizedData['email'])) {
        $result['message'] = 'Por favor, informe um email válido.';
        return $result;
    }
    
    // Validate phone if provided
    if (!empty($sanitizedData['telefone']) && !validatePhone($sanitizedData['telefone'])) {
        $result['message'] = 'Por favor, informe um telefone válido.';
        return $result;
    }
    
    // All checks passed
    $result['success'] = true;
    $result['data'] = $sanitizedData;
    
    return $result;
}