<?php 
session_start(); 
include '../admin/includes/database.php'; 

// Verificar si la cédula está validada en la sesión
if (!isset($_SESSION['cedula_validada']) || $_SESSION['cedula_validada'] !== true) {
    header('Location: validar_acceso_documentos.php');
    exit;
}

// Cerrar sesión de documentos
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../index.php');
    exit;
}

// Obtener todos los documentos
try {
    $stmt = $pdo->query("SELECT * FROM documentos_aruc ORDER BY fecha_subida DESC");
    $documentos = $stmt->fetchAll();
} catch (Exception $e) {
    $documentos = [];
    $error = "Error al cargar los documentos";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos Institucionales - ARUC DE COLOMBIA</title>
    <link rel="shortcut icon" href="../img/logo_aruc.jpeg" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <!-- PDF.js para visualizar PDFs -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf_viewer.min.css">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="../css/admin_style.css">
    <link rel="stylesheet" href="../css/ver_documentos.css">
    
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <img src="../img/logo_aruc.jpeg" alt="Logo ARUC" class="logo-header">
            <div class="header-text">
                <h1>Documentos Institucionales ARUC DE COLOMBIA</h1>
                <div>Acceso Autorizado</div>
            </div>
        </div>
        <a href="?logout=1" class="btn-logout">
            <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
        </a>
    </div>

    <!-- Contenido Principal -->
    <div class="main-container">
        <!-- Información del usuario -->
        <div class="user-info">
            <i class="bi bi-person-check"></i>
            <div class="welcome-text">
                <h5 class="mb-1">Acceso autorizado</h5>
                <p class="mb-0">
                    Cédula: <strong><?php echo htmlspecialchars($_SESSION['cedula_numero']); ?></strong>
                </p>
            </div>
            <div class="access-info">
                <i class="bi bi-clock"></i>
                <span>Acceso válido durante esta sesión</span>
            </div>
        </div>

        <!-- Contenido -->
        <div class="content">
            <!-- Lista de documentos -->
            <?php if (!empty($documentos)): ?>
                <div class="custom-alert">
                    <i class="bi bi-info-circle me-2"></i>
                    Solo puedes <strong>visualizar</strong> los documentos. La descarga está deshabilitada.
                </div>
                
                <div class="row g-4">
                    <?php foreach ($documentos as $documento): 
                        // Verificar si el archivo existe
                        $ruta_archivo = '../uploads/documentos/' . $documento['archivo_pdf'];
                        $archivo_existe = file_exists($ruta_archivo);
                    ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="document-card">
                                <div>
                                    <div class="document-icon">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </div>
                                    <h5><?php echo htmlspecialchars($documento['nombre_documento']); ?></h5>
                                    <div class="document-meta">
                                        <i class="bi bi-calendar"></i>
                                        <span><?php echo date('d/m/Y', strtotime($documento['fecha_subida'])); ?></span>
                                    </div>
                                    <?php if ($archivo_existe): 
                                        $tamano = filesize($ruta_archivo);
                                        $tamano_mb = round($tamano / 1048576, 2);
                                    ?>
                                        <div class="document-size">
                                            <i class="bi bi-hdd"></i>
                                            <span>Tamaño: <?php echo $tamano_mb; ?> MB</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="document-size text-danger">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            <span>Archivo no disponible</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($archivo_existe): ?>
                                    <button type="button" class="btn btn-view" 
                                            onclick="cargarPDF('<?php echo $ruta_archivo; ?>', '<?php echo htmlspecialchars($documento['nombre_documento']); ?>')">
                                        <i class="bi bi-eye me-2"></i> VER DOCUMENTO
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-secondary" disabled>
                                        <i class="bi bi-eye-slash me-2"></i> NO DISPONIBLE
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-docs">
                    <div class="empty-icon">
                        <i class="bi bi-folder-x"></i>
                    </div>
                    <h4 class="mb-3">No hay documentos disponibles</h4>
                    <p class="text-muted">Actualmente no hay documentos institucionales para mostrar.</p>
                    <a href="validar_acceso_documentos.php" class="btn btn-view mt-3" style="width: auto; display: inline-flex;">
                        <i class="bi bi-arrow-left me-2"></i> Volver a validar
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para visualizar PDF -->
    <div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfModalLabel">
                        <i class="bi bi-file-earmark-pdf me-2"></i> 
                        <span id="pdfTitle">Visualizando documento</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="pdfContainer">
                        <div class="loading-pdf" id="loadingPDF">
                            <div class="loading-spinner"></div>
                            <p>Cargando documento...</p>
                        </div>
                        <div class="pdf-controls" id="pdfControls" style="display: none;">
                            <div class="page-navigation">
                                <button id="prevPage" disabled>Anterior</button>
                                <span>Página <span id="currentPage">1</span> de <span id="totalPages">1</span></span>
                                <button id="nextPage" disabled>Siguiente</button>
                            </div>
                        </div>
                        <canvas id="pdfCanvas"></canvas>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="text-muted small">
                        <i class="bi bi-exclamation-circle me-1"></i> 
                        La descarga de este documento está restringida
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Variables globales para el PDF
        let pdfDoc = null;
        let pageNum = 1;
        let pageRendering = false;
        let pageNumPending = null;
        const scale = 1.5;

        // Función para cargar y mostrar el PDF
        function cargarPDF(rutaArchivo, nombreDocumento) {
            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('pdfModal'));
            document.getElementById('pdfTitle').textContent = nombreDocumento;
            
            // Mostrar loading
            document.getElementById('loadingPDF').style.display = 'flex';
            document.getElementById('pdfControls').style.display = 'none';
            document.getElementById('pdfCanvas').style.display = 'none';
            
            // Inicializar PDF.js
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            
            // Cargar el PDF
            pdfjsLib.getDocument(rutaArchivo).promise.then(function(pdf) {
                pdfDoc = pdf;
                
                // Ocultar loading
                document.getElementById('loadingPDF').style.display = 'none';
                document.getElementById('pdfControls').style.display = 'flex';
                document.getElementById('pdfCanvas').style.display = 'block';
                
                // Actualizar controles
                document.getElementById('totalPages').textContent = pdfDoc.numPages;
                
                // Renderizar primera página
                renderPage(pageNum);
                
                // Habilitar/deshabilitar botones
                document.getElementById('prevPage').disabled = pageNum <= 1;
                document.getElementById('nextPage').disabled = pageNum >= pdfDoc.numPages;
                
                // Mostrar el modal
                modal.show();
            }).catch(function(error) {
                console.error('Error al cargar el PDF:', error);
                document.getElementById('loadingPDF').innerHTML = 
                    '<div class="text-danger">' +
                    '<i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>' +
                    '<p>Error al cargar el documento</p>' +
                    '</div>';
            });
        }

        // Función para renderizar una página
        function renderPage(num) {
            pageRendering = true;
            
            pdfDoc.getPage(num).then(function(page) {
                const canvas = document.getElementById('pdfCanvas');
                const ctx = canvas.getContext('2d');
                const viewport = page.getViewport({ scale: scale });
                
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                
                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                
                const renderTask = page.render(renderContext);
                
                renderTask.promise.then(function() {
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });
                
                document.getElementById('currentPage').textContent = num;
            });
        }

        // Función para ir a la página anterior
        document.getElementById('prevPage').addEventListener('click', function() {
            if (pageNum <= 1) return;
            pageNum--;
            queueRenderPage(pageNum);
        });

        // Función para ir a la página siguiente
        document.getElementById('nextPage').addEventListener('click', function() {
            if (pageNum >= pdfDoc.numPages) return;
            pageNum++;
            queueRenderPage(pageNum);
        });

        // Función para encolar la renderización de página
        function queueRenderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
            
            // Actualizar estado de botones
            document.getElementById('prevPage').disabled = num <= 1;
            document.getElementById('nextPage').disabled = num >= pdfDoc.numPages;
        }

        // Limpiar cuando se cierra el modal
        document.getElementById('pdfModal').addEventListener('hidden.bs.modal', function() {
            pdfDoc = null;
            pageNum = 1;
        });

        // Prevenir clic derecho y descarga
        document.addEventListener('contextmenu', function(e) {
            if (e.target.closest('#pdfCanvas')) {
                e.preventDefault();
                alert('La descarga de documentos está deshabilitada');
            }
        });

        // Prevenir arrastrar
        document.addEventListener('dragstart', function(e) {
            if (e.target.closest('#pdfCanvas')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>