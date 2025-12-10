<?php
include 'includes/auth.php';
include 'includes/database.php';

// Agregar cédula
if ($_POST && isset($_POST['agregar_cedula'])) {
    $cedula = $_POST['cedula'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO cedulas_autorizadas (cedula) VALUES (?)");
        $stmt->execute([$cedula]);
        $success = "Cédula autorizada agregada exitosamente";
    } catch (PDOException $e) {
        $error = "Error: La cédula ya existe";
    }
}

// Editar cédula
if ($_POST && isset($_POST['editar_cedula'])) {
    $id_autorizacion = $_POST['id_autorizacion'];
    $nueva_cedula = $_POST['nueva_cedula'];
    
    try {
        $stmt = $pdo->prepare("UPDATE cedulas_autorizadas SET cedula = ? WHERE id_autorizacion = ?");
        $stmt->execute([$nueva_cedula, $id_autorizacion]);
        $success = "Cédula actualizada exitosamente";
    } catch (PDOException $e) {
        $error = "Error: La cédula ya existe o hubo un problema al actualizar";
    }
}

// Eliminar cédula
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM cedulas_autorizadas WHERE id_autorizacion = ?");
    $stmt->execute([$id]);
    $success = "Cédula eliminada exitosamente";
}

$cedulas = $pdo->query("SELECT * FROM cedulas_autorizadas ORDER BY fecha_autorizacion DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cédulas Autorizadas - ARUC DE COLOMBIA</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../img/logo.png">
    <link rel="shortcut icon" href="../img/logo_aruc.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script>
        function toggleEditForm(cedulaId) {
            var form = document.getElementById('edit-form-' + cedulaId);
            form.classList.toggle('active');
        }
    </script>
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
                <a href="admin_index.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="admin_eventos.php">
                    <i class="bi bi-calendar-event"></i> Gestión de Eventos
                </a>
                <a href="afiliaciones.php">
                    <i class="bi bi-people-fill"></i> Afiliaciones
                </a>
                <a href="documentos.php">
                    <i class="bi bi-folder"></i> Documentos
                </a>
                <a href="cedulas.php" class="active">
                    <i class="bi bi-card-checklist"></i> Cédulas Autorizadas
                </a>
                <a href="usuarios.php">
                    <i class="bi bi-person-gear"></i> Usuarios Admin
                </a>
            </div>
        </div>
        
        <div class="content">
            <div class="welcome-user">
                <i class="bi bi-person-check-fill"></i>
                <div class="welcome-text">
                    <strong>Sesión activa:</strong> Has iniciado sesión como <strong><?php echo htmlspecialchars($_SESSION['admin_user']); ?></strong>
                </div>
            </div>
            
            <h2>Gestión de Cédulas Autorizadas</h2>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <h3>Agregar Cédula Autorizada</h3>
                <form method="post">
                    <div class="form-group">
                        <label>Número de Cédula:</label>
                        <input type="text" name="cedula" pattern="[0-9]+" title="Solo números" required>
                    </div>
                    <button type="submit" name="agregar_cedula" class="btn">
                        <i class="bi bi-plus-circle"></i> Agregar Cédula
                    </button>
                </form>
            </div>
            
            <div class="form-container">
                <h3>Cédulas Autorizadas</h3>
                <?php if (count($cedulas) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Cédula</th>
                                <th>Fecha de Autorización</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cedulas as $cedula): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($cedula['cedula']); ?>
                                    <!-- Formulario de edición (oculto por defecto) -->
                                    <div id="edit-form-<?php echo $cedula['id_autorizacion']; ?>" class="edit-form">
                                        <form method="post">
                                            <input type="hidden" name="id_autorizacion" value="<?php echo $cedula['id_autorizacion']; ?>">
                                            <input type="text" name="nueva_cedula" value="<?php echo htmlspecialchars($cedula['cedula']); ?>" pattern="[0-9]+" title="Solo números" required>
                                            <div class="edit-actions">
                                                <button type="submit" name="editar_cedula" class="btn">
                                                    <i class="bi bi-check"></i> Guardar
                                                </button>
                                                <button type="button" class="btn btn-danger" onclick="toggleEditForm(<?php echo $cedula['id_autorizacion']; ?>)">
                                                    <i class="bi bi-x"></i> Cancelar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($cedula['fecha_autorizacion'])); ?></td>
                                <td class="actions">
                                    <button type="button" class="btn btn-edit" onclick="toggleEditForm(<?php echo $cedula['id_autorizacion']; ?>)">
                                        <i class="bi bi-pencil"></i> Editar
                                    </button>
                                    <a href="cedulas.php?eliminar=<?php echo $cedula['id_autorizacion']; ?>" class="btn btn-danger" onclick="return confirm('¿Está seguro de que desea eliminar esta cédula?')">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-message">
                        <i class="bi bi-card-checklist"></i>
                        No hay cédulas autorizadas registradas.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>