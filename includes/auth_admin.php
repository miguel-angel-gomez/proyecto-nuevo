<?php
date_default_timezone_set('America/Bogota');
if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la terminal.\n");
}

require_once __DIR__ . '/../config/db.php';

if ($argc < 8) {
    echo "Uso: php crear_admin.php <email> <password> <id_tipo_user>\n";
    exit(1);
}

$documento = $argv[1];
$pin = $argv[2];
$nombre = $argv[3];
$password = $argv[4];
$id_tipo = (int)$argv[5];
$id_area = (int)$argv[6];
$estado = $argv[7];

if (!FILTER_VAR($documento, FILTER_VALIDATE_INT)) {
    echo " Documento invalido \n";
    exit(1);
}
if (strlen($pin) !==4) {
    echo " El pin debe ser de 4 digitos. \n";
    exit(1);
}
if (strlen($password) < 10) {
    echo " La contraseña debe tener minimo 10 caracteres. \n";
    exit(1);
}
try {
    $db = new Database();
    $pdo = $db->conectar();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_ WHERE documento = ?");
    $stmt->execute([$documento]);
    if ($stmt->fetchColumn() > 0) {
        echo " El documento ya esta registrado.\n";
        exit(1);
    }
    $hash = password_hash($password, PASSWORD_ARGON2ID);
    $pin_hash = password_hash($pin, PASSWORD_ARGON2ID);
    $insert = $pdo->prepare("INSERT INTO user_ (documento, pin, nombre_completo, password, id_tipo, id_area, estado) VALUES (?,?,?,?,?,?,?)");
    $insert->execute([$documento, $pin_hash, $nombre, $hash, $id_tipo, $id_area, $estado]);

    echo "Administrador creado exitosamente.\n";
    echo "Documento: $documento | ID tipo: $id_tipo\n";
} catch (Exception $e) {
    echo "Error;" . $e->getMessage() . "\n";
    exit(1);
}