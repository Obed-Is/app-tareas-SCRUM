<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Obtener tareas próximas a vencer (próximas 48 horas)
$stmt = $pdo->prepare("SELECT * FROM tareas 
                      WHERE usuario_fk = ? 
                      AND estado = 'pendiente'
                      AND fecha_final BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 DAY)
                      ORDER BY fecha_final ASC");
$stmt->execute([$_SESSION['user_id']]);
$notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener tareas vencidas
$stmt = $pdo->prepare("SELECT * FROM tareas 
                      WHERE usuario_fk = ? 
                      AND estado = 'pendiente'
                      AND fecha_final < NOW()
                      ORDER BY fecha_final ASC");
$stmt->execute([$_SESSION['user_id']]);
$vencidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Marcar notificaciones como leídas
if (!empty($notificaciones) || !empty($vencidas)) {
    $stmt = $pdo->prepare("UPDATE usuarios SET ultima_vista_notificaciones = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones | TaskApp</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        .notification-badge {
            display: inline-flex;
            align-items: center;
            padding: var(--space-1) var(--space-2);
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-warning {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        .badge-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--error);
        }
        .time-remaining {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-top: var(--space-2);
        }
        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin: var(--space-5) 0 var(--space-3);
            padding-bottom: var(--space-2);
            border-bottom: 1px solid var(--border);
        }
        .empty-state {
            text-align: center;
            padding: var(--space-6) var(--space-4);
            color: var(--text-light);
        }
        .empty-state svg {
            width: 48px;
            height: 48px;
            margin-bottom: var(--space-3);
            color: var(--text-light);
        }
        .buttons {
            display: flex;
            gap: var(--space-2);
        }
        .btn {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: var(--space-3);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
            text-align: center;
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        .btn .icon {
            margin-right: var(--space-1);
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 800px;">
        <header class="app-header">
            <div>
                <h1>Notificaciones</h1>
                <p class="text-muted">Tareas pendientes por atender</p>
            </div>
            <a href="index.php" class="btn btn-outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-2);">
                    <path d="M19 12H5M12 19l-7-7 7-7" stroke-width="2"/>
                </svg>
                Volver
            </a>
        </header>

        <?php if (empty($notificaciones) && empty($vencidas)): ?>
            <div class="card empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0" stroke-width="2"/>
                </svg>
                <h3>No hay notificaciones</h3>
                <p>No tienes tareas próximas a vencer o vencidas</p>
            </div>
        <?php else: ?>
            <!-- Tareas vencidas -->
            <?php if (!empty($vencidas)): ?>
                <h2 class="section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-2); vertical-align: middle; color: var(--error);">
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-width="2"/>
                    </svg>
                    Tareas vencidas
                </h2>
                
                <div class="task-grid">
                    <?php foreach ($vencidas as $tarea): ?>
                        <div class="card task-card">
                            <div class="flex" style="justify-content: space-between; align-items: flex-start;">
                                <h3><?php echo htmlspecialchars($tarea['titulo']); ?></h3>
                                <span class="notification-badge badge-danger">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-1);">
                                        <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2"/>
                                    </svg>
                                    Vencida
                                </span>
                            </div>
                            
                            <?php if (!empty($tarea['descripcion'])): ?>
                                <p class="text-muted" style="margin: var(--space-2) 0;"><?php echo htmlspecialchars($tarea['descripcion']); ?></p>
                            <?php endif; ?>
                            
                            <div class="time-remaining">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-1); vertical-align: middle;">
                                    <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2"/>
                                </svg>
                                Venció el <?php echo date('d/m/Y', strtotime($tarea['fecha_final'])); ?>
                            </div>
                            
                            <div class="buttons" style="margin-top: var(--space-4);">
                                <a href="edit_task.php?id=<?php echo $tarea['id']; ?>" class="btn" title="Reagendar">
                                    <span class="icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke-width="2"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke-width="2"/>
                                        </svg>
                                    </span>
                                    <span class="text">Reagendar</span>
                                </a>
                                <a href="index.php?completar=<?php echo $tarea['id']; ?>" class="btn" title="Completar">
                                    <span class="icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M20 6L9 17l-5-5" stroke-width="2"/>
                                        </svg>
                                    </span>
                                    <span class="text">Completar</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Tareas próximas a vencer -->
            <?php if (!empty($notificaciones)): ?>
                <h2 class="section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-2); vertical-align: middle; color: var(--warning);">
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-width="2"/>
                    </svg>
                    Próximas a vencer
                </h2>
                
                <div class="task-grid">
                    <?php foreach ($notificaciones as $tarea): ?>
                        <div class="card task-card">
                            <div class="flex" style="justify-content: space-between; align-items: flex-start;">
                                <h3><?php echo htmlspecialchars($tarea['titulo']); ?></h3>
                                <span class="notification-badge badge-warning">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-1);">
                                        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2"/>
                                    </svg>
                                    Próxima
                                </span>
                            </div>
                            
                            <?php if (!empty($tarea['descripcion'])): ?>
                                <p class="text-muted" style="margin: var(--space-2) 0;"><?php echo htmlspecialchars($tarea['descripcion']); ?></p>
                            <?php endif; ?>
                            
                            <div class="time-remaining">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin-right: var(--space-1); vertical-align: middle;">
                                    <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2"/>
                                </svg>
                                <?php 
                                    $fechaVencimiento = new DateTime($tarea['fecha_final']);
                                    $hoy = new DateTime();
                                    $diferencia = $hoy->diff($fechaVencimiento);
                                    
                                    if ($diferencia->d == 0) {
                                        echo "Vence hoy";
                                    } elseif ($diferencia->d == 1) {
                                        echo "Vence mañana";
                                    } else {
                                        echo "Vence en " . $diferencia->d . " días";
                                    }
                                ?>
                                (<?php echo date('d/m/Y', strtotime($tarea['fecha_final'])); ?>)
                            </div>
                            
                            <div class="buttons" style="margin-top: var(--space-4);">
                                <a href="edit_task.php?id=<?php echo $tarea['id']; ?>" class="btn" title="Editar">
                                    <span class="icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke-width="2"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke-width="2"/>
                                        </svg>
                                    </span>
                                    <span class="text">Editar</span>
                                </a>
                                <a href="index.php?completar=<?php echo $tarea['id']; ?>" class="btn" title="Completar">
                                    <span class="icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M20 6L9 17l-5-5" stroke-width="2"/>
                                        </svg>
                                    </span>
                                    <span class="text">Completar</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>