<?php
/**
 * Setup Admin Script
 * -----------------
 * Simple tool to create the first admin user and verify password hashes.
 * This file only works in Development mode.
 */

// Include required files
require_once __DIR__ . '/../../config/config.php';

// Safety check - only allow in development mode
if (MODE !== 'Development') {
    die('This script can only be executed in Development mode.');
}

// Include database connection
require_once __DIR__ . '/../../includes/database.php';

// Default admin credentials
$admin_name = 'Administrador';
$admin_email = 'admin@example.com';
$admin_password = 'admin123';
$admin_nivel = 'Administrador';

// Initialize variables
$message = '';
$success = false;
$hash_result = null;
$verify_success = false;
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create_admin') {
        // Get form data for creating admin
        $admin_name = trim($_POST['name'] ?? $admin_name);
        $admin_email = trim($_POST['email'] ?? $admin_email);
        $admin_password = trim($_POST['password'] ?? $admin_password);
        $admin_nivel = trim($_POST['nivel'] ?? $admin_nivel);
        
        // Check if admin user already exists
        try {
            $stmt = $databaseConnection->prepare("SELECT COUNT(*) as count FROM sistema_usuarios");
            $stmt->execute();
            $count = $stmt->fetch()['count'];
            
            if ($count > 0) {
                $message = 'Users already exist in the system. This script should only be used to create the first administrator.';
                $success = false;
            } else {
                // Hash password
                $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                
                // Create admin user
                $stmt = $databaseConnection->prepare(
                    "INSERT INTO sistema_usuarios (nome, email, senha, nivel) 
                     VALUES (:nome, :email, :senha, :nivel)"
                );
                
                $stmt->bindParam(':nome', $admin_name);
                $stmt->bindParam(':email', $admin_email);
                $stmt->bindParam(':senha', $hashed_password);
                $stmt->bindParam(':nivel', $admin_nivel);
                
                $stmt->execute();
                
                $message = 'Admin user created successfully!';
                $success = true;
            }
        } catch (PDOException $e) {
            $message = 'Error creating admin user: ' . $e->getMessage();
            $success = false;
        }
    } elseif ($action === 'verify_hash') {
        // Get form data for hash verification
        $password = trim($_POST['verify_password'] ?? '');
        $hash = trim($_POST['hash'] ?? '');
        
        if (!empty($password) && !empty($hash)) {
            $verify_success = password_verify($password, $hash);
            $hash_result = $verify_success ? 'Password matches the hash!' : 'Password does not match the hash.';
        } else {
            $hash_result = 'Please enter both password and hash.';
        }
    } elseif ($action === 'generate_hash') {
        // Generate hash for given password
        $password = trim($_POST['gen_password'] ?? '');
        
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $hash_result = 'Generated hash: ' . $hash;
        } else {
            $hash_result = 'Please enter a password to hash.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup Tool - <?= SITE_NAME ?></title>
    
    <style>
        :root {
            --primary-color: #baa448;
            --dark-color: #333333;
            --light-color: #f5f5f5;
            --success-color: #2ecc71;
            --error-color: #e74c3c;
            --border-radius: 3px;
            --box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
        }
        
        body {
            font-family: var(--font-family);
            background-color: var(--light-color);
            margin: 0;
            padding: 20px;
            color: var(--dark-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        h1, h2, h3 {
            color: var(--dark-color);
        }
        
        h1 {
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-top: 0;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            margin-bottom: -1px;
            background-color: transparent;
            font-family: var(--font-family);
            font-size: 16px;
        }
        
        .tab.active {
            border-color: #ddd;
            border-top: 2px solid var(--primary-color);
            border-bottom: 1px solid white;
            background-color: white;
        }
        
        .tab-content {
            display: none;
            padding: 20px 0;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            border-left: 4px solid;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-left-color: var(--success-color);
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left-color: var(--error-color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-family: var(--font-family);
            box-sizing: border-box;
        }
        
        .code-output {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            padding: 15px;
            font-family: monospace;
            margin-top: 10px;
            word-break: break-all;
            max-height: 100px;
            overflow: auto;
        }
        
        button {
            background-color: var(--primary-color);
            color: black;
            border: none;
            padding: 10px 15px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 16px;
            font-family: var(--font-family);
        }
        
        button:hover {
            opacity: 0.9;
        }
        
        .user-details {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            padding: 15px;
            margin-top: 20px;
        }
        
        .user-details h3 {
            margin-top: 0;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .detail-label {
            font-weight: 500;
            width: 100px;
        }
        
        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        a.button {
            display: inline-block;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: var(--border-radius);
        }
        
        .tab-description {
            margin-bottom: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Setup Tool</h1>
        
        <!-- Tabs Navigation -->
        <div class="tabs">
            <button class="tab active" data-tab="create-admin">Create Admin</button>
            <button class="tab" data-tab="generate-hash">Generate Hash</button>
            <button class="tab" data-tab="verify-hash">Verify Hash</button>
        </div>
        
        <!-- Create Admin Tab -->
        <div id="create-admin" class="tab-content active">
            <p class="tab-description">Use this form to create the first administrator user for your system.</p>
            
            <?php if ($action === 'create_admin'): ?>
                <div class="message <?= $success ? 'success' : 'error' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <!-- Success Message with User Details -->
                <div class="user-details">
                    <h3>Admin User Details</h3>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span><?= htmlspecialchars($admin_name) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span><?= htmlspecialchars($admin_email) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Password:</span>
                        <span><?= htmlspecialchars($admin_password) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Level:</span>
                        <span><?= htmlspecialchars($admin_nivel) ?></span>
                    </div>
                </div>
                
                <div class="actions">
                    <a href="<?= BASE_URL ?>/admin/Admin_Login.php" class="button">Go to Login</a>
                    <a href="<?= BASE_URL ?>" class="button">Go to Website</a>
                </div>
            <?php else: ?>
                <!-- Admin Creation Form -->
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_admin">
                    
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($admin_name) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($admin_email) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="text" id="password" name="password" value="<?= htmlspecialchars($admin_password) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nivel">Access Level</label>
                        <select id="nivel" name="nivel" required>
                            <option value="Administrador" <?= $admin_nivel === 'Administrador' ? 'selected' : '' ?>>Administrator</option>
                            <option value="Editor" <?= $admin_nivel === 'Editor' ? 'selected' : '' ?>>Editor</option>
                        </select>
                    </div>
                    
                    <button type="submit">Create Admin</button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Generate Hash Tab -->
        <div id="generate-hash" class="tab-content">
            <p class="tab-description">Generate a secure password hash that you can use in your database.</p>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="generate_hash">
                
                <div class="form-group">
                    <label for="gen_password">Password</label>
                    <input type="text" id="gen_password" name="gen_password" placeholder="Enter password to hash" required>
                </div>
                
                <button type="submit">Generate Hash</button>
            </form>
            
            <?php if ($action === 'generate_hash' && !empty($hash_result)): ?>
                <div class="code-output">
                    <?= htmlspecialchars($hash_result) ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Verify Hash Tab -->
        <div id="verify-hash" class="tab-content">
            <p class="tab-description">Verify if a password matches a specific hash.</p>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="verify_hash">
                
                <div class="form-group">
                    <label for="verify_password">Password</label>
                    <input type="text" id="verify_password" name="verify_password" placeholder="Enter password to verify" required>
                </div>
                
                <div class="form-group">
                    <label for="hash">Hash</label>
                    <input type="text" id="hash" name="hash" placeholder="Enter the password hash" required>
                </div>
                
                <button type="submit">Verify</button>
            </form>
            
            <?php if ($action === 'verify_hash' && !empty($hash_result)): ?>
                <div class="message <?= $verify_success ? 'success' : 'error' ?>">
                    <?= htmlspecialchars($hash_result) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab functionality
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to current tab and corresponding content
                    const tabId = this.getAttribute('data-tab');
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>