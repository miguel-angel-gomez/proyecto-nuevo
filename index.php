<?php
require_once __DIR__ . '/config/db.php';

$db = new Database();
$pdo = $db->conectar();

if (!$pdo) {
    die('<div class="alert alert-danger text-center mt-5"> Error de conexión a la base de datos</div>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $documento = intval($_POST['documento']);
    $pin = trim($_POST['pin']);

    // ✅ Buscar solo por documento, luego verificar PIN con password_verify
    $sql = "SELECT * FROM user_ WHERE documento = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$documento]);

    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($empleado && password_verify($pin, $empleado['pin'])) {
        // ✅ Bug corregido: paréntesis en el lugar correcto
        if (intval($empleado['estado']) !== 1) {
            $mensaje = "El usuario se encuentra inactivo.";
        } else {
            $sql = "SELECT * FROM asistencias WHERE documento = ? AND fecha_hora_sal IS NULL ORDER BY id_asistencia DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$documento]);

            $asistencia = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($asistencia) {
                $sql = "UPDATE asistencias
                    SET fecha_hora_sal = NOW(),
                        cantidad_horas = TIMESTAMPDIFF(MINUTE, fecha_hora_ent, NOW()) / 60
                    WHERE id_asistencia = ?";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$asistencia['id_asistencia']]);

                $mensaje = "Salida registrada correctamente";
            } else {
                $sql = "INSERT INTO asistencias (documento, fecha_hora_ent)
                    VALUES(?, NOW())";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$documento]);

                $mensaje = "Entrada registrada correctamente";
            }
        }
    } else {
        // ✅ Mensaje genérico sin revelar si el documento existe o no
        $mensaje = "Documento o PIN incorrecto";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Asistencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a, #111827, #1e293b);
        }

        .hero-card {
            backdrop-filter: blur(15px);
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 25px;
        }

        .glass-card {
            backdrop-filter: blur(15px);
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 20px;
            transition: .3s;
        }

        .glass-card:hover {
            transform: translateY(-8px);
        }

        .logo-circle {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #0dcaf0;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 30px;
            color: white;
            margin: auto;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px;
        }

        .btn-register {
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
        }

        .feature-icon {
            font-size: 45px;
        }

        footer {
            margin-top: 70px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark bg-opacity-50 border-bottom border-secondary">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="#"> <i class="bi bi-fingerprint text-info"></i>Systemcontrol</a>
            <a href="admin/login.php" class="btn btn-outline-info"> <i class="bi bi-shield-lock"></i>Administrador</a>
        </div>
    </nav>
    <div class="container py-5">
        <div class="row align-items-center">
            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-info">
                    <?= $mensaje ?>
                </div>
            <?php endif; ?>
            <div class="col-lg-6 mb-4">
                <div class="hero-card p-5 text-white h-100">
                    <span class="badge bg-dark border border-info text-info mb-3">Plataforma de Gestión</span>
                    <h1 class="display-4 fw-bold">Control de Asistencia</h1>
                    <p class="lead text-light mt-3">
                        Gestiona el registro de asistencia de manera rápida,
                        segura y organizada desde una plataforma moderna diseñada
                        para optimizar el control y seguimiento de aprendices. </p>
                    <hr class="border-secondary my-4">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="glass-card shadow-lg p-4 text-white">
                    <div class="logo-circle mb-3">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <h3 class="text-center fw-bold mb-4"> Registro de Asistencia </h3>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label"> Documento </label>
                            <div class="input-group">
                                <span class="input-group-text"> <i class="bi bi-person-badge"></i> </span>
                                <input type="text" name="documento" class="form-control" required maxlength="10" pattern="[0-9]{1,10}" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label"> PIN </label>
                            <div class="input-group">
                                <span class="input-group-text"> <i class="bi bi-key"></i> </span>
                                <input type="password" name="pin" class="form-control" required maxlength="4" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-info w-100 btn-register">
                            <i class="bi bi-check-circle-fill"></i> Registrar Asistencia
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="row mt-5 g-4">
            <div class="col-md-4">
                <div class="glass-card p-4 text-center text-white h-100">
                    <i class="bi bi-lightning-charge-fill text-warning feature-icon"></i>
                    <h5 class="mt-3"> Rápido </h5>
                    <p>Registro inmediato sin procesos complejos. </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card p-4 text-center text-white h-100">
                    <i class="bi bi-shield-lock-fill text-info feature-icon"></i>
                    <h5 class="mt-3"> Seguro </h5>
                    <p> Protección mediante autenticación y control. </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card p-4 text-center text-white h-100">
                    <i class="bi bi-graph-up-arrow text-success feature-icon"></i>
                    <h5 class="mt-3"> Eficiente </h5>
                    <p> Seguimiento y control centralizado. </p>
                </div>
            </div>
        </div>
    </div>
    <footer class="border-top border-secondary text-center text-light py-4">
        <div class="container">
            <p class="mb-1 fw-bold">Control de Asistencia </p>
            <small class="text-secondary"> © 2026 Todos los derechos reservados </small>
        </div>
    </footer>
</body>

</html>