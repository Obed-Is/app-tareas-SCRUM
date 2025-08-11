<?php
require 'includes/config.php';
require 'includes/auth.php';
redirectIfNotLoggedIn();

$errors = [];
$titulo = '';
$descripcion = '';
$prioridad = 'media';
$fecha_final = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $prioridad = $_POST['prioridad'] ?? 'media';
    $fecha_final = $_POST['fecha_final'] ?? '';

    // Validar título
    if (strlen($titulo) < 3) {
        $errors['titulo'] = "El título debe tener al menos 3 caracteres";
    }

    // Validar fecha
    if (empty($fecha_final)) {
        $errors['fecha_final'] = "La fecha de vencimiento es obligatoria";
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $fecha_final);
        if (!($d && $d->format('Y-m-d') === $fecha_final)) {
            $errors['fecha_final'] = "La fecha de vencimiento no es válida";
        }
    }

    // En la parte donde se crea la tarea exitosamente:
if (empty($errors)) {
    try {
        $stmt = $conn->prepare("INSERT INTO tareas (titulo, descripcion, prioridad, fecha_inicio, fecha_final, usuario_fk) VALUES (?, ?, ?, CURDATE(), ?, ?)");
        $stmt->execute([$titulo, $descripcion, $prioridad, $fecha_final, $_SESSION['user_id']]);
        $_SESSION['swal'] = [
            'icon' => 'success',
            'title' => '¡Tarea creada!',
            'text' => 'La tarea ha sido creada exitosamente'
        ];
        header("Location: index.php");
        exit();
    } catch(PDOException $e) {
        $errors['general'] = "Error al crear la tarea: " . $e->getMessage();
    }
}

    // Validar prioridad
    $validPrioridades = ['alta', 'media', 'baja'];
    if (!in_array($prioridad, $validPrioridades)) {
        $errors['prioridad'] = "Prioridad inválida";
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO tareas (titulo, descripcion, prioridad, fecha_inicio, fecha_final, usuario_fk) VALUES (?, ?, ?, CURDATE(), ?, ?)");
            $stmt->execute([$titulo, $descripcion, $prioridad, $fecha_final, $_SESSION['user_id']]);
            $_SESSION['success'] = "Tarea creada exitosamente";
            header("Location: index.php");
            exit();
        } catch(PDOException $e) {
            $errors['general'] = "Error al crear la tarea: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Nueva Tarea | TaskApp</title>
  <link rel="stylesheet" href="assets/css/style.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    /* Prioridades */
    .priority-selector {
      display: flex;
      gap: var(--space-2);
      margin: var(--space-3) 0;
    }
    .priority-option {
      flex: 1;
      text-align: center;
      padding: var(--space-3);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      cursor: pointer;
      transition: var(--transition);
      user-select: none;
    }
    .priority-option:hover {
      border-color: var(--primary);
    }
    .priority-option.selected {
      border-color: var(--primary);
      background-color: rgba(37, 99, 235, 0.05);
    }
    .priority-option.alta.selected {
      border-color: var(--error);
      background-color: rgba(239, 68, 68, 0.05);
    }
    .priority-option.media.selected {
      border-color: var(--warning);
      background-color: rgba(245, 158, 11, 0.05);
    }
    .priority-option.baja.selected {
      border-color: var(--success);
      background-color: rgba(16, 185, 129, 0.05);
    }
    .priority-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      display: inline-block;
      margin-right: var(--space-1);
    }
    .priority-dot.alta { background: var(--error); }
    .priority-dot.media { background: var(--warning); }
    .priority-dot.baja { background: var(--success); }
    .invalid-feedback {
      color: var(--error);
      font-size: 0.75rem;
      margin-top: 4px;
    }
  </style>
</head>
<body>
  <div class="container" style="max-width: 600px;">
    <header class="app-header" style="margin-bottom: var(--space-4);">
      <h1>Nueva Tarea</h1>
      <p class="text-muted">Organiza tus actividades</p>
      <a href="index.php" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 0.5rem;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path d="M19 12H5M12 19l-7-7 7-7" stroke-width="2"/>
        </svg>
        Volver
      </a>
    </header>

    <div class="card">
      <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error" style="margin-bottom: var(--space-3);">
          <?php echo htmlspecialchars($errors['general']); ?>
        </div>
      <?php endif; ?>

      <form method="post" id="taskForm" novalidate>
        <div class="form-group">
          <label for="titulo">Título de la tarea</label>
          <input
            type="text"
            id="titulo"
            name="titulo"
            class="input <?php echo isset($errors['titulo']) ? 'is-invalid' : ''; ?>"
            value="<?php echo htmlspecialchars($titulo); ?>"
            required
            autofocus
          />
          <?php if (isset($errors['titulo'])): ?>
            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['titulo']); ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label for="descripcion">Descripción</label>
          <textarea
            id="descripcion"
            name="descripcion"
            class="input"
            rows="4"
            placeholder="Agrega detalles importantes..."
          ><?php echo htmlspecialchars($descripcion); ?></textarea>
        </div>

        <div class="form-group">
          <label>Prioridad</label>
          <div class="priority-selector" role="radiogroup" aria-label="Prioridad de la tarea">
            <?php
            $prioridades = ['alta' => 'Alta', 'media' => 'Media', 'baja' => 'Baja'];
            foreach ($prioridades as $key => $label):
              $selected = $prioridad === $key;
            ?>
              <div
                class="priority-option <?php echo $key . ($selected ? ' selected' : ''); ?>"
                role="radio"
                tabindex="0"
                aria-checked="<?php echo $selected ? 'true' : 'false'; ?>"
                onclick="selectPriority('<?php echo $key; ?>')"
                onkeydown="if(event.key==='Enter' || event.key===' ') selectPriority('<?php echo $key; ?>')"
              >
                <span class="priority-dot <?php echo $key; ?>"></span>
                <?php echo $label; ?>
                <input type="radio" name="prioridad" value="<?php echo $key; ?>" id="prioridad_<?php echo $key; ?>" <?php echo $selected ? 'checked' : ''; ?> style="display:none;" />
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="form-group">
          <label for="fecha_final">Fecha de vencimiento</label>
          <input
            type="date"
            id="fecha_final"
            name="fecha_final"
            class="input <?php echo isset($errors['fecha_final']) ? 'is-invalid' : ''; ?>"
            value="<?php echo htmlspecialchars($fecha_final); ?>"
            required
            min="<?php echo date('Y-m-d'); ?>"
          />
          <?php if (isset($errors['fecha_final'])): ?>
            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['fecha_final']); ?></div>
          <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--space-4);">
          Crear Tarea
        </button>
      </form>
    </div>
  </div>

  <script>
    function selectPriority(prio) {
      const options = document.querySelectorAll('.priority-option');
      options.forEach(opt => opt.classList.remove('selected'));
      const selected = document.getElementById('prioridad_' + prio).parentElement;
      selected.classList.add('selected');
      document.getElementById('prioridad_' + prio).checked = true;
    }

    document.getElementById('taskForm').addEventListener('submit', function(e) {
      const titulo = document.getElementById('titulo');
      const fecha = document.getElementById('fecha_final');
      let isValid = true;

      if (titulo.value.trim().length < 3) {
        titulo.classList.add('is-invalid');
        isValid = false;
      } else {
        titulo.classList.remove('is-invalid');
      }

      if (!fecha.value) {
        fecha.classList.add('is-invalid');
        isValid = false;
      } else {
        fecha.classList.remove('is-invalid');
      }

      if (!isValid) {
        e.preventDefault();
        Swal.fire({
          icon: 'error',
          title: 'Error en el formulario',
          text: 'Por favor corrige los errores marcados',
          confirmButtonColor: '#2563EB'
        });
      }
    });

    // Quitar error al corregir
    ['titulo', 'fecha_final'].forEach(id => {
      const el = document.getElementById(id);
      el.addEventListener('input', () => el.classList.remove('is-invalid'));
      el.addEventListener('change', () => el.classList.remove('is-invalid'));
    });
  </script>
</body>
</html>