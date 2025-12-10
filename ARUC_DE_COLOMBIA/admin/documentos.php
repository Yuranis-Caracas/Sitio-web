<?php
include 'includes/auth.php';
include 'includes/database.php';

// Subir documento
if ($_POST && isset($_POST['subir_documento'])) {
    $nombre_documento = $_POST['nombre_documento'];
    
    if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] == 0) {
        // Verificar si la carpeta de uploads existe, si no crearla
        $upload_dir = '../uploads/documentos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $archivo_pdf = 'doc_' . time() . '_' . $_FILES['archivo_pdf']['name'];
        move_uploaded_file($_FILES['archivo_pdf']['tmp_name'], $upload_dir . $archivo_pdf);
        
        $stmt = $pdo->prepare("INSERT INTO documentos_aruc (nombre_documento, archivo_pdf) VALUES (?, ?)");
        $stmt->execute([$nombre_documento, $archivo_pdf]);
        $success = "Documento subido exitosamente";
    }
}

// Editar nombre del documento
if ($_POST && isset($_POST['editar_documento'])) {
    $id_documento = $_POST['id_documento'];
    $nuevo_nombre = $_POST['nuevo_nombre'];
    
    $stmt = $pdo->prepare("UPDATE documentos_aruc SET nombre_documento = ? WHERE id_documento = ?");
    $stmt->execute([$nuevo_nombre, $id_documento]);
    $success = "Documento actualizado exitosamente";
}

// Eliminar documento
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    
    // Primero obtener el nombre del archivo para eliminarlo del servidor
    $stmt = $pdo->prepare("SELECT archivo_pdf FROM documentos_aruc WHERE id_documento = ?");
    $stmt->execute([$id]);
    $documento = $stmt->fetch();
    
    if ($documento) {
        $archivo_path = '../uploads/documentos/' . $documento['archivo_pdf'];
        if (file_exists($archivo_path)) {
            unlink($archivo_path);
        }
        
        $stmt = $pdo->prepare("DELETE FROM documentos_aruc WHERE id_documento = ?");
        $stmt->execute([$id]);
        $success = "Documento eliminado exitosamente";
    }
}

$documentos = $pdo->query("SELECT * FROM documentos_aruc ORDER BY fecha_subida DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos Institucionales - ARUC DE COLOMBIA</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../img/logo.png">
    <link rel="shortcut icon" href="../img/logo_aruc.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script>
        function toggleEditForm(documentId) {
            var form = document.getElementById('edit-form-' + documentId);
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
                <a href="documentos.php" class="active">
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
            <div class="welcome-user">
                <i class="bi bi-person-check-fill"></i>
                <div class="welcome-text">
                    <strong>Sesión activa:</strong> Has iniciado sesión como <strong><?php echo htmlspecialchars($_SESSION['admin_user']); ?></strong>
                </div>
            </div>
            
            <h2>Documentos Institucionales</h2>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <h3>Subir Nuevo Documento</h3>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Nombre del Documento:</label>
                        <input type="text" name="nombre_documento" required>
                    </div>
                    <div class="form-group">
                        <label>Archivo PDF:</label>
                        <input type="file" name="archivo_pdf" accept=".pdf" required>
                        <div class="file-input-info">Formatos aceptados: PDF. Tamaño máximo: 10MB</div>
                    </div>
                    <button type="submit" name="subir_documento" class="btn">
                        <i class="bi bi-upload"></i> Subir Documento
                    </button>
                </form>
            </div>
            
            <div class="form-container">
                <h3>Lista de Documentos</h3>
                <?php if (count($documentos) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Fecha de Subida</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documentos as $doc): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($doc['nombre_documento']); ?>
                                    <!-- Formulario de edición (oculto por defecto) -->
                                    <div id="edit-form-<?php echo $doc['id_documento']; ?>" class="edit-form">
                                        <form method="post">
                                            <input type="hidden" name="id_documento" value="<?php echo $doc['id_documento']; ?>">
                                            <input type="text" name="nuevo_nombre" value="<?php echo htmlspecialchars($doc['nombre_documento']); ?>" required>
                                            <div class="edit-actions">
                                                <button type="submit" name="editar_documento" class="btn">
                                                    <i class="bi bi-check"></i> Guardar
                                                </button>
                                                <button type="button" class="btn btn-danger" onclick="toggleEditForm(<?php echo $doc['id_documento']; ?>)">
                                                    <i class="bi bi-x"></i> Cancelar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($doc['fecha_subida'])); ?></td>
                                <td class="actions">
                                    <a href="../uploads/documentos/<?php echo $doc['archivo_pdf']; ?>" target="_blank" class="btn btn-view">
                                        <i class="bi bi-eye"></i> Ver
                                    </a>
                                    <button type="button" class="btn btn-edit" onclick="toggleEditForm(<?php echo $doc['id_documento']; ?>)">
                                        <i class="bi bi-pencil"></i> Editar
                                    </button>
                                    <a href="documentos.php?eliminar=<?php echo $doc['id_documento']; ?>" class="btn btn-danger" onclick="return confirm('¿Está seguro de que desea eliminar este documento?')">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-message">
                        <i class="bi bi-folder-x"></i>
                        No hay documentos subidos
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>