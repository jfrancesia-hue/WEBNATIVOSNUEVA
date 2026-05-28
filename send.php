<?php
declare(strict_types=1);

/**
 * Form de contacto de la landing.
 * Envia los mensajes a contacto@nativosconsultora.com.ar via mail() nativo.
 * No requiere SMTP configurado: usa el relay del propio Hostinger.
 *
 * Importante: el buzon contacto@nativosconsultora.com.ar debe existir en
 * hPanel -> Emails -> Email Accounts para que el correo sea entregado.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo 'Metodo no permitido';
    exit;
}

$name    = trim(strip_tags((string)($_POST['name']    ?? '')));
$emailIn = trim((string)($_POST['email']   ?? ''));
$company = trim(strip_tags((string)($_POST['company'] ?? '')));
$message = trim(strip_tags((string)($_POST['message'] ?? '')));

$email = filter_var($emailIn, FILTER_SANITIZE_EMAIL);

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo 'Datos invalidos';
    exit;
}

$to      = 'contacto@nativosconsultora.com.ar';
$from    = 'contacto@nativosconsultora.com.ar';
$subject = 'Nuevo mensaje desde la web';

// Anti CRLF-injection en headers
$safeName  = preg_replace('/[\r\n]+/', ' ', $name)  ?? '';
$safeEmail = preg_replace('/[\r\n]+/', ' ', $email) ?? '';

$body  = "Nuevo mensaje desde el formulario de Nativos Consultora.\n\n";
$body .= "Nombre:       {$safeName}\n";
$body .= "Email:        {$safeEmail}\n";
if ($company !== '') $body .= "Organizacion: {$company}\n";
$body .= "Fecha:        " . date('Y-m-d H:i:s') . "\n";
$body .= "IP:           " . ($_SERVER['REMOTE_ADDR'] ?? '-') . "\n";
$body .= str_repeat('-', 60) . "\n\n";
$body .= $message . "\n";

$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

$fromHeader = sprintf(
    '"%s via Nativos" <%s>',
    addslashes($safeName !== '' ? $safeName : 'Web'),
    $from
);

$headers = implode("\r\n", [
    'From: ' . $fromHeader,
    'Reply-To: ' . $safeEmail,
    'X-Mailer: PHP/' . PHP_VERSION,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
]);

$additionalParams = '-f ' . escapeshellarg($from);

$sent = @mail($to, $encodedSubject, $body, $headers, $additionalParams);

if (!$sent) {
    error_log('[send.php] mail() devolvio false para visitante ' . $safeEmail);
    http_response_code(500);
    echo 'Error al enviar';
    exit;
}

echo 'OK';
