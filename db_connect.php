<?php
$servername = "localhost";
$username = "root"; // Asenda oma andmebaasi kasutajanimega
$password = ""; // Asenda oma andmebaasi parooliga
$dbname = "kasutajad";

// Loome ühenduse
$conn = new mysqli($servername, $username, $password, $dbname);

// Kontrollime ühendust
if ($conn->connect_error) {
    die("Ühenduse viga: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>