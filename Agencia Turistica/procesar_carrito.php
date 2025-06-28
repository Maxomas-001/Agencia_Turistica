<?php
require_once 'session_manager.php';
require_once 'conexion.php';

SessionManager::checkUserSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['producto_id'])) {
    header("Location: home.php");
    exit();
}

$producto_id = intval($_POST['producto_id']);
$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? 'add';

$stmt = $conexion->prepare("SELECT id FROM productos WHERE id = ?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['error'] = "El producto no existe";
    header("Location: home.php");
    exit();
}


if ($action === 'add') {
    
    $stmt = $conexion->prepare("SELECT id, cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?");
    $stmt->bind_param("ii", $user_id, $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        
        $item = $result->fetch_assoc();
        $nueva_cantidad = $item['cantidad'] + 1;

        $stmt = $conexion->prepare("UPDATE carrito SET cantidad = ? WHERE id = ?");
        $stmt->bind_param("ii", $nueva_cantidad, $item['id']);
    } else {
       
        $stmt = $conexion->prepare("INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, 1)");
        $stmt->bind_param("ii", $user_id, $producto_id);
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = "Producto agregado al carrito";
    } else {
        $_SESSION['error'] = "Error al agregar al carrito";
    }
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'home.php'));
exit();
?>