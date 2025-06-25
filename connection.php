<?php
// Database connection parameters
$servername = "localhost";
$username = "root";  // Default XAMPP MySQL username
$password = "";      // Default XAMPP MySQL password 
$dbname = "lost_and_found";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8 for proper character encoding
$conn->set_charset("utf8");

echo "Connected successfully to database: " . $dbname;
?>
