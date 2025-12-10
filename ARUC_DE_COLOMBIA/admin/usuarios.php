<?php
// admin/usuarios.php
include 'includes/auth.php';
include 'includes/database.php';

// Verificar autenticación básica
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Leer usuarios
$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY fecha_creacion DESC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear nuevo usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_usuario'])) {
    $nombre_completo = $_POST['nombre_completo'];
    $correo = $_POST['correo'];
    $usuario = $_POST['usuario'];
    $estado = $_POST['estado'];
    $pass = $_POST['contrasena'];

    // VALIDACIONES DE SEGURIDAD
    if (strlen($pass) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres.";
    } elseif (!preg_match('/[A-Z]/', $pass)) {
        $error = "La contraseña debe incluir al menos una letra mayúscula.";
    } elseif (!preg_match('/[a-z]/', $pass)) {
        $error = "La contraseña debe incluir al menos una letra minúscula.";
    } elseif (!preg_match('/[0-9]/', $pass)) {
        $error = "La contraseña debe incluir al menos un número.";
    } elseif (!preg_match('/[\W_]/', $pass)) {
        $error = "La contraseña debe incluir al menos un símbolo.";
    } else {
        // SI TODO ES CORRECTO → HASH
        $contrasena = password_hash($pass, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_completo, correo, usuario, contrasena, estado) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre_completo, $correo, $usuario, $contrasena, $estado]);

            $success = "Usuario creado exitosamente";
        } catch (PDOException $e) {
            $error = "Error: El usuario o correo ya existe";
        }
    }
}

// Cambiar estado del usuario
if (isset($_POST['cambiar_estado'])) {
    $id_usuario = $_POST['id_usuario'];
    $nuevo_estado = $_POST['nuevo_estado'];
    
    $stmt = $pdo->prepare("UPDATE usuarios SET estado = ? WHERE id_usuario = ?");
    $stmt->execute([$nuevo_estado, $id_usuario]);
    $success = "Estado del usuario actualizado exitosamente";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - ARUC DE COLOMBIA</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../img/logo.png">
    <link rel="shortcut icon" href="../img/logo_aruc.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <img src="../img/logo_aruc.jpeg" alt="Logo ARUC" class="logo-header">
            <div class="header-text">
                <h1>Panel de Administración ARUC DE COLOMBIA</h1>
                <div>Bienvenido(a), <?php echo htmlspecialchars($_SESSION['admin_name']); ?></div>
            </div>
        </div>
        <a href="logout.php" class="logout-link">
            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
        </a>
    </div>
    
    <div class="container">
        <div class="sidebar">
            <div class="menu">
                <a href="admin_index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="admin_eventos.php"><i class="bi bi-calendar-event"></i> Gestión de Eventos</a>
                <a href="afiliaciones.php"><i class="bi bi-people-fill"></i> Afiliaciones</a>
                <a href="documentos.php"><i class="bi bi-folder"></i> Documentos</a>
                <a href="cedulas.php"><i class="bi bi-card-checklist"></i> Cédulas Autorizadas</a>
                <a href="usuarios.php" class="active"><i class="bi bi-person-gear"></i> Usuarios Admin</a>
            </div>
        </div>
        
        <div class="content">
            <div class="welcome-user">
                <i class="bi bi-person-check-fill"></i>
                <div class="welcome-text">
                    <strong>Sesión activa:</strong> Has iniciado sesión como <strong><?php echo htmlspecialchars($_SESSION['admin_user']); ?></strong>
                </div>
            </div>
            
            <h2>Gestión de Usuarios Administradores</h2>
            
            <?php if (isset($success)): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>
            <?php if (isset($error)): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
            
            <div class="form-container">
                <h3>Agregar Nuevo Usuario</h3>
                <form method="post">
                    <div class="form-group">
                        <label>Nombre Completo:</label>
                        <input type="text" name="nombre_completo" required>
                    </div>
                    <div class="form-group">
                        <label>Correo:</label>
                        <input type="email" name="correo" required>
                    </div>
                    <div class="form-group">
                        <label>Usuario:</label>
                        <input type="text" name="usuario" required>
                    </div>

                    <!-- CONTRASEÑA CON REQUISITOS Y MOSTRAR -->
                    <div class="form-group">
                        <label>Contraseña:</label>
                        <input type="password" id="contrasena" name="contrasena" required>

                        <small style="color:#555; font-size:12px; display:block; margin-top:5px;">
                            La contraseña debe tener mínimo 8 caracteres, incluir una mayúscula,
                            una minúscula, un número y un símbolo.
                        </small>

                        <div class="show-password-container">
                            <input type="checkbox" id="mostrar_contra">
                            <label for="mostrar_contra">Mostrar contraseña</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Estado:</label>
                        <select name="estado">
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                    <button type="submit" name="crear_usuario" class="btn">
                        <i class="bi bi-person-plus"></i> Crear Usuario
                    </button>
                </form>
            </div>

            <div class="form-container">
                <h3>Lista de Usuarios</h3>
                <?php if (count($usuarios) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre Completo</th>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $user): ?>
                            <tr>
                                <td><?php echo $user['id_usuario']; ?></td>
                                <td><?php echo htmlspecialchars($user['nombre_completo']); ?></td>
                                <td><?php echo htmlspecialchars($user['usuario']); ?></td>
                                <td><?php echo htmlspecialchars($user['correo']); ?></td>
                                <td>
                                    <?php if ($user['estado'] == 'Activo'): ?>
                                        <span class="badge-activo">Activo</span>
                                    <?php else: ?>
                                        <span class="badge-inactivo">Inactivo</span>
                                    <?php endif; ?>

                                    <form method="post" style="display:inline; margin-left: 10px;">
                                        <input type="hidden" name="id_usuario" value="<?php echo $user['id_usuario']; ?>">
                                        <input type="hidden" name="nuevo_estado" value="<?php echo $user['estado'] == 'Activo' ? 'Inactivo' : 'Activo'; ?>">
                                        <button type="submit" name="cambiar_estado" class="btn btn-sm" onclick="return confirm('¿Cambiar estado del usuario?')">
                                            <i class="bi bi-arrow-repeat"></i> Cambiar
                                        </button>
                                    </form>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($user['fecha_creacion'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No hay usuarios registrados.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<script>
// Mostrar / ocultar contraseña
document.getElementById("mostrar_contra").addEventListener("change", function() {
    const input = document.getElementById("contrasena");
    input.type = this.checked ? "text" : "password";
});

// Validación antes de enviar
document.querySelector("form").addEventListener("submit", function(e) {
    const pass = document.getElementById("contrasena").value;

    if (pass.length < 8) {
        alert("La contraseña debe tener al menos 8 caracteres.");
        e.preventDefault(); return;
    }
    if (!/[A-Z]/.test(pass)) {
        alert("Debe incluir al menos una letra mayúscula.");
        e.preventDefault(); return;
    }
    if (!/[a-z]/.test(pass)) {
        alert("Debe incluir al menos una letra minúscula.");
        e.preventDefault(); return;
    }
    if (!/[0-9]/.test(pass)) {
        alert("Debe incluir al menos un número.");
        e.preventDefault(); return;
    }
    if (!/[\W_]/.test(pass)) {
        alert("Debe incluir al menos un símbolo.");
        e.preventDefault(); return;
    }
});
</script>

</body>
</html>