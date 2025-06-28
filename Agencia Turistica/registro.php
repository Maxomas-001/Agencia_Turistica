<?php
require_once 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['user_type'] === 'admin' ? 'admin.php' : 'home.php'));
    exit();
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Todos los campos son obligatorios";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El email no tiene un formato válido";
    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden";
    } elseif (strlen($password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres";
    } else {
        $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Este correo ya está registrado";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password, tipo) VALUES (?, ?, ?, 'cliente')");
            $stmt->bind_param("sss", $nombre, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_type'] = 'cliente';
                $_SESSION['user_nombre'] = $nombre;
                
                header("Location: home.php");
                exit();
            } else {
                $error = "Error al registrar el usuario. Intente nuevamente.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .auth-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .auth-title {
            color: #d50000;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .btn-primary {
            background-color: #d50000;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        .alert-danger {
            background-color: #ffebee;
            color: #d50000;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 5px solid #d50000;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1 class="auth-title"><i class="fas fa-user-plus"></i> Registro</h1>
        
        <?php if ($error): ?>
            <div class="alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="nombre"><i class="fas fa-user"></i> Nombre completo:</label>
                <input type="text" id="nombre" name="nombre" required class="form-control" value="<?= htmlspecialchars($nombre ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Correo electrónico:</label>
                <input type="email" id="email" name="email" required class="form-control" value="<?= htmlspecialchars($email ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Contraseña (mínimo 8 caracteres):</label>
                <input type="password" id="password" name="password" required minlength="8" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Confirmar contraseña:</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8" class="form-control">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Registrarse
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            ¿Ya tienes cuenta? <a href="login.php" style="color: #d50000;">Inicia sesión aquí</a>
        </div>
    </div>
</body>
</html>