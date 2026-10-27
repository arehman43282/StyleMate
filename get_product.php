<?php
require 'db_connect.php';
$r = $conn->query('SELECT Products_ID, Stock FROM products WHERE Stock > 0 LIMIT 1');
$row = $r->fetch_assoc();
echo 'ID:'.$row['Products_ID'].' Stock:'.$row['Stock'];
?>