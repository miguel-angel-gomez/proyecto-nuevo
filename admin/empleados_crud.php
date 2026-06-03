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

$accion = $_GET['accion'] ?? '';
if (empty($accion)) {
    header("Location: dashboard.php");
    exit();
}

$mensaje = '';
$tipo_usuario = obtenertodoslostipos($pdo);
$area_usu = obtenertodaslasareas($pdo);

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
        $nombre = trim($_POST['nombre']);
        $id_tipo = isset($_POST['id_tipo']) && $_POST['id_tipo'] !== '' ? intval($_POST['id_tipo']) : NULL;
        $id_area = isset($_POST['id_area']) && $_POST['id_area'] !== '' ? intval($_POST['id_area']) : NULL;
        $estado = isset($_POST['estado']) ? intval($_POST['estado']) : 1;

        if ($documento > 0 && $nombre !== '') {
            $ok = editarEmpleado($pdo, $documento, $nombre, $id_tipo, $id_area, $estado);
            $mensaje = $ok ? "Los datos del empleado se actualizaron correctamente." : "No se detectaron cambios nuevos.";
        } else {
            $mensaje = "Datos inválidos para actualizar.";
        }
    }
}

$empleados = [];
if ($accion === "listar") {
    $empleados = obtenerReporteAsistencias($pdo);
}

$empleado_editar = null;
if ($accion === 'editar_form') {
    $id_buscar = intval($_GET['documento'] ?? 0);
    if ($id_buscar > 0) {
        $empleado_editar = buscarEmpleadoPorDocumento($pdo, $id_buscar);
    }
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0 fw-bold text-dark"><i class="fa-solid fa-list me-2 text-primary"></i>Listado de Empleados</h2>
                        <a href="?accion=crear_form" class="btn btn-success btn-sm"><i class="fa-solid fa-plus me-1"></i> Registrar Empleado</a>
                    </div>

                    <?php if (count($empleados) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Documento</th>
                                        <th>Nombre Completo</th>
                                        <th>Rol / Tipo</th>
                                        <th>Fecha Registro</th>
                                        <th>Área</th>
                                        <th>Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($empleados as $e): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= htmlspecialchars($e['documento']) ?></td>
                                            <td><?= htmlspecialchars($e['nombre_completo']) ?></td>
                                            <td><?= htmlspecialchars($e['nombre_tipo'] ?? 'Sin Rol') ?></td>
                                            <td><?= htmlspecialchars($e['fecha_creacion']) ?></td>
                                            <td><?= htmlspecialchars($e['nombre_area'] ?? 'Sin Área') ?></td>
                                            <td>
                                                <span class="badge bg-<?= $e['estado'] ? 'success' : 'danger' ?>">
                                                    <?= $e['estado'] ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="?accion=editar_form&documento=<?= $e['documento'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fa-solid fa-user-pen"></i> Editar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning m-0">No se encontraron empleados registrados en la base de datos.</div>
                    <?php endif; ?>
                    <div class="mt-4">
                        <a href="dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-house me-1"></i> Volver al Panel</a>
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

        <?php elseif ($accion === 'editar_form' && !$empleado_editar): ?>
            <div class="card shadow-sm col-md-6 mx-auto">
                <div class="card-body">
                    <h2 class="fw-bold mb-3 text-warning"><i class="fa-solid fa-user-gear me-2"></i>Modificar Registro</h2>
                    <p class="text-muted">Busque al empleado ingresando su número de documento:</p>
                    <form action="" method="GET">
                        <input type="hidden" name="accion" value="editar_form">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Documento de Identidad:</label>
                            <input type="number" name="documento" required min="1" class="form-control" placeholder="Ej: 1090222">
                        </div>
                        <button type="submit" class="btn btn-warning fw-bold"><i class="fa-solid fa-magnifying-glass me-1"></i> Buscar Empleado</button>
                        <a href="dashboard.php" class="btn btn-secondary">Volver</a>
                    </form>
                </div>
            </div>

        <?php elseif ($empleado_editar): ?>
            <div class="card shadow-sm col-lg-8 mx-auto">
                <div class="card-body">
                    <h2 class="card-title mb-4 fw-bold text-dark">Editar Datos del Empleado</h2>
                    <div class="alert alert-secondary py-2 small">Modificando la Cédula: <strong><?= htmlspecialchars($empleado_editar['documento']) ?></strong></div>

                    <form method="POST">
                        <input type="hidden" name="documento" value="<?= htmlspecialchars($empleado_editar['documento']) ?>">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre Completo</label>
                            <input type="text" name="nombre" value="<?= htmlspecialchars($empleado_editar['nombre_completo']) ?>" required class="form-control">
                        </div>

                        <div class="row">
                            <div class="mb-3">
                                <label class="form-label">Tipo de usuario:</label>
                                <select name="id_tipo" id="id_tipo" class="form-select">
                                    <?php foreach ($tipo_usuario as $t): ?>
                                        <option value="<?= $t['id_tipo'] ?>" <?= ($empleado_editar['id_tipo'] ?? '') == $t['id_tipo'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nombre_tipo']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Área</label>
                                <select name="id_area" class="form-select" required> 
                                    <?php foreach ($area_usu as $area): ?>
                                        <option value="<?= $area['id_area'] ?>" <?= ($empleado_editar['id_area'] == $area['id_area']) ? 'selected' : '' ?>> <?= htmlspecialchars($area['nombre_area']) ?> </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Estado del Empleado</label>
                            <select name="estado" class="form-select">
                                <option value="1" <?= $empleado_editar['estado'] == 1 ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= $empleado_editar['estado'] == 0 ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>

                        <button type="submit" name="actualizar" class="btn btn-warning fw-bold px-4"><i class="fa-solid fa-rotate me-1"></i> Actualizar Cambios</button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>