<?php
/**
 * AttendEase System Configuration
 * Database and System Settings
 */

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');  // Default WAMP/XAMPP password
define('DB_NAME', 'attendease_db');

// System timezone
date_default_timezone_set('Africa/Algiers');

// Development mode (set to 0 in production)
define('DISPLAY_ERRORS', 1);
define('DEBUG_MODE', 1);

// Application settings
define('APP_NAME', 'AttendEase');
define('APP_VERSION', '2.0');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

?>
