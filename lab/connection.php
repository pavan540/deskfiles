<?php
$servername = "localhost";
$username = "root";
$password = ""; // Replace with your actual MySQL root password
$dbname = "lab";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
