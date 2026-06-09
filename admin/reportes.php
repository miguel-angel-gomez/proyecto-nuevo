<?php
session_start();

if (!isset($_SESSION['nombre_tipo']) || $_SESSION['nombre_tipo'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

$db = new Database();
$pdo = $db->conectar();

$fechaInicio = limpiar($_GET['fecha_inicio'] ?? '');
$fechaFin    = limpiar($_GET['fecha_fin'] ?? '');

// ✅ CORRECCIÓN: Se envían los parámetros para activar el filtro dinámico
$asistencias = obtenerReporteAsistencias($pdo, $fechaInicio, $fechaFin);

$total = round(array_sum(array_column($asistencias, 'cantidad_horas')), 2);

if (isset($_GET['exportar']) && $_GET['exportar'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Documento', 'Nombre', 'Entrada', 'Salida', 'Horas']);
    
    foreach ($asistencias as $f) {
        // Formateamos las horas en HH:MM para el documento CSV
        $horas = (float)($f['cantidad_horas'] ?? 0);
        $h = floor($horas);
        $m = round(($horas - $h) * 60);
        $formatoHoras = sprintf('%02d:%02d', $h, $m);

        fputcsv($out, [
            $f['id_asistencia'],
            $f['documento'],
            $f['nombre_completo'],
            $f['fecha_hora_ent'],
            $f['fecha_hora_sal'] ?? '-',
            $formatoHoras
        ]);
    }
    fclose($out);
    exit;
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte de asistencias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Reporte de asistencias</h1>
            <a href="dashboard.php" class="btn btn-outline-secondary">Volver</a>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <form method="get" class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Fecha inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control"
                            value="<?= htmlspecialchars($fechaInicio) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha fin</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($fechaFin) ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button class="btn btn-primary" type="submit">Filtrar</button>
                        <a class="btn btn-outline-secondary" href="reportes.php">Limpiar</a>
                        <a class="btn btn-success"
                            href="reportes.php?fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>&exportar=csv">Exportar CSV</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0">Resultados</h2>
                    <span class="badge bg-primary">Total horas: <?= number_format($total, 2) ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Documento</th>
                                <th>Nombre</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Horas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($asistencias)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No hay registros para el criterio seleccionado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($asistencias as $f): ?>
                                    <tr>
                                        <td><?= (int) $f['id_asistencia'] ?></td>
                                        <td><?= limpiar((string) $f['documento']) ?></td>
                                        <td><?= limpiar((string) $f['nombre_completo']) ?></td>
                                        <td><?= limpiar((string) $f['fecha_hora_ent']) ?></td>
                                        <td><?= limpiar((string) ($f['fecha_hora_sal'] ?? '-')) ?></td>
                                        
                                        <td>
                                            <?php 
                                                $horas = (float)$f['cantidad_horas'];
                                                $h = floor($horas);
                                                $m = round(($horas - $h) * 60);
                                                echo sprintf('%02d:%02d', $h, $m); 
                                            ?> 
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>