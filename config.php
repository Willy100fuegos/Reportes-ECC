<?php
// Forzar reporte de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuración de Zona Horaria
date_default_timezone_set('America/Mexico_City');

// DB CREDENCIALES
define('DB_HOST', 'localhost');
define('DB_USER', 'DEMO'); 
define('DB_PASS', 'DEMO');
define('DB_NAME', 'DEMO'); 

// SMTP
define('SMTP_HOST', 'DEMO');
define('SMTP_USER', 'DEMO');
define('SMTP_PASS', 'DEMO');
define('SMTP_PORT', 465); 
define('SMTP_SECURE', 'ssl'); 

// LISTA DE DESTINATARIOS
$destinatarios = [
    'MAIL DEL O LOS DESTINATARIOS'
];

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la Base de Datos: " . $e->getMessage());
}

function getTurnoActual() {
    $hora = (int)date('H');
    return ($hora >= 7 && $hora < 19) ? 'Diurno' : 'Nocturno';
}

function sugerirFolio($pdo) {
    try {
        $stmt = $pdo->query("SELECT ultimo_folio FROM config WHERE id = 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        $ultimo = $config ? (int)$config['ultimo_folio'] : 0;
        return $ultimo + 1;
    } catch (Exception $e) {
        return 1;
    }
}
?>