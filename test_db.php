<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #667eea; }
        .success { color: #2ecc71; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .info { background: #d1ecf1; padding: 15px; border-radius: 8px; margin: 20px 0; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 8px; overflow-x: auto; }
        .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #667eea; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Connection Diagnostics</h1>
        
        <?php
        require_once 'config.php';
        
        echo "<h2>Configuration Check</h2>";
        echo "<pre>";
        echo "DB_HOST: " . DB_HOST . "\n";
        echo "DB_NAME: " . DB_NAME . "\n";
        echo "DB_USER: " . DB_USER . "\n";
        echo "DB_PASS: " . (empty(DB_PASS) ? "(empty - default XAMPP)" : "(set)") . "\n";
        echo "</pre>";
        
        echo "<h2>Connection Tests</h2>";
        
        // Test 1: Can we connect to MySQL at all?
        echo "<div class='step'>";
        echo "<h3>Test 1: MySQL Server Connection</h3>";
        try {
            $test_pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
            echo "<p class='success'>✓ Successfully connected to MySQL server!</p>";
            
            // Test 2: Does the database exist?
            echo "<h3>Test 2: Database Existence</h3>";
            $databases = $test_pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'")->fetchAll();
            
            if (count($databases) > 0) {
                echo "<p class='success'>✓ Database '" . DB_NAME . "' exists!</p>";
                
                // Test 3: Can we select the database?
                $test_pdo->exec("USE " . DB_NAME);
                echo "<p class='success'>✓ Successfully selected database!</p>";
                
                // Test 4: Does the users table exist?
                echo "<h3>Test 3: Users Table Check</h3>";
                $tables = $test_pdo->query("SHOW TABLES LIKE 'weather_users'")->fetchAll();
                
                if (count($tables) > 0) {
                    echo "<p class='success'>✓ Table 'weather_users' exists!</p>";
                    
                    // Show table structure
                    $columns = $test_pdo->query("DESCRIBE weather_users")->fetchAll();
                    echo "<p><strong>Table Structure:</strong></p>";
                    echo "<pre>";
                    foreach ($columns as $col) {
                        echo $col['Field'] . " (" . $col['Type'] . ")\n";
                    }
                    echo "</pre>";
                    
                    echo "<h2 class='success'>🎉 Everything is set up correctly!</h2>";
                    echo "<p><a href='index.php' style='color: #667eea; font-weight: bold;'>→ Go to Homepage</a></p>";
                    echo "<p><a href='register.php' style='color: #667eea; font-weight: bold;'>→ Register New Account</a></p>";
                    
                } else {
                    echo "<p class='error'>✗ Table 'weather_users' does not exist.</p>";
                    echo "<p><strong>Action needed:</strong> Run the setup script below.</p>";
                    echo "<p><a href='setup_database.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; display: inline-block;'>Run Database Setup</a></p>";
                }
                
            } else {
                echo "<p class='error'>✗ Database '" . DB_NAME . "' does not exist.</p>";
                echo "<p><strong>Action needed:</strong> Run the setup script to create it.</p>";
                echo "<p><a href='setup_database.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; display: inline-block;'>Run Database Setup</a></p>";
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Cannot connect to MySQL server!</p>";
            echo "<pre>Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
            
            echo "<div class='info'>";
            echo "<h3>📋 Troubleshooting Steps:</h3>";
            echo "<ol>";
            echo "<li><strong>Open XAMPP Control Panel</strong></li>";
            echo "<li><strong>Start MySQL</strong> if it's not running (click 'Start' button)</li>";
            echo "<li>Wait for the status to show 'Running' in green</li>";
            echo "<li>Refresh this page</li>";
            echo "</ol>";
            echo "<p><strong>Common Issues:</strong></p>";
            echo "<ul>";
            echo "<li>MySQL service not started in XAMPP</li>";
            echo "<li>Port 3306 already in use by another program</li>";
            echo "<li>XAMPP MySQL configuration issue</li>";
            echo "</ul>";
            echo "</div>";
        }
        echo "</div>";
        ?>
        
        <hr style="margin: 30px 0;">
        <p style="text-align: center; color: #7f8c8d;">
            <small>After fixing issues, <a href="test_db.php">refresh this page</a> to test again.</small>
        </p>
    </div>
</body>
</html>
