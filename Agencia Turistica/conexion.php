<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'agencia_turismo';

$conexion = new mysqli($host, $user, $password, $database);

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

$tablas_requeridas = ['usuarios', 'productos', 'compras', 'facturas'];
foreach ($tablas_requeridas as $tabla) {
    $result = $conexion->query("SHOW TABLES LIKE '$tabla'");
    if ($result->num_rows == 0) {
        die("Error: La tabla $tabla no existe en la base de datos");
    }
}
?>