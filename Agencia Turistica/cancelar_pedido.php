<?php
require_once 'session_manager.php';
require_once 'conexion.php';

SessionManager::checkUserSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['pedido_id'])) {
    header("Location: mis_pedidos.php");
    exit();
}

$pedido_id = intval($_POST['pedido_id']);
$user_id = $_SESSION['user_id'];

$stmt = $conexion->prepare("SELECT id FROM compras WHERE id = ? AND usuario_id = ? AND estado_id = 1");
$stmt->bind_param("ii", $pedido_id, $user_id);
$stmt->execute();

if ($stmt->get_result()->num_rows === 1) {
    
    $stmt_update = $conexion->prepare("UPDATE compras SET estado_id = 4 WHERE id = ?");
    $stmt_update->bind_param("i", $pedido_id);
    $stmt_update->execute();
    
   
    $stmt_factura = $conexion->prepare("UPDATE facturas SET estado = 'cancelada' WHERE compra_id = ?");
    $stmt_factura->bind_param("i", $pedido_id);
    $stmt_factura->execute();
    
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Pedido cancelado correctamente'];
} else {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'No se pudo cancelar el pedido'];
}

header("Location: mis_pedidos.php");
exit();
?>