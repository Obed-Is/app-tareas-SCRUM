<?php
function registrarUsuario($usuario, $correo, $contraseña, $pdo) {
    // Verificar si el usuario/correo ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? OR correo = ?");
    $stmt->execute([$usuario, $correo]);
    
    if ($stmt->fetch()) {
        return false; // Usuario/correo ya existe
    }
    
    // Crear nuevo usuario
    $hash = password_hash($contraseña, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, correo, contraseña) VALUES (?, ?, ?)");
    return $stmt->execute([$usuario, $correo, $hash]);
}

function loginUsuario($usuario, $contraseña, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? OR correo = ?");
    $stmt->execute([$usuario, $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($contraseña, $user['contraseña'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['usuario'];
        return true;
    }
    return false;
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('logout')) {
    function logout() {
        $_SESSION = array();
        session_destroy();
        header("Location: login.php");
        exit();
    }
}
?>