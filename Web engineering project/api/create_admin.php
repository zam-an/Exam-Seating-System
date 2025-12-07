<?php
/**
 * One-time script to create default admin user
 * Run this once to create an admin account: admin / admin123
 * After running, you can delete this file for security
 */
require_once __DIR__.'/config.php';

header('Content-Type: application/json');

$username = 'admin';
$email = 'admin@example.com';
$password = 'admin123';

// Check if admin user already exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo json_encode([
        'success' => false,
        'message' => 'Admin user already exists'
    ]);
    exit;
}

// Create admin user
$passhash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users(username, email, password) VALUES (?, ?, ?)');
$stmt->execute([$username, $email, $passhash]);

echo json_encode([
    'success' => true,
    'message' => 'Admin user created successfully',
    'username' => $username,
    'password' => $password,
    'note' => 'Please change the password after first login for security'
]);

