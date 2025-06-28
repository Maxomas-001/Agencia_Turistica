<?php
// Crear un archivo header.php que puedas incluir en todas las páginas
require_once 'conexion.php';

$cantidad_carrito = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $conexion->prepare("SELECT SUM(cantidad) as total FROM carrito WHERE usuario_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $cantidad_carrito = $result->fetch_assoc()['total'] ?? 0;
}
?>

<header>
    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-plane"></i> Viajes Increíbles
        </div>
        <div class="navbar-links">
            <a href="home.php" class="<?= basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Inicio
            </a>
            <a href="carrito.php" class="<?= basename($_SERVER['PHP_SELF']) == 'carrito.php' ? 'active' : '' ?>">
                <i class="fas fa-shopping-cart"></i> 
                <span id="cart-count">Carrito (<?= $cantidad_carrito ?>)</span>
                <?php if ($cantidad_carrito > 0): ?>
                    <span class="cart-badge animate__animated animate__bounceIn"><?= $cantidad_carrito ?></span>
                <?php endif; ?>
            </a>
            <a href="mis_pedidos.php" class="<?= basename($_SERVER['PHP_SELF']) == 'mis_pedidos.php' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i> Mis Pedidos
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </a>
        </div>
    </nav>
</header>

<script>
// Función para actualizar el contador del carrito
function updateCartCount() {
    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            const cartCountElement = document.getElementById('cart-count');
            const badgeElement = document.querySelector('.cart-badge');
            
            cartCountElement.textContent = `Carrito (${data.count})`;
            
            if (data.count > 0) {
                if (!badgeElement) {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'cart-badge animate__animated animate__bounceIn';
                    newBadge.textContent = data.count;
                    document.querySelector('a[href="carrito.php"]').appendChild(newBadge);
                } else {
                    badgeElement.textContent = data.count;
                    badgeElement.classList.add('animate__bounceIn');
                    setTimeout(() => {
                        badgeElement.classList.remove('animate__bounceIn');
                    }, 1000);
                }
            } else if (badgeElement) {
                badgeElement.remove();
            }
        });
}

// Actualizar cada 2 segundos (puedes ajustar este tiempo)
setInterval(updateCartCount, 2000);

// También actualizar después de interacciones con el carrito
document.addEventListener('DOMContentLoaded', function() {
    // Escuchar eventos de modificación del carrito
    document.body.addEventListener('cartUpdated', updateCartCount);
});
</script>