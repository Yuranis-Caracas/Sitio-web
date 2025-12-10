<?php 
include 'includes/auth.php';
include 'includes/database.php';

// Crear evento
if ($_POST && isset($_POST['crear_evento'])) {
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $fecha_evento = $_POST['fecha_evento'];
    $enlace = $_POST['enlace'];
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    
    // Insertar evento principal
    $stmt = $pdo->prepare("INSERT INTO eventos (titulo, descripcion, fecha_evento, enlace, destacado) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$titulo, $descripcion, $fecha_evento, $enlace, $destacado]);
    $id_evento = $pdo->lastInsertId();
    
    // Manejar múltiples imágenes
    $imagenes_subidas = 0;
    if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
        $uploadDir = '../uploads/eventos/';
        
        // Crear el directorio si no existe con permisos completos
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                die("Error: No se pudo crear el directorio $uploadDir");
            }
        }
        
        // Verificar permisos del directorio
        if (!is_writable($uploadDir)) {
            die("Error: El directorio $uploadDir no tiene permisos de escritura");
        }
        
        $total_imagenes = count($_FILES['imagenes']['name']);
        
        for ($i = 0; $i < $total_imagenes; $i++) {
            if ($_FILES['imagenes']['error'][$i] == 0) {
                // Validar que sea una imagen
                $tipo_permitido = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $tipo_real = mime_content_type($_FILES['imagenes']['tmp_name'][$i]);
                
                if (in_array($tipo_real, $tipo_permitido)) {
                    // Generar nombre único
                    $imagen_nombre = 'evento_' . $id_evento . '_' . time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $_FILES['imagenes']['name'][$i]);
                    $ruta_imagen = $uploadDir . $imagen_nombre;
                    
                    if (move_uploaded_file($_FILES['imagenes']['tmp_name'][$i], $ruta_imagen)) {
                        // Insertar cada imagen en la tabla evento_imagenes
                        $stmt_imagen = $pdo->prepare("INSERT INTO evento_imagenes (id_evento, imagen) VALUES (?, ?)");
                        $stmt_imagen->execute([$id_evento, $imagen_nombre]);
                        $imagenes_subidas++;
                    } else {
                        error_log("Error al mover la imagen: " . $_FILES['imagenes']['name'][$i]);
                    }
                } else {
                    error_log("Tipo de archivo no permitido: " . $tipo_real);
                }
            } else {
                error_log("Error en archivo " . $_FILES['imagenes']['name'][$i] . ": " . $_FILES['imagenes']['error'][$i]);
            }
        }
    }
    
    if ($imagenes_subidas > 0) {
        $success = "Evento creado exitosamente con " . $imagenes_subidas . " imagen(es)";
    } else {
        $success = "Evento creado exitosamente (sin imágenes)";
    }
}

// Eliminar evento
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    
    // Primero, obtener las imágenes asociadas para eliminarlas del servidor
    $stmt_imagenes = $pdo->prepare("SELECT imagen FROM evento_imagenes WHERE id_evento = ?");
    $stmt_imagenes->execute([$id]);
    $imagenes = $stmt_imagenes->fetchAll();
    
    // Eliminar las imágenes físicas
    foreach ($imagenes as $imagen) {
        $ruta_imagen = '../uploads/eventos/' . $imagen['imagen'];
        if (file_exists($ruta_imagen)) {
            unlink($ruta_imagen);
        }
    }
    
    // Eliminar las entradas de las imágenes en la base de datos
    $stmt_eliminar_imagenes = $pdo->prepare("DELETE FROM evento_imagenes WHERE id_evento = ?");
    $stmt_eliminar_imagenes->execute([$id]);
    
    // Finalmente, eliminar el evento
    $stmt = $pdo->prepare("DELETE FROM eventos WHERE id_evento = ?");
    $stmt->execute([$id]);
    $success = "Evento eliminado exitosamente";
}

// Obtener eventos con conteo de imágenes
$eventos = $pdo->query("
    SELECT e.*, COUNT(ei.id_imagen) as total_imagenes 
    FROM eventos e 
    LEFT JOIN evento_imagenes ei ON e.id_evento = ei.id_evento 
    GROUP BY e.id_evento 
    ORDER BY e.fecha_evento DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gestión de Eventos - ARUC DE COLOMBIA</title>
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
            <h2>Gestión de Eventos</h2>
            
            <?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>
            
            <div class="form-container">
                <h3 style="color: #1b5e20; margin-bottom: 20px;">Crear Nuevo Evento</h3>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="titulo">Título:</label>
                        <input type="text" id="titulo" name="titulo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción:</label>
                        <textarea id="descripcion" name="descripcion" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_evento">Fecha del Evento:</label>
                        <input type="date" id="fecha_evento" name="fecha_evento" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="imagenes">Imágenes (puede seleccionar múltiples):</label>
                        <input type="file" id="imagenes" name="imagenes[]" accept="image/*" multiple>
                        <div class="file-input-info">
                            <i class="bi bi-info-circle"></i> Puede seleccionar múltiples imágenes manteniendo presionada la tecla Ctrl (Windows) o Cmd (Mac)
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="enlace">Enlace (opcional):</label>
                        <input type="url" id="enlace" name="enlace" placeholder="https://ejemplo.com">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="destacado" name="destacado" value="1">
                        <label for="destacado" style="margin: 0;">Marcar como evento destacado</label>
                    </div>
                    
                    <button type="submit" name="crear_evento" class="btn">
                        <i class="bi bi-plus-circle"></i> Crear Evento
                    </button>
                </form>
            </div>
            
            <div class="form-container">
                <h3 style="color: #1b5e20; margin-bottom: 20px;">Lista de Eventos</h3>
                
                <?php if (empty($eventos)): ?>
                    <p>No hay eventos creados aún.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Fecha</th>
                                <th>Destacado</th>
                                <th>Imágenes</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventos as $evento): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($evento['titulo']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($evento['fecha_evento'])); ?></td>
                                <td>
                                    <?php if ($evento['destacado']): ?>
                                        <span class="badge badge-destacado">Destacado</span>
                                    <?php else: ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($evento['total_imagenes'] > 0): ?>
                                        <span class="badge badge-imagenes">
                                            <i class="bi bi-image"></i> <?php echo $evento['total_imagenes']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="display: flex; gap: 8px;">
                                    <a href="eventos_editar.php?id=<?php echo $evento['id_evento']; ?>" class="btn btn-edit">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>
                                    <a href="eventos_imagenes.php?id=<?php echo $evento['id_evento']; ?>" class="btn btn-images">
                                        <i class="bi bi-images"></i> Imágenes
                                    </a>
                                    <a href="admin_eventos.php?eliminar=<?php echo $evento['id_evento']; ?>" class="btn btn-danger" onclick="return confirm('¿Está seguro de que desea eliminar este evento? Se eliminarán todas las imágenes asociadas.')">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>