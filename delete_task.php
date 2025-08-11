<?php
require 'includes/config.php';
require 'includes/auth.php';
redirectIfNotLoggedIn();

if (isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM tareas WHERE id = ? AND usuario_fk = ?");
        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
        
        $_SESSION['swal'] = [
            'icon' => 'success',
            'title' => '¡Tarea eliminada!',
            'text' => 'La tarea ha sido eliminada correctamente'
        ];
    } catch(PDOException $e) {
        $_SESSION['swal'] = [
            'icon' => 'error',
            'title' => 'Error',
            'text' => 'Error al eliminar la tarea: ' . $e->getMessage()
        ];
    }
}

header("Location: index.php");
exit();
?>