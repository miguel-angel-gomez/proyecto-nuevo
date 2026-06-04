<?php
session_start();

// Control de acceso para el administrador
if (!isset($_SESSION['nombre_tipo']) || $_SESSION['nombre_tipo'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

$db = new Database();
$pdo = $db->conectar();

if ($pdo === null) {
    die("Error de conexión a la base de datos");
}

$accion = $_GET['accion'] ?? 'listar';
$mensaje = '';
$tipo_usuario = obtenertodoslostipos($pdo);
$area_usu = obtenertodaslasareas($pdo);

$empleados = [];
if ($accion === 'listar') {
    $empleados = obtenerEmpleados($pdo);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Guardar Empleado Nuevo
    if (isset($_POST['crear'])) {
        $documento = intval($_POST['documento']);
        $nombre = trim($_POST['nombre_completo']);
        $pin = intval($_POST['pin']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $id_tipo = isset($_POST['id_tipo']) && $_POST['id_tipo'] !== '' ? intval($_POST['id_tipo']) : NULL;
        $fecha_creacion = date('Y-m-d H:i:s');
        $id_area = isset($_POST['id_area']) && $_POST['id_area'] !== '' ? intval($_POST['id_area']) : NULL;
        $estado = isset($_POST['estado']) ? intval($_POST['estado']) : 1;

        if ($nombre !== '' && $documento > 0) {
            $id_nuevo = crearEmpleado($pdo, $documento, $nombre, $pin, $password, $id_tipo, $fecha_creacion, $id_area, $estado);
            $mensaje = $id_nuevo ? "Empleado creado con éxito. Cédula: $documento" : "Error al insertar el registro.";
        } else {
            $mensaje = "El documento y el nombre son campos obligatorios.";
        }
    }

    // Guardar Cambios de Edición
    elseif (isset($_POST['actualizar'])) {
        $documento = intval($_POST['documento']);
        $nombre = trim($_POST['nombre_completo']);
        $id_tipo = intval($_POST['id_tipo']);
        $id_area = intval($_POST['id_area']);
        $estado = intval($_POST['estado']);

        if ($documento > 0 && $nombre !== '') {
            $ok = editarEmpleado($pdo, $documento, $nombre, $id_tipo, $id_area, $estado);
            if ($ok) {
                $empleados = obtenerEmpleados($pdo);
                $mensaje = "Empleado actualizado correctamente.";
            } else {
                $mensaje = "No se detectaron cambios.";
            }
        } else {
            $mensaje = "Datos inválidos para actualizar.";
        }
    } elseif (isset($_POST['eliminar'])) {

        $documento = intval($_POST['documento']);

        if ($documento > 0) {

            $ok = eliminarEmpleado($pdo, $documento);

            if ($ok) {
                $empleados = obtenerEmpleados($pdo);
                $mensaje = "Empleado eliminado correctamente.";
            } else {
                $mensaje = "No se pudo eliminar el empleado.";
            }
        }
    }
}
if ($accion === 'editar_form') {
    $id_buscar = intval($_GET['documento'] ?? 0);
    $empleado_editar = buscarEmpleadoPorDocumento($pdo, $id_buscar);
}

$asistencias = [];
if ($accion === "listar") {
    $asistencias = obtenerReporteAsistencias($pdo);
}


?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Empleados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-4 mb-5">
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa-solid fa-circle-info me-2"></i> <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($accion === 'listar'): ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Empleados registrados</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Area</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($empleados as $emp): ?>
                                    <tr>
                                        <form method="POST">
                                            <td>
                                                <?= htmlspecialchars($emp['documento']) ?>
                                                <input type="hidden" name="documento" value="<?= $emp['documento'] ?>">
                                            </td>

                                            <td>
                                                <input
                                                    type="text"
                                                    name="nombre_completo"
                                                    class="form-control form-control-sm"
                                                    value="<?= htmlspecialchars($emp['nombre_completo']) ?>"
                                                    required>
                                            </td>

                                            <td>
                                                <select class="form-select form-select-sm" name="id_tipo" required>
                                                    <?php foreach ($tipo_usuario as $tipo): ?>
                                                        <option
                                                            value="<?= $tipo['id_tipo'] ?>"
                                                            <?= ($emp['id_tipo'] == $tipo['id_tipo']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($tipo['nombre_tipo']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>

                                            <td>
                                                <select class="form-select form-select-sm" name="id_area" required>
                                                    <?php foreach ($area_usu as $area): ?>
                                                        <option
                                                            value="<?= $area['id_area'] ?>"
                                                            <?= ($emp['id_area'] == $area['id_area']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($area['nombre_area']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>

                                            <td>
                                                <select class="form-select form-select-sm" name="estado">
                                                    <option value="1" <?= ($emp['estado'] == 1) ? 'selected' : '' ?>>
                                                        Activo
                                                    </option>
                                                    <option value="0" <?= ($emp['estado'] == 0) ? 'selected' : '' ?>>
                                                        Inactivo
                                                    </option>
                                                </select>
                                            </td>

                                            <td class="d-flex gap-2">
                                                <button
                                                    type="submit"
                                                    name="actualizar"
                                                    class="btn btn-success btn-sm">
                                                    Guardar
                                                </button>

                                                <button type="submit" name="eliminar" class="btn btn-danger btn-sm" onclick="return confirm('¿Desea eliminar este empleado?')"> Eliminar</button>
                                            </td>
                                        </form>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <a href="dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-xmark me-1"></i> Volver al menu</a>
                    </div>
                </div>
            </div>
        <?php elseif ($accion === 'crear_form'): ?>
            <div class="card shadow-sm col-lg-8 mx-auto">
                <div class="card-body">
                    <h2 class="card-title mb-4 fw-bold text-success"><i class="fa-solid fa-user-plus me-2"></i>Registrar Empleado</h2>
                    <form method="POST" autocomplete="off">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Cédula / Documento</label>
                                <input type="number" name="documento" required class="form-control" placeholder="Número de identificación">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nombre Completo</label>
                                <input type="text" name="nombre_completo" required class="form-control" placeholder="Ej: Juan Pérez">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">PIN de Marcado (Ingreso físico)</label>
                                <input type="password" name="pin" required class="form-control" placeholder="4 dígitos numéricos" maxlength="4">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Contraseña para el Sistema Web</label>
                                <input type="password" name="password" required class="form-control" placeholder="Establezca una contraseña">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">ID Tipo de Usuario (Rol)</label>
                                <select name="id_tipo" class="form-select">
                                    <option value="">Sin seleccionar</option>
                                    <option value="1">Admin</option>
                                    <option value="2">Empleado</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Área</label>
                                <select name="id_area" class="form-select" required>
                                    <option value="">Seleccione un área</option>
                                    <?php foreach ($area_usu as $area): ?>
                                        <option value="<?= $area['id_area'] ?>"> <?= htmlspecialchars($area['nombre_area']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Estado Inicial</label>
                            <select name="estado" class="form-select">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>

                        <button type="submit" name="crear" class="btn btn-success fw-bold px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
                        <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fa-solid fa-xmark me-1"></i> Cancelar</a>
                    </form>
                </div>
            </div>
        <?php endif ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>