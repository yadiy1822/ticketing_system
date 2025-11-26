<?php
// Database configuration
$host = "localhost";
$user = "root";
$password = ""; // No password
$database = "pc_maintenance_ticketing_system";

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
