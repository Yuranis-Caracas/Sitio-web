<?php
include 'includes/auth.php';
include 'includes/database.php';

// Verificar autenticación
if (!isset($_SESSION['admin_name'])) {
    header('Location: login.php');
    exit;
}

// Procesar cambio de estado si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $id_afiliacion = $_POST['id_afiliacion'];
    $nuevo_estado = $_POST['nuevo_estado'];
    
    $stmt = $pdo->prepare("UPDATE afiliaciones SET estado_afiliacion = ? WHERE id_afiliacion = ?");
    $stmt->execute([$nuevo_estado, $id_afiliacion]);
    
    // Redirigir para evitar reenvío del formulario
    header('Location: afiliaciones.php' . (isset($_GET['id']) ? '?id=' . $_GET['id'] : ''));
    exit;
}

// Verificar si se está viendo un detalle específico
$ver_detalle = isset($_GET['id']);
$detalle_id = $ver_detalle ? $_GET['id'] : 0;

// Si estamos viendo detalles, obtener esa afiliación específica
if ($ver_detalle && $detalle_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM afiliaciones WHERE id_afiliacion = ?");
    $stmt->execute([$detalle_id]);
    $afiliacion_detalle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener información relacionada
    if ($afiliacion_detalle) {
        // Grupo familiar
        $stmtFam = $pdo->prepare("SELECT * FROM grupo_familiar WHERE id_afiliacion = ?");
        $stmtFam->execute([$detalle_id]);
        $familiares = $stmtFam->fetchAll(PDO::FETCH_ASSOC);
        
        // Actividades agrícolas
        $stmtAgri = $pdo->prepare("SELECT * FROM actividades_agricolas WHERE id_afiliacion = ?");
        $stmtAgri->execute([$detalle_id]);
        $agricolas = $stmtAgri->fetchAll(PDO::FETCH_ASSOC);
        
        // Actividades pecuarias
        $stmtPecu = $pdo->prepare("SELECT * FROM actividades_pecuarias WHERE id_afiliacion = ?");
        $stmtPecu->execute([$detalle_id]);
        $pecuarias = $stmtPecu->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Leer todas las afiliaciones para la vista de lista
$stmt = $pdo->query("SELECT * FROM afiliaciones ORDER BY fecha_registro DESC");
$afiliaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Afiliaciones - ARUC DE COLOMBIA</title>
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
                <a href="afiliaciones.php" class="active">
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
            <?php if ($ver_detalle && $detalle_id > 0 && $afiliacion_detalle): ?>
                <!-- VISTA DE DETALLES COMPLETOS -->
                <div class="detalle-header">
                    <h2>Detalles Completos de la Afiliación #<?php echo $afiliacion_detalle['id_afiliacion']; ?></h2>
                    <a href="afiliaciones.php" class="btn-volver">
                        <i class="bi bi-arrow-left"></i> Volver a la lista
                    </a>
                </div>
                
                <!-- Información personal -->
                <div class="detalle-section">
                    <h3><i class="bi bi-person-badge"></i> Información Personal</h3>
                    <div class="detalle-grid">
                        <div class="detalle-item">
                            <span class="detalle-label">Cédula</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['cedula']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Primer Apellido</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['primer_apellido']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Segundo Apellido</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['segundo_apellido']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Nombres</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['nombres']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Fecha Expedición Cédula</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['fecha_expedicion']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Lugar Expedición</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['lugar_expedicion']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Edad</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['edad']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Ocupación</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['ocupacion']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Organización</span>
                            <div class="detalle-value">
                                <?php 
                                echo htmlspecialchars($afiliacion_detalle['organizacion']);
                                if (!empty($afiliacion_detalle['otra_organizacion'])) {
                                    echo ' - ' . htmlspecialchars($afiliacion_detalle['otra_organizacion']);
                                }
                                ?>
                            </div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Fecha Registro</span>
                            <div class="detalle-value"><?php echo date('d/m/Y H:i', strtotime($afiliacion_detalle['fecha_registro'])); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Información de ubicación -->
                <div class="detalle-section">
                    <h3><i class="bi bi-geo-alt"></i> Ubicación y Contacto</h3>
                    <div class="detalle-grid">
                        <div class="detalle-item">
                            <span class="detalle-label">Departamento</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['departamento']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Municipio</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['municipio']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Dirección</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['direccion']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">EPS</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['eps']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Grupo Sisbén</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['grupo_sisben']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Se Identifica como Campesino</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['campesino']); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Información del predio -->
                <?php if ($afiliacion_detalle['predio'] == 'Si'): ?>
                <div class="detalle-section">
                    <h3><i class="bi bi-house"></i> Información del Predio</h3>
                    <div class="detalle-grid">
                        <div class="detalle-item">
                            <span class="detalle-label">Predio</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['predio']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Nombre del Predio</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['nombre_predio']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Titularidad</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['titularidad']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Sana Posesión con Certificado de Tradición</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['sana_posesion_cer_tradicion']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Área del Predio</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['area_predio']); ?></div>
                        </div>
                        <div class="detalle-item">
                            <span class="detalle-label">Ubicación del Predio</span>
                            <div class="detalle-value"><?php echo htmlspecialchars($afiliacion_detalle['ubicacion_predio']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Grupo familiar -->
                <?php if (count($familiares) > 0): ?>
                <div class="detalle-section">
                    <h3><i class="bi bi-people"></i> Grupo Familiar (<?php echo count($familiares); ?> miembros)</h3>
                    <table class="sub-table">
                        <thead>
                            <tr>
                                <th>Nombre Completo</th>
                                <th>Edad</th>
                                <th>Documento</th>
                                <th>Número</th>
                                <th>Parentesco</th>
                                <th>Escolaridad</th>
                                <th>Otra Organización</th>
                                <th>Cuál Organización</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($familiares as $familiar): ?>
                            <tr>
                                <td>
                                    <?php 
                                    echo htmlspecialchars(
                                        $familiar['grupo_primer_apellido'] . ' ' . 
                                        $familiar['grupo_segundo_apellido'] . ' ' . 
                                        $familiar['grupo_nombres']
                                    ); 
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($familiar['grupo_edad']); ?></td>
                                <td><?php echo htmlspecialchars($familiar['grupo_documento']); ?></td>
                                <td><?php echo htmlspecialchars($familiar['grupo_num_documento']); ?></td>
                                <td><?php echo htmlspecialchars($familiar['grupo_parentesco']); ?></td>
                                <td><?php echo htmlspecialchars($familiar['grupo_escolaridad']); ?></td>
                                <td><?php echo htmlspecialchars($familiar['grupo_otra_organizacion']); ?></td>
                                <td><?php echo htmlspecialchars($familiar['grupo_cual_org']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="detalle-section">
                    <h3><i class="bi bi-people"></i> Grupo Familiar</h3>
                    <div class="no-data">No hay miembros registrados en el grupo familiar</div>
                </div>
                <?php endif; ?>
                
                <!-- Actividades agrícolas -->
                <?php if (count($agricolas) > 0): ?>
                <div class="detalle-section">
                    <h3><i class="bi bi-tree"></i> Actividades Agrícolas (<?php echo count($agricolas); ?> productos)</h3>
                    <table class="sub-table">
                        <thead>
                            <tr>
                                <th>Producto Agrícola</th>
                                <th>Variedad</th>
                                <th>Antigüedad</th>
                                <th>Periodo Producción</th>
                                <th>Área Cultivo</th>
                                <th>Cantidad Producción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agricolas as $agricola): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agricola['producto_agricola']); ?></td>
                                <td><?php echo htmlspecialchars($agricola['variedad']); ?></td>
                                <td><?php echo htmlspecialchars($agricola['antiguedad']); ?></td>
                                <td><?php echo htmlspecialchars($agricola['periodo_produccion']); ?></td>
                                <td><?php echo htmlspecialchars($agricola['area_cultivo']); ?></td>
                                <td><?php echo htmlspecialchars($agricola['cantidad_produccion']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="detalle-section">
                    <h3><i class="bi bi-tree"></i> Actividades Agrícolas</h3>
                    <div class="no-data">No hay actividades agrícolas registradas</div>
                </div>
                <?php endif; ?>
                
                <!-- Actividades pecuarias -->
                <?php if (count($pecuarias) > 0): ?>
                <div class="detalle-section">
                    <h3><i class="bi bi-bug"></i> Actividades Pecuarias (<?php echo count($pecuarias); ?> tipos)</h3>
                    <table class="sub-table">
                        <thead>
                            <tr>
                                <th>Producto Pecuaria</th>
                                <th>Cantidad de Animales</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pecuarias as $pecuaria): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pecuaria['producto_pecuaria']); ?></td>
                                <td><?php echo htmlspecialchars($pecuaria['cantidad_animales']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="detalle-section">
                    <h3><i class="bi bi-bug"></i> Actividades Pecuarias</h3>
                    <div class="no-data">No hay actividades pecuarias registradas</div>
                </div>
                <?php endif; ?>
                
                <!-- Estado y documentos -->
                <div class="detalle-section">
                    <h3><i class="bi bi-clipboard-check"></i> Estado y Documentos</h3>
                    <div class="detalle-grid">
                        <div class="detalle-item">
                            <span class="detalle-label">Estado Actual</span>
                            <div class="detalle-value">
                                <?php
                                $estadoClass = $afiliacion_detalle['estado_afiliacion'] === 'Completada' ? 'badge-completada' : 'badge-pendiente';
                                ?>
                                <span class="<?php echo $estadoClass; ?>">
                                    <?php echo htmlspecialchars($afiliacion_detalle['estado_afiliacion']); ?>
                                </span>
                                <form method="post" class="estado-form">
                                    <input type="hidden" name="id_afiliacion" value="<?php echo $afiliacion_detalle['id_afiliacion']; ?>">
                                    <select name="nuevo_estado" class="estado-select">
                                        <option value="Pendiente" <?php echo $afiliacion_detalle['estado_afiliacion'] === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="Completada" <?php echo $afiliacion_detalle['estado_afiliacion'] === 'Completada' ? 'selected' : ''; ?>>Completada</option>
                                    </select>
                                    <button type="submit" name="cambiar_estado" class="btn-actualizar">
                                        <i class="bi bi-check-lg"></i> Actualizar
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="detalle-item">
                            <span class="detalle-label">Documentos Adjuntos</span>
                            <div class="detalle-value documentos-links">
                                <?php if ($afiliacion_detalle['foto']): ?>
                                <a href="../uploads/fotos/<?php echo htmlspecialchars($afiliacion_detalle['foto']); ?>" target="_blank">
                                    <i class="bi bi-image"></i> Foto
                                </a>
                                <?php endif; ?>
                                <?php if ($afiliacion_detalle['copia_cedula']): ?>
                                <a href="../uploads/cedulas/<?php echo htmlspecialchars($afiliacion_detalle['copia_cedula']); ?>" target="_blank">
                                    <i class="bi bi-file-pdf"></i> Cédula
                                </a>
                                <?php endif; ?>
                                <?php if (!$afiliacion_detalle['foto'] && !$afiliacion_detalle['copia_cedula']): ?>
                                <span style="color: #999;">No hay documentos adjuntos</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- VISTA DE LISTADO DE AFILIACIONES -->
                <h2>Gestión de Afiliaciones</h2>
                
                <!-- Botón de exportar Excel -->
                <div style="margin-bottom: 20px;">
                    <a href="afiliaciones_exportar.php" class="btn-export-excel">
                        <i class="bi bi-download"></i> Exportar a Excel
                    </a>
                </div>
                
                <div class="form-container">
                    <h3 style="color: #1b5e20; margin-bottom: 20px;">Lista de Afiliaciones</h3>
                    
                    <?php if (isset($_GET['export_success'])): ?>
                        <div class="success">Archivo Excel exportado exitosamente</div>
                    <?php endif; ?>
                    
                    <?php if (empty($afiliaciones)): ?>
                        <div class="info-message">
                            <i class="bi bi-info-circle"></i> No hay afiliaciones registradas.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID AFILIACIÓN</th>
                                        <th>Fecha Registro</th>
                                        <th>Nombres Completos</th>
                                        <th>Cédula</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($afiliaciones as $afiliacion): ?>
                                    <tr>
                                        <td><?php echo $afiliacion['id_afiliacion']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($afiliacion['fecha_registro'])); ?></td>
                                        <td>
                                            <?php 
                                            $nombreCompleto = trim(
                                                $afiliacion['primer_apellido'] . ' ' . 
                                                $afiliacion['segundo_apellido'] . ' ' . 
                                                $afiliacion['nombres']
                                            );
                                            echo htmlspecialchars($nombreCompleto); 
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($afiliacion['cedula']); ?></td>
                                        <td>
                                            <?php 
                                            $estado = $afiliacion['estado_afiliacion'];
                                            $badgeClass = $estado === 'Completada' ? 'badge-completada' : 'badge-pendiente';
                                            ?>
                                            <span class="<?php echo $badgeClass; ?>">
                                                <?php echo $estado; ?>
                                            </span>
                                            <form method="post" class="estado-form-lista">
                                                <input type="hidden" name="id_afiliacion" value="<?php echo $afiliacion['id_afiliacion']; ?>">
                                                <select name="nuevo_estado" class="estado-select-lista">
                                                    <option value="Pendiente" <?php echo $estado === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                                    <option value="Completada" <?php echo $estado === 'Completada' ? 'selected' : ''; ?>>Completada</option>
                                                </select>
                                                <button type="submit" name="cambiar_estado" class="btn-actualizar-lista" title="Actualizar estado">
                                                    ✓
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px; flex-wrap: nowrap;">
                                                <a href="afiliaciones_pdf.php?id=<?php echo $afiliacion['id_afiliacion']; ?>" class="btn-accion btn-pdf" target="_blank" title="Generar PDF">
                                                    <i class="bi bi-file-pdf"></i> PDF
                                                </a>
                                                <a href="afiliaciones.php?id=<?php echo $afiliacion['id_afiliacion']; ?>" class="btn-accion btn-ver" title="Ver todos los detalles">
                                                    <i class="bi bi-eye"></i> Ver todo
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>