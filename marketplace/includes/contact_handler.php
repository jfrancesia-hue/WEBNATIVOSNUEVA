<?php
declare(strict_types=1);

/**
 * Procesa el formulario de contacto (POST) y devuelve estado para el render.
 *
 * Envía el mensaje por mail() a CONTACT_TO (default: jfrancesia@gmail.com).
 * En Hostinger el header From: debe ser un buzón del propio dominio para que
 * el relay SMTP no rebote el mensaje; el correo del visitante va en Reply-To.
 *
 * @return array{errors: array<string,string>, success: bool, data: array<string,string>}
 */
function handle_contact_form(): array {
    $state = [
        'errors' => [],
        'success' => false,
        'data' => ['name' => '', 'email' => '', 'message' => ''],
    ];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['form'] ?? '') !== 'contact') {
        return $state;
    }

    $name    = trim((string)($_POST['name']    ?? ''));
    $email   = trim((string)($_POST['email']   ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));

    $state['data'] = ['name' => $name, 'email' => $email, 'message' => $message];

    if ($name === '')    $state['errors']['name']    = 'El nombre es obligatorio';
    if ($email === '') {
        $state['errors']['email'] = 'El correo es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $state['errors']['email'] = 'Correo electrónico no válido';
    }
    if ($message === '') $state['errors']['message'] = 'El mensaje es obligatorio';

    if (!empty($state['errors'])) {
        return $state;
    }

    $logDir = __DIR__ . '/../storage';
    if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
    $logEntry = sprintf(
        "[%s] %s <%s>: %s\n",
        date('Y-m-d H:i:s'),
        str_replace(["\r","\n"], ' ', $name),
        str_replace(["\r","\n"], ' ', $email),
        str_replace(["\r","\n"], ' ', $message)
    );
    @file_put_contents($logDir . '/contact.log', $logEntry, FILE_APPEND | LOCK_EX);

    $sent = send_contact_email($name, $email, $message);
    if (!$sent) {
        error_log('[contact] mail() devolvió false; mensaje queda solo en contact.log');
    }

    $state['success'] = true;
    $state['data'] = ['name' => '', 'email' => '', 'message' => ''];

    return $state;
}

/**
 * Envía el mensaje del form via mail() nativo. Devuelve true si mail() aceptó el envío.
 */
function send_contact_email(string $name, string $email, string $message): bool {
    $to      = (string)(getenv('CONTACT_TO') ?: 'jfrancesia@gmail.com');
    $fromEnv = (string)(getenv('CONTACT_FROM') ?: '');
    $from    = $fromEnv !== '' ? $fromEnv : 'no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $subject = (string)(getenv('CONTACT_SUBJECT') ?: 'Nuevo contacto desde Nativos Launchpad');

    // Hardening anti CRLF-injection: nombre/email van en headers, así que no pueden tener saltos.
    $safeName  = preg_replace('/[\r\n]+/', ' ', $name) ?? '';
    $safeEmail = preg_replace('/[\r\n]+/', ' ', $email) ?? '';

    $body  = "Nuevo mensaje desde el formulario de contacto de Nativos Launchpad.\n\n";
    $body .= "Nombre:  {$safeName}\n";
    $body .= "Email:   {$safeEmail}\n";
    $body .= "Fecha:   " . date('Y-m-d H:i:s') . "\n";
    $body .= "IP:      " . ($_SERVER['REMOTE_ADDR'] ?? '-') . "\n";
    $body .= str_repeat('-', 60) . "\n\n";
    $body .= $message . "\n";

    // Encoding de subject (RFC 2047) por si el sujeto trae acentos vía env.
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $fromHeader = sprintf('"%s via Nativos" <%s>', addslashes($safeName !== '' ? $safeName : 'Web'), $from);

    $headers = implode("\r\n", [
        'From: ' . $fromHeader,
        'Reply-To: ' . $safeEmail,
        'X-Mailer: PHP/' . PHP_VERSION,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ]);

    // -f setea el envelope sender; en muchos hostings (Hostinger incluido) ayuda con SPF.
    $additionalParams = '-f ' . escapeshellarg($from);

    return @mail($to, $encodedSubject, $body, $headers, $additionalParams);
}
