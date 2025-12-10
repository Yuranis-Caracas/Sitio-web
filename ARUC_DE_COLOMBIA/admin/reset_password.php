<?php
session_start();
include 'includes/database.php';

$error = '';
$success = '';
$mostrarFormularioCodigo = true;
$mostrarFormularioPassword = false;
$user = null;

// PASO 1: Verificar si viene desde un enlace con TOKEN
$token_valido = false;
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verificar que el token sea válido Y que el usuario NO tenga contraseña temporal
    $stmt = $pdo->prepare("SELECT * FROM usuarios 
                          WHERE token_recuperacion = ? 
                          AND token_expiracion > NOW() 
                          AND estado = 'Activo'");
    $stmt->execute([$token]);
    $user_temp = $stmt->fetch();
    
    if ($user_temp) {
        // VERIFICAR QUE NO TENGA CONTRASEÑA TEMPORAL
        if ($user_temp['primer_login'] == 1) {
            $error = "❌ <strong>No puedes restablecer tu contraseña porque aún no has cambiado tu contraseña temporal.</strong><br><br>
                     Por favor, inicia sesión con la contraseña que te proporcionó el administrador y cámbiala primero.";
            $mostrarFormularioCodigo = false;
        } else {
            $token_valido = true;
            $_SESSION['token_recuperacion'] = $token;
            $_SESSION['email_usuario'] = $user_temp['correo'];
        }
    } else {
        $error = "⚠️ El enlace ha expirado o es inválido. Por favor solicita uno nuevo.";
        $mostrarFormularioCodigo = false;
    }
}

// PASO 2: Verificar el CÓDIGO ingresado por el usuario
if (isset($_POST['verificar_codigo']) && !empty($_POST['codigo'])) {
    $codigo = trim($_POST['codigo']);
    
    if (isset($_SESSION['token_recuperacion'])) {
        $token = $_SESSION['token_recuperacion'];
        
        $stmt = $pdo->prepare("SELECT * FROM usuarios 
                              WHERE token_recuperacion = ? 
                              AND codigo_recuperacion = ? 
                              AND token_expiracion > NOW() 
                              AND estado = 'Activo'");
        $stmt->execute([$token, $codigo]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Verificar nuevamente que no tenga contraseña temporal
            if ($user['primer_login'] == 1) {
                $error = "❌ No puedes restablecer tu contraseña porque aún tienes una contraseña temporal activa.";
                $mostrarFormularioCodigo = false;
            } else {
                $_SESSION['usuario_verificado'] = $user['id_usuario'];
                $mostrarFormularioCodigo = false;
                $mostrarFormularioPassword = true;
            }
        } else {
            $error = "❌ Código incorrecto. Verifica el código que recibiste en tu correo.";
            $mostrarFormularioCodigo = true;
        }
    } 
    else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios 
                              WHERE codigo_recuperacion = ? 
                              AND token_expiracion > NOW() 
                              AND estado = 'Activo'");
        $stmt->execute([$codigo]);
        $user = $stmt->fetch();
        
        if ($user) {
            if ($user['primer_login'] == 1) {
                $error = "❌ No puedes restablecer tu contraseña porque aún tienes una contraseña temporal activa.";
                $mostrarFormularioCodigo = false;
            } else {
                $_SESSION['usuario_verificado'] = $user['id_usuario'];
                $mostrarFormularioCodigo = false;
                $mostrarFormularioPassword = true;
            }
        } else {
            $error = "❌ Código inválido o expirado. Por favor solicita uno nuevo.";
            $mostrarFormularioCodigo = true;
        }
    }
}

// PASO 3: Mantener el formulario de contraseña si ya verificó el código
if (isset($_SESSION['usuario_verificado']) && !$user) {
    $id_usuario = $_SESSION['usuario_verificado'];
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios 
                          WHERE id_usuario = ? 
                          AND token_expiracion > NOW() 
                          AND estado = 'Activo'");
    $stmt->execute([$id_usuario]);
    $user = $stmt->fetch();
    
    if ($user) {
        $mostrarFormularioCodigo = false;
        $mostrarFormularioPassword = true;
    } else {
        unset($_SESSION['usuario_verificado']);
        unset($_SESSION['token_recuperacion']);
        $error = "⏱️ La sesión ha expirado. Por favor solicita un nuevo código.";
        $mostrarFormularioCodigo = false;
    }
}

// PASO 4: Procesar el cambio de contraseña
if (isset($_POST['actualizar_password']) && isset($_SESSION['usuario_verificado'])) {
    $id_usuario = $_SESSION['usuario_verificado'];
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ? AND estado = 'Activo'");
    $stmt->execute([$id_usuario]);
    $user = $stmt->fetch();
    
    if ($user) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $error = "Las contraseñas no coinciden.";
            $mostrarFormularioPassword = true;
        } 
        elseif (strlen($new_password) < 8) {
            $error = "La contraseña debe tener al menos 8 caracteres.";
            $mostrarFormularioPassword = true;
        }
        elseif (!preg_match('/[A-Z]/', $new_password)) {
            $error = "La contraseña debe incluir al menos una letra mayúscula.";
            $mostrarFormularioPassword = true;
        }
        elseif (!preg_match('/[a-z]/', $new_password)) {
            $error = "La contraseña debe incluir al menos una letra minúscula.";
            $mostrarFormularioPassword = true;
        }
        elseif (!preg_match('/[0-9]/', $new_password)) {
            $error = "La contraseña debe incluir al menos un número.";
            $mostrarFormularioPassword = true;
        }
        elseif (!preg_match('/[\W_]/', $new_password)) {
            $error = "La contraseña debe incluir al menos un símbolo.";
            $mostrarFormularioPassword = true;
        }
        else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Limpiar tokens
            $stmt = $pdo->prepare("UPDATE usuarios SET 
                contrasena = ?, 
                token_recuperacion = NULL, 
                token_expiracion = NULL, 
                codigo_recuperacion = NULL 
                WHERE id_usuario = ?");

            if ($stmt->execute([$hashed_password, $id_usuario])) {
                $success = "✅ ¡Contraseña actualizada correctamente! Ya puedes iniciar sesión con tu nueva contraseña.";
                $mostrarFormularioPassword = false;
                $mostrarFormularioCodigo = false;
                
                // Limpiar todas las sesiones
                unset($_SESSION['usuario_verificado']);
                unset($_SESSION['token_recuperacion']);
                unset($_SESSION['email_usuario']);
            } else {
                $error = "❌ Error al actualizar la contraseña. Por favor intenta nuevamente.";
                $mostrarFormularioPassword = true;
            }
        }
    } else {
        $error = "❌ Usuario no encontrado o inactivo.";
        $mostrarFormularioPassword = false;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - ARUC</title>
    <link rel="shortcut icon" href="../img/logo_aruc.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="../css/auth_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <div class="header-content">
                <div class="header-text">
                    <h2>🔐 Restablecer Contraseña</h2>
                    <p>Proceso seguro de recuperación de cuenta</p>
                </div>
            </div>
        </div>
        
        <!-- Indicador de pasos -->
        <div class="step-indicator">
            <div class="step <?= $mostrarFormularioCodigo ? 'active' : '' ?>">
                <div class="step-number">1</div>
                <span>Verificar Código</span>
            </div>
            <div class="step-line"></div>
            <div class="step <?= $mostrarFormularioPassword ? 'active' : '' ?>">
                <div class="step-number">2</div>
                <span>Nueva Contraseña</span>
            </div>
        </div>
        
        <div class="login-body">
            
            <?php if ($success): ?>
                <!-- ÉXITO: Contraseña actualizada -->
                <div class="success">
                    <i class="fas fa-check-circle"></i> 
                    <?= htmlspecialchars($success); ?>
                </div>
                <div class="form-footer">
                    <a href="login.php" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Iniciar sesión ahora
                    </a>
                </div>
                
            <?php elseif ($mostrarFormularioPassword && $user): ?>
                <!-- PASO 2: FORMULARIO para cambiar contraseña -->
                <div class="info-box">
                    <h3>
                        <i class="fas fa-user-check"></i> 
                        Hola, <?= htmlspecialchars($user['nombre_completo']); ?>
                    </h3>
                    <p>Tu identidad ha sido verificada. Ahora establece una nueva contraseña segura.</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="error">
                        <i class="fas fa-exclamation-circle"></i> 
                        <?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="password-requirements">
                    <strong><i class="fas fa-info-circle"></i> Requisitos de la contraseña:</strong>
                    <div id="password-rules">
                        <div class="requirement pending" id="rule-length">
                            <i class="fas fa-circle"></i> Mínimo 8 caracteres
                        </div>
                        <div class="requirement pending" id="rule-upper">
                            <i class="fas fa-circle"></i> Al menos una letra mayúscula
                        </div>
                        <div class="requirement pending" id="rule-lower">
                            <i class="fas fa-circle"></i> Al menos una letra minúscula
                        </div>
                        <div class="requirement pending" id="rule-number">
                            <i class="fas fa-circle"></i> Al menos un número
                        </div>
                        <div class="requirement pending" id="rule-symbol">
                            <i class="fas fa-circle"></i> Al menos un símbolo (!, @, #, $, %, &)
                        </div>
                    </div>
                </div>
                
                <form method="post" id="resetPasswordForm">
                    <input type="hidden" name="actualizar_password" value="1">
                    
                    <div class="form-group">
                        <label for="new_password">
                            <i class="fas fa-lock"></i> Nueva Contraseña
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="new_password" name="new_password" required 
                                   oninput="validatePassword()" autocomplete="new-password">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i> Confirmar Nueva Contraseña
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   oninput="validatePasswordMatch()" autocomplete="new-password">
                        </div>
                        <small id="password-match-message" class="form-help"></small>
                    </div>
                    
                    <div class="show-password-container">
                        <input type="checkbox" id="mostrarPasswords">
                        <label for="mostrarPasswords">Mostrar contraseñas</label>
                    </div>
                    
                    <div id="validation-summary" class="validation-summary error" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span id="validation-message"></span>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sync-alt"></i> Actualizar Contraseña
                    </button>
                </form>
                
            <?php elseif ($mostrarFormularioCodigo): ?>
                <!-- PASO 1: FORMULARIO para ingresar código de verificación -->
                <div class="info-box">
                    <h3>
                        <i class="fas fa-shield-alt"></i> 
                        Verificación de Seguridad
                    </h3>
                    <p>Ingresa el <strong>código de 6 dígitos</strong> que recibiste en tu correo electrónico para continuar.</p>
                </div>
                
                <?php if (isset($_SESSION['email_usuario'])): ?>
                    <div class="security-info">
                        <i class="fas fa-envelope"></i>
                        <strong>Correo registrado:</strong> <?= htmlspecialchars($_SESSION['email_usuario']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="error">
                        <i class="fas fa-exclamation-circle"></i> 
                        <?= $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" id="verifyCodeForm">
                    <input type="hidden" name="verificar_codigo" value="1">
                    
                    <div class="form-group">
                        <label for="codigo">
                            <i class="fas fa-keyboard"></i> Código de Verificación
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-hashtag"></i>
                            <input type="text" 
                                   id="codigo" 
                                   name="codigo" 
                                   class="code-input-large"
                                   maxlength="6" 
                                   pattern="[0-9]{6}" 
                                   required 
                                   placeholder="000000" 
                                   oninput="validateCode()"
                                   autocomplete="off"
                                   autofocus>
                        </div>
                        <small id="code-validation-message" class="form-help"></small>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-check-circle"></i> Verificar Código
                    </button>
                </form>
                
                <div class="security-info" style="margin-top: 20px;">
                    <i class="fas fa-info-circle"></i>
                    <strong>¿No encuentras el código?</strong> Revisa tu bandeja de entrada y la carpeta de SPAM.
                </div>
                
                <div class="back-link">
                    <a href="recuperar_password.php">
                        <i class="fas fa-redo"></i> Solicitar un nuevo código
                    </a>
                </div>
                
                <div class="back-link" style="border-top: none; padding-top: 10px; margin-top: 10px;">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Volver al inicio de sesión
                    </a>
                </div>
                
            <?php else: ?>
                <!-- ERROR: Sin acceso -->
                <div class="error">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <?= !empty($error) ? $error : 'No se pudo procesar tu solicitud.' ?>
                </div>
                
                <div class="back-link">
                    <a href="recuperar_password.php">
                        <i class="fas fa-redo"></i> Solicitar recuperación de contraseña
                    </a>
                </div>
                
                <div class="back-link" style="border-top: none; padding-top: 10px; margin-top: 10px;">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Volver al inicio de sesión
                    </a>
                </div>
            <?php endif; ?>
            
        </div>
    </div>

<script>
// Variables globales para validación
let passwordValidations = {
    length: false,
    upper: false,
    lower: false,
    number: false,
    symbol: false
};

let passwordMatch = false;
let codeValid = false;

// Mostrar/Ocultar contraseñas
if (document.getElementById('mostrarPasswords')) {
    document.getElementById('mostrarPasswords').addEventListener('change', function() {
        const type = this.checked ? 'text' : 'password';
        document.getElementById('new_password').type = type;
        document.getElementById('confirm_password').type = type;
    });
}

// Validación de contraseña en tiempo real
function validatePassword() {
    const password = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password')?.value;
    
    const rules = {
        length: password.length >= 8,
        upper: /[A-Z]/.test(password),
        lower: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        symbol: /[\W_]/.test(password)
    };
    
    Object.keys(rules).forEach(rule => {
        const element = document.getElementById(`rule-${rule}`);
        const text = element.textContent.replace(/[✓✗]/, '').trim();
        
        if (rules[rule]) {
            element.classList.remove('pending', 'invalid');
            element.classList.add('valid');
            element.innerHTML = `<i class="fas fa-check-circle"></i> ${text}`;
        } else {
            element.classList.remove('pending', 'valid');
            element.classList.add('invalid');
            element.innerHTML = `<i class="fas fa-times-circle"></i> ${text}`;
        }
        passwordValidations[rule] = rules[rule];
    });
    
    if (confirmPassword) {
        validatePasswordMatch();
    }
}

// Validar coincidencia de contraseñas
function validatePasswordMatch() {
    const password = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const messageElement = document.getElementById('password-match-message');
    
    if (confirmPassword === '') {
        messageElement.textContent = '';
        passwordMatch = false;
    } else if (password === confirmPassword) {
        messageElement.textContent = '✅ Las contraseñas coinciden';
        messageElement.style.color = '#28a745';
        passwordMatch = true;
    } else {
        messageElement.textContent = '❌ Las contraseñas no coinciden';
        messageElement.style.color = '#dc3545';
        passwordMatch = false;
    }
}

// Validar código en tiempo real
function validateCode() {
    const code = document.getElementById('codigo').value;
    const messageElement = document.getElementById('code-validation-message');
    
    // Solo permitir números
    document.getElementById('codigo').value = code.replace(/\D/g, '');
    const cleanCode = code.replace(/\D/g, '');
    
    if (/^\d{6}$/.test(cleanCode)) {
        messageElement.textContent = '✅ Formato correcto';
        messageElement.style.color = '#28a745';
        codeValid = true;
    } else {
        messageElement.textContent = '';
        codeValid = false;
    }
}

// Validación del formulario de contraseña
if (document.getElementById('resetPasswordForm')) {
    document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const validationSummary = document.getElementById('validation-summary');
        const validationMessage = document.getElementById('validation-message');
        
        const missingRules = [];
        if (!passwordValidations.length) missingRules.push('Debe tener al menos 8 caracteres');
        if (!passwordValidations.upper) missingRules.push('Debe incluir al menos una letra mayúscula');
        if (!passwordValidations.lower) missingRules.push('Debe incluir al menos una letra minúscula');
        if (!passwordValidations.number) missingRules.push('Debe incluir al menos un número');
        if (!passwordValidations.symbol) missingRules.push('Debe incluir al menos un símbolo');
        if (!passwordMatch) missingRules.push('Las contraseñas no coinciden');
        
        if (missingRules.length > 0) {
            validationMessage.innerHTML = '<strong>Corrige los siguientes errores:</strong><br>' + 
                                         missingRules.map(rule => `• ${rule}`).join('<br>');
            validationSummary.style.display = 'block';
            validationSummary.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            this.submit();
        }
    });
}

// Validación del formulario de código
if (document.getElementById('verifyCodeForm')) {
    document.getElementById('verifyCodeForm').addEventListener('submit', function(e) {
        const codigo = document.getElementById('codigo').value;
        
        if (!/^\d{6}$/.test(codigo)) {
            e.preventDefault();
            const messageElement = document.getElementById('code-validation-message');
            messageElement.textContent = '❌ El código debe tener exactamente 6 dígitos';
            messageElement.style.color = '#dc3545';
            return false;
        }
        
        return true;
    });
}

// Inicializar validaciones
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('new_password')) {
        validatePassword();
    }
});
</script>

</body>
</html>