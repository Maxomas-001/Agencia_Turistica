<?php
include('conexion.php');
session_start();

$usuario_email = $_SESSION['usuario'];
$productos = $_POST['productos']; 
$total = $_POST['total'];  

$sql = "INSERT INTO compras (usuario_id, productos, precio) VALUES ((SELECT id FROM usuarios WHERE email = ?), ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssd", $usuario_email, $productos, $total);

if ($stmt->execute()) {
    $subject = "Confirmación de tu compra en Agencia de Turismo";
    $message = "Hola, $usuario_email\n\nGracias por elegirnos para tu próxima aventura.\n\nDetalles de tu compra:\n$productos\nTotal: $$total\n\n¡Esperamos que disfrutes de tu viaje y que tengas una experiencia inolvidable!\n\nEquipo de Agencia de Turismo";
    $headers = "From: no-reply@agenciadeturismo.com";
    
    if (mail($usuario_email, $subject, $message, $headers)) {
        echo "Compra procesada con éxito. Se ha enviado un correo de confirmación a $usuario_email.";
    } else {
        echo "Compra procesada, pero hubo un problema al enviar el correo de confirmación.";
    }
} else {
    echo "Hubo un problema al procesar la compra.";
}
?>