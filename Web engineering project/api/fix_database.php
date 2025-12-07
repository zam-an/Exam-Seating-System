<?php
/**
 * Database Fix Script
 * This will drop all corrupted tables and recreate them properly
 * WARNING: This will delete all existing data!
 */

// Database config
$db_name = 'web_project1';
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Database Fix</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:900px;margin:50px auto;padding:20px;background:#f5f5f5;}";
echo ".success{background:#d4edda;color:#155724;padding:15px;border-radius:5px;margin:10px 0;}";
echo ".error{background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin:10px 0;}";
echo ".info{background:#d1ecf1;color:#0c5460;padding:15px;border-radius:5px;margin:10px 0;}";
echo ".warning{background:#fff3cd;color:#856404;padding:15px;border-radius:5px;margin:10px 0;}";
echo "h1{color:#333;} code{background:#e9ecef;padding:2px 6px;border-radius:3px;}</style></head><body>";
echo "<h1>Database Fix Script</h1>";

try {
    // Connect to MySQL (without database first)
    $pdo_temp = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Create database if it doesn't exist
    $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
    
    // Connect to the database
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<div class='success'><strong>✓ Connected to database successfully!</strong></div>";
    
    // Drop all existing tables (in correct order due to foreign keys)
    echo "<div class='info'>Dropping existing tables...</div>";
    
    $tablesToDrop = [
        'seatings',
        'plan_rooms',
        'plan_semesters',
        'comments',
        'posts',
        'plans',
        'students',
        'semesters',
        'rooms',
        'departments',
        'users'
    ];
    
    foreach ($tablesToDrop as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "<div class='info'>Dropped table: <code>$table</code></div>";
        } catch (PDOException $e) {
            echo "<div class='warning'>Could not drop table $table: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    
    echo "<div class='success'><strong>✓ All tables dropped!</strong></div>";
    
    // Now create all tables
    echo "<div class='info'>Creating all tables...</div>";
    
    // Create departments table
    $pdo->exec("
        CREATE TABLE departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<div class='info'>✓ Created table: departments</div>";
    
    // Create semesters table
    $pdo->exec("
        CREATE TABLE semesters (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            code VARCHAR(20) NOT NULL UNIQUE,
            department_id INT,
            exam_date DATE,
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<div class='info'>✓ Created table: semesters</div>";
    
    // Create students table
    $pdo->exec("
        CREATE TABLE students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            roll_no VARCHAR(20) NOT NULL UNIQUE,
            full_name VARCHAR(150) NOT NULL,
            seat_pref VARCHAR(20),
            semester_id INT,
            FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<div class='info'>✓ Created table: students</div>";
    
    // Create rooms table
    $pdo->exec("
        CREATE TABLE rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(20) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            capacity INT NOT NULL,
            num_rows INT,
            cols INT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<div class='info'>✓ Created table: rooms</div>";
    
    // Create plans table
    $pdo->exec("
        CREATE TABLE plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            plan_date DATE,
            strategy ENUM('round-robin','random','preference') DEFAULT 'round-robin',
            status VARCHAR(20) DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            generated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<div class='info'>✓ Created table: plans</div>";
    
    // Create plan_semesters table
    $pdo->exec("
        CREATE TABLE plan_semesters (
            plan_id INT NOT NULL,
            semester_id INT NOT NULL,
            PRIMARY KEY (plan_id, semester_id),
            FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE,
            FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<div class='info'>✓ Created table: plan_semesters</div>";
    
    // Create plan_rooms table
    $pdo->exec("
        CREATE TABLE plan_rooms (
            plan_id INT NOT NULL,
            room_id INT NOT NULL,
            PRIMARY KEY (plan_id, room_id),
            FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE,
            FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<div class='info'>✓ Created table: plan_rooms</div>";
    
    // Create seatings table
    $pdo->exec("
        CREATE TABLE seatings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plan_id INT NOT NULL,
            student_id INT NOT NULL,
            room_id INT NOT NULL,
            seat_row INT NOT NULL,
            seat_col INT NOT NULL,
            FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<div class='info'>✓ Created table: seatings</div>";
    
    // Create users table
    $pdo->exec("
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<div class='info'>✓ Created table: users</div>";
    
    // Create posts table
    $pdo->exec("
        CREATE TABLE posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<div class='info'>✓ Created table: posts</div>";
    
    // Create comments table
    $pdo->exec("
        CREATE TABLE comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<div class='info'>✓ Created table: comments</div>";
    
    echo "<div class='success'><strong>✓ All tables created successfully!</strong></div>";
    
    // Create admin user
    echo "<div class='info'>Creating admin user...</div>";
    
    $username = 'admin';
    $email = 'admin@example.com';
    $password = 'admin123';
    $passhash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare('INSERT INTO users(username, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, $passhash]);
        echo "<div class='success'><strong>✓ Admin user created successfully!</strong></div>";
        echo "<div class='info'>";
        echo "<strong>Admin Credentials:</strong><br>";
        echo "Username: <code>admin</code><br>";
        echo "Password: <code>admin123</code><br>";
        echo "</div>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "<div class='info'><strong>✓ Admin user already exists!</strong></div>";
        } else {
            echo "<div class='warning'>Could not create admin user: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    
    // Verify tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='success'>";
    echo "<strong>✓ Database Fix Complete!</strong><br><br>";
    echo "<strong>Tables in database:</strong> " . implode(', ', $tables) . "<br><br>";
    echo "<a href='../index.html' style='background:#2575fc;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin-top:10px;'>Go to Login Page</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "<br><br><strong>Please check:</strong><br>";
    echo "1. MySQL is running in XAMPP<br>";
    echo "2. Database credentials are correct<br>";
    echo "3. You have permission to create/drop tables";
    echo "</div>";
}

echo "</body></html>";

