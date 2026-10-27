<?php
require_once 'db_connect.php';

// Add is_premium column
$sql1 = "ALTER TABLE users ADD COLUMN is_premium TINYINT(1) DEFAULT 0";
if ($conn->query($sql1) === TRUE) {
    echo "Column is_premium added successfully\n";
} else {
    echo "Error adding is_premium: " . $conn->error . "\n";
}

// Add membership_expiry column
$sql2 = "ALTER TABLE users ADD COLUMN membership_expiry DATETIME NULL";
if ($conn->query($sql2) === TRUE) {
    echo "Column membership_expiry added successfully\n";
} else {
    echo "Error adding membership_expiry: " . $conn->error . "\n";
}
?>