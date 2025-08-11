<?php
require 'includes/config.php';
require 'includes/auth.php';
redirectIfNotLoggedIn();

// En la parte donde se actualiza la tarea exitosamente:
if (empty($errors)) {
    try {
        $stmt = $conn->prepare("UPDATE tareas SET titulo = ?, descripcion = ?, prioridad = ?, fecha_final = ?, estado = ?, categoria = ?, notas = ? WHERE id = ? AND usuario_fk = ?");
        $stmt->execute([$titulo, $descripcion, $prioridad, $fecha_final, $estado, $categoria, $notas, $id, $_SESSION['user_id']]);
        $_SESSION['swal'] = [
            'icon' => 'success',
            'title' => '¡Tarea actualizada!',
            'text' => 'La tarea ha sido actualizada exitosamente'
        ];
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $errors['general'] = "Error al actualizar la tarea: " . $e->getMessage();
    }
}

// Validar que el parámetro 'id' sea numérico
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header("Location: index.php");
    exit();
}

// Obtener la tarea a editar del usuario
$stmt = $conn->prepare("SELECT * FROM tareas WHERE id = ? AND usuario_fk = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$tarea = $stmt->fetch();

if (!$tarea) {
    header("Location: index.php");
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $prioridad = $_POST['prioridad'] ?? 'media';
    $fecha_final = $_POST['fecha_final'] ?? '';
    $estado = $_POST['estado'] ?? 'pendiente';
    $categoria = $_POST['categoria'] ?? '';
    $notas = trim($_POST['notas'] ?? '');

    // Validaciones básicas
    if (strlen($titulo) < 3) {
        $errors['titulo'] = "El título debe tener al menos 3 caracteres";
    }

    if (empty($fecha_final)) {
        $errors['fecha_final'] = "La fecha de vencimiento es obligatoria";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_final)) {
        $errors['fecha_final'] = "Formato de fecha inválido";
    } elseif (strtotime($fecha_final) < strtotime($tarea['fecha_inicio'])) {
        $errors['fecha_final'] = "La fecha de vencimiento no puede ser anterior a la fecha de inicio";
    }

    // Validar valores permitidos para prioridad, estado y categoría
    $prioridades_validas = ['alta', 'media', 'baja'];
    $estados_validos = ['pendiente', 'completada'];
    $categorias_validas = ['', 'trabajo', 'personal', 'estudio'];

    if (!in_array($prioridad, $prioridades_validas)) {
        $prioridad = 'media'; // valor por defecto
    }

    if (!in_array($estado, $estados_validos)) {
        $estado = 'pendiente'; // valor por defecto
    }

    if (!in_array($categoria, $categorias_validas)) {
        $categoria = ''; // sin categoría
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("UPDATE tareas SET titulo = ?, descripcion = ?, prioridad = ?, fecha_final = ?, estado = ?, categoria = ?, notas = ? WHERE id = ? AND usuario_fk = ?");
            $stmt->execute([$titulo, $descripcion, $prioridad, $fecha_final, $estado, $categoria, $notas, $id, $_SESSION['user_id']]);
            $_SESSION['success'] = "Tarea actualizada exitosamente";
            header("Location: index.php");
            exit();
        } catch (PDOException $e) {
            $errors['general'] = "Error al actualizar la tarea: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Editar Tarea | TaskApp</title>
  <link rel="stylesheet" href="assets/css/style.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .status-selector {
      display: flex;
      gap: var(--space-2);
      margin: var(--space-3) 0;
    }
    .status-option {
      flex: 1;
      text-align: center;
      padding: var(--space-3);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      cursor: pointer;
      transition: var(--transition);
      user-select: none;
    }
    .status-option:hover {
      border-color: var(--primary);
    }
    .status-option.selected {
      border-color: var(--primary);
      background-color: rgba(37, 99, 235, 0.05);
    }
    .status-badge {
      display: inline-flex;
      align-items: center;
      padding: var(--space-1) var(--space-2);
      border-radius: var(--radius-sm);
      font-size: 0.75rem;
      font-weight: 500;
    }
    .status-badge.pendiente {
      background-color: rgba(239, 68, 68, 0.1);
      color: var(--error);
    }
    .status-badge.completada {
      background-color: rgba(16, 185, 129, 0.1);
      color: var(--success);
    }
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
      <h1>Editar Tarea</h1>
      <p class="text-muted">Actualiza los detalles de tu tarea</p>
      <a href="index.php" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 0.5rem;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path d="M19 12H5M12 19l-7-7 7-7" stroke-width="2"/>
        </svg>
        Cancelar
      </a>
    </header>

    <div class="card">
      <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error" style="margin-bottom: var(--space-3);">
          <?php echo htmlspecialchars($errors['general']); ?>
        </div>
      <?php endif; ?>

      <form method="post" id="editTaskForm" novalidate>
        <div class="form-group">
          <label for="titulo">Título</label>
          <input
            type="text"
            id="titulo"
            name="titulo"
            class="input <?php echo isset($errors['titulo']) ? 'is-invalid' : ''; ?>"
            value="<?php echo htmlspecialchars($_POST['titulo'] ?? $tarea['titulo']); ?>"
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
          ><?php echo htmlspecialchars($_POST['descripcion'] ?? $tarea['descripcion']); ?></textarea>
        </div>

        <div class="flex" style="gap: var(--space-4);">
          <div class="form-group" style="flex: 1;">
            <label for="fecha_inicio">Fecha inicio</label>
            <input
              type="date"
              id="fecha_inicio"
              name="fecha_inicio"
              class="input"
              value="<?php echo htmlspecialchars($tarea['fecha_inicio']); ?>"
              readonly
            />
          </div>
          <div class="form-group" style="flex: 1;">
            <label for="fecha_final">Fecha vencimiento</label>
            <input
              type="date"
              id="fecha_final"
              name="fecha_final"
              class="input <?php echo isset($errors['fecha_final']) ? 'is-invalid' : ''; ?>"
              value="<?php echo htmlspecialchars($_POST['fecha_final'] ?? $tarea['fecha_final']); ?>"
              required
            />
            <?php if (isset($errors['fecha_final'])): ?>
              <div class="invalid-feedback"><?php echo htmlspecialchars($errors['fecha_final']); ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="form-group">
          <label>Estado</label>
          <div class="status-selector" role="radiogroup" aria-label="Estado de la tarea">
            <?php
              $estados = ['pendiente' => 'Pendiente', 'completada' => 'Completada'];
              foreach ($estados as $key => $label):
                $selected = ($_POST['estado'] ?? $tarea['estado']) === $key;
            ?>
            <div
              class="status-option <?php echo $selected ? 'selected' : ''; ?>"
              role="radio"
              tabindex="0"
              aria-checked="<?php echo $selected ? 'true' : 'false'; ?>"
              onclick="selectStatus('<?php echo $key; ?>')"
              onkeydown="if(event.key==='Enter' || event.key===' ') selectStatus('<?php echo $key; ?>')"
            >
              <span class="status-badge <?php echo $key; ?>">
                <?php if ($key === 'pendiente'): ?>
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: 0.25rem;">
                    <path d="M12 8v4l3 3" stroke-width="2" />
                    <circle cx="12" cy="12" r="10" stroke-width="2" />
                  </svg>
                <?php else: ?>
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: 0.25rem;">
                    <path d="M20 6L9 17l-5-5" stroke-width="2" />
                  </svg>
                <?php endif; ?>
                <?php echo $label; ?>
              </span>
              <input
                type="radio"
                name="estado"
                value="<?php echo $key; ?>"
                id="estado_<?php echo $key; ?>"
                <?php echo $selected ? 'checked' : ''; ?>
                style="display:none;"
              />
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="flex" style="gap: var(--space-4);">
          <div class="form-group" style="flex: 1;">
            <label for="prioridad">Prioridad</label>
            <select id="prioridad" name="prioridad" class="input">
              <?php
              $prioridades = ['alta' => 'Alta', 'media' => 'Media', 'baja' => 'Baja'];
              $selectedPrioridad = $_POST['prioridad'] ?? $tarea['prioridad'];
              foreach ($prioridades as $key => $label):
              ?>
                <option value="<?php echo $key; ?>" <?php echo $selectedPrioridad === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="flex: 1;">
            <label for="categoria">Categoría</label>
            <select id="categoria" name="categoria" class="input">
              <?php
              $categorias = ['', 'trabajo', 'personal', 'estudio'];
              $selectedCategoria = $_POST['categoria'] ?? $tarea['categoria'];
              foreach ($categorias as $cat):
                $label = $cat === '' ? 'Sin categoría' : ucfirst($cat);
              ?>
                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selectedCategoria === $cat ? 'selected' : ''; ?>><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="notas">Notas adicionales</label>
          <textarea
            id="notas"
            name="notas"
            class="input"
            rows="2"
            placeholder="Agregar cualquier información adicional..."
          ><?php echo htmlspecialchars($_POST['notas'] ?? $tarea['notas']); ?></textarea>
        </div>

        <div class="flex" style="gap: var(--space-3); margin-top: var(--space-5);">
          <button type="submit" class="btn btn-primary" style="flex: 1;">
            Guardar Cambios
          </button>
          <a href="delete_task.php?id=<?php echo $tarea['id']; ?>" class="btn btn-outline" style="flex: 1;" onclick="return confirm('¿Eliminar esta tarea permanentemente?')">
            Eliminar
          </a>
        </div>
      </form>
    </div>
  </div>

  <script>
    function selectStatus(status) {
      const options = document.querySelectorAll('.status-option');
      options.forEach(option => option.classList.remove('selected'));
      const selectedOption = document.getElementById('estado_' + status).parentElement;
      selectedOption.classList.add('selected');
      document.getElementById('estado_' + status).checked = true;
    }

    document.getElementById('editTaskForm').addEventListener('submit', function(e) {
      const titulo = document.getElementById('titulo');
      const fechaFinal = document.getElementById('fecha_final');
      let isValid = true;

      if (titulo.value.trim().length < 3) {
        titulo.classList.add('is-invalid');
        isValid = false;
      } else {
        titulo.classList.remove('is-invalid');
      }

      if (!fechaFinal.value) {
        fechaFinal.classList.add('is-invalid');
        isValid = false;
      } else {
        fechaFinal.classList.remove('is-invalid');
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

    // Mejorar UX: quitar error al corregir input
    ['titulo', 'fecha_final'].forEach(id => {
      const el = document.getElementById(id);
      el.addEventListener('input', () => el.classList.remove('is-invalid'));
    });
  </script>
</body>
</html>
