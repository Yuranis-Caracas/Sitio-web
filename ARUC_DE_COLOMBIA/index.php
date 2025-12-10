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
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
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

  <!-- Carrusel -->
  <section id="inicio">
    <div id="carouselARUC" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-inner">
        <div class="carousel-item active">
          <img src="img/banner1.jpeg" class="d-block w-100" alt="ARUC Banner 1">
        </div>
        <div class="carousel-item">
          <img src="img/banner2.jpeg" class="d-block w-100" alt="ARUC Banner 2">
        </div>
        <div class="carousel-item">
          <img src="img/banner3.jpeg" class="d-block w-100" alt="ARUC Banner 3">
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#carouselARUC" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#carouselARUC" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
      </button>
    </div>
  </section>

  <section class="py-5 bg-light">
    <div class="container">
      <div class="row">
        <div class="col-12">
          <h2 class="mb-3 titulo text-center">Bienvenidos a ARUC DE COLOMBIA</h2>
          <p class="lead text-center">
            Somos la Asociación Regional de Usuarios Campesinos del Suroccidente Colombiano, 
            trabajando por el desarrollo integral de las comunidades campesinas, la defensa de sus derechos 
            y la promoción de una agricultura sostenible y justa.
          </p>
          <div class="text-center">
            <a href="quienes_somos.html" class="btn btn-success btn-lg mt-3">Conoce más sobre nosotros</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="container my-5">
    <h2 class="text-center titulo mb-5 text-success">Eventos Destacados</h2>
    <div class="row">
      <?php
      include 'admin/includes/database.php';
      
      $query = "SELECT e.*, 
                (SELECT ei.imagen FROM evento_imagenes ei WHERE ei.id_evento = e.id_evento LIMIT 1) as primera_imagen
                FROM eventos e 
                WHERE e.destacado = 1 
                ORDER BY e.fecha_evento DESC 
                LIMIT 3";
      
      $eventos_destacados = $pdo->query($query)->fetchAll();
      
    if (empty($eventos_destacados)) {
    echo '
    <div class="col-12 text-center">
      <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        Actualmente no hay <strong>eventos destacados</strong>. 
      </div>
    </div>

    <!-- Tarjeta informativa -->
    <div class="col-md-6 offset-md-3 mb-4">
      <div class="card h-100 shadow-sm border-success text-center">
        <div class="card-body">
          <i class="bi bi-calendar text-success display-4 mb-3"></i>
          <h5 class="card-title">Eventos</h5>
          <p class="card-text">
            Consulta la lista completa de actividades y eventos realizados por nuestra organización.
          </p>
          <a href="eventos.php" class="btn btn-success">Ver todos los eventos</a>
        </div>
      </div>
    </div>';
}
 else {
        foreach ($eventos_destacados as $evento) {
          $imagen_html = '';
          if (!empty($evento['primera_imagen'])) {
            $imagen_html = '<img src="uploads/eventos/' . htmlspecialchars($evento['primera_imagen']) . '" 
                            class="card-img-top" 
                            alt="' . htmlspecialchars($evento['titulo']) . '"
                            style="height: 200px; object-fit: cover;">';
          } else {
            $imagen_html = '<div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                            style="height: 200px;">
                            <i class="bi bi-calendar-event text-muted" style="font-size: 3rem;"></i>
                            </div>';
          }
          
          echo '
          <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
              ' . $imagen_html . '
              <div class="card-body">
                <h5 class="card-title">' . htmlspecialchars($evento['titulo']) . '</h5>
                <p class="card-text">' . htmlspecialchars(substr($evento['descripcion'] ?? '', 0, 100)) . '...</p>
                <p class="card-text"><small class="text-muted">Fecha: ' . $evento['fecha_evento'] . '</small></p>
              </div>
              <div class="card-footer text-center">
                <a href="eventos.php" class="btn btn-success">Conoce nuestros eventos</a>
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