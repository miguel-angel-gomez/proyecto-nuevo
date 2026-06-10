<?php
session_start();

// Control de acceso para el administrador
if (!isset($_SESSION['nombre_tipo']) || $_SESSION['nombre_tipo'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../config/db.php';

$db = new Database();
$pdo = $db->conectar();

if ($pdo === null) {
    die("Error de conexión a la base de datos");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-4">

        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 45px; height: 45px;">
                    <?php
                    $nombre_sesion = $_SESSION['nombre_completo'] ?? 'Admin';
                    $iniciales = strtoupper(substr($nombre_sesion, 0, 1));
                    if (($pos = strpos($nombre_sesion, ' ')) !== false) {
                        $iniciales .= strtoupper(substr($nombre_sesion, $pos + 1, 1));
                    }
                    echo htmlspecialchars($iniciales);
                    ?>
                </div>
                <div>
                    <h6 class="m-0 fw-bold"><?= htmlspecialchars($nombre_sesion) ?></h6>
                    <small class="text-muted">
                        <span class="badge bg-info text-dark text-uppercase"><?= htmlspecialchars($_SESSION['nombre_tipo']) ?></span>
                    </small>
                </div>
            </div>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm px-3"> <i class="fas fa-sign-out-alt me-1"></i> Cerrar sesión </a>
        </div>
        <div class="card shadow-sm col-md-8 mx-auto">
            <div class="card-body">
                <h2 class="card-title mb-4 text-center text-primary fw-bold"><i class="fa-solid fa-gauge me-2"></i>GESTION EMPLEADOS</h2>
                <p class="text-muted text-center mb-4">Bienvenido al sistema. Seleccione una de las siguientes opciones para gestionar el personal.</p>
                <div class="list-group shadow-sm">
                    <a href="empleados_crud.php?accion=listar" class="list-group-item list-group-item-action py-3 d-flex align-items-center">
                        <i class="fa-solid fa-users me-3 text-primary fs-5"></i>
                        <div>
                            <strong class="d-block">Listado Empleados</strong>
                            <span class="text-muted small">Ver y modificar personal existente.</span>
                        </div>
                    </a>
                    <a href="empleados_crud.php?accion=crear_form" class="list-group-item list-group-item-action py-3 d-flex align-items-center">
                        <i class="fa-solid fa-user-plus me-3 text-success fs-5"></i>
                        <div>
                            <strong class="d-block">Registrar Nuevo Empleado</strong>
                            <span class="text-muted small">Crear un nuevo empleado al sistema.</span>
                        </div>
                    </a>
                    <a href="reportes.php" class="list-group-item list-group-item-action py-3 d-flex align-items-center">
                        <i class="fa-solid fa-calendar me-4 text-secondary fs-5"></i>
                        <div>
                            <strong class="d-block">Reporte de asistencia</strong>
                            <span class="text-muted small">Ver reporte de asistencia.</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>