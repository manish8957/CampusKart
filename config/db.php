<?php
// config/db.php - CampusKart Database Connection

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'campuskart_db');

// Enable mysqli error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("<h3>Database Connection Error</h3><p>Could not connect to CampusKart database. Make sure MySQL is running in XAMPP.</p><p>Error: " . htmlspecialchars($e->getMessage()) . "</p>");
}

