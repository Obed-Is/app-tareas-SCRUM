<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $usuario = trim($_POST['usuario'] ?? '');
  $contraseña = $_POST['contraseña'] ?? '';

  if (empty($usuario) || empty($contraseña)) {
    $error = "Por favor completa todos los campos";
  } elseif (!loginUsuario($usuario, $contraseña, $pdo)) {
    $error = "Usuario o contraseña incorrectos";
  } else {
    header("Location: index.php");
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso | TaskApp</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>
  <div class="container" style="max-width: 420px; padding-top: 5rem;">
    <div style="text-align: center; margin-bottom: var(--space-6);">
      <svg width="40" height="40" viewBox="0 0 40 40" fill="none" style="margin-bottom: var(--space-3);">
        <rect width="40" height="40" rx="8" fill="#2563EB" />
        <path d="M20 12L12 17V23L20 28L28 23V17L20 12Z" fill="white" />
        <path d="M20 28V20" stroke="white" stroke-width="2" />
      </svg>
      <h1>TaskApp</h1>
      <p class="text-muted">Gestión profesional de tareas</p>
    </div>

    <div class="card">
      <?php if (isset($error)): ?>
        <div id="errorMsg" style="background: #FEE2E2; color: var(--error); padding: var(--space-3); 
             border-radius: var(--radius-sm); margin-bottom: var(--space-4); 
             display: flex; align-items: center; gap: var(--space-2);">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" />
          </svg>
          <span><?php echo htmlspecialchars($error); ?></span>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label for="usuario">Usuario o Email</label>
          <input type="text" id="usuario" name="usuario" class="input" required>
        </div>

        <div class="form-group">
          <label for="contraseña">Contraseña</label>
          <input type="password" id="contraseña" name="contraseña" class="input" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">
          Iniciar Sesión
        </button>
      </form>

      <div class="mt-4" style="text-align: center;">
        <a href="recuperar.php" style="font-size: 0.875rem; color: var(--text-light); text-decoration: none;">
          ¿Olvidaste tu contraseña?
        </a>
      </div>
    </div>

    <div class="card mt-4" style="text-align: center; padding: var(--space-4);">
      <p style="font-size: 0.875rem;">¿No tienes cuenta?
        <a href="register.php" style="color: var(--primary); text-decoration: none; font-weight: 500;">
          Regístrate
        </a>
      </p>
    </div>
  </div>
  <script>
    // Ocultar error general al escribir en cualquier campo
    ['usuario', 'contraseña'].forEach(function (id) {
      var input = document.getElementById(id);
      if (input) {
        input.addEventListener('input', function () {
          var errorDiv = document.getElementById('errorMsg');
          if (errorDiv) errorDiv.style.display = 'none';
        });
      }
    });
  </script>
</body>

</html>