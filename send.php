<?php
declare(strict_types=1);

/**
 * Form de contacto de la landing.
 * Envia los mensajes via SMTP de Google Workspace (smtp.gmail.com).
 *
 * Credenciales: vienen de config.local.php (no commiteado, vive solo en Hostinger).
 * El archivo config.local.php debe estar en el mismo directorio que este script.
 *
 * Para configurar: copiar config.example.php como config.local.php y completar
 * con el App Password generado en https://myaccount.google.com/apppasswords
 */

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

function load_smtp_config(): array {
    $defaults = [
        'host'        => 'smtp.gmail.com',
        'port'        => 587,
        'username'    => '',
        'password'    => '',
        'from_email'  => 'jorge@nativosconsultora.com.ar',
        'from_name'   => 'Web Nativos',
        'to_email'    => 'jorge@nativosconsultora.com.ar',
        'to_name'     => 'Jorge Francesia',
    ];

    $localPath = __DIR__ . '/config.local.php';
    $local = file_exists($localPath) ? (array)require $localPath : [];

    return array_merge($defaults, $local);
}

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

$config = load_smtp_config();
if ($config['username'] === '' || $config['password'] === '') {
    http_response_code(500);
    error_log('[send.php] config.local.php sin credenciales SMTP');
    echo 'Configuracion SMTP incompleta';
    exit;
}

$safeName  = preg_replace('/[\r\n]+/', ' ', $name)  ?? '';
$safeEmail = preg_replace('/[\r\n]+/', ' ', $email) ?? '';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $config['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['username'];
    $mail->Password   = $config['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int)$config['port'];
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['to_email'], $config['to_name']);
    $mail->addReplyTo($safeEmail, $safeName);

    $mail->isHTML(true);
    $mail->Subject = 'Nuevo mensaje desde la web';

    $bodyHtml  = '<h3>Nuevo mensaje desde el formulario de Nativos Consultora</h3>';
    $bodyHtml .= '<p><strong>Nombre:</strong> ' . htmlspecialchars($safeName, ENT_QUOTES, 'UTF-8') . '</p>';
    if ($company !== '') {
        $bodyHtml .= '<p><strong>Organizacion:</strong> ' . htmlspecialchars($company, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    $bodyHtml .= '<p><strong>Email:</strong> ' . htmlspecialchars($safeEmail, ENT_QUOTES, 'UTF-8') . '</p>';
    $bodyHtml .= '<p><strong>Fecha:</strong> ' . date('Y-m-d H:i:s') . '</p>';
    $bodyHtml .= '<p><strong>Mensaje:</strong><br>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>';
    $mail->Body = $bodyHtml;

    $altBody  = "Nombre: {$safeName}\n";
    if ($company !== '') $altBody .= "Organizacion: {$company}\n";
    $altBody .= "Email: {$safeEmail}\n";
    $altBody .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
    $altBody .= "\nMensaje:\n{$message}\n";
    $mail->AltBody = $altBody;

    $mail->send();
    echo 'OK';
} catch (Exception $e) {
    error_log('[send.php] PHPMailer error: ' . $mail->ErrorInfo);
    http_response_code(500);
    echo 'Error al enviar';
}
