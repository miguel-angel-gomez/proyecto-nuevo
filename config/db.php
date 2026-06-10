<?php
// ✅ En producción: desactivar display_errors, registrar en log
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

class Database
{
    private $hostname = "localhost";
    private $database = "proyecto";
    private $username = "root";
    private $password = "";
    private $charset = "utf8mb4"; 

    function conectar()
    {
        try {
            

            $dsn = "mysql:host={$this->hostname};dbname={$this->database};charset={$this->charset}";
           
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];

            $pdo = new PDO($dsn, $this->username, $this->password, $options);
            return $pdo;
            
        } catch(PDOException $e) {
            // ✅ Registrar error en log, nunca mostrarlo al usuario
            error_log('Error de conexión: ' . $e->getMessage());
            die('Error de conexión con la base de datos. Contacte al administrador.');
        }
    }
}
?>