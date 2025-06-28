<?php
require_once 'session_manager.php';
SessionManager::checkUserSession();

if ($_SESSION['user_type'] === 'admin') {
    header("Location: admin.php");
    exit();
}

require_once 'conexion.php';

// Obtener productos
$productos = $conexion->query("SELECT * FROM productos ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Obtener cantidad en carrito
$cantidad = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $conexion->prepare("SELECT SUM(cantidad) as total FROM carrito WHERE usuario_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $cantidad = $result->fetch_assoc()['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paquetes Turísticos</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 5rem 1rem;
            text-align: center;
            margin-bottom: 3rem;
        }
        .hero-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            animation: fadeInDown 1s;
        }
        .hero-subtitle {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 2rem;
            animation: fadeIn 1.5s;
        }
        .packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            padding: 0 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .package-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s;
        }
        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        .package-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .package-image i {
            font-size: 4rem;
            color: white;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.8;
        }
        .package-info {
            padding: 1.5rem;
        }
        .package-title {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .package-description {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        .package-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #d50000;
            margin-bottom: 1.5rem;
        }
        .package-actions {
            display: flex;
            justify-content: space-between;
        }
        .btn-outline {
            background: transparent;
            border: 2px solid #d50000;
            color: #d50000;
        }
        .btn-outline:hover {
            background: #d50000;
            color: white;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .package-card:focus-within, 
        .package-card:active {
            animation: pulse 0.5s ease;
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
                <a href="home.php" class="active"><i class="fas fa-home"></i> Inicio</a>
                <a href="carrito.php"><i class="fas fa-shopping-cart"></i> Carrito (<?= $cantidad ?>)</a>
                <a href="mis_pedidos.php"><i class="fas fa-clipboard-list"></i> Mis Pedidos</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
            </div>
        </nav>
    </header>

    <section class="hero-section">
        <h1 class="hero-title">Bienvenido, <?= htmlspecialchars($_SESSION['user_nombre']) ?></h1>
        <p class="hero-subtitle">Descubre nuestros exclusivos paquetes turísticos y vive experiencias inolvidables</p>
    </section>

    <main>
        <div class="packages-grid">
            <?php foreach ($productos as $producto): ?>
            <div class="package-card animate__animated animate__fadeInUp">
                <div class="package-image" style="background-color: #<?= substr(md5($producto['nombre']), 0, 6) ?>">
                    <i class="fas fa-suitcase-rolling"></i>
                </div>
                <div class="package-info">
                    <h3 class="package-title"><?= htmlspecialchars($producto['nombre']) ?></h3>
                    <p class="package-description"><?= htmlspecialchars($producto['descripcion']) ?></p>
                    <p class="package-price">$<?= number_format($producto['precio'], 2) ?></p>
                    <div class="package-actions">
                        <a href="detalle_paquete.php?id=<?= $producto['id'] ?>" class="btn btn-outline">
                            <i class="fas fa-eye"></i> Ver más
                        </a>
                        <form action="procesar_carrito.php" method="POST" class="add-to-cart-form">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-cart-plus"></i> Agregar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script src="scripts.js"></script>
    <script>
    // Efecto al hacer hover en las tarjetas
    document.querySelectorAll('.package-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.classList.add('animate__pulse');
        });
        
        card.addEventListener('mouseleave', () => {
            card.classList.remove('animate__pulse');
        });
    });

    // Animación al agregar al carrito
    document.querySelectorAll('.add-to-cart-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const button = this.querySelector('button');
            button.innerHTML = '<i class="fas fa-check"></i> Agregado';
            button.classList.add('animate__animated', 'animate__rubberBand');
            
            // Enviar el formulario después de la animación
            setTimeout(() => {
                this.submit();
            }, 1000);
        });
    });
    </script>
</body>
</html>