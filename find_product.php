<?php
require_once 'db_connect.php';
$name = "%Ananth Jewels%";
$stmt = $conn->prepare("SELECT Products_ID, Products_Name FROM products WHERE Products_Name LIKE ?");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
?>
