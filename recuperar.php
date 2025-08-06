<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Estados del proceso
$estado = '';
$mostrar_formulario = true;
$error = '';

// Procesar solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    
    if (empty($email)) {
        $error = "Por favor ingresa tu correo electrónico";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo no es válido";
    } else {
        try {
            // Verificar existencia del email
            $stmt = $pdo->prepare("SELECT id, usuario FROM usuarios WHERE correo = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();
            
            if ($usuario) {
                // Generar token seguro
                $token = bin2hex(random_bytes(32));
                $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Actualizar base de datos
                $stmt = $pdo->prepare("UPDATE usuarios SET token_recuperacion = ?, token_expiracion = ? WHERE id = ?");
                $stmt->execute([$token, $expiracion, $usuario['id']]);
                
                // Crear enlace de recuperación
                $enlace_recuperacion = "https://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/reset_password.php?token=$token";
                
                // Plantilla de email (HTML)
                $asunto = "Restablece tu contraseña en TaskApp";
                $mensaje = "
                <html>
                <head>
                    <link rel='stylesheet' href='assets/css/style.css'>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { color: #4361ee; text-align: center; }
                        .button { 
                            display: inline-block; padding: 10px 20px; 
                            background-color: #4361ee; color: white !important; 
                            text-decoration: none; border-radius: 5px; margin: 15px 0;
                        }
                        .footer { margin-top: 30px; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <h1 class='header'>TaskApp</h1>
                        <h2>Restablecimiento de contraseña</h2>
                        <p>Hola ".htmlspecialchars($usuario['usuario']).",</p>
                        <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta.</p>
                        <p>Por favor haz clic en el siguiente botón para continuar:</p>
                        <p><a href='$enlace_recuperacion' class='button'>Restablecer Contraseña</a></p>
                        <p>Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
                        <p><strong>Este enlace expirará en 1 hora.</strong></p>
                        <div class='footer'>
                            <p>© ".date('Y')." TaskApp. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // Cabeceras para email HTML
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: TaskApp <no-reply@".$_SERVER['HTTP_HOST'].">\r\n";
                
                // Envío de email (simulado en desarrollo)
                if ($_SERVER['SERVER_NAME'] === 'localhost') {
                    // En desarrollo, mostrar el enlace
                    $estado = "success";
                    $mensaje_estado = "
                    <div class='demo-email'>
                        <h3><i class='fas fa-envelope-open-text'></i> Email de Recuperación (Simulado)</h3>
                        <p>En producción, esto se enviaría a: ".htmlspecialchars($email)."</p>
                        <div class='email-preview'>
                            <p>Contenido del email:</p>
                            <div class='email-content'>$mensaje</div>
                            <p class='mt-3'>Enlace directo para pruebas:</p>
                            <a href='$enlace_recuperacion' class='btn btn-sm'>$enlace_recuperacion</a>
                        </div>
                    </div>
                    ";
                } else {
                    // En producción, enviar email real
                    if (mail($email, $asunto, $mensaje, $headers)) {
                        $estado = "success";
                        $mensaje_estado = "Hemos enviado un enlace de recuperación a tu correo electrónico.";
                    } else {
                        $error = "Error al enviar el correo. Por favor intenta más tarde.";
                    }
                }
                
                $mostrar_formulario = false;
            } else {
                $error = "No encontramos una cuenta asociada a este correo";
            }
        } catch (PDOException $e) {
            error_log("Error en recuperación: ".$e->getMessage());
            $error = "Error al procesar la solicitud. Por favor intenta más tarde.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña | TaskApp</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .password-strength {
            margin-top: 5px;
            height: 5px;
            background: #eee;
            border-radius: 3px;
            overflow: hidden;
        }
        .strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s, background 0.3s;
        }
        .demo-email {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .email-preview {
            background: white;
            padding: 15px;
            border-radius: 5px;
        }
        .email-content {
            border: 1px solid #eee;
            padding: 10px;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="form-container animated fadeIn">
        <div class="text-center mb-4">
            <i class="fas fa-key fa-3x text-primary mb-3"></i>
            <h2>Recuperar Contraseña</h2>
            <p class="text-muted">Ingresa tu correo para recibir instrucciones</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger animated shake">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($estado === 'success'): ?>
            <div class="alert alert-success animated fadeIn">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-2"></i>
                    <span>¡Enlace enviado correctamente!</span>
                </div>
            </div>
            <?php echo $mensaje_estado; ?>
            <div class="text-center mt-4">
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-1"></i> Volver al Login
                </a>
            </div>
        <?php elseif ($mostrar_formulario): ?>
            <form id="recoveryForm" method="post" action="recuperar.php" novalidate>
                <div class="form-group mb-4">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-1"></i> Correo Electrónico
                    </label>
                    <input type="email" id="email" name="email" class="form-control" 
                           required autofocus>
                    <div class="invalid-feedback">Por favor ingresa un correo válido</div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block mb-3">
                    <i class="fas fa-paper-plane me-1"></i> Enviar Enlace
                </button>
                
                <div class="text-center mt-3">
                    <a href="login.php" class="text-primary">
                        <i class="fas fa-sign-in-alt me-1"></i> Volver al Login
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
    // Validación en tiempo real
    document.getElementById('recoveryForm').addEventListener('submit', function(e) {
        const email = document.getElementById('email');
        
        if (!email.value) {
            e.preventDefault();
            email.classList.add('is-invalid');
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
            e.preventDefault();
            email.classList.add('is-invalid');
        }
    });

    // Validación mientras escribe
    document.getElementById('email').addEventListener('input', function() {
        if (this.value && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value)) {
            this.classList.remove('is-invalid');
        }
    });
    </script>
</body>
</html>