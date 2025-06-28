<?php
require_once 'session_manager.php';
require_once 'conexion.php';
require_once 'header.php';

SessionManager::checkUserSession();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: mis_pedidos.php");
    exit();
}

$pedido_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

$query = "SELECT c.*, e.nombre as estado, f.monto_total, f.estado as estado_pago
          FROM compras c
          JOIN estados_pedido e ON c.estado_id = e.id
          JOIN facturas f ON c.id = f.compra_id
          WHERE c.id = ? AND c.usuario_id = ?";

$stmt = $conexion->prepare($query);
$stmt->bind_param("ii", $pedido_id, $user_id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    header("Location: mis_pedidos.php");
    exit();
}

$query_items = "SELECT p.nombre, p.descripcion, ci.cantidad, ci.precio_unitario
                FROM compra_items ci
                JOIN productos p ON ci.producto_id = p.id
                WHERE ci.compra_id = ?";

$stmt_items = $conexion->prepare($query_items);
$stmt_items->bind_param("i", $pedido_id);
$stmt_items->execute();
$items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Pedido #<?= $pedido_id ?></title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .order-detail-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .order-title {
            color: #d50000;
            font-size: 2rem;
        }
        .order-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .summary-item {
            display: flex;
            flex-direction: column;
        }
        .summary-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        .summary-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
        }
        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-entregado {
            background-color: #d4edda;
            color: #155724;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .items-table th {
            background: #d50000;
            color: white;
            padding: 1rem;
            text-align: left;
        }
        .items-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .items-table tr:hover {
            background: #f9f9f9;
        }
        .total-row {
            font-weight: bold;
            font-size: 1.1rem;
        }
        .order-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
   

    <main class="order-detail-container">
        <div class="order-header">
            <h1 class="order-title animate__animated animate__fadeIn">
                <i class="fas fa-clipboard-list"></i> Pedido #<?= $pedido_id ?>
            </h1>
            <a href="mis_pedidos.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        
        <div class="order-summary animate__animated animate__fadeIn">
            <div class="summary-item">
                <span class="summary-label">Fecha</span>
                <span class="summary-value"><?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Estado</span>
                <span class="summary-value">
                    <span class="status-badge status-<?= $pedido['estado'] ?>">
                        <?= ucfirst($pedido['estado']) ?>
                    </span>
                </span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Pago</span>
                <span class="summary-value">
                    <span class="status-badge <?= $pedido['estado_pago'] == 'pagada' ? 'status-entregado' : 'status-pendiente' ?>">
                        <?= ucfirst($pedido['estado_pago']) ?>
                    </span>
                </span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total</span>
                <span class="summary-value">$<?= number_format($pedido['monto_total'], 2) ?></span>
            </div>
        </div>
        
        <h2 class="animate__animated animate__fadeIn"><i class="fas fa-box-open"></i> Items del Pedido</h2>
        
        <table class="items-table animate__animated animate__fadeIn">
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
                <tr class="animate__animated animate__fadeInUp">
                    <td><?= htmlspecialchars($item['nombre']) ?></td>
                    <td><?= htmlspecialchars($item['descripcion']) ?></td>
                    <td><?= $item['cantidad'] ?></td>
                    <td>$<?= number_format($item['precio_unitario'], 2) ?></td>
                    <td>$<?= number_format($item['cantidad'] * $item['precio_unitario'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="4" style="text-align: right;">Total:</td>
                    <td>$<?= number_format($pedido['monto_total'], 2) ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="order-actions">
            <?php if ($pedido['estado'] == 'pendiente'): ?>
                <form method="POST" action="cancelar_pedido.php" onsubmit="return confirm('¿Cancelar este pedido?')">
                    <input type="hidden" name="pedido_id" value="<?= $pedido_id ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Cancelar Pedido
                    </button>
                </form>
            <?php endif; ?>
            <a href="#" class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </a>
        </div>
    </main>

    <script src="scripts.js"></script>
</body>
</html>