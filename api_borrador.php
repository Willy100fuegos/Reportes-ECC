<?php
// 1. INICIO DEL BÚFER (Captura cualquier error silencioso)
ob_start();

// Configuración
ini_set('display_errors', 0); 
error_reporting(E_ALL);

// 2. Cargar Configuración
require_once 'config.php';

// 3. LIMPIEZA TOTAL (Elimina espacios o warnings que suelte config.php)
ob_end_clean(); 
header('Content-Type: application/json; charset=utf-8');

// Verificación básica de conexión
if (!isset($pdo)) {
    echo json_encode(['error' => 'Error crítico: No hay conexión a BD en config.php']);
    exit;
}

$accion = $_GET['accion'] ?? '';

// --- ACCIÓN: GUARDAR ---
if ($accion === 'guardar') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'Método inválido']); 
        exit;
    }

    $datos = $_POST;
    $fecha = $datos['fecha'] ?? date('Y-m-d');
    $turno = $datos['turno'] ?? '';

    if (empty($turno)) {
        echo json_encode(['error' => 'Falta el turno.']); 
        exit;
    }

    // Convertir a JSON
    $jsonDatos = json_encode($datos, JSON_UNESCAPED_UNICODE);

    try {
        // Guardar o Actualizar (Upsert)
        $sql = "INSERT INTO borradores (fecha, turno, datos_json, updated_at) 
                VALUES (?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE datos_json = VALUES(datos_json), updated_at = NOW()";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fecha, $turno, $jsonDatos]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // Si la tabla no existe, intentamos crearla al vuelo (Autocuración)
        if (strpos($e->getMessage(), "doesn't exist") !== false) {
             echo json_encode(['error' => 'Falta la tabla borradores. Ejecuta el SQL proporcionado.']);
        } else {
             echo json_encode(['error' => 'Error BD: ' . $e->getMessage()]);
        }
    }

// --- ACCIÓN: CARGAR ---
} elseif ($accion === 'cargar') {
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $turno = $_GET['turno'] ?? '';

    if (empty($turno)) { 
        echo json_encode(['success' => false]); 
        exit; 
    }

    try {
        $stmt = $pdo->query("SELECT datos_json FROM borradores WHERE fecha = '$fecha' AND turno = '$turno'");
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fila) {
            echo json_encode(['success' => true, 'datos' => json_decode($fila['datos_json'], true)]);
        } else {
            echo json_encode(['success' => false]);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error BD al cargar']);
    }

} else {
    echo json_encode(['error' => 'Acción desconocida']);
}
?>