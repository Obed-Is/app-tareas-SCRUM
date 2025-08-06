<?php
require 'includes/config.php';
require 'includes/auth.php';
require_once __DIR__ . '/includes/router.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM tareas WHERE id = ? AND usuario_fk = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
}

header("Location: index.php");
exit();
?>