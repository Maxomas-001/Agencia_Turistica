<?php
require 'conexion.php';
require_once 'session_manager.php';
SessionManager::checkUserSession();

if ($_SESSION['user_type'] !== 'admin') {
    header("Location: home.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['agregar'])) {
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $precio = $_POST['precio'];
        
        $stmt = $conexion->prepare("INSERT INTO productos (nombre, descripcion, precio) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $nombre, $descripcion, $precio);
        $stmt->execute();
        
        header("Location: admin_productos.php?success=1");
        exit;
    } elseif (isset($_POST['editar'])) {
        $id = $_POST['id'];
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $precio = $_POST['precio'];
        
        $stmt = $conexion->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ? WHERE id = ?");
        $stmt->bind_param("ssdi", $nombre, $descripcion, $precio, $id);
        $stmt->execute();
        
        header("Location: admin_productos.php?success=1");
        exit;
    } elseif (isset($_POST['eliminar'])) {
        $id = $_POST['id'];
        
        $conexion->begin_transaction();
        
        try {
            $stmt = $conexion->prepare("DELETE FROM compra_items WHERE producto_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $stmt = $conexion->prepare("DELETE FROM carrito WHERE producto_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $conexion->commit();
            header("Location: admin_productos.php?success=1");
            exit;
        } catch (Exception $e) {
            $conexion->rollback();
            die("Error al eliminar el producto: " . $e->getMessage());
        }
    }
}

$productos = $conexion->query("SELECT * FROM productos")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Paquetes - Panel Admin</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <main class="admin-container">
        <h1 class="admin-title"><i class="fas fa-suitcase"></i> Administrar Paquetes Turísticos</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Operación realizada con éxito!
            </div>
        <?php endif; ?>
        
        <div class="producto-form">
            <h2><i class="fas fa-plus-circle"></i> Agregar Nuevo Paquete</h2>
            <form method="POST">
                <input type="text" name="nombre" placeholder="Nombre del paquete" required>
                <textarea name="descripcion" placeholder="Descripción del paquete" required></textarea>
                <input type="number" step="0.01" name="precio" placeholder="Precio ($)" required>
                <button type="submit" name="agregar" class="btn btn-add">
                    <i class="fas fa-save"></i> Agregar Paquete
                </button>
            </form>
        </div>
        
        <h2 class="admin-title"><i class="fas fa-list"></i> Paquetes Existentes</h2>
        
        <?php if (empty($productos)): ?>
            <div class="producto-item">
                <p>No hay paquetes registrados todavía.</p>
            </div>
        <?php else: ?>
            <?php foreach ($productos as $producto): ?>
                <div class="producto-item">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?= $producto['id'] ?>">
                        <input type="text" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required>
                        <textarea name="descripcion" required><?= htmlspecialchars($producto['descripcion']) ?></textarea>
                        <input type="number" step="0.01" name="precio" value="<?= $producto['precio'] ?>" required>
                        <div class="action-buttons">
                            <button type="submit" name="editar" class="btn btn-edit">
                                <i class="fas fa-edit"></i> Guardar Cambios
                            </button>
                            <button type="submit" name="eliminar" class="btn btn-delete" onclick="return confirm('¿Estás seguro de eliminar este paquete?')">
                                <i class="fas fa-trash-alt"></i> Eliminar
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    
    <?php include 'admin_footer.php'; ?>
    
    <script src="scripts.js"></script>
</body>
</html>