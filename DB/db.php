<?php
// db.php
// Database connection file

$host     = "localhost";     // Database host
$user     = "root";          // Database username
$password = "";              // Database password
$dbname   = "webscrapper"; // Database name

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set charset
$conn->set_charset("utf8");

// If you want to test connection uncomment below
// echo "Database connected successfully!";
?>
