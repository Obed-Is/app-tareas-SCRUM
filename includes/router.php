<?php
// Verificar si la sesión no está iniciada antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lista blanca de páginas permitidas sin autenticación
$public_pages = ['login.php', 'register.php', 'recuperar.php', 'reset_password.php'];

$requested_page = basename($_SERVER['PHP_SELF']);

// Si no está logueado y no es una página pública
if (!isset($_SESSION['user_id']) && !in_array($requested_page, $public_pages)) {
    header("Location: login.php");
    exit();
}

// Si está logueado y trata de acceder a login/register
if (isset($_SESSION['user_id']) && in_array($requested_page, ['login.php', 'register.php'])) {
    header("Location: index.php");
    exit();
}
?>