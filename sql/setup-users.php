<?php
/**
 * Setup Default Users
 * Run once after importing schema.sql
 * Creates admin and user with password: admin123
 */

require_once __DIR__ . '/../config/db.php';

$pdo = getDBConnection();
if (!$pdo) {
    die("Database connection failed. Ensure MySQL is running and config/db.php is correct.");
}

$password = password_hash('admin123', PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE password = VALUES(password)");
    
    $stmt->execute(['admin', $password, 'admin@example.com', 'admin']);
    $stmt->execute(['user', $password, 'user@example.com', 'user']);
    
    echo "Default users created/updated.\n";
    echo "Login: admin / admin123 or user / admin123\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Make sure you have run schema.sql first.\n";
}
