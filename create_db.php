<?php
$servername = "127.0.0.1";
$username = 'root';
$password = (getenv('DB_PASSWORD') ?: '');
$port = 8889;

// Create connection without database
$conn = new mysqli($servername, $username, $password, '', $port);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Drop database if exists and create new
mysqli_query($conn, "DROP DATABASE IF EXISTS dashboard");
$sql = "CREATE DATABASE dashboard";

if (mysqli_query($conn, $sql)) {
    echo "Database created successfully\n";
    
    // Select the database
    mysqli_select_db($conn, "dashboard");

    // Create products table
    $sql = "CREATE TABLE products (\n        products_id INT AUTO_INCREMENT,\n        name VARCHAR(30) NOT NULL,\n        category VARCHAR(20) NOT NULL,\n        price DECIMAL(10,2) NOT NULL,\n        stock INT(10) NOT NULL,\n        description TEXT NOT NULL,\n        image_url VARCHAR(20) NOT NULL,\n        image_url1 VARCHAR(20),\n        image_url2 VARCHAR(20),\n        image_url3 VARCHAR(20),\n        Status TINYINT(1) DEFAULT 1,\n        PRIMARY KEY (products_id)\n    )";

    
    if (mysqli_query($conn, $sql)) {
        echo "Products table created successfully<br>";
    }

}





?>