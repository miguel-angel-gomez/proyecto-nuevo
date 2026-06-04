<?php

/**
 * funciones.php
 * Funciones reutilizables del sistema:
 *  - Registro de asistencia (entrada / salida)
 *  - Cálculo de horas trabajadas
 *  - Validaciones de empleados
 *  - Reporte de asistencias
 * 
 * Uso: require_once __DIR__ . '/../includes/funciones.php';
 */


// ══════════════════════════════════════════════════
//  ASISTENCIA
// ══════════════════════════════════════════════════

/**
 * Registra entrada o salida de un empleado según el estado del día.
 * - Si no hay registro de hoy → INSERT (entrada).
 * - Si existe registro sin salida → UPDATE (salida + horas trabajadas).
 *
 * @param PDO    $pdo        Conexión activa
 * @param string $documento  Documento del empleado
 * @return array ['ok' => bool, 'mensaje' => string, 'tipo' => 'entrada'|'salida'|'ya_salio']
 */
function registrarAsistencia(PDO $pdo, string $documento): array
{
    try {
        // Buscar registro abierto de HOY para este empleado
        $sql = "SELECT a.id_asistencia, a.fecha_entrada
                FROM asistencias a
                INNER JOIN user u ON a.id_empleado = u.documento
                WHERE u.documento = ?
                  AND DATE(a.fecha_entrada) = CURDATE()
                  AND a.fecha_salida IS NULL
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$documento]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            // ── ENTRADA ──
            $sql = "INSERT INTO asistencias (id_empleado, fecha_entrada)
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
                      WHERE id_empleado = ?
                        AND DATE(fecha_entrada) = CURDATE()
                        AND fecha_salida IS NOT NULL
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
                SET fecha_salida      = NOW(),
                    horas_trabajadas  = ROUND(TIMESTAMPDIFF(MINUTE, fecha_entrada, NOW()) / 60, 2)
                WHERE id_asistencia = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$registro['id_asistencia']]);

        // Obtener las horas calculadas para el mensaje
        $sqlHoras = "SELECT horas_trabajadas FROM asistencias WHERE id_asistencia = ?";
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

/**
 * Calcula horas trabajadas entre dos fechas/horas (sin tocar la BD).
 * Útil para mostrar totales en reportes.
 *
 * @param string $entrada  Formato: 'Y-m-d H:i:s'
 * @param string $salida   Formato: 'Y-m-d H:i:s'
 * @return float  Horas con 2 decimales (ej: 7.5)
 */
function calcularHorasTrabajadas(string $entrada, string $salida): float
{
    $dt1 = new DateTime($entrada);
    $dt2 = new DateTime($salida);
    $diff = $dt1->diff($dt2);

    $minutos = ($diff->days * 1440) + ($diff->h * 60) + $diff->i;
    return round($minutos / 60, 2);
}

/**
 * Formatea horas decimales a texto legible.
 * Ej: 7.75 → "7 h 45 min"
 *
 * @param float $horas
 * @return string
 */
function formatearHoras(float $horas): string
{
    $h   = (int) $horas;
    $min = (int) round(($horas - $h) * 60);
    return "{$h} h {$min} min";
}



function verificarEmpleado(PDO $pdo, string $documento): array|false
{
    $sql = "SELECT u.documento, u.nombre, u.email, t.tip_user
            FROM user u
            INNER JOIN TYPE_USER t ON u.id_tip_user = t.id_tip_user
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
    return round(array_sum(array_column($asistencias, 'horas_trabajadas')), 2);
}

function crearEmpleado(PDO $pdo, int $documento, string $nombre, int $pin, string $password, ?int $id_tipo, string $fecha_creacion, ?int $id_area, int $estado) {
    $sqlCheck = "SELECT documento FROM user_ WHERE documento = ?";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([$documento]);

    if ($stmtCheck->fetch()) {
        return false;
    }
    $sql = "INSERT INTO user_ (documento, nombre_completo, pin, password, id_tipo, fecha_creacion, id_area, estado ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
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
        return $stmt->execute([ $nombre, $id_tipo, $id_area, $estado, $documento]);
    } catch (PDOException $e) {
        die($e->getMessage());
    }
}


function buscarEmpleadoPorDocumento(PDO $pdo, string $documento): ?array
{
    try {
        $sql = "SELECT u.documento, u.nombre_completo,u.id_tipo, u.id_area, t.nombre_tipo, a.nombre_area, u.estado FROM user_ u INNER JOIN tipo_usuario t ON u.id_tipo = t.id_tipo INNER JOIN area a ON u.id_Area = a.id_area WHERE u.documento = ?";
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

