<?php
require 'includes/config.php';
require 'includes/auth.php';
require_once __DIR__ . '/includes/router.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $prioridad = $_POST['prioridad'];
    $fecha_vencimiento = $_POST['fecha_vencimiento'];
    $usuario_fk = $_SESSION['user_id'];

    $fecha_final = !empty($fecha_vencimiento) ? $fecha_vencimiento . ' 23:59:59' : null;

    // Insertar
    $stmt = $pdo->prepare("INSERT INTO tareas (titulo, descripcion, prioridad, fecha_final, estado, usuario_fk) VALUES (?, ?, ?, ?, 'pendiente', ?)");
    $stmt->execute([$titulo, $descripcion, $prioridad, $fecha_final, $usuario_fk]);
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nueva Tarea | TaskApp</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
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
  </style>
</head>
<body>
  <div class="container" style="max-width: 600px;">
    <header class="app-header">
      <div>
        <h1>Nueva Tarea</h1>
        <p class="text-muted">Organiza tus actividades</p>
      </div>
      <a href="index.php" class="btn btn-outline">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-2);">
          <path d="M19 12H5M12 19l-7-7 7-7" stroke-width="2"/>
        </svg>
        Volver
      </a>
    </header>
    
    <div class="card">
      <form method="post" id="taskForm">
        <div class="form-group">
          <label for="titulo">Título de la tarea</label>
          <input type="text" id="titulo" name="titulo" class="input" placeholder="Ej: Revisar informe mensual" required>
          <span id="error-titulo" style="color:var(--error);font-size:0.85em;display:none;margin-top:4px;"></span>
        </div>
        
        <div class="form-group">
          <label for="descripcion">Descripción</label>
          <textarea id="descripcion" name="descripcion" class="input" rows="4" placeholder="Agrega detalles importantes..."></textarea>
        </div>
        
        <div class="form-group">
          <label>Prioridad</label>
          <div class="priority-selector">
            <div class="priority-option alta" onclick="selectPriority('alta')">
              <span class="priority-dot alta"></span>
              <span>Alta</span>
              <input type="radio" name="prioridad" value="alta" id="alta" style="display: none;">
            </div>
            <div class="priority-option media selected" onclick="selectPriority('media')">
              <span class="priority-dot media"></span>
              <span>Media</span>
              <input type="radio" name="prioridad" value="media" id="media" checked style="display: none;">
            </div>
            <div class="priority-option baja" onclick="selectPriority('baja')">
              <span class="priority-dot baja"></span>
              <span>Baja</span>
              <input type="radio" name="prioridad" value="baja" id="baja" style="display: none;">
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <label for="fecha_vencimiento">Fecha de vencimiento</label>
          <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" class="input" required>
          <span id="error-fecha" style="color:var(--error);font-size:0.85em;display:none;margin-top:4px;"></span>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--space-4);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-2);">
            <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z" stroke-width="2"/>
            <path d="M17 21v-8H7v8M7 3v5h8" stroke-width="2"/>
          </svg>
          Crear Tarea
        </button>
      </form>
    </div>
  </div>

  <script>
  // Selector de prioridad interactivo
  function selectPriority(priority) {
    document.querySelectorAll('.priority-option').forEach(option => {
      option.classList.remove('selected');
    });
    document.querySelector(`.priority-option.${priority}`).classList.add('selected');
    document.getElementById(priority).checked = true;
  }
  
  // Establecer fecha mínima como hoy
  document.getElementById('fecha_vencimiento').min = new Date().toISOString().split('T')[0];
  
  // Validación del formulario
  document.getElementById('taskForm').addEventListener('submit', function(e) {
    const titulo = document.getElementById('titulo');
    const fecha = document.getElementById('fecha_vencimiento');
    let valid = true;

    if (titulo.value.trim().length < 3) {
      e.preventDefault();
      titulo.style.borderColor = 'var(--error)';
      document.getElementById('error-titulo').textContent = 'El título debe tener al menos 3 caracteres.';
      document.getElementById('error-titulo').style.display = 'block';
      valid = false;
    }

    if (!fecha.value) {
      e.preventDefault();
      fecha.style.borderColor = 'var(--error)';
      document.getElementById('error-fecha').textContent = 'Debes seleccionar una fecha de vencimiento.';
      document.getElementById('error-fecha').style.display = 'block';
      valid = false;
    }
    // Comparar solo año, mes y día
    if (fecha.value) {
      const fechaSeleccionada = new Date(fecha.value + 'T00:00:00');
      const hoy = new Date();
      hoy.setHours(0,0,0,0);
      if (fechaSeleccionada < hoy) {
        e.preventDefault();
        fecha.style.borderColor = 'var(--error)';
        document.getElementById('error-fecha').textContent = 'La fecha de vencimiento no puede ser anterior a hoy.';
        document.getElementById('error-fecha').style.display = 'block';
        valid = false;
      }
    }

    if (valid) {
      document.getElementById('error-titulo').style.display = 'none';
      document.getElementById('error-fecha').style.display = 'none';
    }
  });

  // Validación en tiempo real título
  document.getElementById('titulo').addEventListener('input', function() {
    if (this.value.trim().length >= 3) {
      this.style.borderColor = '';
      document.getElementById('error-titulo').style.display = 'none';
    } else {
      this.style.borderColor = 'var(--error)';
      document.getElementById('error-titulo').textContent = 'El título debe tener al menos 3 caracteres.';
      document.getElementById('error-titulo').style.display = 'block';
    }
  });

  // Validación en tiempo real fecha
  document.getElementById('fecha_vencimiento').addEventListener('input', function() {
    if (this.value) {
      this.style.borderColor = '';
      document.getElementById('error-fecha').style.display = 'none';
    } else {
      this.style.borderColor = 'var(--error)';
      document.getElementById('error-fecha').textContent = 'Debes seleccionar una fecha de vencimiento.';
      document.getElementById('error-fecha').style.display = 'block';
    }
  });
  </script>
</body>
</html>