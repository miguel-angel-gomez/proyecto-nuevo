<?php

function registrarAsistencia(PDO $pdo, string $documento): array
{
    try {
        // Buscar registro abierto de HOY para este empleado
        $sql = "SELECT a.id_asistencia, a.fecha_hora_ent
                FROM asistencias a
                WHERE a.documento = ?
                  AND DATE(a.fecha_hora_ent) = CURDATE()
                  AND a.fecha_hora_sal IS NULL
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$documento]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            // ── ENTRADA ──
            $sql = "INSERT INTO asistencias (documento, fecha_hora_ent)
                    VALUES (?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$documento]);

            return [
                'ok'      => true,
                'tipo'    => 'entrada',
                'mensaje' => '✅ Entrada registrada a las ' . date('H:i:s')
            ];
        }

        // Verificar si ya marcó salida hoy
        $sqlSalida = "SELECT id_asistencia FROM asistencias
                      WHERE documento = ?
                        AND DATE(fecha_hora_ent) = CURDATE()
                        AND fecha_hora_sal IS NOT NULL
                      LIMIT 1";
        $stmtS = $pdo->prepare($sqlSalida);
        $stmtS->execute([$documento]);
        if ($stmtS->fetch()) {
            return [
                'ok'      => false,
                'tipo'    => 'ya_salio',
                'mensaje' => 'ℹ️ Ya registraste entrada y salida el día de hoy.'
            ];
        }

        // ── SALIDA ──
        $sql = "UPDATE asistencias
                SET fecha_hora_sal  = NOW(),
                    cantidad_horas  = ROUND(TIMESTAMPDIFF(MINUTE, fecha_hora_ent, NOW()) / 60, 2)
                WHERE id_asistencia = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$registro['id_asistencia']]);

        // Obtener las horas calculadas para el mensaje
        $sqlHoras = "SELECT cantidad_horas FROM asistencias WHERE id_asistencia = ?";
        $stmtH = $pdo->prepare($sqlHoras);
        $stmtH->execute([$registro['id_asistencia']]);
        $horas = $stmtH->fetchColumn();

        return [
            'ok'      => true,
            'tipo'    => 'salida',
            'mensaje' => "✅ Salida registrada. Horas trabajadas hoy: {$horas} h"
        ];
    } catch (PDOException $e) {
        error_log('Error registrarAsistencia: ' . $e->getMessage());
        return [
            'ok'      => false,
            'tipo'    => 'error',
            'mensaje' => '❌ Error interno al registrar asistencia.'
        ];
    }
}

function calcularHorasTrabajadas(string $entrada, string $salida): float
{
    $dt1 = new DateTime($entrada);
    $dt2 = new DateTime($salida);
    $diff = $dt1->diff($dt2);
    $minutos = ($diff->days * 1440) + ($diff->h * 60) + $diff->i;
    return round($minutos / 60, 2);
}

function formatearHoras(float $horas): string
{
    $h   = (int) $horas;
    $min = (int) round(($horas - $h) * 60);
    return "{$h} h {$min} min";
}

function verificarEmpleado(PDO $pdo, string $documento): array|false
{
    $sql = "SELECT u.documento, u.nombre_completo, t.nombre_tipo
            FROM user_ u
            INNER JOIN tipo_usuario t ON u.id_tipo = t.id_tipo
            WHERE u.documento = ?
              AND u.estado = 1
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$documento]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function validarDocumento(string $documento): bool
{
    return (bool) preg_match('/^\d{6,12}$/', $documento);
}

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

function totalHorasReporte(array $asistencias): float
{
    return round(array_sum(array_column($asistencias, 'cantidad_horas')), 2);
}

function crearEmpleado(PDO $pdo, int $documento, string $nombre, int $pin, string $password, ?int $id_tipo, string $fecha_creacion, ?int $id_area, int $estado) {
    $sqlCheck = "SELECT documento FROM user_ WHERE documento = ?";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([$documento]);

    if ($stmtCheck->fetch()) {
        return false;
    }
    $sql = "INSERT INTO user_ (documento, nombre_completo, pin, password, id_tipo, fecha_creacion, id_area, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    try {
        return $stmt->execute([$documento, $nombre, $pin, $password, $id_tipo, $fecha_creacion, $id_area, $estado]);
    } catch (PDOException $e) {
        die($e->getMessage());
    }
}

function editarEmpleado(PDO $pdo, string $documento, string $nombre, int $id_tipo, int $id_area, int $estado)
{
    $sql = "UPDATE user_ SET nombre_completo = ?, id_tipo = ?, id_area = ?, estado = ? WHERE documento = ?";
    $stmt = $pdo->prepare($sql);
    try {
        return $stmt->execute([$nombre, $id_tipo, $id_area, $estado, $documento]);
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