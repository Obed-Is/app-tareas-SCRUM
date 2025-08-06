<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/router.php';

// Variables de estado
$token = $_GET['token'] ?? '';
$error = '';
$success = false;
$show_form = false;

// Verificar token
if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT id, usuario, token_expiracion FROM usuarios WHERE token_recuperacion = ?");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        $error = "El enlace de recuperación es inválido";
    } elseif (strtotime($usuario['token_expiracion']) < time()) {
        $error = "El enlace de recuperación ha expirado";
    } else {
        $show_form = true;
        
        // Procesar cambio de contraseña
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($password) || empty($confirm_password)) {
                $error = "Ambos campos son obligatorios";
            } elseif ($password !== $confirm_password) {
                $error = "Las contraseñas no coinciden";
            } elseif (strlen($password) < 8) {
                $error = "La contraseña debe tener al menos 8 caracteres";
            } else {
                // Actualizar contraseña
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE usuarios SET contraseña = ?, token_recuperacion = NULL, token_expiracion = NULL WHERE id = ?");
                
                if ($stmt->execute([$hash, $usuario['id']])) {
                    $success = true;
                    $show_form = false;
                    
                    // Iniciar sesión automáticamente
                    $_SESSION['user_id'] = $usuario['id'];
                    $_SESSION['username'] = $usuario['usuario'];
                } else {
                    $error = "Error al actualizar la contraseña. Por favor intenta nuevamente.";
                }
            }
        }
    }
} else {
    $error = "Enlace de recuperación no proporcionado";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña | TaskApp</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .password-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        .password-rules {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        .password-rules ul {
            padding-left: 20px;
            margin: 5px 0;
        }
        .password-rules .valid {
            color: #28a745;
        }
        .password-rules .invalid {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="form-container animated fadeIn">
        <div class="text-center mb-4">
            <i class="fas fa-lock fa-3x text-primary mb-3"></i>
            <h2>Nueva Contraseña</h2>
            <?php if ($show_form): ?>
                <p class="text-muted">Crea una nueva contraseña para tu cuenta</p>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger animated shake">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success animated fadeIn">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-2"></i>
                    <span>¡Contraseña actualizada correctamente!</span>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-tasks me-1"></i> Ir a Mis Tareas
                </a>
            </div>
        <?php elseif ($show_form): ?>
            <form id="resetForm" method="post" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" novalidate>
                <div class="form-group mb-3 password-container">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-1"></i> Nueva Contraseña
                    </label>
                    <input type="password" id="password" name="password" class="form-control" 
                           required minlength="8">
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('password')"></i>
                    <div class="invalid-feedback">La contraseña debe tener al menos 8 caracteres</div>
                    
                    <div class="password-rules mt-2">
                        <strong>Requisitos:</strong>
                        <ul>
                            <li id="rule-length" class="invalid">Mínimo 8 caracteres</li>
                            <li id="rule-uppercase" class="invalid">Al menos una mayúscula</li>
                            <li id="rule-number" class="invalid">Al menos un número</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group mb-4 password-container">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-lock me-1"></i> Confirmar Contraseña
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           class="form-control" required minlength="8">
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
                    <div class="invalid-feedback">Las contraseñas deben coincidir</div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block mb-3">
                    <i class="fas fa-save me-1"></i> Guardar Contraseña
                </button>
            </form>
        <?php else: ?>
            <div class="text-center mt-3">
                <a href="recuperar.php" class="btn btn-primary">
                    <i class="fas fa-key me-1"></i> Solicitar Nuevo Enlace
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Mostrar/ocultar contraseña
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = field.nextElementSibling;
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
    
    // Validación en tiempo real de la contraseña
    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const lengthValid = password.length >= 8;
        const upperValid = /[A-Z]/.test(password);
        const numberValid = /[0-9]/.test(password);
        
        // Actualizar reglas visualmente
        document.getElementById('rule-length').className = lengthValid ? 'valid' : 'invalid';
        document.getElementById('rule-uppercase').className = upperValid ? 'valid' : 'invalid';
        document.getElementById('rule-number').className = numberValid ? 'valid' : 'invalid';
        
        // Validar campo
        if (password.length > 0 && password.length < 8) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });
    
    // Validación de confirmación de contraseña
    document.getElementById('confirm_password').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        
        if (this.value !== password) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });
    
    // Validación del formulario
    document.getElementById('resetForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm_password');
        let valid = true;
        
        if (password.value.length < 8) {
            password.classList.add('is-invalid');
            valid = false;
        }
        
        if (confirm.value !== password.value) {
            confirm.classList.add('is-invalid');
            valid = false;
        }
        
        if (!valid) {
            e.preventDefault();
        }
    });
    </script>
</body>
</html>