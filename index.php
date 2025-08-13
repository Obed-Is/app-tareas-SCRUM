<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$search = trim($_GET['search'] ?? '');
$order = $_GET['order'] ?? 'az';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$orderBy = "titulo ASC";
if ($order === 'za') {
    $orderBy = "titulo DESC";
}

// Construir consulta dinámica
$where = ["usuario_fk = ?"];
$params = [$_SESSION['user_id']];

// Búsqueda por palabra inicial (case-insensitive, solo títulos que comienzan con la búsqueda)
if ($search !== '') {
    // Permitir varias palabras separadas por espacio, pero solo usar la primera palabra para el filtro de inicio
    $firstWord = preg_split('/\s+/', $search, 2, PREG_SPLIT_NO_EMPTY)[0];
    $where[] = "LOWER(titulo) LIKE ?";
    $params[] = strtolower($firstWord) . '%';
}

// Filtro por fecha de vencimiento (rango)
if ($date_from !== '') {
    $where[] = "DATE(fecha_final) >= ?";
    $params[] = $date_from;
}
if ($date_to !== '') {
    $where[] = "DATE(fecha_final) <= ?";
    $params[] = $date_to;
}

$whereSQL = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT * FROM tareas WHERE $whereSQL ORDER BY $orderBy");
$stmt->execute($params);
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar completar tarea
if (isset($_GET['completar'])) {
    $taskId = $_GET['completar'];
    $stmt = $pdo->prepare("UPDATE tareas SET estado = 'completada' WHERE id = ? AND usuario_fk = ?");
    $stmt->execute([$taskId, $_SESSION['user_id']]);
    header("Location: index.php");
    exit();
}

// Procesar eliminar tarea
if (isset($_GET['eliminar'])) {
    $taskId = $_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM tareas WHERE id = ? AND usuario_fk = ?");
    $stmt->execute([$taskId, $_SESSION['user_id']]);
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tareas | TaskApp</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <header class="app-header">
            <div>
                <h1>Mis Tareas</h1>
                <p class="text-muted">Hola, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            </div>
            <a href="logout.php" class="btn btn-outline" id="btnLogout">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    style="margin-right: var(--space-2);">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" stroke-width="2" />
                </svg>
                Cerrar Sesión
            </a>
        </header>

        <!-- NUEVO: Barra de búsqueda y filtros avanzados -->
        <form method="get" class="flex"
            style="gap: var(--space-3); margin-bottom: var(--space-4); align-items: flex-end; flex-wrap: wrap;">
            <div>
                <label for="search" style="font-size:0.9rem;">Buscar tarea</label>
                <input type="text" id="search" name="search" class="input" placeholder="Ej: Informe Trimestral"
                    value="<?php echo htmlspecialchars($search); ?>" style="min-width:160px;">
            </div>
            <div>
                <label for="date_from" style="font-size:0.9rem;">Vencimiento desde</label>
                <input type="date" id="date_from" name="date_from" class="input"
                    value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            <div>
                <label for="date_to" style="font-size:0.9rem;">Vencimiento hasta</label>
                <input type="date" id="date_to" name="date_to" class="input"
                    value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            <div>
                <label for="order" style="font-size:0.9rem;">Ordenar</label>
                <select id="order" name="order" class="input">
                    <option value="az" <?php if($order==='az') echo 'selected'; ?>>A-Z</option>
                    <option value="za" <?php if($order==='za') echo 'selected'; ?>>Z-A</option>
                </select>
            </div>
            <button type="submit" class="btn btn-outline"
                style="height: 40px; margin-top: 24px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    style="margin-right: var(--space-2);">
                    <circle cx="11" cy="11" r="8" stroke-width="2" />
                    <path d="M21 21l-4.35-4.35" stroke-width="2" />
                </svg>
                Filtrar
            </button>
            <?php if($search !== '' || $order !== 'az' || $date_from !== '' || $date_to !== ''): ?>
            <a href="index.php" class="btn btn-outline"
                style="height: 40px; margin-top: 24px;">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="flex" style="gap: var(--space-3); margin-bottom: var(--space-4);">
            <a href="create_task.php" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    style="margin-right: var(--space-2);">
                    <path d="M12 4v16m8-8H4" stroke-width="2" />
                </svg>
                Nueva Tarea
            </a>
            <a href="notifications.php" class="btn btn-outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    style="margin-right: var(--space-2);">
                    <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0" stroke-width="2" />
                </svg>
                Notificaciones
            </a>
        </div>

        <?php if (empty($tareas)): ?>
        <div class="card" style="text-align: center; padding: var(--space-6);">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                style="margin-bottom: var(--space-3); color: var(--text-light);">
                <path
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    stroke-width="2" />
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
            <div
                class="card task-card <?php echo $tarea['prioridad']; ?> <?php echo $tarea['estado'] === 'completada' ? 'completada' : ''; ?>">
                <div class="flex" style="justify-content: space-between; align-items: center;">
                    <h3 style="max-width: 70%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <?php echo htmlspecialchars($tarea['titulo']); ?>
                    </h3>
                    <span
                        class="status-badge <?php echo $tarea['estado'] === 'completada' ? 'completada' : 'pendiente'; ?>"
                        style="min-width: 120px; text-align: center; display: flex; align-items: center; justify-content: center;">
                        <?php if ($tarea['estado'] === 'completada'): ?>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            style="margin-right: 6px;">
                            <path d="M20 6L9 17l-5-5" stroke-width="2" />
                        </svg>
                        Completada
                        <?php else: ?>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            style="margin-right: 6px;">
                            <path d="M12 8v4l3 3" stroke-width="2" />
                            <circle cx="12" cy="12" r="10" stroke-width="2" />
                        </svg>
                        Pendiente
                        <?php endif; ?>
                    </span>
                </div>
                <?php if (!empty($tarea['descripcion'])): ?>
                <p class="text-muted"
                    style="margin: var(--space-2) 0 var(--space-3); max-height: 4.5em; overflow: hidden; text-overflow: ellipsis;">
                    <?php echo htmlspecialchars($tarea['descripcion']); ?>
                </p>
                <?php endif; ?>
                <div class="task-meta">
                    <span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            style="margin-right: var(--space-1);">
                            <circle cx="12" cy="12" r="10" stroke-width="2" />
                            <path d="M12 6v6l4 2" stroke-width="2" />
                        </svg>
                        <?php echo date('d/m/Y', strtotime($tarea['fecha_inicio'])); ?> -
                        <?php echo date('d/m/Y', strtotime($tarea['fecha_final'])); ?>
                    </span>
                    <span class="prioridad-badge <?php echo $tarea['prioridad']; ?>">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            style="margin-right: var(--space-1);">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z" stroke-width="2" />
                        </svg>
                        <?php echo ucfirst($tarea['prioridad']); ?>
                    </span>
                </div>
                <div class="buttons" style="margin-top: var(--space-4);">
                    <button type="button" class="btn" title="Información" onclick="showInfoModal(<?php echo htmlspecialchars(json_encode([
                        'titulo' => $tarea['titulo'],
                        'descripcion' => $tarea['descripcion'],
                        'prioridad' => $tarea['prioridad'],
                        'fecha_inicio' => $tarea['fecha_inicio'],
                        'fecha_final' => $tarea['fecha_final'],
                        'estado' => $tarea['estado']
                    ])); ?>)">
                        <span class="icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 16v-4" />
                                <path d="M12 8h.01" />
                            </svg>
                        </span>
                        <span class="text">Información</span>
                    </button>
                    <a href="edit_task.php?id=<?php echo $tarea['id']; ?>" class="btn" title="Editar">
                        <span class="icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke-width="2" />
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke-width="2" />
                            </svg>
                        </span>
                        <span class="text">Editar</span>
                    </a>
                    <?php if ($tarea['estado'] === 'pendiente'): ?>
                    <a href="index.php?completar=<?php echo $tarea['id']; ?>" class="btn" title="Completar">
                        <span class="icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M20 6L9 17l-5-5" stroke-width="2" />
                            </svg>
                        </span>
                        <span class="text">Completar</span>
                    </a>
                    <?php endif; ?>
                    <a href="index.php?eliminar=<?php echo $tarea['id']; ?>" class="btn btn-eliminar" title="Eliminar">
                        <span class="icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    stroke-width="2" />
                            </svg>
                        </span>
                        <span class="text">Eliminar</span>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <script>
        document.getElementById('btnLogout').addEventListener('click', function (event) {
            event.preventDefault();
            Swal.fire({
                title: '¿Cerrar sesión?',
                text: "Estas seguro de querer cerrar sesión.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, cerrar sesión',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = this.href;
                }
            });
        });

        function showInfoModal(data) {
            Swal.fire({
                title: `${data.titulo}`,
                html: `
                    <div style="text-align:left; font-size:1rem;">
                        <div style="margin-bottom:12px;">
                            <span style="display:inline-flex;align-items:center;gap:6px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b">
                                    <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z" stroke-width="2"/>
                                </svg>
                                <b>Prioridad:</b>
                                <span style="color:${data.prioridad === 'alta' ? '#ef4444' : data.prioridad === 'media' ? '#f59e0b' : '#10b981'};font-weight:600;">
                                    ${data.prioridad.charAt(0).toUpperCase() + data.prioridad.slice(1)}
                                </span>
                            </span>
                        </div>
                        <div style="margin-bottom:12px;">
                            <span style="display:inline-flex;align-items:center;gap:6px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="${data.estado === 'completada' ? '#10b981' : '#ef4444'}">
                                    ${data.estado === 'completada'
                        ? '<path d="M20 6L9 17l-5-5" stroke-width="2"/>'
                        : '<path d="M12 8v4l3 3" stroke-width="2"/><circle cx="12" cy="12" r="10" stroke-width="2"/>'}
                                </svg>
                                <b>Estado:</b>
                                <span style="font-weight:600;">${data.estado.charAt(0).toUpperCase() + data.estado.slice(1)}</span>
                            </span>
                        </div>
                        <div style="margin-bottom:12px;">
                            <span style="display:inline-flex;align-items:center;gap:6px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <path d="M12 6v6l4 2" stroke-width="2"/>
                                </svg>
                                <b>Fecha inicio:</b>
                                <span>${data.fecha_inicio ? new Date(data.fecha_inicio).toLocaleDateString() : ''}</span>
                            </span>
                        </div>
                        <div style="margin-bottom:12px;">
                            <span style="display:inline-flex;align-items:center;gap:6px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                    <path d="M12 6v6l4 2" stroke-width="2"/>
                                </svg>
                                <b>Fecha vencimiento:</b>
                                <span>${data.fecha_final ? new Date(data.fecha_final).toLocaleDateString() : ''}</span>
                            </span>
                        </div>
                        <div style="margin-bottom:6px;">
                            <span style="display:inline-flex;align-items:center;gap:6px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b">
                                    <path d="M8 17l4 4 4-4M12 12v9" stroke-width="2"/>
                                    <rect x="4" y="4" width="16" height="8" rx="2" stroke-width="2"/>
                                </svg>
                                <b>Descripción:</b>
                            </span>
                            <div style="max-height:120px;overflow:auto;word-break:break-word;background:#f8fafc;padding:8px;border-radius:6px;margin-top:4px;">
                                ${data.descripcion ? data.descripcion : '<i style="color:#64748b;">Sin descripción</i>'}
                            </div>
                        </div>
                    </div>
                `,
                icon: false,
                confirmButtonText: 'Cerrar',
                width: 420,
                showCloseButton: true,
                customClass: {
                    popup: 'swal2-taskinfo'
                }
            });
        }

        // Eliminar tarea con SweetAlert2
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
    <style>
        .swal2-custom-popup {
            background: #f8fafc !important;
            color: #1e293b !important;
            border-radius: 10px !important;
        }

        .swal2-confirm-custom {
            background-color: #ef4444 !important;
            color: #fff !important;
            border-radius: 6px !important;
            font-weight: 500 !important;
            border: none !important;
        }

        .swal2-cancel-custom {
            background-color: #64748b !important;
            color: #fff !important;
            border-radius: 6px !important;
            font-weight: 500 !important;
            border: none !important;
            margin-left: 8px !important;
        }
    </style>
</body>

</html>