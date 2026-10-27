<?php
require_once 'db_connect.php';

// Disable foreign key checks to allow truncating tables with relationships
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$tables = ['Order_Details', 'Orders', 'Payment_Details', 'Track_Orders', 'Complaint_records'];

foreach ($tables as $table) {
    if ($conn->query("TRUNCATE TABLE $table")) {
        echo "Table $table reset successfully.\n";
    } else {
        echo "Error resetting table $table: " . $conn->error . "\n";
    }
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "Order system reset complete.\n";
?>
