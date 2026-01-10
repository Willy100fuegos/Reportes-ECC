<?php
// 1. Limpieza y Cabeceras
ob_clean(); 
header('Content-Type: application/json; charset=utf-8');

// 2. Verificación de Entorno
if (!function_exists('curl_init')) {
    echo json_encode(['error' => 'CRÍTICO: CURL no instalado en el servidor.']);
    exit;
}

// 3. Recepción de Datos
$input = json_decode(file_get_contents('php://input'), true);
$textoOriginal = $input['texto'] ?? '';

if (empty($textoOriginal)) {
    echo json_encode(['error' => 'El servidor recibió un texto vacío.']);
    exit;
}

// 4. Configuración
$apiKey = 'AQUI VA TU API'; 

// --- FUNCIÓN INTELIGENTE: DETECTAR MODELO DISPONIBLE ---
function obtenerModeloDisponible($key) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models?key=$key";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Fix cPanel
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    // Lista de preferencia (Más rápido/nuevo primero)
    $preferidos = ['gemini-1.5-flash', 'gemini-1.0-pro', 'gemini-pro'];
    
    if (isset($data['models'])) {
        foreach ($data['models'] as $m) {
            $nombreReal = str_replace('models/', '', $m['name']);
            if (isset($m['supportedGenerationMethods']) && in_array('generateContent', $m['supportedGenerationMethods'])) {
                if (strpos($nombreReal, '1.5-flash') !== false) return $nombreReal;
            }
        }
        foreach ($data['models'] as $m) {
             if (isset($m['supportedGenerationMethods']) && in_array('generateContent', $m['supportedGenerationMethods'])) {
                 return str_replace('models/', '', $m['name']);
             }
        }
    }
    return 'gemini-1.5-flash'; // Fallback
}

$modeloAUsar = obtenerModeloDisponible($apiKey);
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/$modeloAUsar:generateContent?key=$apiKey";

// 5. Preparar Petición (PROMPT REFINADO ESTILO EJECUTIVO)
// Instrucción clave: "Solo el texto corregido, sin títulos, sin negritas innecesarias, en prosa fluida."
$prompt = "Actúa como un experto en redacción de reportes de seguridad corporativa. Reescribe el siguiente texto para que sea profesional, ejecutivo y directo.
REGLAS:
1. Elimina redundancias y saludos/títulos innecesarios (como 'Informe de Seguridad' o 'Observaciones:').
2. No uses formato de lista ni pares clave-valor (ej. 'Hora: 17:00'). Escribe en prosa fluida.
3. Corrige ortografía y gramática.
4. Mantén un tono formal y objetivo.
5. El resultado debe ser un párrafo cohesivo (o dos si es muy largo) listo para pegar en un informe.

Texto original: \"$textoOriginal\"";

$data = [
    "contents" => [
        [ "parts" => [ ["text" => $prompt] ] ]
    ]
];

// 6. Ejecutar CURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 7. Manejo de Respuesta
if ($curlError) {
    echo json_encode(['error' => 'Error de conexión: ' . $curlError]);
    exit;
}

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $textoMejorado = $result['candidates'][0]['content']['parts'][0]['text'];
    echo json_encode(['texto_mejorado' => $textoMejorado]);
} else {
    $msg = isset($result['error']['message']) ? $result['error']['message'] : "HTTP $httpCode";
    if ($httpCode === 429) {
        echo json_encode(['error' => '⚠️ IA Saturada momentáneamente. Por favor intenta en 1 minuto o redacta manualmente.']);
    } else {
        echo json_encode(['error' => "Error ($httpCode) usando el modelo '$modeloAUsar': $msg"]);
    }
}
?>