<?php
require_once 'db_connect.php';

// Fix for User jiyaacharya05 (Order #10 vs #13)

echo "Starting fix...<br>";

// 1. Delete the conflicting row (Order_ID 10 in Track_Orders, which is the $200 mismatch)
$conn->query("DELETE FROM Track_Orders WHERE Order_ID = 10");
if ($conn->affected_rows > 0) {
    echo "Deleted conflicting Track_Orders row with ID 10.<br>";
} else {
    echo "No row with ID 10 found (or already deleted).<br>";
}

// 2. Update Order_ID 13 to 10 and set Status to Confirmed
$conn->query("UPDATE Track_Orders SET Order_ID = 10, Status = 'Confirmed' WHERE Order_ID = 13");
if ($conn->affected_rows > 0) {
    echo "Successfully updated Track_Orders ID 13 to 10 and set status to Confirmed.<br>";
} else {
    echo "Failed to update ID 13 (maybe it doesn't exist or ID 10 still exists).<br>";
}

// 3. Verify
$res = $conn->query("SELECT * FROM Track_Orders WHERE Order_ID = 10");
$row = $res->fetch_assoc();
if ($row) {
    echo "Verification: Track_Orders ID 10 exists. Amount: " . $row['Total_Amount'] . ", Status: " . $row['Status'] . "<br>";
} else {
    echo "Verification Failed: Track_Orders ID 10 not found.<br>";
}

?>