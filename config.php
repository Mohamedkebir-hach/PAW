<?php
/**
 * Tutorial 3 - Exercise 3
 * config.php
 * Database configuration file
 */

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');  // Default WAMP password is empty
define('DB_NAME', 'student_dashboard');

// Optional: Set timezone
date_default_timezone_set('Africa/Algiers');

// Optional: Error reporting (set to 0 in production)
define('DISPLAY_ERRORS', 1);

?>