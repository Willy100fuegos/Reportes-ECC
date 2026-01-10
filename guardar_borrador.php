<?php
// Configuración
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

$accion = $_GET['accion'] ?? '';

if ($accion === 'guardar') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'Método no permitido']);
        exit;
    }

    // Recibir todos los datos del formulario
    $datos = $_POST;
    
    // Identificadores clave
    $fecha = $datos['fecha'] ?? date('Y-m-d');
    $turno = $datos['turno'] ?? '';

    if (empty($turno)) {
        echo json_encode(['error' => 'El turno es obligatorio para guardar.']);
        exit;
    }

    // Convertir todo el array de datos a JSON para guardarlo en un solo campo
    // Esto es muy flexible, guarda textos, rutas de imágenes, todo.
    $jsonDatos = json_encode($datos, JSON_UNESCAPED_UNICODE);

    try {
        // Usamos INSERT ... ON DUPLICATE KEY UPDATE para "Guardar o Actualizar"
        // Si ya existe un borrador para esa fecha/turno, lo sobrescribe.
        $sql = "INSERT INTO borradores (fecha, turno, datos_json, updated_at) 
                VALUES (?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE datos_json = VALUES(datos_json), updated_at = NOW()";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fecha, $turno, $jsonDatos]);

        echo json_encode(['success' => true, 'mensaje' => 'Progreso guardado correctamente.']);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error BD: ' . $e->getMessage()]);
    }

} elseif ($accion === 'cargar') {
    // Cargar el borrador de una fecha/turno específico
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $turno = $_GET['turno'] ?? '';

    if (empty($turno)) {
        echo json_encode(['datos' => null]); // Sin turno no cargamos nada
        exit;
    }

    try {
        $stmt = $pdo->query("SELECT datos_json FROM borradores WHERE fecha = '$fecha' AND turno = '$turno'");
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fila) {
            echo json_encode(['success' => true, 'datos' => json_decode($fila['datos_json'], true)]);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'No hay borrador guardado.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error BD: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Acción desconocida']);
}
?>