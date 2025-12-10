<?php
include 'includes/auth.php';
include 'includes/database.php';

// Verificar autenticación
if (!isset($_SESSION['admin_name'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: admin_eventos.php');
    exit;
}

// Obtener el evento actual
$stmt = $pdo->prepare("SELECT * FROM eventos WHERE id_evento = ?");
$stmt->execute([$id]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
    header('Location: admin_eventos.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $fecha_evento = $_POST['fecha_evento'];
    $enlace = $_POST['enlace'];
    $destacado = isset($_POST['destacado']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE eventos SET titulo = ?, descripcion = ?, fecha_evento = ?, enlace = ?, destacado = ? WHERE id_evento = ?");
    $stmt->execute([$titulo, $descripcion, $fecha_evento, $enlace, $destacado, $id]);

    $success = "Evento actualizado exitosamente";
    
    // Recargar los datos del evento
    $stmt = $pdo->prepare("SELECT * FROM eventos WHERE id_evento = ?");
    $stmt->execute([$id]);
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Evento - ARUC DE COLOMBIA</title>
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
                <div>Bienvenido(a), <?php echo $_SESSION['admin_name']; ?></div>
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
                <a href="cedulas.php">
                    <i class="bi bi-card-checklist"></i> Cédulas Autorizadas
                </a>
                <a href="usuarios.php">
                    <i class="bi bi-person-gear"></i> Usuarios Admin
                </a>
            </div>
        </div>
        
        <div class="content">
            <h2>Editar Evento</h2>
            
            <?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>
            
            <div class="form-container">
                <form method="post">
                    <div class="form-group">
                        <label for="titulo">Título:</label>
                        <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($evento['titulo']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción:</label>
                        <textarea id="descripcion" name="descripcion" rows="4" required><?php echo htmlspecialchars($evento['descripcion']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_evento">Fecha del Evento:</label>
                        <input type="date" id="fecha_evento" name="fecha_evento" value="<?php echo htmlspecialchars($evento['fecha_evento']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="enlace">Enlace (opcional):</label>
                        <input type="url" id="enlace" name="enlace" value="<?php echo htmlspecialchars($evento['enlace']); ?>" placeholder="https://ejemplo.com">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="destacado" name="destacado" value="1" <?php echo $evento['destacado'] ? 'checked' : ''; ?>>
                        <label for="destacado" style="margin: 0;">Marcar como evento destacado</label>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn">
                            <i class="bi bi-check-circle"></i> Actualizar Evento
                        </button>
                        <a href="admin_eventos.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>