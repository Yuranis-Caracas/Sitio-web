<?php
session_start();
include 'includes/database.php';

// Verificar si PHPMailer está disponible
$phpmailer_available = (
    file_exists(__DIR__ . '/../phpmailer/src/PHPMailer.php') && 
    file_exists(__DIR__ . '/../phpmailer/src/SMTP.php') && 
    file_exists(__DIR__ . '/../phpmailer/src/Exception.php')
);

$mensaje = '';
$enlace_mostrar = '';
$codigo_recuperacion = '';
$email_enviado = false;
$error_envio = '';

if ($_POST) {
    $email = trim($_POST['email']);
    
    // Verificar si el correo existe
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ? AND estado = 'Activo'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // NUEVA VALIDACIÓN: Verificar que NO tenga contraseña temporal
        if ($user['primer_login'] == 1) {
            $error_envio = "❌ <strong>No puedes restablecer tu contraseña porque aún no has cambiado tu contraseña temporal.</strong><br><br>
                           Por favor, inicia sesión con la contraseña que te proporcionó el administrador y cámbiala primero.<br><br>";
        } else {
            // Usuario válido y ya cambió su contraseña temporal - proceder con recuperación
            // Generar token único
            $token = bin2hex(random_bytes(16));
            $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Generar código de 6 dígitos
            $codigo = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Guardar en base de datos
            $stmt = $pdo->prepare("UPDATE usuarios SET 
                                  token_recuperacion = ?, 
                                  token_expiracion = ?, 
                                  codigo_recuperacion = ? 
                                  WHERE id_usuario = ?");
            $stmt->execute([$token, $expiracion, $codigo, $user['id_usuario']]);
            
            // Determinar si estamos en localhost o hosting
            $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            
            // Construir enlace de recuperación
            if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
                $ruta_base = "$protocolo://$host/ARUC_DE_COLOMBIA/admin";
            } else {
                $ruta_base = "$protocolo://$host/admin";
            }
            $enlace = "$ruta_base/reset_password.php?token=$token";
            
            $enlace_mostrar = $enlace;
            $codigo_recuperacion = $codigo;
            
            // Intentar enviar correo si PHPMailer está disponible
            if ($phpmailer_available) {
                $email_enviado = enviarEmailPHPMailer($email, $user['nombre_completo'], $enlace, $codigo);
                
                if ($email_enviado) {
                    $mensaje = "📧 <strong>Correo enviado exitosamente!</strong> Hemos enviado las instrucciones de recuperación a tu email.";
                } else {
                    $mensaje = "⚠ <strong>Mostrando enlace de recuperación:</strong> El sistema de correo no pudo enviar el email.";
                }
            } else {
                $mensaje = "⚠ <strong>PHPMailer no encontrado:</strong> Mostrando enlace de recuperación.";
            }
        }
    } else {
        // Por seguridad, no revelar si el email existe o no
        $mensaje = "📧 Si el email está registrado, recibirás las instrucciones de recuperación.";
    }
}

// Función para enviar email con PHPMailer
function enviarEmailPHPMailer($destinatario, $nombre, $enlace, $codigo) {
    try {
        // Cargar PHPMailer
        require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../phpmailer/src/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer();
        
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'yuraniscaracas06@gmail.com';
        $mail->Password = 'mbrojmqdnccdrhho';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Configuración del correo
        $mail->setFrom('yuraniscaracas06@gmail.com', 'Soporte ARUC DE COLOMBIA');
        $mail->addAddress($destinatario, $nombre);
        
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de Contraseña - ARUC DE COLOMBIA';
        
        // Cuerpo del correo
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Recuperación de Contraseña</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
                .header { text-align: center; margin-bottom: 30px; }
                .header h2 { color: #1b5e20; }
                .content { margin: 20px 0; }
                .button { display: inline-block; background: #2e7d32; color: white; padding: 12px 25px; 
                         text-decoration: none; border-radius: 5px; margin: 10px 0; }
                .code { font-size: 24px; font-weight: bold; letter-spacing: 5px; color: #333; 
                       background: #f8f9fa; padding: 15px; text-align: center; margin: 15px 0; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; 
                         font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>🔐 Recuperación de Contraseña</h2>
                    <p>ARUC DE COLOMBIA</p>
                </div>
                
                <div class="content">
                    <p>Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
                    <p>Hemos recibido una solicitud para restablecer tu contraseña.</p>
                    
                    <p><strong> Al ingresar al enlace, deberás usar el siguiente código para completar el proceso:</strong></p>
                    <div class="code">' . $codigo . '</div>
                    
                    <p><strong>Para restablecer tu contraseña, haz clic en el enlace a continuación</strong>:</p>
                    <p>
                    <a href="' . $enlace . '"
                        style="
                            display:inline-block;
                            color:#ffffff;
                            background-color:#1b5e20;
                            padding:12px 25px;
                            text-decoration:none;
                            border-radius:5px;
                            font-weight:bold;
                            font-size:15px;
                            ">
                            🔗 Restablecer Contraseña
                        </a>
                    </p>

                    
                    <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>
                    <p><em>Este enlace expirará en 1 hora.</em></p>
                </div>
                
                <div class="footer">
                    <p>© ' . date('Y') . ' ARUC DE COLOMBIA - Todos los derechos reservados.</p>
                    <p>Este es un correo automático, por favor no respondas.</p>
                </div>
            </div>
        </body>
        </html>';
        
        $mail->AltBody = "Recuperación de Contraseña ARUC DE COLOMBIA\n\n" .
                        "Hola " . $nombre . ",\n\n" .
                        "Código de verificación: " . $codigo . "\n" .
                        "Enlace: " . $enlace . "\n\n" .
                        "Este enlace expira en 1 hora.";
        
        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Error PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../img/logo.png">
    <link rel="shortcut icon" href="../img/logo_aruc.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="../css/auth_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h2>🔐 Recuperar Contraseña</h2>
        
        <!-- Estado de PHPMailer -->
        <div class="phpmailer-status">
            <strong>Estado del sistema de correo:</strong> 
            <?php 
            if ($phpmailer_available) {
                echo '<span style="color: #28a745;">✓ PHPMailer disponible</span>';
            } else {
                echo '<span style="color: #dc3545;">✗ PHPMailer no encontrado</span>';
            }
            ?>
            <br>
            <small>Configuración actual: Gmail SMTP (cambiar en producción)</small>
        </div>
        
        <?php if ($error_envio): ?>
            <!-- Error: Usuario con contraseña temporal -->
            <div class="alert alert-warning" style="background: #e0f2f1; color: #1b5e20; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <?php echo $error_envio; ?>
            </div>

            
            <div class="back-link">
                <a href="login.php">← Volver al inicio de sesión</a>
            </div>
            
        <?php elseif ($mensaje): ?>
            <div class="alert alert-info">
                <?php echo $mensaje; ?>
            </div>
            
            <!-- Mostrar enlace solo si estamos en modo desarrollo -->
            <?php  if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false): ?>
                <?php if ($codigo_recuperacion): ?>
                    <div class="codigo-box">
                        <h4>📋 Código de verificación:</h4>
                        <div class="codigo" id="codigoVerificacion">
                            <?php echo $codigo_recuperacion; ?>
                        </div>
                        <button onclick="copiarCodigo()" class="copy-btn">
                            📋 Copiar Código
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if ($enlace_mostrar): ?>
                    <div class="enlace-box">
                        <h4>🔗 Enlace de recuperación:</h4>
                        <input type="text" 
                               value="<?php echo htmlspecialchars($enlace_mostrar); ?>" 
                               id="enlaceRecuperacion" 
                               readonly
                               onclick="this.select()"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin: 10px 0;">
                        
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button onclick="copiarEnlace()" style="flex: 1;">
                                📋 Copiar Enlace
                            </button>
                            <a href="<?php echo htmlspecialchars($enlace_mostrar); ?>" 
                               target="_blank" 
                               class="btn" 
                               style="flex: 1; text-decoration: none;">
                                🌐 Abrir Enlace
                            </a>
                        </div>
                    </div>
                    
                    <div class="instructions">
                        <h4>📝 Información de configuración:</h4>
                        <p><strong>Para configurar el envío automático de correos:</strong></p>
                        <ol>
                            <li>Abre el archivo <code>recuperar_password.php</code></li>
                            <li>Busca la función <code>enviarEmailPHPMailer()</code></li>
                            <li>Cambia estos valores:
                                <ul>
                                    <li><code>$mail->Host</code> = tu servidor SMTP</li>
                                    <li><code>$mail->Username</code> = tu correo</li>
                                    <li><code>$mail->Password</code> = tu contraseña</li>
                                    <li><code>$mail->setFrom()</code> = tu correo y nombre</li>
                                </ul>
                            </li>
                            <li>Para Gmail, necesitarás una <strong>contraseña de aplicación</strong></li>
                        </ol>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="back-link">
                <a href="login.php">← Volver al inicio de sesión</a>
            </div>
            
        <?php else: ?>
        
            <div class="instructions">
                <h4>📝 ¿Olvidaste tu contraseña?</h4>
                <p>Ingresa tu dirección de email registrado.</p>
                <div class="alert alert-warning" style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 15px 0;">
                    <strong>⚠️ Nota importante:</strong> Solo puedes restablecer tu contraseña si ya cambiaste la contraseña temporal proporcionada por el administrador.
                </div>
                <?php if (!$phpmailer_available): ?>
                    <div class="alert alert-warning">
                        <strong>Nota:</strong> El sistema de correo no está configurado. Se mostrará el enlace en pantalla.
                    </div>
                <?php endif; ?>
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label for="email">Correo electrónico:</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="tucorreo@ejemplo.com" 
                           required
                           autocomplete="email"
                           autofocus>
                </div>
                
                <button type="submit" class="btn-recuperacion">
                    <i class="fas fa-key"></i> 
                    <?php echo $phpmailer_available ? 'Enviar correo de recuperación' : 'Obtener enlace de recuperación'; ?>
                </button>
            </form>
            
            <div class="back-link">
                <a href="login.php">← Volver al inicio de sesión</a>
            </div>
            
        <?php endif; ?>
    </div>
    
    <script>
        function copiarEnlace() {
            const input = document.getElementById('enlaceRecuperacion');
            input.select();
            input.setSelectionRange(0, 99999);
            
            try {
                navigator.clipboard.writeText(input.value).then(() => {
                    mostrarNotificacion('✅ Enlace copiado al portapapeles');
                });
            } catch (err) {
                document.execCommand('copy');
                mostrarNotificacion('✅ Enlace copiado al portapapeles');
            }
        }
        
        function copiarCodigo() {
            const codigo = document.getElementById('codigoVerificacion').innerText;
            
            try {
                navigator.clipboard.writeText(codigo).then(() => {
                    mostrarNotificacion('✅ Código copiado al portapapeles');
                });
            } catch (err) {
                const tempInput = document.createElement('input');
                tempInput.value = codigo;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                mostrarNotificacion('✅ Código copiado al portapapeles');
            }
        }
        
        function mostrarNotificacion(mensaje) {
            const notificacion = document.createElement('div');
            notificacion.textContent = mensaje;
            notificacion.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #28a745;
                color: white;
                padding: 15px 25px;
                border-radius: 8px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                z-index: 10000;
                animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.7s forwards;
            `;
            
            document.body.appendChild(notificacion);
            
            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.parentNode.removeChild(notificacion);
                }
            }, 3000);
        }
        
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>