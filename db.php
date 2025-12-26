<?php
/**
 * ============================================================================
 * DATABASE CONNECTION FILE
 * ============================================================================
 * This file creates a secure connection to the MySQL database.
 * It uses PDO (PHP Data Objects) which is the modern, safe way to talk to databases.
 * 
 * Think of this as opening a phone line to the database - once it's open,
 * we can ask questions (queries) and get answers (results) back.
 */

require_once 'config.php';

try {
    // Build the connection string (DSN = Data Source Name)
    // This tells PHP: "Connect to MySQL at 'localhost', use database 'weather_app', and speak UTF-8"
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    // Set up some important rules for how PDO should behave
    $options = [
        // If something goes wrong, throw an exception (so we can catch and handle it)
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        
        // Always return results as associative arrays (easier to work with)
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        
        // Use real prepared statements (safer against SQL injection attacks)
        PDO::ATTR_EMULATE_PREPARES   => false,
        
        // Don't keep the connection alive between requests (cleaner for web apps)
        PDO::ATTR_PERSISTENT         => false
    ];
    
    // Actually create the database connection!
    // $pdo is now our "phone line" to the database - we'll use it everywhere
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // Uh oh! Connection failed. Let's handle it gracefully.
    
    // Log the real error to server logs (for developers to debug)
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Throw a new exception that calling code can catch
    // We don't expose the real error message to users (security best practice)
    throw new Exception("Database connection failed: " . $e->getMessage());
}

?>
