<?php
require_once 'session_manager.php';
require_once 'conexion.php';

SessionManager::checkUserSession();

if ($_SESSION['user_type'] === 'admin') {
    header("Location: admin.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$carrito = [];
$total = 0;

$sql = "SELECT p.id, p.nombre, p.precio, c.cantidad 
        FROM carrito c 
        JOIN productos p ON c.producto_id = p.id 
        WHERE c.usuario_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($item = $result->fetch_assoc()) {
    $subtotal = $item['precio'] * $item['cantidad'];
    $total += $subtotal;
    $carrito[] = $item;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['actualizar'])) {
        foreach ($_POST['cantidad'] as $producto_id => $cantidad) {
            $cantidad = intval($cantidad);
            $producto_id = intval($producto_id);
            
            if ($cantidad > 0) {
                $stmt = $conexion->prepare("UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?");
                $stmt->bind_param("iii", $cantidad, $user_id, $producto_id);
                $stmt->execute();
            } else {
                $stmt = $conexion->prepare("DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?");
                $stmt->bind_param("ii", $user_id, $producto_id);
                $stmt->execute();
            }
        }
        
        $_SESSION['success'] = "Carrito actualizado";
        header("Location: carrito.php");
        exit();
    }
    
    if (isset($_POST['vaciar'])) {
        $stmt = $conexion->prepare("DELETE FROM carrito WHERE usuario_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $_SESSION['success'] = "Carrito vaciado";
        header("Location: carrito.php");
        exit();
    }
    
    if (isset($_POST['comprar'])) {
        if (empty($carrito)) {
            $_SESSION['error'] = "No puedes comprar con el carrito vacío";
            header("Location: carrito.php");
            exit();
        }
        
        $conexion->begin_transaction();
        
        try {
            $stmt = $conexion->prepare("INSERT INTO compras (usuario_id, estado_id) VALUES (?, 1)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $compra_id = $conexion->insert_id;
            
            $productos_desc = [];
            $total_compra = 0;
            
            foreach ($carrito as $item) {
                $subtotal = $item['precio'] * $item['cantidad'];
                $total_compra += $subtotal;
                
                $stmt = $conexion->prepare("INSERT INTO compra_items (compra_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $compra_id, $item['id'], $item['cantidad'], $item['precio']);
                $stmt->execute();
                
                $productos_desc[] = $item['nombre'] . " (x" . $item['cantidad'] . ")";
            }
            
            $stmt = $conexion->prepare("INSERT INTO facturas (compra_id, monto_total) VALUES (?, ?)");
            $stmt->bind_param("id", $compra_id, $total_compra);
            $stmt->execute();
            
            $stmt = $conexion->prepare("DELETE FROM carrito WHERE usuario_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            $conexion->commit();
            
            $_SESSION['success'] = "Compra realizada con éxito";
            header("Location: mis_pedidos.php");
            exit();
        } catch (Exception $e) {
            $conexion->rollback();
            $_SESSION['error'] = "Error al procesar la compra: " . $e->getMessage();
            header("Location: carrito.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #d50000;
            --primary-light: #ff5131;
            --primary-dark: #9b0000;
            --secondary: #f5f5f5;
            --dark: #333;
            --light: #fff;
            --gray: #777;
            --success: #4caf50;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .navbar {
            background-color: var(--primary);
            color: var(--light);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-links {
            display: flex;
            gap: 20px;
        }
        
        .navbar a {
            color: var(--light);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .navbar a:hover, .navbar a.active {
            background-color: var(--primary-dark);
        }
        
        .cart-container {
            padding: 2rem 0;
            animation: fadeIn 0.5s;
        }
        
        .cart-title {
            color: var(--primary);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cart-items {
            background-color: var(--light);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: 100px 1fr 150px 150px;
            gap: 20px;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 100px;
            height: 100px;
            background-color: #f5f5f5;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 2rem;
        }
        
        .item-details h3 {
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .item-price {
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .item-quantity {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-input {
            width: 70px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .item-subtotal {
            font-weight: bold;
            font-size: 1.1rem;
            text-align: right;
        }
        
        .cart-summary {
            background-color: var(--light);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .summary-total {
            font-size: 1.3rem;
            font-weight: bold;
        }
        
        .cart-actions {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: var(--light);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: var(--secondary);
            color: var(--dark);
        }
        
        .btn-secondary:hover {
            background-color: #e0e0e0;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: #f44336;
            color: var(--light);
        }
        
        .btn-danger:hover {
            background-color: #d32f2f;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background-color: var(--success);
            color: var(--light);
        }
        
        .btn-success:hover {
            background-color: #388e3c;
            transform: translateY(-2px);
        }
        
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            background-color: var(--light);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .empty-cart i {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .empty-cart h2 {
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .empty-cart p {
            color: var(--gray);
            margin-bottom: 2rem;
        }
        
        
        @media (max-width: 768px) {
            .cart-item {
                grid-template-columns: 80px 1fr;
                grid-template-rows: auto auto;
            }
            
            .item-subtotal {
                grid-column: 2;
                text-align: left;
            }
            
            .cart-actions {
                flex-direction: column;
            }
            
            .navbar-links {
                gap: 10px;
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .animate-bounce {
            animation: bounce 0.5s;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="container navbar-container">
            <div class="navbar-brand">
                <i class="fas fa-shopping-cart"></i> Mi Carrito
            </div>
            <div class="navbar-links">
                <a href="home.php"><i class="fas fa-home"></i> Inicio</a>
                <a href="carrito.php" class="active"><i class="fas fa-shopping-cart"></i> Carrito</a>
                <a href="mis_pedidos.php"><i class="fas fa-clipboard-list"></i> Mis Pedidos</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
            </div>
        </div>
    </header>

    <main class="container cart-container">
        <h1 class="cart-title animate__animated animate__fadeIn">
            <i class="fas fa-shopping-cart"></i> Mi Carrito
        </h1>
        
        <?php if (empty($carrito)): ?>
            <div class="empty-cart animate__animated animate__fadeIn">
                <i class="fas fa-shopping-cart"></i>
                <h2>Tu carrito está vacío</h2>
                <p>¡Explora nuestros paquetes turísticos y agrega algo especial!</p>
                <a href="home.php" class="btn btn-primary">
                    <i class="fas fa-suitcase"></i> Ver paquetes
                </a>
            </div>
        <?php else: ?>
            <form action="carrito.php" method="POST">
                <div class="cart-items">
                    <?php foreach ($carrito as $item): ?>
                        <div class="cart-item animate__animated animate__fadeIn">
                            <div class="item-image">
                                <i class="fas fa-suitcase-rolling"></i>
                            </div>
                            <div class="item-details">
                                <h3><?= htmlspecialchars($item['nombre']) ?></h3>
                                <p class="item-price">$<?= number_format($item['precio'], 2) ?></p>
                                <div class="item-quantity">
                                    <label>Cantidad:</label>
                                    <input type="number" name="cantidad[<?= $item['id'] ?>]" 
                                           value="<?= $item['cantidad'] ?>" min="1" class="quantity-input">
                                </div>
                            </div>
                            <div class="item-subtotal">
                                $<?= number_format($item['precio'] * $item['cantidad'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary animate__animated animate__fadeInUp">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?= number_format($total, 2) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Envío:</span>
                        <span>Gratis</span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total:</span>
                        <span>$<?= number_format($total, 2) ?></span>
                    </div>
                </div>
                
                <div class="cart-actions animate__animated animate__fadeInUp">
                    <button type="submit" name="actualizar" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i> Actualizar carrito
                    </button>
                    <div class="action-group">
                        <button type="submit" name="vaciar" class="btn btn-danger" 
                                onclick="return confirm('¿Estás seguro de vaciar tu carrito?')">
                            <i class="fas fa-trash-alt"></i> Vaciar carrito
                        </button>
                        <button type="submit" name="comprar" class="btn btn-success">
                            <i class="fas fa-check-circle"></i> Finalizar compra
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>

    $('button[name="actualizar"]').click(function() {
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Actualizando...');
        $('.cart-item').addClass('animate-bounce');
    });
    

    $('button[name="comprar"]').click(function() {
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
    });
    </script>
</body>
</html>
