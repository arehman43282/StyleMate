<?php
require_once 'db_connect.php';
$sql = "SELECT DISTINCT Categories_name FROM categories WHERE Category_Group = 'Women'";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    echo $row['Categories_name'] . "\n";
}
?>
