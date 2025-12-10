<?php include 'includes/auth.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Panel de Administración - ARUC DE COLOMBIA</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../img/logo.png">
    <link rel="shortcut icon" href="../img/logo_aruc.jpeg" type="image/x-icon">
    
    <link rel="stylesheet" href="../css/admin_style.css">
   
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <style>
    .alert-success {
    background: linear-gradient(135deg, #1b5e20 0%, #4caf50 100%); 
    color: white; 
    padding: 15px 20px;
    border-radius: 8px;
    margin: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 15px rgba(27, 94, 32, 0.3); 
    animation: slideDown 0.5s ease-out;
}

        
        .alert-success i {
            font-size: 24px;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Auto-ocultar después de 5 segundos */
        .alert-success.fade-out {
            animation: fadeOut 0.5s ease-out forwards;
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-20px);
            }
        }
    </style>
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
        <a href="cambiar_password.php" class="change-pass-btn">
             <i class="bi bi-key-fill"></i> Cambiar Contraseña
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
            <?php 
            // Mensaje de éxito al cambiar contraseña
            if (isset($_GET['password_changed']) && $_GET['password_changed'] == '1') {
                echo '<div class="alert-success" id="successAlert">
                        <i class="bi bi-check-circle-fill"></i> 
                        <div>
                            <strong>¡Contraseña actualizada exitosamente!</strong><br>
                            Ya puedes usar el sistema con normalidad.
                        </div>
                      </div>';
            }
            ?>
            
            <div class="welcome-user">
                <i class="bi bi-person-check-fill"></i>
                <div class="welcome-text">
                    <strong>Sesión activa:</strong> Has iniciado sesión como <strong><?php echo $_SESSION['admin_user']; ?></strong>
                </div>
            </div>
            
            <h2>Dashboard</h2>
            
            <div class="stats">
                <?php
                include 'includes/database.php';
                
                // Contar afiliaciones
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM afiliaciones");
                $total_afiliaciones = $stmt->fetch()['total'];
                
                // Contar eventos
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM eventos");
                $total_eventos = $stmt->fetch()['total'];
                
                // Contar documentos
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM documentos_aruc");
                $total_documentos = $stmt->fetch()['total'];
                
                // Contar cédulas autorizadas
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM cedulas_autorizadas");
                $total_cedulas = $stmt->fetch()['total'];
                ?>
                
                <div class="stat-card">
                    <h3>Total Afiliaciones</h3>
                    <p><?php echo $total_afiliaciones; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Eventos Activos</h3>
                    <p><?php echo $total_eventos; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Documentos</h3>
                    <p><?php echo $total_documentos; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Cédulas Autorizadas</h3>
                    <p><?php echo $total_cedulas; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-ocultar el mensaje de éxito después de 5 segundos
        const successAlert = document.getElementById('successAlert');
        if (successAlert) {
            setTimeout(() => {
                successAlert.classList.add('fade-out');
                setTimeout(() => {
                    successAlert.remove();
                    // Limpiar el parámetro de la URL
                    if (window.history.replaceState) {
                        const url = new URL(window.location);
                        url.searchParams.delete('password_changed');
                        window.history.replaceState({}, '', url);
                    }
                }, 500);
            }, 5000);
        }
    </script>
</body>
</html>