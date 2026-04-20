<?php
$conn = pg_connect("host=127.0.0.1 port=5432 dbname=cyberguard user=cyberuser password=1234");

if (!$conn) {
    die("Error de conexión a PostgreSQL");
}
?>