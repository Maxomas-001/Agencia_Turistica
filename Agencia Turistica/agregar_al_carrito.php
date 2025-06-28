<?php
require 'conexion.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión']);
    exit;
}

$producto_id = $_POST['producto_id'] ?? null;

if (!$producto_id) {
    echo json_encode(['success' => false, 'message' => 'Producto no válido']);
    exit;
}

$stmt = $conexion->prepare("SELECT id FROM productos WHERE id = ?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
if (!$stmt->get_result()->num_rows) {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    exit;
}

$stmt = $conexion->prepare("SELECT id, cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?");
$stmt->bind_param("ii", $_SESSION['user_id'], $producto_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $item = $result->fetch_assoc();
    $nueva_cantidad = $item['cantidad'] + 1;
    $stmt = $conexion->prepare("UPDATE carrito SET cantidad = ? WHERE id = ?");
    $stmt->bind_param("ii", $nueva_cantidad, $item['id']);
} else {
    $stmt = $conexion->prepare("INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, 1)");
    $stmt->bind_param("ii", $_SESSION['user_id'], $producto_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el carrito']);
}
?>