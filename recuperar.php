<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
include "sendEmails/envio_codigo_recuperacion.php";


if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $email_recuperacion = trim($_POST["email"]) ?? '';
    if (empty($email_recuperacion) || !filter_var($email_recuperacion, FILTER_VALIDATE_EMAIL)) {
        $error = "Ingresa una direccion de correo electronico valida";
    } else {

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE correo = ?");
        $stmt->execute([$email_recuperacion]);
        $comprobacionCorreo = $stmt->fetchColumn();

        if ($comprobacionCorreo <= 0) {
            $error = "El correo no esta asociado a nuestros servicios";
        } else {

            $codigo = "";
            for ($i = 0; $i < 6; $i++) {
                $codigo .= random_int(0, 9);
            }
            $_SESSION["email_recuperacion"] = $email_recuperacion;
            $_SESSION["_codigo"] = $codigo;
            $_SESSION["expiracion_codigo"] = time() + 120;
            try {
                envio_codigo($email_recuperacion);
            } catch (\Throwable $th) {
                $_SESSION["erroremail"] = "Ocurrio un error al intentar enviar el codigo, intente de nuevo o mas tarde";
            }
            header("Location: codigo_recuperacion.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f7fa;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        p {
            color: #6b7280;
            margin-bottom: 20px;
        }

        .card {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            width: 500px;
            text-align: center;
        }

        label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-weight: bold;
        }


        button {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            margin-top: 15px;
            cursor: pointer;
            font-size: 14px;
            width: 100%;
        }

        button:hover {
            background-color: #1d4ed8;
        }

        a {
            text-decoration: none;
            font-size: 0.875rem;
            color: var(--text-light);
            text-decoration: none;
        }

        .link {
            margin-top: 15px;
            display: block;
        }
    </style>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>

    <div style="text-align: center; margin-bottom: var(--space-6);">
        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" style="margin-bottom: var(--space-3);">
            <rect width="40" height="40" rx="8" fill="#2563EB" />
            <path d="M20 12L12 17V23L20 28L28 23V17L20 12Z" fill="white" />
            <path d="M20 28V20" stroke="white" stroke-width="2" />
        </svg>
        <h1>TaskApp</h1>
        <p class="text-muted">Gestión profesional de tareas</p>
    </div>

    <form method="post" class="card">
        <div class="form-group">
            <label for="email">Introduce tu correo</label>
            <input type="email" class="input" name="email" id="email" placeholder="google@gmail.com" required>
            <button type="submit">Enviar código</button>
            <a href="login.php" class="link">Volver al inicio de sesión</a>
        </div>

        <?php if (isset($error)): ?>
            <div style="background: #FEE2E2; color: var(--error); padding: var(--space-3); 
             border-radius: var(--radius-sm); margin-top: var(--space-4); 
             display: flex; align-items: center; gap: var(--space-2);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" />
                </svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
    </form>

</body>

</html>