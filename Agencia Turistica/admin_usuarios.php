<?php
require_once 'session_manager.php';
require_once 'conexion.php';

SessionManager::checkUserSession();

if ($_SESSION['user_type'] !== 'admin') {
    header("Location: home.php");
    exit();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cambiar_rol'])) {
        $usuario_id = intval($_POST['usuario_id']);
        $nuevo_rol = in_array($_POST['nuevo_rol'], ['admin', 'cliente']) ? $_POST['nuevo_rol'] : 'cliente';
        
        $stmt = $conexion->prepare("UPDATE usuarios SET tipo = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_rol, $usuario_id);
        
        if ($stmt->execute()) {
            $message = "Rol actualizado correctamente";
        } else {
            $message = "Error al actualizar el rol";
        }
    }
    
    if (isset($_POST['eliminar_usuario'])) {
        $usuario_id = intval($_POST['usuario_id']);
        
        if ($usuario_id != $_SESSION['user_id']) {
            $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $usuario_id);
            
            if ($stmt->execute()) {
                $message = "Usuario eliminado correctamente";
            } else {
                $message = "Error al eliminar el usuario";
            }
        } else {
            $message = "No puedes eliminarte a ti mismo";
        }
    }
}

$usuarios = [];
$query = "SELECT u.id, u.nombre, u.email, u.tipo, 
                 COUNT(c.id) as total_pedidos,
                 SUM(CASE WHEN c.estado_id = 3 THEN 1 ELSE 0 END) as pedidos_entregados
          FROM usuarios u
          LEFT JOIN compras c ON u.id = c.usuario_id
          GROUP BY u.id
          ORDER BY u.nombre";

$result = $conexion->query($query);
if ($result) {
    $usuarios = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Usuarios</title>
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
                <a href="admin_usuarios.php" class="active"><i class="fas fa-users"></i> Usuarios</a>
                <a href="admin_pedidos.php"><i class="fas fa-clipboard-list"></i> Pedidos</a>
                <a href="admin_productos.php"><i class="fas fa-suitcase"></i> Paquetes</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>
        </nav>
    </header>

    <main class="admin-container">
        <h1 class="admin-title"><i class="fas fa-users"></i> Administración de Usuarios</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= strpos($message, 'Error') === false ? 'success' : 'danger' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Total Pedidos</th>
                        <th>Pedidos Entregados</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay usuarios registrados</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?= htmlspecialchars($usuario['id']) ?></td>
                            <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                            <td><?= htmlspecialchars($usuario['email']) ?></td>
                            <td>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="usuario_id" value="<?= $usuario['id'] ?>">
                                    <select name="nuevo_rol" class="form-select" onchange="this.form.submit()">
                                        <option value="cliente" <?= $usuario['tipo'] == 'cliente' ? 'selected' : '' ?>>Cliente</option>
                                        <option value="admin" <?= $usuario['tipo'] == 'admin' ? 'selected' : '' ?>>Administrador</option>
                                    </select>
                                    <input type="hidden" name="cambiar_rol">
                                </form>
                            </td>
                            <td><?= $usuario['total_pedidos'] ?></td>
                            <td><?= $usuario['pedidos_entregados'] ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="admin_usuario_detalle.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-info" title="Ver detalles" onclick="viewUserDetails(<?= $usuario['id'] ?>)">
                                        <i class="fas fa-eye"></i> Detalles
                                    </a>
                                    <form method="POST" onsubmit="return confirm('¿Eliminar este usuario?')">
                                        <input type="hidden" name="usuario_id" value="<?= $usuario['id'] ?>">
                                        <button type="submit" name="eliminar_usuario" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </button>
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
    <script>
        function viewUserDetails(userId) {
            window.location.href = `admin_usuario_detalle.php?id=${userId}`;
        }
    </script>
</body>
</html>

