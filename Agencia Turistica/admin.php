<?php
require_once 'session_manager.php';
SessionManager::checkUserSession();

if ($_SESSION['user_type'] !== 'admin') {
    header("Location: home.php");
    exit();
}

require_once 'conexion.php';

if (!isset($conexion) || $conexion->connect_error) {
    die("Error de conexión a la base de datos");
}

try {
    $stats = [
        'total_usuarios' => $conexion->query("SELECT COUNT(*) FROM usuarios")->fetch_row()[0],
        'pedidos_pendientes' => $conexion->query("SELECT COUNT(*) FROM compras WHERE estado_id = 1")->fetch_row()[0],
        'pedidos_entregados' => $conexion->query("SELECT COUNT(*) FROM compras WHERE estado_id = 3")->fetch_row()[0],
        'ingresos_mes' => $conexion->query("SELECT IFNULL(SUM(monto_total), 0) FROM facturas WHERE estado = 'pagada' AND MONTH(fecha_emision) = MONTH(CURRENT_DATE())")->fetch_row()[0]
    ];
} catch (Exception $e) {
    die("Error al obtener estadísticas: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
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
                <a href="admin.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="admin_usuarios.php"><i class="fas fa-users"></i> Usuarios</a>
                <a href="admin_pedidos.php"><i class="fas fa-clipboard-list"></i> Pedidos</a>
                <a href="admin_productos.php"><i class="fas fa-suitcase"></i> Paquetes</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>
        </nav>
    </header>

    <main class="admin-container">
        <h1 class="admin-title"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #4e73df;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Usuarios</h3>
                    <p><?= $stats['total_usuarios'] ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #1cc88a;">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="stat-info">
                    <h3>Pedidos Entregados</h3>
                    <p><?= $stats['pedidos_entregados'] ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #f6c23e;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>Pedidos Pendientes</h3>
                    <p><?= $stats['pedidos_pendientes'] ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #e74a3b;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-info">
                    <h3>Ingresos del Mes</h3>
                    <p>$<?= number_format($stats['ingresos_mes'], 2) ?></p>
                </div>
            </div>
        </div>
        
        <section class="recent-orders">
            <h2><i class="fas fa-history"></i> Pedidos Recientes</h2>
            <?php
            $pedidos_recientes = $conexion->query("
                SELECT c.id, u.nombre as cliente, c.fecha, e.nombre as estado 
                FROM compras c
                JOIN usuarios u ON c.usuario_id = u.id
                JOIN estados_pedido e ON c.estado_id = e.id
                ORDER BY c.fecha DESC LIMIT 5
            ")->fetch_all(MYSQLI_ASSOC);
            ?>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos_recientes as $pedido): ?>
                    <tr>
                        <td><?= $pedido['id'] ?></td>
                        <td><?= htmlspecialchars($pedido['cliente']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></td>
                        <td><span class="estado-badge estado-<?= $pedido['estado'] ?>"><?= ucfirst($pedido['estado']) ?></span></td>
                        <td>
                            <a href="admin_detalle_pedido.php?id=<?= $pedido['id'] ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

    <script src="scripts.js"></script>
</body>
</html>