<?php
// Iniciar sesión al principio para que las redirecciones funcionen
session_start();

require_once __DIR__ . '/../config/db.php';

// Cabeceras de seguridad y caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirección si ya hay sesión activa
if (isset($_SESSION['nombre_tipo'])) {
    $rol = $_SESSION['nombre_tipo'];
    $rutas = ['admin' => 'dashboard.php'];
    header('Location: ' . ($rutas[$rol] ?? 'login.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documento = intval($_POST['documento']);
    $pin = intval($_POST['pin']);
    $password = $_POST['password'];

    if (empty($documento) || empty($pin) || empty($password)) {
        $error = 'Todos los campos son obligatorios';
    } else {
        try {
            $db = new Database();
            $pdo = $db->conectar();

            // Validamos Documento y PIN en la consulta
            $sql = 'SELECT u.documento, u.nombre_completo, u.pin, u.password, t.nombre_tipo, t.id_tipo 
                    FROM user_ u 
                    INNER JOIN tipo_usuario t ON u.id_tipo = t.id_tipo 
                    WHERE u.documento = ? AND u.pin = ?';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$documento, $pin]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['documento'] = $user['documento'];
                $_SESSION['nombre_completo'] = $user['nombre_completo'];
                $_SESSION['id_tipo'] = $user['id_tipo'];
                $_SESSION['nombre_tipo'] = strtolower(trim($user['nombre_tipo']));

                $rol = $_SESSION['nombre_tipo'];
                $rutas = ['admin' => 'dashboard.php'];
                header('Location: ' . ($rutas[$rol] ?? 'login.php'));
                exit;
            } else {
                $error = 'Credenciales incorrectas (Documento, PIN o Contraseña)';
            }
        } catch (Exception $e) {
            $error = 'Error de conexión con el servidor.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Bootstrap Only</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container vh-100 d-flex align-items-center justify-content-center">
        <div class="col-12 col-sm-8 col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    <h2 class="text-center fw-bold mb-4 text-primary">INICIAR SESIÓN</h2>
                    <?php if ($error != ""): ?>
                        <div class="alert alert-danger py-2 small text-center" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Documento</label>
                            <input type="number" name="documento" class="form-control" placeholder="Ingrese documento" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">PIN</label>
                            <input type="password" name="pin" class="form-control" placeholder="Ingrese PIN" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-medium">Contraseña</label>
                            <input type="password" name="password" class="form-control" placeholder="Ingrese contraseña" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-bold shadow-sm">
                                Ingresar al Sistema
                            </button>
                        </div>
                    </form>
                    <div class="text-center mt-4">
                        <a href="../index.php" class="text-decoration-none text-muted small">
                            ← Volver al inicio
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>
</html>