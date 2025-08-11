<?php
require 'includes/config.php';
require 'includes/auth.php';
redirectIfNotLoggedIn();

if (isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("UPDATE tareas SET estado = 'completada' WHERE id = ? AND usuario_fk = ?");
        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
        
        $_SESSION['swal'] = [
            'icon' => 'success',
            'title' => '¡Tarea completada!',
            'text' => 'La tarea ha sido marcada como completada'
        ];
    } catch(PDOException $e) {
        $_SESSION['swal'] = [
            'icon' => 'error',
            'title' => 'Error',
            'text' => 'Error al completar la tarea: ' . $e->getMessage()
        ];
    }
}

header("Location: index.php");
exit();
?>