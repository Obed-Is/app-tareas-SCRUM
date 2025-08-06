<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $usuario = trim($_POST['usuario'] ?? '');
  $correo = trim($_POST['correo'] ?? '');
  $contraseña = $_POST['password'] ?? '';
  $confirmar_contraseña = $_POST['confirm_password'] ?? '';

  if (empty($usuario) || empty($correo) || empty($contraseña)) {
    $error = "Todos los campos son obligatorios";
  } elseif ($contraseña !== $confirmar_contraseña) {
    $error = "Las contraseñas no coinciden";
  } elseif (strlen($contraseña) < 6) {
    $error = "La contraseña debe tener al menos 6 caracteres";
  } else {
    if (registrarUsuario($usuario, $correo, $contraseña, $pdo)) {
      header("Location: login.php?registro=exito");
      exit();
    } else {
      $error = "El usuario o correo electrónico ya está registrado";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro | TaskApp</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
</head>

<body>
  <div class="container" style="max-width: 420px; padding-top: 5rem;">
    <div style="text-align: center; margin-bottom: var(--space-6);">
      <svg width="40" height="40" viewBox="0 0 40 40" fill="none" style="margin-bottom: var(--space-3);">
        <rect width="40" height="40" rx="8" fill="#2563EB" />
        <path d="M20 12L12 17V23L20 28L28 23V17L20 12Z" fill="white" />
        <path d="M20 28V20" stroke="white" stroke-width="2" />
      </svg>
      <h1>Crear cuenta</h1>
      <p class="text-muted">Comienza a organizar tus tareas</p>
    </div>

    <div class="card">
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

      <form method="post" id="registerForm">
        <div class="form-group">
          <label for="usuario">Nombre de usuario</label>
          <input type="text" id="usuario" name="usuario" class="input" required>
        </div>

        <div class="form-group">
          <label for="correo">Correo electrónico</label>
          <input type="email" id="correo" name="correo" class="input" required>
        </div>

        <div class="form-group">
          <label for="password">Contraseña</label>
          <div class="password-container">
            <input type="password" id="password" name="password" class="input" required minlength="6"
              oninput="checkPasswordStrength(this.value)">
            <span class="toggle-password" onclick="togglePassword('password')">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke-width="2" />
                <circle cx="12" cy="12" r="3" stroke-width="2" />
              </svg>
            </span>
          </div>
          <div class="strength-meter">
            <div class="strength-bar" id="strengthBar"></div>
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
          <p style="font-size: 0.75rem; color: var(--text-light); margin-top: var(--space-1);">
            <!-- Mínimo 6 caracteres -->
          </p>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--space-4);">
          Crear cuenta
        </button>
      </form>
    </div>

    <div class="card mt-4" style="text-align: center; padding: var(--space-4);">
      <p style="font-size: 0.875rem;">¿Ya tienes una cuenta?
        <a href="login.php" style="color: var(--primary); text-decoration: none; font-weight: 500;">
          Iniciar sesión
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

    // Medidor de fuerza de contraseña
    function checkPasswordStrength(password) {
      const strengthBar = document.getElementById('strengthBar');
      let strength = 0;

      // Longitud mínima
      if (password.length > 5) strength += 20;
      if (password.length > 8) strength += 20;

      // Complejidad
      if (/[A-Z]/.test(password)) strength += 20;
      if (/[0-9]/.test(password)) strength += 20;
      if (/[^A-Za-z0-9]/.test(password)) strength += 20;

      // Actualizar barra
      strengthBar.style.width = strength + '%';

      // Cambiar color según fuerza
      if (strength < 40) {
        strengthBar.style.background = 'var(--error)';
      } else if (strength < 80) {
        strengthBar.style.background = 'var(--warning)';
      } else {
        strengthBar.style.background = 'var(--success)';
      }
    }

    // Validación del formulario
    document.getElementById('registerForm').addEventListener('submit', function (e) {
      const password = document.getElementById('password');
      const confirm = document.getElementById('confirm_password');

      if (password.value !== confirm.value) {
        e.preventDefault();
        confirm.style.borderColor = 'var(--error)';
        confirm.nextElementSibling.style.color = 'var(--error)';
      }
    });
  </script>
</body>

</html>