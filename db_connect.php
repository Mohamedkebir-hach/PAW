<?php
/**
 * Tutorial 3 - Exercise 3
 * db_connect.php
 * Database connection with error handling
 */

require_once 'config.php';

/**
 * Get database connection using PDO
 * @return PDO|null Returns PDO connection object or null on failure
 */
function getConnection() {
    try {
        // Create DSN (Data Source Name)
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        
        // PDO options for better error handling
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        // Create PDO instance
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Log error to file (optional but recommended)
        $logFile = 'db_errors.log';
        $logMessage = date('Y-m-d H:i:s') . ' - Connection Error: ' . $e->getMessage() . PHP_EOL;
        error_log($logMessage, 3, $logFile);
        
        // Display error if DISPLAY_ERRORS is enabled
        if (DISPLAY_ERRORS) {
            echo "Database Connection Error: " . $e->getMessage();
        } else {
            echo "Database connection failed. Please contact administrator.";
        }
        
        return null;
    }
}

// Test connection (can be removed after testing)
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #10B981; font-size: 18px; font-weight: 600; }
        .error { color: #ef4444; font-size: 18px; font-weight: 600; }
        .info { margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 5px; }
        .info strong { color: #A6615A; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Database Connection Test</h1>";
    
    $conn = getConnection();
    
    if ($conn !== null) {
        echo "<p class='success'>✅ Connection successful!</p>";
        echo "<div class='info'>";
        echo "<strong>Database:</strong> " . DB_NAME . "<br>";
        echo "<strong>Host:</strong> " . DB_HOST . "<br>";
        echo "<strong>Status:</strong> Connected<br>";
        echo "<strong>Server Info:</strong> " . $conn->getAttribute(PDO::ATTR_SERVER_VERSION);
        echo "</div>";
    } else {
        echo "<p class='error'>❌ Connection failed!</p>";
        echo "<div class='info'>";
        echo "Please check:<br>";
        echo "1. WAMP is running<br>";
        echo "2. Database '" . DB_NAME . "' exists<br>";
        echo "3. Credentials in config.php are correct<br>";
        echo "4. Check db_errors.log for details";
        echo "</div>";
    }
    
    echo "</div></body></html>";
}

?>