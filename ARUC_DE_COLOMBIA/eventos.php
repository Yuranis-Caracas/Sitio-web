<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Asociación Regional de Usuarios Campesinos Del Suroccidente Colombiano (ARUC DE COLOMBIA)</title>
  <link rel="icon" type="image/png" sizes="32x32" href="img/logo.png">
  <link rel="shortcut icon" href="img/logo_aruc.jpeg" type="image/x-icon">


  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <header>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
      <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
          <img src="img/logo_aruc.jpeg" alt="Logo ARUC" class="logo">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuARUC" aria-controls="menuARUC" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="menuARUC">
          <ul class="navbar-nav mb-2 mb-lg-0">
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-people-fill me-1"></i>Quiénes somos</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="quienes_somos.html">Nuestra historia</a></li>
                <li><a class="dropdown-item" href="logros.html">Logros</a></li>
                <li><a class="dropdown-item" href="documentos_aruc/validar_acceso_documentos.php">Documentos Institucionales</a></li>
              </ul>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="reglamentacioncampe.html"><i class="bi bi-file-earmark-text me-1"></i>Reglamentación del Campesinado</a>
            </li>
            <li class="nav-item"><a class="nav-link" href="municipios.html"><i class="bi bi-geo-alt-fill me-1"></i>Municipios</a></li>
            <li class="nav-item"><a class="nav-link" href="proyectos.html"><i class="bi bi-calendar-event me-1"></i>Proyectos</a></li>
            <li class="nav-item"><a class="nav-link" href="eventos.php"><i class="bi bi-calendar-event me-1"></i>Eventos</a></li>
            <li class="nav-item"><a class="nav-link" href="afiliate.html"><i class="bi bi-person-plus-fill me-1"></i>Afíliate</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <div class="breadcrumb-custom debajo-navbar">
    <div class="container">
      <p class="mb-0">
        <a href="index.php">Inicio</a> | <i class="bi bi-eye-fill me-2"></i>Estás viendo: <strong>Eventos</strong>
      </p>
    </div>
  </div>


<section class="container my-5">
    <h2 class="text-center titulo mb-5">Eventos ARUC DE COLOMBIA</h2>
    <div class="row">
        <?php
        include 'admin/includes/database.php';
        
        // Obtener todos los eventos
        $query_eventos = "SELECT * FROM eventos ORDER BY fecha_evento DESC";
        $eventos = $pdo->query($query_eventos)->fetchAll();
        
        if (empty($eventos)) {
    echo '
    <div class="col-md-6 offset-md-3 mb-4">
        <div class="card h-100 shadow-sm border-success text-center">
            <div class="card-body">
                <i class="bi bi-calendar-x text-success display-4 mb-3"></i>
                <h5 class="card-title">Sin eventos disponibles</h5>
                <p class="card-text">
                    Actualmente no hay eventos programados. Vuelve pronto para conocer nuevas actividades.
                </p>
            </div>
        </div>
    </div>';
}
 else {
            foreach ($eventos as $evento) {
                // Obtener TODAS las imágenes de este evento
                $query_imagenes = "SELECT imagen FROM evento_imagenes WHERE id_evento = ?";
                $stmt_imagenes = $pdo->prepare($query_imagenes);
                $stmt_imagenes->execute([$evento['id_evento']]);
                $imagenes = $stmt_imagenes->fetchAll();
                
                // Manejo de las imágenes
                $imagenes_html = '';
                
                if (!empty($imagenes)) {
                    if (count($imagenes) == 1) {
                        // Si solo hay una imagen
                        $imagenes_html = '<img src="uploads/eventos/' . htmlspecialchars($imagenes[0]['imagen']) . '" 
                                          class="card-img-top" 
                                          alt="' . htmlspecialchars($evento['titulo']) . '"
                                          style="height: 250px; object-fit: cover;">';
                    } else {
                        // Si hay múltiples imágenes, usar carrusel con autoplay
                        $imagenes_html = '<div id="carouselEvento' . $evento['id_evento'] . '" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="3000">
                                            <div class="carousel-inner">';
                        
                        foreach ($imagenes as $index => $imagen) {
                            $active_class = $index == 0 ? 'active' : '';
                            $imagenes_html .= '<div class="carousel-item ' . $active_class . '">
                                                <img src="uploads/eventos/' . htmlspecialchars($imagen['imagen']) . '" 
                                                     class="d-block w-100" 
                                                     alt="' . htmlspecialchars($evento['titulo']) . ' - Imagen ' . ($index + 1) . '"
                                                     style="height: 250px; object-fit: cover;">
                                               </div>';
                        }
                        
                        $imagenes_html .= '</div>
                                            <button class="carousel-control-prev" type="button" data-bs-target="#carouselEvento' . $evento['id_evento'] . '" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon"></span>
                                            </button>
                                            <button class="carousel-control-next" type="button" data-bs-target="#carouselEvento' . $evento['id_evento'] . '" data-bs-slide="next">
                                                <span class="carousel-control-next-icon"></span>
                                            </button>
                                          </div>';
                    }
                } else {
                    // Si no hay imágenes
                    $imagenes_html = '<div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                    style="height: 250px;">
                                    <i class="bi bi-images text-muted" style="font-size: 3rem;"></i>
                                    </div>';
                }
                
                // Manejo del botón de enlace
                $boton_html = '';
                if (!empty($evento['enlace'])) {
                    $boton_html = '<a href="' . htmlspecialchars($evento['enlace']) . '" 
                                   target="_blank" 
                                   class="btn btn-success mt-2">
                                   <i class="bi bi-link-45deg me-1"></i>Más información
                                   </a>';
                }
                
                echo '
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="position-relative">
                            ' . $imagenes_html . '
                        </div>
                        <div class="card-body">
                            <h5 class="card-title text-success">' . htmlspecialchars($evento['titulo']) . '</h5>
                            <p class="card-text text-muted">' . htmlspecialchars($evento['descripcion'] ?? '') . '</p>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    Fecha: ' . date('d/m/Y', strtotime($evento['fecha_evento'])) . '
                                </small>
                            </p>
                            ' . $boton_html . '
                        </div>
                    </div>
                </div>';
            }
        }
        ?>
    </div>
</section>

  <footer class="text-center text-white py-4" style="background-color: #1b5e20;">
    <p class="mb-1">© 2025 Asociación Regional de Usuarios Campesinos del Suroccidente Colombiano (ARUC DE COLOMBIA)</p>
    <p class="small">
      <a href="politica_de_privacidad.html" class="text-white text-decoration-underline">Política de Privacidad</a> | 
      <a href="terminos_condiciones.html" class="text-white text-decoration-underline">Términos y Condiciones</a>
    </p>
    <div>
      <a href="https://www.facebook.com/share/14SqWHbb2Cb/" class="text-white me-3" target="_blank"><i class="bi bi-facebook"></i></a>
      <a href="https://www.instagram.com/aruccolombia?igsh=MWo0NTBidzJrdDEwdg==" class="text-white me-3" target="_blank"><i class="bi bi-instagram"></i></a>
      <a href="mailto:cooperativaaruc@gmail.com" class="text-white"><i class="bi bi-envelope"></i></a>
    </div>
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    window.addEventListener('scroll', function() {
      const navbar = document.querySelector('.navbar-custom');
      if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
  </script>

</body>
</html>
