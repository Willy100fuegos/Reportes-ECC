<?php
// 1. INICIO DEL BÚFER (CRÍTICO: Debe ser la primera línea)
// Esto captura cualquier error o advertencia previa para que no rompa el JSON
ob_start();

// Configuración de entorno
ini_set('display_errors', 0); 
error_reporting(E_ALL);
ini_set('memory_limit', '512M'); // Aumentar memoria para procesar fotos grandes
header('Content-Type: application/json; charset=utf-8');

// Configuración de Directorio
$anio = date('Y'); 
$mes = date('m');
$uploadDir = "uploads/$anio/$mes/";

// 2. DETECCIÓN DE LÍMITE DE SERVIDOR (post_max_size)
// Si $_FILES está vacío pero se enviaron datos, es porque el servidor bloqueó el peso.
if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    ob_clean(); // Limpiar cualquier basura
    $maxSize = ini_get('post_max_size');
    echo json_encode(['error' => "El archivo es demasiado grande para el servidor. Límite actual: $maxSize. Revisa 'post_max_size' en cPanel."]);
    exit;
}

// 3. Crear carpeta si no existe
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        ob_clean();
        echo json_encode(['error' => "Error crítico: No se pudo crear la carpeta $uploadDir. Verifica permisos (debe ser 755)."]);
        exit;
    }
}

// 4. Validar que llegó un archivo
if (!isset($_FILES['file']) || $_FILES['file']['error'] != 0) {
    ob_clean();
    $code = $_FILES['file']['error'] ?? 'N/A';
    echo json_encode(['error' => "No se recibió archivo o hubo error PHP (Código: $code)."]);
    exit;
}

$tmp = $_FILES['file']['tmp_name'];
// Verificar si es una imagen real
$check = @getimagesize($tmp);

if($check === false) {
    ob_clean();
    echo json_encode(['error' => 'El archivo subido no es una imagen válida o está dañado.']);
    exit;
}

// 5. Generar nombre único
$fileName = uniqid('img_') . '.jpg';
$targetPath = $uploadDir . $fileName;

// 6. Procesar y Comprimir
list($w, $h, $t) = $check;
$s = null;

switch($t){ 
    case IMAGETYPE_JPEG: $s = imagecreatefromjpeg($tmp); break; 
    case IMAGETYPE_PNG: $s = imagecreatefrompng($tmp); break; 
}

if(!$s) {
    ob_clean();
    echo json_encode(['error' => 'Formato de imagen no soportado (Use JPG o PNG).']);
    exit;
}

// Redimensionar si es gigante (Max 1280px)
$maxW = 1280;
if ($w > $maxW) { 
    $r = $maxW/$w; 
    $nw = $maxW; 
    $nh = $h*$r; 
} else { 
    $nw = $w; 
    $nh = $h; 
}

$n = imagecreatetruecolor($nw, $nh);
imagecopyresampled($n, $s, 0, 0, 0, 0, $nw, $nh, $w, $h);

// 7. Guardar en disco
if (imagejpeg($n, $targetPath, 75)) {
    imagedestroy($s); 
    imagedestroy($n);
    
    // LIMPIEZA FINAL Y RESPUESTA ÉXITOSA
    ob_clean();
    echo json_encode(['success' => true, 'path' => $targetPath]);
} else {
    imagedestroy($s); 
    imagedestroy($n);
    
    ob_clean();
    echo json_encode(['error' => "Error de escritura en $uploadDir. Verifica permisos de carpeta."]);
}
?>