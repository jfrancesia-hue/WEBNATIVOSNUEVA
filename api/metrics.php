<?php
declare(strict_types=1);

/**
 * /api/metrics.php
 * Query params:
 *  - from=YYYY-MM-DD   (inclusive)
 *  - to=YYYY-MM-DD     (exclusive)
 *  - g=day|week|month
 *
 * Optional (to avoid hardcoding event names):
 *  - e_signup=your_event_name
 *  - e_hire_created=your_event_name
 *  - e_hire_completed=your_event_name
 *
 * Env vars (recommended):
 *  APP_TIMEZONE=America/Argentina/Catamarca
 *
 *  DB_DRIVER=mysql|sqlite
 *  DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS (mysql)
 *  DB_SQLITE_PATH (sqlite)
 *
 *  EVENTS_TABLE=events
 *  COL_EVENT_NAME=event_name
 *  COL_USER_ID=user_id
 *  COL_OCCURRED_AT=occurred_at
 *
 *  EVENT_SIGNUP=user_signup
 *  EVENT_HIRE_CREATED=hire_created
 *  EVENT_HIRE_COMPLETED=hire_completed
 */

function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function load_env_file(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        $k = trim($parts[0]);
        $v = trim($parts[1]);
        $v = trim($v, "\"'");
        $_ENV[$k] = $v;
    }
}

function get_str(string $key, string $default = ''): string {
    return isset($_ENV[$key]) ? (string)$_ENV[$key] : $default;
}

function get_q(string $key, string $default = ''): string {
    return isset($_GET[$key]) ? (string)$_GET[$key] : $default;
}

function parse_date_ymd(string $s, DateTimeImmutable $fallback): DateTimeImmutable {
    $s = trim($s);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $fallback;
    try {
        return new DateTimeImmutable($s . ' 00:00:00');
    } catch (Throwable $e) {
        return $fallback;
    }
}

function clamp_granularity(string $g): string {
    $g = strtolower(trim($g));
    return in_array($g, ['day', 'week', 'month'], true) ? $g : 'day';
}

function safe_ident(string $s, string $fallback): string {
    // allow only letters, numbers, underscore (table/column names)
    if (preg_match('/^[a-zA-Z0-9_]+$/', $s)) return $s;
    return $fallback;
}

function db(): PDO {
    $driver = get_str('DB_DRIVER', 'mysql');

    if ($driver === 'sqlite') {
        $path = get_str('DB_SQLITE_PATH', '');
        if ($path === '') json_response(['error' => 'DB_SQLITE_PATH no configurado'], 500);

        $pdo = new PDO("sqlite:" . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }

    $host = get_str('DB_HOST', '127.0.0.1');
    $port = get_str('DB_PORT', '3306');
    $name = get_str('DB_NAME', '');
    $user = get_str('DB_USER', '');
    $pass = get_str('DB_PASS', '');

    if ($name === '' || $user === '') json_response(['error' => 'DB_NAME/DB_USER no configurados'], 500);

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function group_expr(string $driver, string $colOccurredAt, string $granularity): string {
    if ($driver === 'sqlite') {
        return match ($granularity) {
            'month' => "strftime('%Y-%m', {$colOccurredAt})",
            'week'  => "strftime('%Y-W%W', {$colOccurredAt})",
            default => "strftime('%Y-%m-%d', {$colOccurredAt})",
        };
    }
    // mysql
    return match ($granularity) {
        'month' => "DATE_FORMAT({$colOccurredAt}, '%Y-%m')",
        'week'  => "DATE_FORMAT({$colOccurredAt}, '%x-W%v')",
        default => "DATE_FORMAT({$colOccurredAt}, '%Y-%m-%d')",
    };
}

// Load .env if exists (optional)
load_env_file(__DIR__ . '/../.env');

// Timezone
$tz = get_str('APP_TIMEZONE', 'UTC');
date_default_timezone_set($tz);

// Inputs
$today = new DateTimeImmutable('today');
$defaultFrom = $today->sub(new DateInterval('P6D'));
$defaultTo   = $today->add(new DateInterval('P1D'));

$from = parse_date_ymd(get_q('from', $defaultFrom->format('Y-m-d')), $defaultFrom);
$to   = parse_date_ymd(get_q('to',   $defaultTo->format('Y-m-d')),   $defaultTo);
$g    = clamp_granularity(get_q('g', 'day'));

if ($to <= $from) {
    json_response(['error' => 'Rango inválido: "to" debe ser mayor que "from"'], 400);
}

// Configurable table/cols (env only, not query)
$table = safe_ident(get_str('EVENTS_TABLE', 'events'), 'events');
$colEvent = safe_ident(get_str('COL_EVENT_NAME', 'event_name'), 'event_name');
$colUser  = safe_ident(get_str('COL_USER_ID', 'user_id'), 'user_id');
$colTime  = safe_ident(get_str('COL_OCCURRED_AT', 'occurred_at'), 'occurred_at');

// Event names (env defaults, overridable via query to avoid hardcoding)
$eventSignup       = trim(get_q('e_signup', get_str('EVENT_SIGNUP', 'user_signup')));
$eventHireCreated  = trim(get_q('e_hire_created', get_str('EVENT_HIRE_CREATED', 'hire_created')));
$eventHireCompleted= trim(get_q('e_hire_completed', get_str('EVENT_HIRE_COMPLETED', 'hire_completed')));

try {
    $pdo = db();
    $driver = get_str('DB_DRIVER', 'mysql');

    // KPI query
    $kpiSql = "
      SELECT
        SUM(CASE WHEN {$colEvent} = :signup THEN 1 ELSE 0 END) AS signups,
        SUM(CASE WHEN {$colEvent} = :hc THEN 1 ELSE 0 END) AS hires_created,
        SUM(CASE WHEN {$colEvent} = :hd THEN 1 ELSE 0 END) AS hires_completed,
        COUNT(DISTINCT CASE WHEN {$colUser} IS NOT NULL THEN {$colUser} END) AS active_users
      FROM {$table}
      WHERE {$colTime} >= :from AND {$colTime} < :to
    ";
    // MySQL optimization: boolean sums
    if ($driver !== 'sqlite') {
        $kpiSql = "
          SELECT
            SUM({$colEvent} = :signup) AS signups,
            SUM({$colEvent} = :hc) AS hires_created,
            SUM({$colEvent} = :hd) AS hires_completed,
            COUNT(DISTINCT {$colUser}) AS active_users
          FROM {$table}
          WHERE {$colTime} >= :from AND {$colTime} < :to
        ";
    }

    $stmt = $pdo->prepare($kpiSql);
    $stmt->execute([
        ':signup' => $eventSignup,
        ':hc'     => $eventHireCreated,
        ':hd'     => $eventHireCompleted,
        ':from'   => $from->format('Y-m-d H:i:s'),
        ':to'     => $to->format('Y-m-d H:i:s'),
    ]);
    $kpis = $stmt->fetch() ?: ['signups'=>0,'hires_created'=>0,'hires_completed'=>0,'active_users'=>0];

    // Series query
    $periodExpr = group_expr($driver, $colTime, $g);

    $seriesSql = "
      SELECT
        {$periodExpr} AS period,
        SUM(CASE WHEN {$colEvent} = :signup THEN 1 ELSE 0 END) AS signups,
        SUM(CASE WHEN {$colEvent} = :hc THEN 1 ELSE 0 END) AS hires_created,
        SUM(CASE WHEN {$colEvent} = :hd THEN 1 ELSE 0 END) AS hires_completed,
        COUNT(DISTINCT CASE WHEN {$colUser} IS NOT NULL THEN {$colUser} END) AS active_users
      FROM {$table}
      WHERE {$colTime} >= :from AND {$colTime} < :to
      GROUP BY period
      ORDER BY period ASC
    ";
    if ($driver !== 'sqlite') {
        $seriesSql = "
          SELECT
            {$periodExpr} AS period,
            SUM({$colEvent} = :signup) AS signups,
            SUM({$colEvent} = :hc) AS hires_created,
            SUM({$colEvent} = :hd) AS hires_completed,
            COUNT(DISTINCT {$colUser}) AS active_users
          FROM {$table}
          WHERE {$colTime} >= :from AND {$colTime} < :to
          GROUP BY period
          ORDER BY period ASC
        ";
    }

    $stmt2 = $pdo->prepare($seriesSql);
    $stmt2->execute([
        ':signup' => $eventSignup,
        ':hc'     => $eventHireCreated,
        ':hd'     => $eventHireCompleted,
        ':from'   => $from->format('Y-m-d H:i:s'),
        ':to'     => $to->format('Y-m-d H:i:s'),
    ]);
    $series = $stmt2->fetchAll() ?: [];

    // Normalize ints
    $kpisOut = [
        'active_users'    => (int)($kpis['active_users'] ?? 0),
        'signups'         => (int)($kpis['signups'] ?? 0),
        'hires_created'   => (int)($kpis['hires_created'] ?? 0),
        'hires_completed' => (int)($kpis['hires_completed'] ?? 0),
    ];

    $seriesOut = array_map(static function(array $r): array {
        return [
            'period'         => (string)($r['period'] ?? ''),
            'active_users'   => (int)($r['active_users'] ?? 0),
            'signups'        => (int)($r['signups'] ?? 0),
            'hires_created'  => (int)($r['hires_created'] ?? 0),
            'hires_completed'=> (int)($r['hires_completed'] ?? 0),
        ];
    }, $series);

    json_response([
        'kpis' => $kpisOut,
        'series' => $seriesOut,
        'meta' => [
            'from' => $from->format('Y-m-d'),
            'to'   => $to->format('Y-m-d'),
            'g'    => $g,
            'events' => [
                'signup'        => $eventSignup,
                'hire_created'  => $eventHireCreated,
                'hire_completed'=> $eventHireCompleted,
            ]
        ],
    ]);

} catch (Throwable $e) {
    json_response(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
