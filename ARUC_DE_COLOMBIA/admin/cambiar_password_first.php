<?php
session_start();
include 'includes/database.php';

// Verificar que el usuario está logueado
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password_actual = $_POST['password_actual'];
    $password_nueva = $_POST['password_nueva'];
    $password_confirmar = $_POST['password_confirmar'];
    
    try {
        // Obtener datos del usuario
        $stmt = $pdo->prepare("SELECT contrasena FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch();
        
        // Validaciones
        if (!password_verify($password_actual, $user['contrasena'])) {
            $error = "❌ La contraseña actual es incorrecta.";
        } 
        elseif ($password_actual === $password_nueva) {
            $error = "❌ La nueva contraseña debe ser diferente a la actual.";
        }
        elseif (strlen($password_nueva) < 8) {
            $error = "❌ La contraseña debe tener al menos 8 caracteres.";
        }
        elseif (!preg_match('/[A-Z]/', $password_nueva)) {
            $error = "❌ La contraseña debe incluir al menos una letra mayúscula.";
        }
        elseif (!preg_match('/[a-z]/', $password_nueva)) {
            $error = "❌ La contraseña debe incluir al menos una letra minúscula.";
        }
        elseif (!preg_match('/[0-9]/', $password_nueva)) {
            $error = "❌ La contraseña debe incluir al menos un número.";
        }
        elseif (!preg_match('/[\W_]/', $password_nueva)) {
            $error = "❌ La contraseña debe incluir al menos un símbolo (%, &, #, !, etc.).";
        }
        elseif ($password_nueva !== $password_confirmar) {
            $error = "❌ Las contraseñas nuevas no coinciden.";
        }
        else {
            // Guardar nueva contraseña
            $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET contrasena = ?, primer_login = 0 
                WHERE id_usuario = ?
            ");
            $stmt->execute([$password_hash, $_SESSION['admin_id']]);
            
            // Redirigir con éxito
            header('Location: admin_index.php?password_changed=1');
            exit;
        }

    } catch (PDOException $e) {
        $error = "❌ Error al actualizar la contraseña. Intente nuevamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - ARUC</title>
    <link rel="shortcut icon" href="../img/logo_aruc.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="../css/auth_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <div class="header-content">
                <div class="header-text">
                    <h2>Cambio de Contraseña Obligatorio</h2>
                    <p>Primera vez en el sistema</p>
                </div>
            </div>
        </div>
        
        <div class="login-body">
            <div class="info-box">
                <h3>
                    <i class="fas fa-shield-alt"></i> 
                    Bienvenido(a), <?= htmlspecialchars($_SESSION['admin_name']); ?>
                </h3>
                <p>Por seguridad, debes cambiar tu contraseña temporal antes de continuar.</p>
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
                        <i class="fas fa-circle"></i> Al menos un símbolo (!, @, #, %, &)
                    </div>
                    <div class="requirement pending" id="rule-different">
                        <i class="fas fa-circle"></i> Diferente a la contraseña actual
                    </div>
                </div>
            </div>
            
            <form method="post" id="cambiarPasswordForm">
                <div class="form-group">
                    <label for="password_actual">
                        <i class="fas fa-key"></i> Contraseña Actual (temporal)
                    </label>
                    <div class="input-with-icon">
                        <i class="fas fa-key"></i>
                        <input type="password" id="password_actual" name="password_actual" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password_nueva">
                        <i class="fas fa-lock"></i> Nueva Contraseña
                    </label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password_nueva" name="password_nueva" required 
                               oninput="validatePassword()">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password_confirmar">
                        <i class="fas fa-lock"></i> Confirmar Nueva Contraseña
                    </label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password_confirmar" name="password_confirmar" required 
                               oninput="validatePasswordMatch()">
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
                    <i class="fas fa-sync-alt"></i> Cambiar Contraseña y Continuar
                </button>
            </form>
            
             <div class="login-footer">
                <p>
                    <i class="fas fa-sign-out-alt"></i> 
                    <a href="logout.php">Cerrar sesión</a>
                </p>
            </div>
        </div>
    </div>

<script>
// Variables globales para almacenar el estado de validación
let passwordValidations = {
    length: false,
    upper: false,
    lower: false,
    number: false,
    symbol: false,
    different: false
};

let passwordMatch = false;

// Mostrar/Ocultar contraseñas
document.getElementById('mostrarPasswords').addEventListener('change', function() {
    const type = this.checked ? 'text' : 'password';
    document.getElementById('password_actual').type = type;
    document.getElementById('password_nueva').type = type;
    document.getElementById('password_confirmar').type = type;
});

// Validación de contraseña en tiempo real
function validatePassword() {
    const passwordActual = document.getElementById('password_actual').value;
    const passwordNueva = document.getElementById('password_nueva').value;
    const confirmPassword = document.getElementById('password_confirmar').value;
    
    // Validar cada regla
    const rules = {
        length: passwordNueva.length >= 8,
        upper: /[A-Z]/.test(passwordNueva),
        lower: /[a-z]/.test(passwordNueva),
        number: /[0-9]/.test(passwordNueva),
        symbol: /[\W_]/.test(passwordNueva),
        different: passwordNueva !== passwordActual && passwordNueva !== ''
    };
    
    // Actualizar visualmente cada regla
    Object.keys(rules).forEach(rule => {
        const element = document.getElementById(`rule-${rule}`);
        if (rules[rule]) {
            element.classList.remove('pending', 'invalid');
            element.classList.add('valid');
            element.innerHTML = `<i class="fas fa-check-circle"></i> ${element.textContent.split(' ').slice(1).join(' ')}`;
        } else {
            element.classList.remove('pending', 'valid');
            element.classList.add('invalid');
            element.innerHTML = `<i class="fas fa-times-circle"></i> ${element.textContent.split(' ').slice(1).join(' ')}`;
        }
        passwordValidations[rule] = rules[rule];
    });
    
    // Validar coincidencia si ya hay confirmación
    if (confirmPassword) {
        validatePasswordMatch();
    }
    
    // Actualizar el botón de submit
    updateSubmitButton();
}

// Validar coincidencia de contraseñas en tiempo real
function validatePasswordMatch() {
    const passwordNueva = document.getElementById('password_nueva').value;
    const confirmPassword = document.getElementById('password_confirmar').value;
    const messageElement = document.getElementById('password-match-message');
    
    if (confirmPassword === '') {
        messageElement.textContent = '';
        passwordMatch = false;
    } else if (passwordNueva === confirmPassword) {
        messageElement.textContent = '✅ Las contraseñas coinciden';
        messageElement.style.color = '#28a745';
        passwordMatch = true;
    } else {
        messageElement.textContent = '❌ Las contraseñas no coinciden';
        messageElement.style.color = '#dc3545';
        passwordMatch = false;
    }
    
    updateSubmitButton();
}

// Actualizar estado del botón de envío
function updateSubmitButton() {
    const submitBtn = document.getElementById('cambiarPasswordForm').querySelector('button[type="submit"]');
    const allValid = Object.values(passwordValidations).every(v => v) && passwordMatch;
    
    if (allValid) {
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
        document.getElementById('validation-summary').style.display = 'none';
    } else {
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
    }
}

// Validación del formulario al enviar
document.getElementById('cambiarPasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const passwordActual = document.getElementById('password_actual').value;
    const passwordNueva = document.getElementById('password_nueva').value;
    const confirmPassword = document.getElementById('password_confirmar').value;
    const validationSummary = document.getElementById('validation-summary');
    const validationMessage = document.getElementById('validation-message');
    
    // Verificar todas las validaciones
    const missingRules = [];
    if (!passwordValidations.length) missingRules.push('Debe tener al menos 8 caracteres');
    if (!passwordValidations.upper) missingRules.push('Debe incluir al menos una letra mayúscula');
    if (!passwordValidations.lower) missingRules.push('Debe incluir al menos una letra minúscula');
    if (!passwordValidations.number) missingRules.push('Debe incluir al menos un número');
    if (!passwordValidations.symbol) missingRules.push('Debe incluir al menos un símbolo');
    if (!passwordValidations.different) missingRules.push('Debe ser diferente a la contraseña actual');
    if (!passwordMatch) missingRules.push('Las contraseñas no coinciden');
    
    if (missingRules.length > 0) {
        validationMessage.innerHTML = '<strong>Corrige los siguientes errores:</strong><br>' + 
                                     missingRules.map(rule => `• ${rule}`).join('<br>');
        validationSummary.style.display = 'block';
        validationSummary.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        // Todas las validaciones pasaron, enviar formulario
        this.submit();
    }
});

// Inicializar validaciones
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('password_nueva')) {
        validatePassword();
    }
});
</script>

</body>
</html>