<?php
// db_connection.php
// Database configuration
$servername = "localhost"; // MySQL host
$port = 3307;              // Custom port
$username = "root";        // Your MySQL username
$password = "";            // Your MySQL password
$dbname = "smart_cultivation"; // Your database name

// Create connection using MySQLi
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");
?>
