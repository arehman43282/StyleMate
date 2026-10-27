<?php
$servername = "localhost";
$username = "root";
$password = (getenv('DB_PASSWORD') ?: '');

$conn = new mysqli('127.0.0.1', 'root', 'root', '', 8889);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($conn->query("CREATE DATABASE IF NOT EXISTS dashboard") === TRUE) {
    echo "Connected to database.";
} else {
    echo "Error creating database: " . $conn->error;
}

$conn->select_db("dashboard");

$sql = "CREATE TABLE IF NOT EXISTS users (
    User_ID INT(5) PRIMARY KEY,
    Username VARCHAR(30),
    Password VARCHAR(255),
    Email VARCHAR(50),
    Phone_no VARCHAR(15),
    Address VARCHAR(100)
)";

$ok1 = $conn->query($sql);
if ($ok1 === TRUE) {
    echo " Users table is created.";
} else {
    echo " Error creating users table: " . $conn->error;
}
$conn->query("ALTER TABLE users MODIFY Phone_no VARCHAR(15)");

// Orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    Order_ID INT(5) PRIMARY KEY,
    User_ID INT(5),
    Order_Date DATE NOT NULL,
    Total_Amount DECIMAL(10,2) NOT NULL,
    Status CHAR(2) NOT NULL,
    FOREIGN KEY (User_ID) REFERENCES users(User_ID)
)";
$ok2 = $conn->query($sql);
if ($ok2 === TRUE) {
    echo " Orders table is created.";
} else {
    echo " Error creating orders table: " . $conn->error;
}

$conn->close();
