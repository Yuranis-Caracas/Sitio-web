<?php
session_start();
require_once 'includes/database.php';

// Permitir esta página si viene del cambio de contraseña exitoso
if (
    (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true)
    && !isset($_SESSION['password_changed'])
) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_actual = $_POST['password_actual'];
    $nueva_password = $_POST['nueva_password'];
    $confirmar_password = $_POST['confirmar_password'];

    // ---------- VALIDACIONES ----------
    if (empty($password_actual) || empty($nueva_password) || empty($confirmar_password)) {
        $error = "Todos los campos son obligatorios.";

    } elseif (strlen($nueva_password) < 8) {
        $error = "La nueva contraseña debe tener al menos 8 caracteres.";

    } elseif (!preg_match('/[A-Z]/', $nueva_password)) {
        $error = "La nueva contraseña debe incluir al menos una letra mayúscula.";

    } elseif (!preg_match('/[a-z]/', $nueva_password)) {
        $error = "La nueva contraseña debe incluir al menos una letra minúscula.";

    } elseif (!preg_match('/[0-9]/', $nueva_password)) {
        $error = "La nueva contraseña debe incluir al menos un número.";

    } elseif (!preg_match('/[\W_]/', $nueva_password)) {
        $error = "La nueva contraseña debe incluir al menos un símbolo (ej: !, @, #, %, &).";

    } elseif ($nueva_password !== $confirmar_password) {
        $error = "Las contraseñas nuevas no coinciden.";

    } else {
        try {
            $stmt = $pdo->prepare("SELECT contrasena FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $user = $stmt->fetch();

            if ($user && password_verify($password_actual, $user['contrasena'])) {

                // Guardar nueva contraseña
                $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE usuarios 
                    SET contrasena = ?, primer_login = 0 
                    WHERE id_usuario = ?
                ");
                $stmt->execute([$hash, $_SESSION['admin_id']]);

                $_SESSION['password_changed'] = true;

                // Cerrar sesión del usuario pero mantener bandera
                unset($_SESSION['admin_logged']);
                unset($_SESSION['admin_id']);
                unset($_SESSION['admin_user']);
                unset($_SESSION['admin_name']);

                $success = "Contraseña cambiada correctamente. Será redirigido al inicio de sesión...";

            } else {
                $error = "La contraseña actual es incorrecta.";
            }

        } catch (PDOException $e) {
            $error = "Error al cambiar la contraseña.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Panel ARUC</title>
    <link rel="shortcut icon" href="../img/logo_aruc.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="../css/auth_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
<div class="login-container">
    
    <div class="login-header">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fas fa-key"></i> Cambiar Contraseña</h2>
                <p>Actualice su contraseña para continuar</p>
            </div>
        </div>
    </div>

    <div class="login-body">

        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success); ?>
            </div>

            <script>
                setTimeout(function() {
                    window.location.href = "login.php?password_updated=1";
                }, 2500);
            </script>
        <?php endif; ?>

        <form method="post" action="cambiar_password.php">

            <div class="form-group">
                <label for="password_actual"><i class="fas fa-lock"></i> Contraseña Actual</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password_actual" name="password_actual" required>
                </div>
            </div>

            <!-- Nueva contraseña -->
            <div class="form-group">
                <label for="nueva_password"><i class="fas fa-key"></i> Nueva Contraseña</label>
                <div class="input-with-icon">
                    <i class="fas fa-key"></i>
                    <input type="password" id="nueva_password" name="nueva_password" required>
                </div>
                <small style="color:#555;">
                    Debe contener: mínimo 8 caracteres, una mayúscula, una minúscula, un número y un símbolo.
                </small>
            </div>

            <!-- Confirmar nueva contraseña -->
            <div class="form-group">
                <label for="confirmar_password"><i class="fas fa-key"></i> Confirmar Nueva Contraseña</label>
                <div class="input-with-icon">
                    <i class="fas fa-key"></i>
                    <input type="password" id="confirmar_password" name="confirmar_password" required>
                </div>
            </div>

            <!-- Mostrar / ocultar -->
            <div class="show-password-container">
                <input type="checkbox" id="mostrar_contrasenas">
                <label for="mostrar_contrasenas">Mostrar contraseñas</label>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-save"></i> Guardar Nueva Contraseña
            </button>

            <a href="admin_index.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver
            </a>

        </form>
    </div>
</div>

<script>
// Mostrar / ocultar
document.getElementById('mostrar_contrasenas').addEventListener('change', function() {
    const type = this.checked ? 'text' : 'password';
    document.getElementById('password_actual').type = type;
    document.getElementById('nueva_password').type = type;
    document.getElementById('confirmar_password').type = type;
});
</script>

</body>
</html>
