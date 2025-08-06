<?php
require 'includes/config.php';
require 'includes/auth.php';
require_once __DIR__ . '/includes/router.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Obtener la tarea a editar
$stmt = $pdo->prepare("SELECT * FROM tareas WHERE id = ? AND usuario_fk = ?");
$stmt->execute([$_GET['id'], $_SESSION['user_id']]);
$tarea = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tarea) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $prioridad = $_POST['prioridad'];
    $fecha_final = $_POST['fecha_final'];
    $estado = $_POST['estado'];
    
    $stmt = $pdo->prepare("UPDATE tareas SET titulo = ?, descripcion = ?, prioridad = ?, fecha_final = ?, estado = ? WHERE id = ? AND usuario_fk = ?");
    $stmt->execute([$titulo, $descripcion, $prioridad, $fecha_final, $estado, $_GET['id'], $_SESSION['user_id']]);
    
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar Tarea | TaskApp</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
  </style>
</head>
<body>
  <div class="container" style="max-width: 600px;">
    <header class="app-header">
      <div>
        <h1>Editar Tarea</h1>
        <p class="text-muted">Actualiza los detalles de tu tarea</p>
      </div>
      <a href="index.php" class="btn btn-outline">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-2);">
          <path d="M19 12H5M12 19l-7-7 7-7" stroke-width="2"/>
        </svg>
        Cancelar
      </a>
    </header>
    
    <div class="card">
      <form method="post" id="editTaskForm">
        <div class="form-group">
          <label for="titulo">Título</label>
          <input type="text" id="titulo" name="titulo" class="input" value="Revisar informe trimestral" required>
        </div>
        
        <div class="form-group">
          <label for="descripcion">Descripción</label>
          <textarea id="descripcion" name="descripcion" class="input" rows="4">Analizar métricas del Q3 y preparar presentación para el equipo directivo.</textarea>
        </div>
        
        <div class="form-group">
          <label>Estado</label>
          <div class="status-selector">
            <div class="status-option selected" onclick="selectStatus('pendiente')">
              <span class="status-badge pendiente">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-1);">
                  <path d="M12 8v4l3 3" stroke-width="2"/>
                  <circle cx="12" cy="12" r="10" stroke-width="2"/>
                </svg>
                Pendiente
              </span>
              <input type="radio" name="estado" value="pendiente" id="pendiente" checked style="display: none;">
            </div>
            <div class="status-option" onclick="selectStatus('completada')">
              <span class="status-badge completada">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-1);">
                  <path d="M20 6L9 17l-5-5" stroke-width="2"/>
                </svg>
                Completada
              </span>
              <input type="radio" name="estado" value="completada" id="completada" style="display: none;">
            </div>
          </div>
        </div>
        
        <div class="flex" style="gap: var(--space-4);">
          <div class="form-group" style="flex: 1;">
            <label for="prioridad">Prioridad</label>
            <select id="prioridad" name="prioridad" class="input">
              <option value="alta">Alta</option>
              <option value="media" selected>Media</option>
              <option value="baja">Baja</option>
            </select>
          </div>
         
          <div class="form-group" style="flex: 1;">
            <label for="fecha_vencimiento">Fecha vencimiento</label>
            <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" class="input" value="2023-06-15" required>
          </div>
        </div>
        
        <div class="flex" style="gap: var(--space-3); margin-top: var(--space-5);">
          <button type="submit" class="btn btn-primary" style="flex: 1;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-2);">
              <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke-width="2"/>
              <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke-width="2"/>
            </svg>
            Guardar Cambios
          </button>
          
          <a href="delete_task.php?id=1" class="btn btn-outline" style="flex: 1;" onclick="return confirm('¿Eliminar esta tarea permanentemente?')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-2);">
              <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/>
            </svg>
            Eliminar
          </a>
        </div>
      </form>
    </div>
  </div>

  <script>
  // Selector de estado interactivo
  function selectStatus(status) {
    document.querySelectorAll('.status-option').forEach(option => {
      option.classList.remove('selected');
    });
    document.querySelector(`#${status}`).parentElement.classList.add('selected');
    document.getElementById(status).checked = true;
  }
  
  // Validación del formulario
  document.getElementById('editTaskForm').addEventListener('submit', function(e) {
    const titulo = document.getElementById('titulo');
    const fechaVencimiento = document.getElementById('fecha_vencimiento');
    
    if (titulo.value.trim().length < 3) {
      e.preventDefault();
      titulo.style.borderColor = 'var(--error)';
    }
    
    if (!fechaVencimiento.value) {
      e.preventDefault();
      fechaVencimiento.style.borderColor = 'var(--error)';
    }
  });
  
  // Resetear estilos al corregir
  document.getElementById('titulo').addEventListener('input', function() {
    if (this.value.trim().length >= 3) {
      this.style.borderColor = '';
    }
  });
  </script>
</body>
</html>