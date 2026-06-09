<?php
function limpiar(string $valor): string
{
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

function obtenerReporteAsistencias(PDO $pdo, ?string $fechaInicio = null, ?string $fechaFin = null): array
{
    $sql = "SELECT
                a.id_asistencia,
                u.documento,
                u.nombre_completo,
                a.fecha_hora_ent,
                a.fecha_hora_sal,
                a.cantidad_horas
            FROM asistencias a
            INNER JOIN user_ u ON a.documento = u.documento
            WHERE 1=1";

    $params = [];

    if ($fechaInicio) {
        $sql .= " AND DATE(a.fecha_hora_ent) >= ?";
        $params[] = $fechaInicio;
    }
    if ($fechaFin) {
        $sql .= " AND DATE(a.fecha_hora_sal) <= ?";
        $params[] = $fechaFin;
    }

    $sql .= " ORDER BY a.fecha_hora_ent DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function crearEmpleado(PDO $pdo, int $documento, string $nombre, int $pin, ?int $id_tipo, string $fecha_creacion, ?int $id_area, int $estado) {
    $sqlCheck = "SELECT documento FROM user_ WHERE documento = ?";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([$documento]);

    if ($stmtCheck->fetch()) {
        return false;
    }
    $sql = "INSERT INTO user_ (documento, nombre_completo, pin, id_tipo, fecha_creacion, id_area, estado) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    try {
        return $stmt->execute([$documento, $nombre, $pin, $id_tipo, $fecha_creacion, $id_area, $estado]);
    } catch (PDOException $e) {
        die($e->getMessage());
    }
}

function editarEmpleado(PDO $pdo, string $documento, string $nombre, int $id_area, int $estado)
{
    $sql = "UPDATE user_ SET nombre_completo = ?, id_area = ?, estado = ? WHERE documento = ?";
    $stmt = $pdo->prepare($sql);
    try {
        return $stmt->execute([$nombre, $id_area, $estado, $documento]);
    } catch (PDOException $e) {
        die($e->getMessage());
    }
}

function buscarEmpleadoPorDocumento(PDO $pdo, string $documento): ?array
{
    try {
        $sql = "SELECT u.documento, u.nombre_completo, u.id_tipo, u.id_area, t.nombre_tipo, a.nombre_area, u.estado
                FROM user_ u
                INNER JOIN tipo_usuario t ON u.id_tipo = t.id_tipo
                INNER JOIN area a ON u.id_area = a.id_area
                WHERE u.documento = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$documento]);
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $empleado ?: null;
    } catch (PDOException $e) {
        error_log('Error buscarEmpleadoPorDocumento: ' . $e->getMessage());
        return null;
    }
}

function obtenertodoslostipos(PDO $pdo): array
{
    $sql = "SELECT * FROM tipo_usuario ORDER BY id_tipo ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenertodaslasareas(PDO $pdo): array
{
    $sql = "SELECT * FROM area ORDER BY id_area ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerEmpleados(PDO $pdo): array
{
    $sql = "SELECT u.documento, u.nombre_completo, u.fecha_creacion, u.estado, u.id_tipo, u.id_area, t.nombre_tipo, a.nombre_area
            FROM user_ u
            LEFT JOIN tipo_usuario t ON t.id_tipo = u.id_tipo
            LEFT JOIN area a ON a.id_area = u.id_area
            WHERE u.id_tipo = 2
            ORDER BY u.fecha_creacion DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function eliminarEmpleado(PDO $pdo, int $documento): bool
{
    $sql = "DELETE FROM user_ WHERE documento = :documento";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':documento' => $documento
    ]);
}