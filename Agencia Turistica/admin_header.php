<?php
require_once 'session_manager.php';
SessionManager::checkUserSession();

if ($_SESSION['user_type'] !== 'admin') {
    header("Location: home.php");
    exit();
}
?>
<header>
    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-user-shield"></i> Panel Admin
        </div>
        <div class="navbar-links">
            <a href="admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="admin_usuarios.php"><i class="fas fa-users"></i> Usuarios</a>
            <a href="admin_pedidos.php"><i class="fas fa-clipboard-list"></i> Pedidos</a>
            <a href="admin_productos.php" class="active"><i class="fas fa-suitcase"></i> Paquetes</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>
        </div>
    </nav>
</header>