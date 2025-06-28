<?php
require_once 'session_manager.php';
require_once 'conexion.php';
require_once 'header.php';

SessionManager::checkUserSession();

$query = "SELECT c.id, c.fecha, e.nombre as estado, 
                 COUNT(ci.id) as items_count,
                 SUM(ci.cantidad * ci.precio_unitario) as total,
                 f.estado as estado_pago
          FROM compras c
          JOIN estados_pedido e ON c.estado_id = e.id
          JOIN compra_items ci ON c.id = ci.compra_id
          JOIN facturas f ON c.id = f.compra_id
          WHERE c.usuario_id = ?
          GROUP BY c.id
          ORDER BY c.fecha DESC";

$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .orders-title {
            color: #d50000;
            font-size: 2rem;
        }
        .orders-grid {
            display: grid;
            gap: 1.5rem;
        }
        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s;
        }
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .order-header {
            background: #f8f9fa;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
        }
        .order-id {
            font-weight: bold;
            color: #333;
        }
        .order-date {
            color: #666;
            font-size: 0.9rem;
        }
        .order-status {
            display: flex;
            gap: 1rem;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-proceso {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-entregado {
            background-color: #d4edda;
            color: #155724;
        }
        .status-cancelado {
            background-color: #f8d7da;
            color: #721c24;
        }
        .order-body {
            padding: 1.5rem;
        }
        .order-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .summary-item {
            display: flex;
            flex-direction: column;
        }
        .summary-label {
            font-size: 0.9rem;
            color: #666;
        }
        .summary-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }
        .order-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        .empty-orders {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .empty-orders i {
            font-size: 3rem;
            color: #d50000;
            margin-bottom: 1rem;
        }
        .timeline {
            position: relative;
            padding-left: 1.5rem;
            margin: 1.5rem 0;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #eee;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        .timeline-dot {
            position: absolute;
            left: -1.5rem;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #d50000;
        }
        .timeline-content {
            padding-left: 1rem;
        }
        .timeline-date {
            font-size: 0.8rem;
            color: #666;
        }
        .timeline-text {
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>

    <main class="orders-container">
        <div class="orders-header">
            <h1 class="orders-title animate__animated animate__fadeIn"><i class="fas fa-clipboard-list"></i> Mis Pedidos</h1>
        </div>
        
        <?php if (empty($pedidos)): ?>
            <div class="empty-orders animate__animated animate__fadeIn">
                <i class="fas fa-box-open"></i>
                <h2>No tienes pedidos registrados</h2>
                <p>¡Empieza a explorar nuestros paquetes y haz tu primera reserva!</p>
                <a href="home.php" class="btn btn-primary" style="margin-top: 1rem;">
                    <i class="fas fa-suitcase"></i> Ver paquetes
                </a>
            </div>
        <?php else: ?>
            <div class="orders-grid">
                <?php foreach ($pedidos as $pedido): ?>
                <div class="order-card animate__animated animate__fadeInUp">
                    <div class="order-header">
                        <div>
                            <div class="order-id">Pedido #<?= $pedido['id'] ?></div>
                            <div class="order-date"><?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></div>
                        </div>
                        <div class="order-status">
                            <span class="status-badge status-<?= $pedido['estado'] ?>">
                                <?= ucfirst($pedido['estado']) ?>
                            </span>
                            <span class="status-badge <?= $pedido['estado_pago'] == 'pagada' ? 'status-entregado' : 'status-pendiente' ?>">
                                <?= ucfirst($pedido['estado_pago']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="order-body">
                        <div class="order-summary">
                            <div class="summary-item">
                                <span class="summary-label">Total</span>
                                <span class="summary-value">$<?= number_format($pedido['total'], 2) ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Items</span>
                                <span class="summary-value"><?= $pedido['items_count'] ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Fecha</span>
                                <span class="summary-value"><?= date('d/m/Y', strtotime($pedido['fecha'])) ?></span>
                            </div>
                        </div>
                        
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-date"><?= date('d/m/Y', strtotime($pedido['fecha'])) ?></div>
                                    <div class="timeline-text">Pedido realizado</div>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-dot" style="background: <?= in_array($pedido['estado'], ['entregado', 'proceso']) ? '#d50000' : '#ccc' ?>"></div>
                                <div class="timeline-content">
                                    <div class="timeline-date"><?= date('d/m/Y', strtotime($pedido['fecha']) + 86400) ?></div>
                                    <div class="timeline-text">Pedido en proceso</div>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-dot" style="background: <?= $pedido['estado'] == 'entregado' ? '#d50000' : '#ccc' ?>"></div>
                                <div class="timeline-content">
                                    <div class="timeline-date"><?= date('d/m/Y', strtotime($pedido['fecha']) + 172800) ?></div>
                                    <div class="timeline-text">Pedido <?= $pedido['estado'] == 'entregado' ? 'entregado' : 'por entregar' ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-actions">
                            <?php if ($pedido['estado'] == 'pendiente'): ?>
                                <form method="POST" action="cancelar_pedido.php" onsubmit="return confirm('¿Cancelar este pedido?')">
                                    <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a href="detalle_pedido.php?id=<?= $pedido['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Ver detalles
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script src="scripts.js"></script>
</body>
</html>