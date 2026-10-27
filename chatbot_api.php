<?php
require_once 'db_connect.php';
// session_start(); // Already started in db_connect.php

header('Content-Type: application/json');

// Disable error display to prevent breaking JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // Get raw POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $message = isset($input['message']) ? trim($input['message']) : '';

    if (empty($message)) {
        echo json_encode(['response' => 'Please type a message.']);
        exit;
    }

    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '0';
    
    // Python script path
    $python_script = __DIR__ . '/chatbot_engine.py';
    
    // Escape arguments for security
    $escaped_message = escapeshellarg($message);
    $escaped_user_id = escapeshellarg($user_id);
    
    // Execute Python script
    // Note: Ensure 'python3' is in the system PATH or use full path
    $command = "python3 " . escapeshellarg($python_script) . " $escaped_message $escaped_user_id";
    
    $output = shell_exec($command);
    
    if ($output === null) {
        throw new Exception("Failed to execute Python backend.");
    }
    
    // Attempt to decode to verify it's valid JSON
    $decoded = json_decode($output, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // If not valid JSON, it might be a Python traceback
        throw new Exception("Python Backend Error: " . $output);
    }
    
    // Return the JSON directly
    echo $output;

} catch (Exception $e) {
    echo json_encode([
        'response' => "I encountered a system error connecting to my Python brain.", 
        'actions' => [],
        'debug_error' => $e->getMessage()
    ]);
}
?>