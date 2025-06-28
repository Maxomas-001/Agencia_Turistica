<?php
require_once 'session_manager.php';
require_once 'conexion.php';

SessionManager::checkUserSession();

if ($_SESSION['user_type'] !== 'admin') {
    header("Location: home.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_usuarios.php");
    exit();
}

$usuario_id = intval($_GET['id']);

$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    header("Location: admin_usuarios.php");
    exit();
}

$pedidos = [];
$stmt = $conexion->prepare("SELECT c.id, c.fecha, e.nombre as estado, f.monto_total 
                           FROM compras c 
                           JOIN estados_pedido e ON c.estado_id = e.id
                           JOIN facturas f ON c.id = f.compra_id
                           WHERE c.usuario_id = ?
                           ORDER BY c.fecha DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $pedidos = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Usuario</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .user-detail-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .info-card {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #d50000;
        }
        .pedidos-table {
            width: 100%;
            border-collapse: collapse;
        }
        .pedidos-table th {
            background: #d50000;
            color: white;
            padding: 1rem;
            text-align: left;
        }
        .pedidos-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        .pedidos-table tr:hover {
            background: #f5f5f5;
        }
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-pendiente {
            background-color: #f8f4e6;
            color: #8a6d3b;
        }
        .status-entregado {
            background-color: #e8f5e9;
            color: #3d8b40;
        }
    </style>
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

    <main class="user-detail-container">
        <div class="user-header">
            <h1 class="admin-title">
                <i class="fas fa-user"></i> <?= htmlspecialchars($usuario['nombre']) ?>
            </h1>
            <a href="admin_usuarios.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        
        <div class="user-info-grid">
            <div class="info-card animate__animated animate__fadeIn">
                <h3><i class="fas fa-info-circle"></i> Información Básica</h3>
                <p><strong>ID:</strong> <?= $usuario['id'] ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($usuario['email']) ?></p>
                <p><strong>Tipo:</strong> <?= $usuario['tipo'] === 'admin' ? 'Administrador' : 'Cliente' ?></p>
            </div>
            
            <div class="info-card animate__animated animate__fadeIn animate__delay-1s">
                <h3><i class="fas fa-chart-bar"></i> Estadísticas</h3>
                <p><strong>Total Pedidos:</strong> <?= count($pedidos) ?></p>
                <p><strong>Pedidos Entregados:</strong> <?= array_reduce($pedidos, function($carry, $item) {
                    return $carry + ($item['estado'] === 'entregado' ? 1 : 0);
                }, 0) ?></p>
                <p><strong>Total Gastado:</strong> $<?= number_format(array_reduce($pedidos, function($carry, $item) {
                    return $carry + $item['monto_total'];
                }, 0), 2) ?></p>
            </div>
        </div>
        
        <h2 class="animate__animated animate__fadeIn"><i class="fas fa-clipboard-list"></i> Historial de Pedidos</h2>
        
        <?php if (empty($pedidos)): ?>
            <div class="alert alert-info animate__animated animate__fadeIn">
                <i class="fas fa-info-circle"></i> Este usuario no tiene pedidos registrados.
            </div>
        <?php else: ?>
            <table class="pedidos-table animate__animated animate__fadeIn">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Monto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                    <tr class="animate__animated animate__fadeInUp">
                        <td><?= $pedido['id'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= $pedido['estado'] ?>">
                                <?= ucfirst($pedido['estado']) ?>
                            </span>
                        </td>
                        <td>$<?= number_format($pedido['monto_total'], 2) ?></td>
                        <td>
                            <a href="admin_detalle_pedido.php?id=<?= $pedido['id'] ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-search"></i> Ver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

    <script src="scripts.js"></script>
</body>
</html>