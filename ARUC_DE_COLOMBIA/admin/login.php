<?php
session_start();
include 'includes/database.php';

$mensaje = '';
$error = '';

// Si ya está logueado, redirigir al panel
if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    header('Location: admin_index.php');
    exit;
}

if ($_POST) {
    $usuario = trim($_POST['usuario']);
    $contrasena = $_POST['contrasena'];
    
    try {
        // Buscar usuario (incluyendo campos de bloqueo)
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();

        if ($user) {
            // Verificar si la cuenta está bloqueada
            if ($user['bloqueado_hasta'] && strtotime($user['bloqueado_hasta']) > time()) {
                $tiempo_restante = strtotime($user['bloqueado_hasta']) - time();
                $minutos = ceil($tiempo_restante / 60);
                $error = "Cuenta temporalmente bloqueada. Intente nuevamente en $minutos minutos.";
            }
            // Validar si está desactivado
            else if ($user['estado'] !== 'Activo') {
                $error = "Su usuario está deshabilitado. Contacte al administrador.";
            } 
            // Si está activo y no bloqueado, verificar contraseña
            else {
                if (password_verify($contrasena, $user['contrasena'])) {
                    // Login exitoso - Resetear intentos fallidos
                    $stmtReset = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = ?");
                    $stmtReset->execute([$user['id_usuario']]);
                    
                    // Guardar datos en sesión
                    $_SESSION['admin_logged'] = true;
                    $_SESSION['admin_user'] = $user['usuario'];
                    $_SESSION['admin_name'] = $user['nombre_completo'];
                    $_SESSION['admin_id'] = $user['id_usuario'];
                    $_SESSION['login_time'] = time();

                    // ¿Primer login?
                    if ($user['primer_login'] == 1) {
                        header('Location: cambiar_password_first.php');
                        exit;
                    }

                    header('Location: admin_index.php');
                    exit;
                } 
                else {
                    // Contraseña incorrecta - Incrementar intentos
                    $nuevos_intentos = $user['intentos_fallidos'] + 1;
                    $stmtIntentos = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = ? WHERE id_usuario = ?");
                    $stmtIntentos->execute([$nuevos_intentos, $user['id_usuario']]);
                    
                    // Si alcanza 3 intentos fallidos, bloquear por 15 minutos
                    if ($nuevos_intentos >= 3) {
                        $bloqueo_hasta = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $stmtBloquear = $pdo->prepare("UPDATE usuarios SET bloqueado_hasta = ? WHERE id_usuario = ?");
                        $stmtBloquear->execute([$bloqueo_hasta, $user['id_usuario']]);
                        
                        $error = "Demasiados intentos fallidos. Cuenta bloqueada por 15 minutos.";
                    } else {
                        $intentos_restantes = 3 - $nuevos_intentos;
                        $error = "Contraseña incorrecta. Le quedan $intentos_restantes intentos.";
                    }
                }
            }
        } else {
            // Usuario NO existe en la base de datos
            $error = "El usuario no existe.";
        }

    } catch (PDOException $e) {
        $error = "Error en el sistema. Por favor, intente más tarde.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel de Administración ARUC</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../img/logo.png">
    <link rel="shortcut icon" href="../img/logo_aruc.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="../css/auth_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="header-content">
                <img src="../img/logo_aruc.jpeg" alt="Logo ARUC" class="logo-header">
                <div class="header-text">
                    <h2>Panel de Administración ARUC</h2>
                    <p>Sistema de Gestión Interna</p>
                </div>
            </div>
        </div>
        
        <div class="login-body">
            <?php 
            if (isset($_GET['success']) && $_GET['success'] == 'password_changed') {
                echo '<div class="success"><i class="fas fa-check-circle"></i> Contraseña cambiada exitosamente. Ahora puede iniciar sesión.</div>';
            }
            
            if (isset($_GET['success']) && $_GET['success'] == 'recovery_sent') {
                echo '<div class="success"><i class="fas fa-envelope"></i> Se ha enviado un enlace de recuperación a su correo electrónico.</div>';
            }
            
            // Mostrar error si existe
            if (!empty($error)) {
                echo '<div class="error"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($error) . '</div>';
            }
            ?>
            
            <form method="post" id="loginForm">
                <div class="form-group">
                    <label for="usuario"><i class="fas fa-user"></i> Usuario</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="usuario" name="usuario" required 
                               placeholder="Ingrese su nombre de usuario"
                               autocomplete="username"
                               value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>"
                               class="<?php echo isset($_POST['usuario']) ? 'has-value' : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="contrasena"><i class="fas fa-key"></i> Contraseña</label>
                    <div class="input-with-icon">
                        <i class="fas fa-key"></i>
                        <input type="password" id="contrasena" name="contrasena" required 
                               placeholder="Ingrese su contraseña"
                               autocomplete="current-password">
                    </div>
                    
                    <div class="show-password-container">
                        <input type="checkbox" id="mostrarContrasena">
                        <label for="mostrarContrasena">Mostrar contraseña</label>
                    </div>
                </div>
                
                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p>Verificando credenciales...</p>
                </div>
                
                <button type="submit" class="btn-login" id="submitBtn">
                    <i class="fas fa-sign-in-alt"></i> Ingresar al Sistema
                </button>
                
                <div class="forgot-password">
                    <a href="recuperar_password.php">
                        <i class="fas fa-question-circle"></i> ¿Olvidó su contraseña?
                    </a>
                </div>
            </form>
            
            <div class="login-footer">
                <p><i class="fas fa-shield-alt"></i> Sistema seguro • Acceso restringido al personal autorizado</p>
            </div>
        </div>
    </div>

    <script>
        // Mostrar/Ocultar contraseña con checkbox
        const mostrarCheckbox = document.getElementById('mostrarContrasena');
        const passwordInput = document.getElementById('contrasena');
        
        mostrarCheckbox.addEventListener('change', function() {
            if (this.checked) {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        });
        
        // Validación del formulario
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const usuario = document.getElementById('usuario').value.trim();
            const contrasena = document.getElementById('contrasena').value;
            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loading');
            
            if (!usuario || !contrasena) {
                e.preventDefault();
                alert('Por favor, complete todos los campos.');
                return false;
            }
            
            if (contrasena.length < 4) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 4 caracteres.');
                return false;
            }
            
            // Mostrar animación de carga
            submitBtn.style.display = 'none';
            loading.style.display = 'block';
            
            return true;
        });
        
        // Auto-enfocar el campo de usuario
        document.getElementById('usuario').focus();
        
        // Marcar campos con valor
        const usuarioInput = document.getElementById('usuario');
        usuarioInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.classList.add('has-value');
            } else {
                this.classList.remove('has-value');
            }
        });
        
        // Si ya tiene valor desde PHP
        if (usuarioInput.value.trim() !== '') {
            usuarioInput.classList.add('has-value');
        }
        
        // Prevenir reenvío del formulario con F5
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Enter para enviar formulario
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.target.matches('a, button')) {
                const form = document.getElementById('loginForm');
                if (form) {
                    form.requestSubmit();
                }
            }
        });
        
        // Manejar logo si no se carga
        const logo = document.querySelector('.logo-header');
        if (logo) {
            logo.addEventListener('error', function() {
                // Si el logo no carga, mostrar ícono como fallback
                const headerContent = document.querySelector('.header-content');
                if (headerContent && !document.querySelector('.logo-fallback')) {
                    const fallback = document.createElement('div');
                    fallback.className = 'logo-fallback';
                    fallback.innerHTML = '<i class="fas fa-user-lock" style="font-size: 60px; color: white; margin-bottom: 15px;"></i>';
                    logo.style.display = 'none';
                    headerContent.insertBefore(fallback, headerContent.firstChild);
                }
            });
        }
    </script>
</body>
</html>