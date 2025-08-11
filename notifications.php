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
                      AND fecha_final BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
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

if (isset($_GET['completar'])) {
    $tareaId = $_GET['completar'];
    $stmt = $pdo->prepare("UPDATE tareas SET estado = 'completada' WHERE id = ? AND usuario_fk = ?");
    $stmt->execute([$tareaId, $_SESSION['user_id']]);
    header("Location: notifications.php");
    exit();
}

if (isset($_GET["eliminar"])) {
    $tareaId = $_GET["eliminar"];
    $stmt = $pdo->prepare("DELETE FROM tareas WHERE id = ? AND usuario_fk = ?");
    $stmt->execute([$tareaId, $_SESSION['user_id']]);
    header("Location: notifications.php");
    exit();
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        .notificaciones-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .notificacion-card {
            background: #fff;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 150px;
        }

        .notificacion-header {
            margin-bottom: 0.8rem;
        }

        .notificacion-header h3 {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin: 0;
            font-size: 1.2rem;
            color: #333;
        }

        .fecha {
            display: block;
            font-size: 0.9rem;
            color: #777;
            margin-top: 0.3rem;
        }

        .notificacion-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            text-decoration: none;
        }

        .btn-eliminar {
            background: #E53935;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
        }

        span {
            font-size: 0.75rem;
            padding: var(--space-1) var(--space-2);
            background: var(--background);
            border-radius: var(--radius-sm);
            color: var(--text-light);
            overflow-wrap: break-word;
            word-break: break-word;
            max-width: 100%;
            margin-right: 10px;
        }


        .btn-eliminar:hover {
            background: #d32f2f;
        }

        .notificacion-card {
            background: #fff;
            padding: 1rem;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-left: 3px solid #10b981;
        }

        .notificacion-header h3 {
            font-size: 1.15rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .fecha {
            font-size: 0.85rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .notificacion-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 12px;
        }

        .btn.btn-primary {
            background: #2563eb;
            color: white;
            border: none;
            padding: 0.45rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn.btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-eliminar {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.45rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-eliminar:hover {
            background: #dc2626;
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
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    style="margin-right: var(--space-2);">
                    <path d="M19 12H5M12 19l-7-7 7-7" stroke-width="2" />
                </svg>
                Volver
            </a>
        </header>

        <?php if (empty($notificaciones) && empty($vencidas)): ?>
            <div class="card empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0" stroke-width="2" />
                </svg>
                <h3>No hay notificaciones</h3>
                <p>No tienes tareas próximas a vencer o vencidas</p>
            </div>
        <?php else: ?>
            <div class="notificaciones-container">
                <?php if (!empty($vencidas)): ?>
                    <?php foreach ($vencidas as $venc): ?>

                        <div class="notificacion-card">
                            <div class="notificacion-header">
                                <h3 style="margin-bottom: 7px;"><?php echo $venc["titulo"] ?></h3>
                            </div>
                            <div class="spans">
                                <span>
                                    Vencio el
                                    <?php echo date('d/m/Y', strtotime($venc["fecha_final"])); ?>
                                </span>
                            </div>
                            <div class="notificacion-actions">
                                <a href="notifications.php?eliminar=<?php echo $venc["id"] ?>" class="btn btn-eliminar"
                                    title="Eliminar">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        style="vertical-align: middle; margin-right: 6px;">
                                        <line x1="18" y1="6" x2="6" y2="18" stroke-width="2" />
                                        <line x1="6" y1="6" x2="18" y2="18" stroke-width="2" />
                                    </svg>
                                    Eliminar
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php foreach ($notificaciones as $noti): ?>
                    <div class="notificacion-card">
                        <div class="notificacion-header">
                            <h3 style="margin-bottom: 7px;"><?php echo $noti["titulo"] ?></h3>
                        </div>
                        <div class="spans">
                            <span>
                                Vence el
                                <?php echo date('d/m/Y', strtotime($noti["fecha_final"])); ?>
                            </span>
                            <span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    style="margin-right: 6px;">
                                    <path d="M12 8v4l3 3" stroke-wi dth="2" />
                                    <circle cx="12" cy="12" r="10" stroke-width="2" />
                                </svg>
                                Pendiente
                            </span>
                        </div>
                        <div class="notificacion-actions">
                            <a href="notifications.php?completar=<?php echo $noti["id"] ?>" class="btn btn-primary"
                                title="Completar">✔ Completar</a>
                            <a href="notifications.php?eliminar=<?php echo $noti["id"] ?>" class="btn btn-eliminar"
                                title="Eliminar">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    style="vertical-align: middle; margin-right: 6px;">
                                    <line x1="18" y1="6" x2="6" y2="18" stroke-width="2" />
                                    <line x1="6" y1="6" x2="18" y2="18" stroke-width="2" />
                                </svg>
                                Eliminar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.querySelectorAll('.btn-eliminar').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                Swal.fire({
                    title: '¿Eliminar tarea?',
                    text: 'Esta acción no se puede deshacer. ¿Seguro que quieres eliminar esta tarea?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444', // var(--error)
                    cancelButtonColor: '#64748b',  // var(--text-light)
                    background: '#f8fafc',         // var(--background)
                    color: '#1e293b',              // var(--text)
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        popup: 'swal2-custom-popup',
                        confirmButton: 'swal2-confirm-custom',
                        cancelButton: 'swal2-cancel-custom'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = btn.href;
                    }
                });
            });
        });
    </script>
</body>

</html>