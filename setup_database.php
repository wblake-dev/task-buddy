<?php
/**
 * TaskBuddy Database Setup Script
 * Run this file once to set up the database and tables
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'task_buddy_db';

try {
    // First, connect without specifying database to create it
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "✓ Database '$dbname' created or already exists.<br>";
    
    // Now connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute SQL file
    $sql = file_get_contents('database_setup.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✓ All database tables created successfully.<br>";
    echo "✓ Sample data inserted.<br>";
    echo "✓ Default admin users created (admin/8 and H/8).<br>";
    echo "<br><strong>Setup completed successfully!</strong><br>";
    echo "<br>You can now:<br>";
    echo "• <a href='user/index.php'>Visit the main site</a><br>";
    echo "• <a href='Admin/admin_login.php'>Login as admin</a> (username: admin or H, password: 8)<br>";
    echo "• <a href='user/auth/register.php'>Register as a new user</a><br>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Please check your database configuration and try again.";
}
?>