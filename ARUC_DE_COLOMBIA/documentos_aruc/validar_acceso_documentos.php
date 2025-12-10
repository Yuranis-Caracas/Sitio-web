<?php
session_start();
include '../admin/includes/database.php';

// Verificar si ya está validado en esta sesión
if (isset($_SESSION['cedula_validada']) && $_SESSION['cedula_validada'] === true) {
    header('Location: ver_documentos.php');
    exit;
}

$error = '';
$success = '';

// Procesar el formulario de validación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = trim($_POST['cedula'] ?? '');
    
    // Validar que sea solo números
    if (empty($cedula)) {
        $error = 'Por favor ingrese su número de cédula';
    } elseif (!preg_match('/^[0-9]{6,15}$/', $cedula)) {
        $error = 'Formato de cédula inválido. Solo números (6-15 dígitos)';
    } else {
        try {
            // Verificar si la cédula está autorizada
            $stmt = $pdo->prepare("SELECT * FROM cedulas_autorizadas WHERE cedula = ?");
            $stmt->execute([$cedula]);
            $autorizado = $stmt->fetch();
            
            if ($autorizado) {
                // Guardar en sesión
                $_SESSION['cedula_validada'] = true;
                $_SESSION['cedula_numero'] = $cedula;
                $_SESSION['fecha_validacion'] = time();
                
                header('Location: ver_documentos.php');
                exit;
            } else {
                $error = 'Número de cédula no autorizado. No tiene acceso a los documentos institucionales.';
            }
        } catch (Exception $e) {
            $error = 'Error en la validación. Por favor intente más tarde.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar Acceso a Documentos - ARUC DE COLOMBIA</title>
    <link rel="shortcut icon" href="../img/logo_aruc.jpeg" type="image/x-icon">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="../css/auth_style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="header-content">
                <img src="../img/logo_aruc.jpeg" alt="Logo ARUC" class="logo-header">
                <div class="header-text">
                    <h2><i class="bi bi-file-earmark-lock-fill me-2"></i>Documentos Institucionales</h2>
                    <p>Acceso restringido para miembros autorizados</p>
                </div>
            </div>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="error">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="cedula-icon">
                <i class="bi bi-person-badge"></i>
            </div>
            
            <div class="title-section">
                <h3>Validación de Identidad</h3>
                <p>Ingrese su número de cédula para verificar si tiene acceso a los documentos institucionales de ARUC DE COLOMBIA</p>
            </div>
            
            <form method="POST" action="" id="validacionForm">
                <div class="form-group">
                    <label for="cedula">
                        <i class="bi bi-card-heading me-1"></i> Número de Cédula
                    </label>
                    <div class="input-with-icon">
                        <i class="bi bi-123"></i>
                        <input type="text" 
                               id="cedula" 
                               name="cedula" 
                               placeholder="Ej: 1234567890" 
                               required
                               maxlength="15"
                               value="<?php echo isset($_POST['cedula']) ? htmlspecialchars($_POST['cedula']) : ''; ?>"
                               class="<?php echo isset($_POST['cedula']) ? 'has-value' : ''; ?>">
                    </div>
                    <div class="form-text">
                        Solo números, sin puntos ni espacios. Ej: 1234567890
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn-login">
                        <i class="bi bi-check-circle me-2"></i> VALIDAR ACCESO
                    </button>
                    
                    <a href="../index.php" class="btn-volver">
                        <i class="bi bi-arrow-left me-2"></i> VOLVER AL INICIO
                    </a>
                </div>
            </form>
            
            <div class="info-box">
                <h6><i class="bi bi-info-circle-fill"></i> Información importante:</h6>
                <ul>
                    <li>Solo podrás <strong>visualizar</strong> los documentos, no descargarlos</li>
                    <li>El acceso es exclusivo para miembros autorizados por ARUC DE COLOMBIA</li>
                </ul>
            </div>
            
            <div class="login-footer">
                <i class="bi bi-shield-lock"></i>
                <span>Sistema seguro de validación de identidad</span>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (solo para íconos) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Validación del formulario en el cliente
        document.getElementById('validacionForm').addEventListener('submit', function(e) {
            const cedula = document.getElementById('cedula').value.trim();
            const cedulaRegex = /^[0-9]{6,15}$/;
            
            if (!cedulaRegex.test(cedula)) {
                e.preventDefault();
                alert('Por favor ingrese un número de cédula válido (solo números, 6 a 15 dígitos)');
                document.getElementById('cedula').focus();
            } else {
                // Mostrar animación de carga
                const submitBtn = this.querySelector('.btn-login');
                submitBtn.innerHTML = '<div class="spinner" style="display: inline-block; border: 2px solid #f3f3f3; border-top: 2px solid #2e7d32; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; margin-right: 8px;"></div>Validando...';
                submitBtn.disabled = true;
            }
        });
        
        // Limpiar solo números en el campo de cédula
        document.getElementById('cedula').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Agregar/quitar clase has-value
            if (this.value.length > 0) {
                this.classList.add('has-value');
            } else {
                this.classList.remove('has-value');
            }
        });
        
        // Verificar campo al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const cedulaField = document.getElementById('cedula');
            if (cedulaField.value.length > 0) {
                cedulaField.classList.add('has-value');
            }
        });
        
        // Prevenir envío con Enter fuera del formulario
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>