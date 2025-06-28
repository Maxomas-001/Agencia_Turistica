<?php
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambiar_estado'])) {
    $compra_id = $_POST['compra_id'];
    $nuevo_estado = $_POST['nuevo_estado'];
    
    $sql = "UPDATE compras SET estado_id = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $nuevo_estado, $compra_id);
    $stmt->execute();
    
    if ($nuevo_estado == 3) {
        $sql_factura = "UPDATE facturas SET estado = 'pagada' WHERE compra_id = ?";
        $stmt_factura = $conexion->prepare($sql_factura);
        $stmt_factura->bind_param("i", $compra_id);
        $stmt_factura->execute();
    }
    
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Estado del pedido actualizado'];
    header("Location: admin_pedidos.php");
    exit();
}


$filtro_estado = $_GET['estado'] ?? '';
$where = '';
$params = [];
$types = '';

if ($filtro_estado && is_numeric($filtro_estado)) {
    $where = "WHERE c.estado_id = ?";
    $params[] = $filtro_estado;
    $types .= 'i';
}

$sql = "SELECT c.id, u.nombre as cliente, c.fecha, e.id as estado_id, e.nombre as estado, 
               f.monto_total, f.estado as estado_pago
        FROM compras c
        JOIN usuarios u ON c.usuario_id = u.id
        JOIN estados_pedido e ON c.estado_id = e.id
        JOIN facturas f ON c.id = f.compra_id
        $where
        ORDER BY c.fecha DESC";

$stmt = $conexion->prepare($sql);
if ($where) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


$estados = $conexion->query("SELECT * FROM estados_pedido")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Pedidos</title>
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
                <a href="admin_pedidos.php" class="active"><i class="fas fa-clipboard-list"></i> Pedidos</a>
                <a href="admin_productos.php"><i class="fas fa-suitcase"></i> Paquetes</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>
        </nav>
    </header>

    <main class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title"><i class="fas fa-clipboard-list"></i> Administración de Pedidos</h1>
            
            <div class="admin-filters">
                <form method="GET" class="filter-form">
                    <select name="estado" class="filter-select" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?= $estado['id'] ?>" <?= $filtro_estado == $estado['id'] ? 'selected' : '' ?>>
                                <?= ucfirst($estado['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
        
        <?php if (isset($_SESSION['toast'])): ?>
            <div class="toast toast-<?= $_SESSION['toast']['type'] ?>">
                <?= $_SESSION['toast']['message'] ?>
                <button class="toast-close">&times;</button>
            </div>
            <?php unset($_SESSION['toast']); ?>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Monto</th>
                        <th>Pago</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pedidos)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay pedidos registrados</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td><?= $pedido['id'] ?></td>
                            <td><?= htmlspecialchars($pedido['cliente']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></td>
                            <td>
                                <span class="estado-badge estado-<?= $pedido['estado'] ?>">
                                    <?= ucfirst($pedido['estado']) ?>
                                </span>
                            </td>
                            <td>$<?= number_format($pedido['monto_total'], 2) ?></td>
                            <td>
                                <span class="estado-badge estado-<?= $pedido['estado_pago'] ?>">
                                    <?= ucfirst($pedido['estado_pago']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="admin_detalle_pedido.php?id=<?= $pedido['id'] ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" class="form-inline">
                                        <input type="hidden" name="compra_id" value="<?= $pedido['id'] ?>">
                                        <select name="nuevo_estado" class="action-select" onchange="this.form.submit()">
                                            <?php foreach ($estados as $estado): ?>
                                                <option value="<?= $estado['id'] ?>" <?= $estado['id'] == $pedido['estado_id'] ? 'selected' : '' ?>>
                                                    <?= ucfirst($estado['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="cambiar_estado">
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="scripts.js"></script>
</body>
</html>