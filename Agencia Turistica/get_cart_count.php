<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

$count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $conexion->prepare("SELECT SUM(cantidad) as total FROM carrito WHERE usuario_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['total'] ?? 0;
}

echo json_encode(['count' => $count]);
?>