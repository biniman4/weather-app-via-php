<?php
/**
 * Database Setup Script
 * Run this file once to create the weather_app database and users table
 * Access: http://localhost/weather%20app/setup_database.php
 */

require_once 'config.php';

// First, connect without selecting a database to create it
try {
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✓ Database '" . DB_NAME . "' created successfully or already exists.</p>";
    
    // Select the database
    $pdo->exec("USE " . DB_NAME);
    
    // Create users table
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS weather_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            favorite_cities TEXT DEFAULT NULL,
            preferences TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL DEFAULT NULL,
            INDEX idx_username (username),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($createTableSQL);
    echo "<p style='color: green;'>✓ Table 'weather_users' created successfully or already exists.</p>";
    
    echo "<h2 style='color: #2ecc71;'>Database Setup Completed Successfully!</h2>";
    echo "<p>You can now use the authentication system.</p>";
    echo "<p><a href='index.php' style='color: #3498db;'>Go to Home Page</a> | <a href='register.php' style='color: #3498db;'>Register</a> | <a href='login.php' style='color: #3498db;'>Login</a></p>";
    echo "<p style='color: #e74c3c; font-weight: bold;'>IMPORTANT: For security, delete this file after setup!</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please make sure MySQL is running in XAMPP and your database credentials in config.php are correct.</p>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Weather App</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #2c3e50;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Weather App - Database Setup</h1>
        <?php // Results are echoed above ?>
    </div>
</body>
</html>
