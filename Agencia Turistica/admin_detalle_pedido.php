<?php

require 'conexion.php';

$pedido_id = $_GET['id'];

$sql_pedido = "SELECT c.*, u.nombre as cliente, u.email, e.nombre as estado, 
               f.estado as estado_pago, f.monto_total, f.fecha_emision
               FROM compras c
               JOIN usuarios u ON c.usuario_id = u.id
               JOIN estados_pedido e ON c.estado_id = e.id
               JOIN facturas f ON c.id = f.compra_id
               WHERE c.id = ?";
$stmt_pedido = $conexion->prepare($sql_pedido);
$stmt_pedido->bind_param("i", $pedido_id);
$stmt_pedido->execute();
$pedido = $stmt_pedido->get_result()->fetch_assoc();

$sql_items = "SELECT p.nombre, p.descripcion, ci.cantidad, ci.precio_unitario
              FROM compra_items ci
              JOIN productos p ON ci.producto_id = p.id
              WHERE ci.compra_id = ?";
$stmt_items = $conexion->prepare($sql_items);
$stmt_items->bind_param("i", $pedido_id);
$stmt_items->execute();
$items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['cambiar_estado'])) {
        $nuevo_estado = $_POST['nuevo_estado'];
        
        $sql_update = "UPDATE compras SET estado_id = ? WHERE id = ?";
        $stmt_update = $conexion->prepare($sql_update);
        $stmt_update->bind_param("ii", $nuevo_estado, $pedido_id);
        $stmt_update->execute();
        
        if ($nuevo_estado == 3) {
            $sql_factura = "UPDATE facturas SET estado = 'pagada' WHERE compra_id = ?";
            $stmt_factura = $conexion->prepare($sql_factura);
            $stmt_factura->bind_param("i", $pedido_id);
            $stmt_factura->execute();
        }
        
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Estado del pedido actualizado'];
        header("Location: admin_detalle_pedido.php?id=$pedido_id");
        exit();
    }
}

$estados = $conexion->query("SELECT * FROM estados_pedido")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Pedido #<?= $pedido_id ?></title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="navbar-brand">
                <i class="fas fa-user-shield"></i> Panel Admin
            </div>
            <div class="navbar-links">
                <a href="admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="admin_usuarios.php"><i class="fas fa-users"></i> Usuarios</a>
                <a href="admin_pedidos.php"><i class="fas fa-clipboard-list"></i> Pedidos</a>
                <a href="admin_productos.php"><i class="fas fa-suitcase"></i> Paquetes</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>
        </nav>
    </header>

    <main class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">
                <i class="fas fa-clipboard-list"></i> Pedido #<?= $pedido_id ?>
                <span class="estado-badge estado-<?= $pedido['estado'] ?>"><?= ucfirst($pedido['estado']) ?></span>
            </h1>
            <a href="admin_pedidos.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        
        <?php if (isset($_SESSION['toast'])): ?>
            <div class="toast toast-<?= $_SESSION['toast']['type'] ?>">
                <?= $_SESSION['toast']['message'] ?>
                <button class="toast-close">&times;</button>
            </div>
            <?php unset($_SESSION['toast']); ?>
        <?php endif; ?>
        
        <div class="pedido-detail-grid">
            <div class="pedido-info-card">
                <h3><i class="fas fa-info-circle"></i> Información del Pedido</h3>
                <div class="info-row">
                    <span class="info-label">Cliente:</span>
                    <span class="info-value"><?= htmlspecialchars($pedido['cliente']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?= htmlspecialchars($pedido['email']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha:</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Estado:</span>
                    <form action="admin_detalle_pedido.php?id=<?= $pedido_id ?>" method="POST" class="form-inline">
                        <select name="nuevo_estado" class="estado-select">
                            <?php foreach ($estados as $estado): ?>
                                <option value="<?= $estado['id'] ?>" <?= $estado['id'] == $pedido['estado_id'] ? 'selected' : '' ?>>
                                    <?= ucfirst($estado['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="cambiar_estado" class="btn btn-sm btn-primary">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="pedido-info-card">
                <h3><i class="fas fa-file-invoice-dollar"></i> Información de Facturación</h3>
                <div class="info-row">
                    <span class="info-label">N° Factura:</span>
                    <span class="info-value">#<?= $pedido['id'] ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Estado:</span>
                    <span class="info-value estado-badge estado-<?= $pedido['estado_pago'] ?>">
                        <?= ucfirst($pedido['estado_pago']) ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Monto Total:</span>
                    <span class="info-value">$<?= number_format($pedido['monto_total'], 2) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha Emisión:</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($pedido['fecha_emision'])) ?></span>
                </div>
            </div>
        </div>
        
        <div class="pedido-items-card">
            <h3><i class="fas fa-box-open"></i> Items del Pedido</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Paquete</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['nombre']) ?></td>
                        <td><?= htmlspecialchars($item['descripcion']) ?></td>
                        <td><?= $item['cantidad'] ?></td>
                        <td>$<?= number_format($item['precio_unitario'], 2) ?></td>
                        <td>$<?= number_format($item['precio_unitario'] * $item['cantidad'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="4" class="text-right"><strong>Total:</strong></td>
                        <td><strong>$<?= number_format($pedido['monto_total'], 2) ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="pedido-actions">
            <a href="#" class="btn btn-danger" id="btn-cancelar-pedido">
                <i class="fas fa-times-circle"></i> Cancelar Pedido
            </a>
            <?php if ($pedido['estado_id'] != 3): ?>
                <a href="#" class="btn btn-success" id="btn-marcar-entregado">
                    <i class="fas fa-check-circle"></i> Marcar como Entregado
                </a>
            <?php endif; ?>
            <a href="#" class="btn btn-secondary">
                <i class="fas fa-print"></i> Imprimir Factura
            </a>
        </div>
    </main>

    <script src="scripts.js"></script>
</body>
</html>