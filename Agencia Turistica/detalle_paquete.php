<?php
require_once 'session_manager.php';
require_once 'conexion.php';
SessionManager::checkUserSession();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: home.php");
    exit();
}

$producto_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

$stmt = $conexion->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();

$cantidad = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $conexion->prepare("SELECT SUM(cantidad) as total FROM carrito WHERE usuario_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $cantidad = $result->fetch_assoc()['total'] ?? 0;
}

if (!$producto) {
    header("Location: home.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $stmt_check = $conexion->prepare("SELECT * FROM carrito WHERE usuario_id = ? AND producto_id = ?");
    $stmt_check->bind_param("ii", $user_id, $producto_id);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        $stmt_update = $conexion->prepare("UPDATE carrito SET cantidad = cantidad + 1 WHERE usuario_id = ? AND producto_id = ?");
        $stmt_update->bind_param("ii", $user_id, $producto_id);
        $stmt_update->execute();
    } else {
        $stmt_insert = $conexion->prepare("INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, 1)");
        $stmt_insert->bind_param("ii", $user_id, $producto_id);
        $stmt_insert->execute();
    }
    
    header("Location: carrito.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($producto['nombre']) ?> | Viajes Increíbles</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .package-detail-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .package-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s;
        }
        .package-image {
            height: 400px;
            background-color: #<?= substr(md5($producto['nombre']), 0, 6) ?>;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .package-image i {
            font-size: 8rem;
            color: white;
            opacity: 0.8;
            animation: pulse 2s infinite;
        }
        .package-content {
            padding: 2rem;
        }
        .package-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #333;
        }
        .package-price {
            font-size: 2rem;
            font-weight: bold;
            color: #d50000;
            margin-bottom: 1.5rem;
        }
        .package-description {
            line-height: 1.6;
            color: #555;
            margin-bottom: 2rem;
        }
        .package-actions {
            display: flex;
            gap: 1rem;
        }
        .btn-large {
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        @media (max-width: 768px) {
            .package-detail {
                grid-template-columns: 1fr;
            }
            .package-image {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="navbar-brand">
                <i class="fas fa-plane"></i> Viajes Increíbles
            </div>
            <div class="navbar-links">
                <a href="home.php"><i class="fas fa-home"></i> Inicio</a>
                <a href="carrito.php"><i class="fas fa-shopping-cart"></i> Carrito (<?= $cantidad ?>)</a>
                <a href="mis_pedidos.php"><i class="fas fa-clipboard-list"></i> Mis Pedidos</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
            </div>
        </nav>
    </header>

    <main class="package-detail-container">
        <div class="package-detail animate__animated animate__fadeIn">
            <div class="package-image">
                <i class="fas fa-suitcase-rolling"></i>
            </div>
            <div class="package-content">
                <h1 class="package-title"><?= htmlspecialchars($producto['nombre']) ?></h1>
                <p class="package-price">$<?= number_format($producto['precio'], 2) ?></p>
                <div class="package-description">
                    <?= nl2br(htmlspecialchars($producto['descripcion'])) ?>
                </div>
                <div class="package-actions">
                    <a href="home.php" class="btn btn-outline btn-large">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <form action="procesar_carrito.php" method="POST">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
                        <button type="submit" name="add_to_cart" class="btn btn-primary btn-large">
                            <i class="fas fa-cart-plus"></i> Agregar al carrito
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="scripts.js"></script> 
    <script>
    document.querySelector('button[name="add_to_cart"]').addEventListener('click', function(e) {
        e.preventDefault();
        
        this.innerHTML = '<i class="fas fa-check"></i> Agregado';
        this.classList.add('animate__animated', 'animate__rubberBand');
        
        setTimeout(() => {
            this.form.submit();
        }, 1000);
    });
    </script>
</body>
</html>