<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Verificar conexión a la base de datos
if (!isset($conn)) {
    die("Error de conexión a la base de datos");
}

// Verificar si el usuario está logueado
redirectIfNotLoggedIn();

// Manejar completar tarea
if (isset($_GET['completar'])) {
    header("Location: complete_task.php?id=" . (int)$_GET['completar']);
    exit();
}

// Manejar eliminar tarea
if (isset($_GET['eliminar'])) {
    header("Location: delete_task.php?id=" . (int)$_GET['eliminar']);
    exit();
}

try {
    // Obtener las tareas del usuario actual con paginación
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Consulta para obtener tareas
    $stmt = $conn->prepare("SELECT * FROM tareas WHERE usuario_fk = ? ORDER BY fecha_inicio DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tareas = $stmt->fetchAll();
    
    // Consulta para contar total de tareas
    $stmt = $conn->prepare("SELECT COUNT(*) FROM tareas WHERE usuario_fk = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $totalTareas = $stmt->fetchColumn();
    $totalPaginas = ceil($totalTareas / $limit);
} catch(PDOException $e) {
    $_SESSION['error'] = "Error al cargar tareas: " . $e->getMessage();
    $tareas = [];
    $totalPaginas = 1;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tareas | TaskApp</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['swal'])): ?>
            <script>
                Swal.fire({
                    icon: '<?php echo $_SESSION['swal']['icon']; ?>',
                    title: '<?php echo $_SESSION['swal']['title']; ?>',
                    text: '<?php echo $_SESSION['swal']['text']; ?>',
                    confirmButtonColor: '#2563EB'
                });
            </script>
            <?php unset($_SESSION['swal']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <header class="app-header">
            <div>
                <h1>Mis Tareas</h1>
                <p class="text-muted">Hola, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            </div>
            <a href="logout.php" class="btn btn-outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-2);">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" stroke-width="2"/>
                </svg>
                Cerrar Sesión
            </a>
        </header>

        <div class="flex" style="gap: var(--space-3); margin-bottom: var(--space-4);">
            <a href="create_task.php" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-2);">
                    <path d="M12 4v16m8-8H4" stroke-width="2"/>
                </svg>
                Nueva Tarea
            </a>
            <a href="notifications.php" class="btn btn-outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-2);">
                    <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0" stroke-width="2"/>
                </svg>
                Notificaciones
            </a>
        </div>

        <?php if (empty($tareas)): ?>
            <div class="card" style="text-align: center; padding: var(--space-6);">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-bottom: var(--space-3); color: var(--text-light);">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-width="2"/>
                </svg>
                <h3>No hay tareas</h3>
                <p class="text-muted">Crea tu primera tarea para comenzar</p>
                <a href="create_task.php" class="btn btn-primary" style="margin-top: var(--space-3);">
                    Crear Tarea
                </a>
            </div>
        <?php else: ?>
            <div class="task-grid">
                <?php foreach ($tareas as $tarea): ?>
                    <div class="card task-card <?php echo htmlspecialchars($tarea['prioridad']); ?> <?php echo $tarea['estado'] === 'completada' ? 'completada' : ''; ?>">
                        <div class="flex" style="justify-content: space-between; align-items: flex-start;">
                            <h3><?php echo htmlspecialchars($tarea['titulo']); ?></h3>
                            <span class="status-badge <?php echo $tarea['estado'] === 'completada' ? 'completada' : 'pendiente'; ?>">
                                <?php if ($tarea['estado'] === 'completada'): ?>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-1);">
                                        <path d="M20 6L9 17l-5-5" stroke-width="2"/>
                                    </svg>
                                    Completada
                                <?php else: ?>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-1);">
                                        <path d="M12 8v4l3 3" stroke-width="2"/>
                                        <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    </svg>
                                    Pendiente
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($tarea['descripcion'])): ?>
                            <p class="text-muted" style="margin: var(--space-2) 0 var(--space-3);">
                                <?php echo htmlspecialchars($tarea['descripcion']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="task-meta">
                            <span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-1);">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <path d="M12 6v6l4 2" stroke-width="2"/>
                                </svg>
                                <?php echo date('d/m/Y', strtotime($tarea['fecha_inicio'])); ?>
                                -
                                <?php echo date('d/m/Y', strtotime($tarea['fecha_final'])); ?>
                            </span>
                            <span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-1);">
                                    <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z" stroke-width="2"/>
                                </svg>
                                <?php echo ucfirst(htmlspecialchars($tarea['prioridad'])); ?>
                            </span>
                        </div>
                        
                        <div class="flex" style="gap: var(--space-2); margin-top: var(--space-4);">
                            <a href="edit_task.php?id=<?php echo (int)$tarea['id']; ?>" class="btn btn-outline" style="flex: 1;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-1);">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke-width="2"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke-width="2"/>
                                </svg>
                                Editar
                            </a>
                            
                            <?php if ($tarea['estado'] === 'pendiente'): ?>
                                <a href="complete_task.php?id=<?php echo (int)$tarea['id']; ?>" class="btn btn-outline" style="flex: 1;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-1);">
                                        <path d="M20 6L9 17l-5-5" stroke-width="2"/>
                                    </svg>
                                    Completar
                                </a>
                            <?php endif; ?>
                            
                            <a href="delete_task.php?id=<?php echo (int)$tarea['id']; ?>" class="btn btn-outline" style="flex: 1;" onclick="return confirm('¿Eliminar esta tarea permanentemente?')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-1);">
                                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/>
                                </svg>
                                Eliminar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPaginas > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>