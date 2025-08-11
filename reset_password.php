<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/router.php';
if ($_SESSION["confirmacion"] != 1 || !isset($_SESSION["confirmacion"])) { 
    header("Location: recuperar.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $contraseña = trim($_POST["password"]) ?? '';
    $confirmar_contraseña = trim($_POST["confirm_password"]) ?? '';

    if (empty($contraseña) || $contraseña != $confirmar_contraseña) {
        $error = "Las contraseñas no coinciden";
    } elseif (!isset($_SESSION["email_recuperacion"])) {
        $error = "No se encontró correo asociado. Intenta el proceso de recuperación de nuevo.";
    } else {
        $contraseña_hasheada = password_hash($contraseña, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE usuarios SET contraseña = ? WHERE correo = ?");
        $stmt->execute([$contraseña_hasheada, $_SESSION["email_recuperacion"]]);

        if ($stmt->rowCount() > 0) {
            unset($_SESSION["email_recuperacion"]);
            unset($_SESSION["_codigo"]);
            unset($_SESSION["expiracion_codigo"]);

            $resultPass = true;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña | TaskApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-light);
        }

        .strength-meter {
            height: 4px;
            background: var(--border);
            border-radius: 2px;
            margin-top: var(--space-2);
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            background: var(--error);
            transition: width 0.3s ease, background 0.3s ease;
        }
    </style>
    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>
    <div class="container" style="max-width: 420px; padding-top: 5rem;">
        <div style="text-align: center; margin-bottom: var(--space-6);">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" style="margin-bottom: var(--space-3);">
                <rect width="40" height="40" rx="8" fill="#2563EB" />
                <path d="M20 12L12 17V23L20 28L28 23V17L20 12Z" fill="white" />
                <path d="M20 28V20" stroke="white" stroke-width="2" />
            </svg>
            <h1>Nueva contraseña</h1>
        </div>

        <div class="card">
            <?php if (!isset($resultPass)): ?>
                <?php if (isset($error)): ?>
                    <div style="background: #FEE2E2; color: var(--error); padding: var(--space-3); 
             border-radius: var(--radius-sm); margin-bottom: var(--space-4); 
             display: flex; align-items: center; gap: var(--space-2);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" />
                        </svg>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" id="registerForm" onsubmit="return verifyPass(event)">

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="password-container">
                            <input type="password" id="password" name="password" class="input" required minlength="6">
                            <span class="toggle-password" onclick="togglePassword('password')">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke-width="2" />
                                    <circle cx="12" cy="12" r="3" stroke-width="2" />
                                </svg>
                            </span>
                        </div>
                        <p style="font-size: 0.75rem; color: var(--text-light); margin-top: var(--space-1);">
                            Mínimo 6 caracteres
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmar contraseña</label>
                        <div class="password-container">
                            <input type="password" id="confirm_password" name="confirm_password" class="input" required>
                            <span class="toggle-password" onclick="togglePassword('confirm_password')">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke-width="2" />
                                    <circle cx="12" cy="12" r="3" stroke-width="2" />
                                </svg>
                            </span>
                        </div>
                        <p style="font-size: 0.9rem;" class="error-message text-muted"></p>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--space-4);">
                        Guardar contraseña
                    </button>
                </form>
            <?php else: ?>
                <h3>La contraseña se actualizo correctamente</h3>
                <a href="login.php" class="btn btn-primary" style="width: 100%; margin-top: var(--space-4); text-decoration: none;">Iniciar sesion</a>
            <?php endif ?>
        </div>

        <div class="card mt-4" style="text-align: center; padding: var(--space-4);">
                <a href="login.php" style="color: var(--primary); text-decoration: none; font-weight: 500;">
                    Volver al inicio de sesion
                </a>
            </p>
        </div>
    </div>

    <script>
        // Mostrar/ocultar contraseña
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('svg');

            if (field.type === 'password') {
                field.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22" stroke-width="2"/>';
            } else {
                field.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke-width="2"/>';
            }
        }

        function verifyPass(e) {
            const pass1 = e.target[0].value;
            const pass2 = e.target[1].value;

            if (pass1 !== pass2) {
                e.preventDefault();
                document.querySelector('.error-message').textContent = "Las contraseñas no coinciden";
                return;
            }
        }
    </script>
</body>

</html>