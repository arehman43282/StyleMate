<?php
if (session_status() === PHP_SESSION_NONE) {
    // Fix for MAMP session permission issues
    $sess_path = __DIR__ . '/sessions';
    if (is_dir($sess_path) && is_writable($sess_path)) {
        session_save_path($sess_path);
    }
    session_start();
}

// Language logic
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}
$current_lang = $_SESSION['lang'];
require_once __DIR__ . "/lang/{$current_lang}.php";

$db_servername = "127.0.0.1";
$db_username = "root";
$db_password = (getenv('DB_PASSWORD') ?: '');
$db_name = "dashboard";
$db_port = 8889;

$conn = new mysqli($db_servername, $db_username, $db_password, $db_name, $db_port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Schema updates moved to setup_db.php to prevent request blocking
?>
