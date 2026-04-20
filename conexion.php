<?php
$host = "IP_PRIVADA_MYSQL";
$user = "usuario";
$pass = "password";
$db   = "cyberguard";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>