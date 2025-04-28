<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'kasutaja');
define('DB_PASSWORD', 'Gd7HhvSX7HUEBCEkjFDy');
define('DB_DATABASE', 'kasutajad');

// Create connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
