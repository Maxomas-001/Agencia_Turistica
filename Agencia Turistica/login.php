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
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Todos los campos son obligatorios";
    } else {
        $stmt = $conexion->prepare("SELECT id, email, password, tipo, nombre FROM usuarios WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_type'] = $user['tipo'];
                    $_SESSION['user_nombre'] = $user['nombre'];
                    
                    if ($user['tipo'] === 'admin') {
                        header("Location: admin.php");
                    } else {
                        header("Location: home.php");
                    }
                    exit();
                } else {
                    $error = "Credenciales incorrectas";
                }
            } else {
                $error = "Usuario no encontrado";
            }
        } else {
            $error = "Error en el sistema. Intente más tarde.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
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
        <h1 class="auth-title"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</h1>
        
        <?php if ($error): ?>
            <div class="alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Correo electrónico:</label>
                <input type="email" id="email" name="email" required class="form-control" value="<?= htmlspecialchars($email ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Contraseña:</label>
                <input type="password" id="password" name="password" required class="form-control">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Iniciar sesión
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            ¿No tienes cuenta? <a href="registro.php" style="color: #d50000;">Regístrate aquí</a>
        </div>
    </div>
</body>
</html>