<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'medical_store');

// Establish Connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Check if activity_logs table exists, if not create it
    $check = @$conn->query("SHOW TABLES LIKE 'activity_logs'");
    if ($check && $check->num_rows == 0) {
        @$conn->query("CREATE TABLE `activity_logs` (
            `log_id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `action` varchar(50) NOT NULL,
            `table_name` varchar(50) DEFAULT NULL,
            `record_id` int(11) DEFAULT NULL,
            `description` text DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`log_id`),
            KEY `fk_log_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    
} catch (Exception $e) {
    // Silently ignore - logging is optional
}

// System Constants
define('APP_NAME', 'Pharmacy MS');
define('BASE_URL', 'http://localhost/medical_store/');

// Start Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
