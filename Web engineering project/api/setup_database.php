<?php
/**
 * Database Setup Script
 * This script will create the users table if it doesn't exist
 * Run this BEFORE create_admin.php
 */

// Database config
$db_name = 'web_project1';
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';

header('Content-Type: text/html; charset=utf-8');

// Try to connect to database, create if it doesn't exist
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    // Database might not exist, try to create it
    try {
        $pdo_temp = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
        $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    } catch (PDOException $e2) {
        die("Cannot connect to database: " . $e2->getMessage());
    }
}

echo "<!DOCTYPE html><html><head><title>Database Setup</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5;}";
echo ".success{background:#d4edda;color:#155724;padding:15px;border-radius:5px;margin:10px 0;}";
echo ".error{background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin:10px 0;}";
echo ".info{background:#d1ecf1;color:#0c5460;padding:15px;border-radius:5px;margin:10px 0;}";
echo "h1{color:#333;} code{background:#e9ecef;padding:2px 6px;border-radius:3px;}</style></head><body>";
echo "<h1>Database Setup</h1>";

try {
    // Check if table actually exists and is usable
    $tableExists = false;
    $tableUsable = false;
    
    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE 'users'");
        $tableExists = $checkTable->rowCount() > 0;
        
        // Try to query the table to see if it's actually usable
        if ($tableExists) {
            $testQuery = $pdo->query("SELECT COUNT(*) FROM users");
            $tableUsable = true;
        }
    } catch (PDOException $e) {
        // Table doesn't exist or is corrupted
        $tableExists = false;
        $tableUsable = false;
    }
    
    // If table exists but is corrupted, drop it
    if ($tableExists && !$tableUsable) {
        echo "<div class='info'>Table exists but appears corrupted. Recreating...</div>";
        try {
            $pdo->exec("DROP TABLE IF EXISTS users");
        } catch (PDOException $e) {
            // Ignore drop errors
        }
        $tableExists = false;
    }
    
    if ($tableExists && $tableUsable) {
        echo "<div class='success'><strong>✓ Users table exists and is working!</strong></div>";
    } else {
        echo "<div class='info'>Creating users table...</div>";
        
        // Create users table
        $createTableSQL = "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $pdo->exec($createTableSQL);
        echo "<div class='success'><strong>✓ Users table created successfully!</strong></div>";
    }
    
    // Check if admin user exists
    $adminExists = false;
    try {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute(['admin']);
        $adminExists = $stmt->fetch() !== false;
    } catch (PDOException $e) {
        // Table might still have issues
        echo "<div class='error'>Error checking admin user: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    if ($adminExists) {
        echo "<div class='info'><strong>✓ Admin user already exists!</strong></div>";
    } else {
        echo "<div class='info'>Creating admin user...</div>";
        
        // Create admin user
        $username = 'admin';
        $email = 'admin@example.com';
        $password = 'admin123';
        $passhash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare('INSERT INTO users(username, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, $passhash]);
        
        echo "<div class='success'><strong>✓ Admin user created successfully!</strong></div>";
        echo "<div class='info'>";
        echo "<strong>Admin Credentials:</strong><br>";
        echo "Username: <code>admin</code><br>";
        echo "Password: <code>admin123</code><br>";
        echo "<small>Please change the password after first login for security.</small>";
        echo "</div>";
    }
    
    // Show all users
    $stmt = $pdo->query('SELECT id, username, email, created_at FROM users ORDER BY id');
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "<div class='info'>";
        echo "<strong>Current Users in Database:</strong><br><br>";
        echo "<table border='1' cellpadding='10' cellspacing='0' style='width:100%;border-collapse:collapse;background:white;'>";
        echo "<tr style='background:#f8f9fa;'><th>ID</th><th>Username</th><th>Email</th><th>Created</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td><strong>{$user['username']}</strong></td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    
    echo "<div class='success'>";
    echo "<strong>Setup Complete!</strong><br><br>";
    echo "<a href='../index.html' style='background:#2575fc;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Go to Login Page</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "<br><br><strong>Please check:</strong><br>";
    echo "1. MySQL is running in XAMPP<br>";
    echo "2. Database 'web_project1' exists<br>";
    echo "3. Database credentials in config.php are correct";
    echo "</div>";
}

echo "</body></html>";

