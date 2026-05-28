<?php
/**
 * Endpoint del chatbot. Proxy a la Messages API de Anthropic (Claude).
 * Recibe { history: [{role,text}], message: string } y devuelve { text }.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (!function_exists('load_env')) {
    function load_env(string $path): void {
        if (!is_file($path)) return;
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if ($line === '' || $line[0] === '#') continue;
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) continue;
            [$k, $v] = $parts;
            $k = trim($k); $v = trim($v, " \t\"'");
            if ($k !== '' && getenv($k) === false) {
                putenv("$k=$v");
                $_ENV[$k] = $v;
            }
        }
    }
}
load_env(__DIR__ . '/../.env');

$apiKey = getenv('ANTHROPIC_API_KEY') ?: ($_ENV['ANTHROPIC_API_KEY'] ?? '');
if ($apiKey === '') {
    http_response_code(500);
    echo json_encode(['error' => 'ANTHROPIC_API_KEY no configurada.']);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$body = json_decode($raw, true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido.']);
    exit;
}

$history = is_array($body['history'] ?? null) ? $body['history'] : [];
$message = trim((string)($body['message'] ?? ''));
if ($message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Mensaje vacío.']);
    exit;
}

$SYSTEM_INSTRUCTION = <<<TXT
Eres el asistente virtual de Nativos Launchpad, una plataforma premium para la adquisición y operación de negocios digitales listos para usar.
Tu objetivo es ayudar a los usuarios a entender qué es Nativos Launchpad, qué productos ofrecemos y cómo pueden empezar su camino como dueños de negocios digitales.

Información clave:
1. Qué hacemos: Creamos, validamos y entregamos negocios digitales (SaaS, Marketplaces, E-commerce) listos para ser operados.
2. Productos actuales: MenuAI ($45,000 USD), TechNova ($70,000 USD), PetPrime ($55,000 USD), entre otros.
3. Qué incluye cada compra: Código fuente completo, base de datos lista, panel de administración y soporte inicial.
4. Proceso: el usuario elige un negocio, realiza la inversión y recibe todo el ecosistema para empezar a operar de inmediato.
5. Tono: profesional, elegante, inspirador y servicial.

Responde de manera concisa y siempre en español. Si te preguntan algo fuera del contexto de Nativos Launchpad, redirige amablemente la conversación hacia nuestros servicios.
TXT;

// El front usa role: user|model (legado). Anthropic espera user|assistant.
$messages = [];
foreach ($history as $m) {
    $role = ($m['role'] ?? '') === 'user' ? 'user' : 'assistant';
    $text = (string)($m['text'] ?? '');
    if ($text === '') continue;
    $messages[] = ['role' => $role, 'content' => $text];
}
$messages[] = ['role' => 'user', 'content' => $message];

// Anthropic requiere que el primer mensaje sea de 'user' y que se alternen los roles.
// Si el primer mensaje del historial es 'assistant' (saludo inicial del bot), lo saltamos.
while (!empty($messages) && $messages[0]['role'] !== 'user') {
    array_shift($messages);
}

$model = getenv('ANTHROPIC_MODEL') ?: 'claude-haiku-4-5-20251001';
$payload = [
    'model' => $model,
    'max_tokens' => 1024,
    // System como bloque con cache_control: en cuanto el prompt supere el mínimo
    // (~2048 tokens en Haiku), las llamadas siguientes pegan el caché de 5 min.
    'system' => [[
        'type' => 'text',
        'text' => $SYSTEM_INSTRUCTION,
        'cache_control' => ['type' => 'ephemeral'],
    ]],
    'messages' => $messages,
];

$ch = curl_init('https://api.anthropic.com/v1/messages');
$opts = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT => 60,
];
$caBundle = __DIR__ . '/../certs/cacert.pem';
if (is_file($caBundle)) {
    $opts[CURLOPT_CAINFO] = $caBundle;
}
curl_setopt_array($ch, $opts);

$response = curl_exec($ch);
$status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err      = curl_error($ch);
curl_close($ch);

if ($response === false || $status >= 400) {
    $parsed   = json_decode((string)$response, true);
    $apiError = $parsed['error'] ?? [];
    $type     = (string)($apiError['type']    ?? '');
    $apiMsg   = (string)($apiError['message'] ?? ($err ?: 'Respuesta vacía.'));

    $friendly = match (true) {
        $type === 'authentication_error' || $status === 401 =>
            'La API key de Claude es inválida o fue revocada.',
        $type === 'permission_error' || $status === 403 =>
            'La cuenta no tiene acceso al modelo "' . $model . '".',
        $type === 'rate_limit_error' || $status === 429 =>
            'Se alcanzó el límite de consultas. Esperá unos segundos y reintentá.',
        $type === 'overloaded_error' || $status === 529 =>
            'La API de Claude está sobrecargada en este momento. Reintentá en unos segundos.',
        str_contains(strtolower($apiMsg), 'credit') || str_contains(strtolower($apiMsg), 'billing') =>
            'La cuenta de Anthropic no tiene créditos. Cargá saldo en console.anthropic.com/settings/billing.',
        $status >= 500 =>
            'Claude tuvo un error interno. Reintentá en unos minutos.',
        default =>
            'Error consultando Claude: ' . $apiMsg,
    };

    http_response_code(502);
    echo json_encode([
        'error'  => $friendly,
        'status' => $status,
        'detail' => $apiMsg,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode((string)$response, true);
$text = $data['content'][0]['text']
    ?? 'Lo siento, no puedo responder en este momento.';

echo json_encode(['text' => $text], JSON_UNESCAPED_UNICODE);
