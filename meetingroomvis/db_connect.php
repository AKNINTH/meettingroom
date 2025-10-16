<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "reservemeeting";

try {
    // Create a new PDO instance
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // Log the error and show a user-friendly message
    // In a production environment, you would log the error to a file
    // For now, we'll display a simple message
    die("Connection failed: " . $e->getMessage());
}
?>