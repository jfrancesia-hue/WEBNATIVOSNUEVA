<?php
/**
 * Cliente mínimo de Supabase (PostgREST) vía cURL + caché por archivo con TTL.
 *
 * Requiere en .env:
 *   SUPABASE_URL=https://xxx.supabase.co
 *   SUPABASE_SERVICE_ROLE_KEY=eyJ...        (service_role; vive solo en server)
 *   SUPABASE_SCHEMA=public                  (opcional)
 *   SUPABASE_CACHE_TTL=60                   (segundos, opcional; 0 desactiva caché)
 */
declare(strict_types=1);

if (!function_exists('load_env')) {
    function load_env(string $path): void {
        if (!is_file($path)) return;
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if ($line === '' || $line[0] === '#') continue;
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) continue;
            [$k, $v] = $parts;
            $k = trim($k);
            $v = trim($v, " \t\"'");
            if ($k !== '' && getenv($k) === false) {
                putenv("$k=$v");
                $_ENV[$k] = $v;
            }
        }
    }
}
load_env(__DIR__ . '/../.env');

/**
 * GET contra una tabla del REST de Supabase.
 *
 * @param string $table     Nombre de la tabla (sin schema).
 * @param string $query     Query string PostgREST (ej: 'select=*&order=sort_order.asc').
 * @return array            Filas decodificadas como arrays asociativos.
 * @throws RuntimeException Si la respuesta no es 2xx o falla cURL.
 */
function supabase_get(string $table, string $query = 'select=*'): array {
    $url = rtrim((string)(getenv('SUPABASE_URL') ?: ''), '/');
    $key = (string)(getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '');
    $schema = (string)(getenv('SUPABASE_SCHEMA') ?: 'public');

    if ($url === '' || $key === '') {
        throw new RuntimeException('Supabase no configurado: faltan SUPABASE_URL o SUPABASE_SERVICE_ROLE_KEY.');
    }

    $endpoint = "{$url}/rest/v1/{$table}?{$query}";

    $ch = curl_init($endpoint);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $key,
            'Authorization: Bearer ' . $key,
            'Accept-Profile: ' . $schema,
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
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

    if ($response === false) {
        throw new RuntimeException("Error cURL Supabase: $err");
    }
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException("Supabase respondió $status: $response");
    }

    $data = json_decode((string)$response, true);
    return is_array($data) ? $data : [];
}

/**
 * Lee de caché si está fresco; si no, ejecuta $loader, guarda y devuelve.
 * Si el loader tira excepción y hay caché viejo, devuelve el caché (stale-on-error).
 */
function cached(string $key, callable $loader): array {
    $ttl = (int)(getenv('SUPABASE_CACHE_TTL') ?: 60);
    $dir = __DIR__ . '/../storage/cache';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $file = $dir . '/' . preg_replace('/[^a-z0-9_-]/i', '_', $key) . '.json';

    if ($ttl > 0 && is_file($file) && (time() - filemtime($file)) < $ttl) {
        $raw = @file_get_contents($file);
        if ($raw !== false) {
            $parsed = json_decode($raw, true);
            if (is_array($parsed)) return $parsed;
        }
    }

    try {
        $data = $loader();
        @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $data;
    } catch (Throwable $e) {
        // Fallback: caché viejo si existe.
        if (is_file($file)) {
            $raw = @file_get_contents($file);
            if ($raw !== false) {
                $parsed = json_decode($raw, true);
                if (is_array($parsed)) {
                    error_log('[supabase] usando caché stale por error: ' . $e->getMessage());
                    return $parsed;
                }
            }
        }
        throw $e;
    }
}

/**
 * Mapea una fila de la tabla `products` (snake_case) al shape que usa la app
 * (camelCase + JSONB ya decodificado). Compatible con $PRODUCTS original.
 */
function map_product_row(array $row): array {
    $decode = fn($v) => is_string($v) ? (json_decode($v, true) ?? []) : (is_array($v) ? $v : []);
    return [
        'id'             => (string)($row['id']          ?? ''),
        'title'          => (string)($row['title']       ?? ''),
        'category'       => (string)($row['category']    ?? ''),
        'description'    => (string)($row['description'] ?? ''),
        'price'          => (string)($row['price']       ?? ''),
        'image'          => (string)($row['image']       ?? ''),
        'stats'          => $decode($row['stats']    ?? []),
        'details'        => $decode($row['details']  ?? []),
        'included'       => $decode($row['included'] ?? []),
        'isVerified'     => !empty($row['is_verified']),
        'securityRating' => isset($row['security_rating']) ? (float)$row['security_rating'] : null,
        'sellerVerified' => !empty($row['seller_verified']),
    ];
}
