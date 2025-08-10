<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (!isset($_SESSION["_codigo"]) || !isset($_SESSION["expiracion_codigo"])) {
    header("Location: recuperar.php");
}

if (isset($_SESSION["erroremail"])) {
    $error = $_SESSION["erroremail"];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $codigo = implode('', $_POST['codigo']);

    if (strlen($codigo) < 6) {
        $error = "Codigo invalido, intenta obtener uno nuevo";
    } elseif (time() > $_SESSION["expiracion_codigo"]) {
        $error = "El codigo ha expirado, intenta obtener uno nuevo";
        unset($_SESSION["_codigo"], $_SESSION["expiracion_codigo"]);
    } elseif ($codigo !== $_SESSION["_codigo"]) {
        $error = "El codigo no es valido, si el problema persiste intenta generar uno nuevo";
    } else {
        $_SESSION["confirmacion"] = 1;
        header("Location: reset_password.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Codigo</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f5f7fa;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        .btn {
            text-decoration: none;
            position: absolute;
            left: 300px;
            width: 200px;
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
            width: 320px;
            text-align: center;
            height: auto;
        }

        .code-inputs {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .code-inputs input {
            width: 40px;
            height: 50px;
            text-align: center;
            font-size: 18px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }

        button {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            width: 100%;
            margin-bottom: 10px;
        }

        button:hover {
            background-color: #1d4ed8;
        }

        .reenviar {
            font-size: 0.9rem;
            font-weight: 100;
            text-decoration: none;
            color: #6b7280;
        }
    </style>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>
    <div style="text-align: center; position: relative">
        <a id="red-log" href="login.php" class="btn btn-outline">Volver a iniciar sesion</a>
        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" style="margin-top: var(--space-3);">
            <rect width="40" height="40" rx="8" fill="#2563EB" />
            <path d="M20 12L12 17V23L20 28L28 23V17L20 12Z" fill="white" />
            <path d="M20 28V20" stroke="white" stroke-width="2" />
        </svg>
        <h1>TaskApp</h1>
        <p class="text-muted">Gestion profesional de tareas</p>
    </div>

    <div class="card">
        <form method="POST">
            <p style="text-align: left;">Ingresa el codigo revisido en tu direccion de correo</p>
            <div class="code-inputs">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="text" name="codigo[]" maxlength="1" required>
                <?php endfor; ?>
            </div>
            <button type="submit">Verificar codigo</button>
        </form>
        <a href="recuperar.php" class="reenviar">Generar nuevo codigo</a>

        <?php if (isset($error)): ?>
            <div style="background: #FEE2E2; color: var(--error); padding: var(--space-3); 
             border-radius: var(--radius-sm); margin-top: var(--space-4); 
             display: flex; align-items: center; gap: var(--space-2);">
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.querySelectorAll('.code-inputs input').forEach((input, index, arr) => {
            input.addEventListener('input', () => {
                if (input.value.length === 1 && index < arr.length - 1) {
                    arr[index + 1].focus();
                }
            });
        });

    </script>

</body>

</html>