<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

function smtp_config(): array
{
    $localConfigPath = __DIR__ . '/config.local.php';
    $localConfig = file_exists($localConfigPath) ? require $localConfigPath : [];

    return [
        'host' => getenv('SMTP_HOST') ?: ($localConfig['smtp_host'] ?? ''),
        'username' => getenv('SMTP_USERNAME') ?: ($localConfig['smtp_username'] ?? ''),
        'password' => getenv('SMTP_PASSWORD') ?: ($localConfig['smtp_password'] ?? ''),
        'port' => (int) (getenv('SMTP_PORT') ?: ($localConfig['smtp_port'] ?? 587)),
        'from_email' => getenv('SMTP_FROM_EMAIL') ?: ($localConfig['smtp_from_email'] ?? ''),
        'from_name' => getenv('SMTP_FROM_NAME') ?: ($localConfig['smtp_from_name'] ?? 'Formulario Web'),
        'to_email' => getenv('SMTP_TO_EMAIL') ?: ($localConfig['smtp_to_email'] ?? ''),
        'to_name' => getenv('SMTP_TO_NAME') ?: ($localConfig['smtp_to_name'] ?? 'Nativos Consultora'),
    ];
}

function clean_input(string $key): string
{
    return trim(strip_tags((string) ($_POST[$key] ?? '')));
}

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo 'Metodo no permitido';
    exit;
}

$name = clean_input('name');
$email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
$company = clean_input('company');
$message = clean_input('message');

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo 'Datos invalidos';
    exit;
}

$config = smtp_config();
$required = ['host', 'username', 'password', 'from_email', 'to_email'];
foreach ($required as $key) {
    if (($config[$key] ?? '') === '') {
        http_response_code(500);
        echo 'Configuracion SMTP incompleta';
        exit;
    }
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $config['port'];
    $mail->CharSet = 'UTF-8';

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['to_email'], $config['to_name']);
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = 'Nuevo mensaje desde la web';
    $mail->Body = sprintf(
        '<h3>Nuevo mensaje desde el formulario web</h3>
        <p><strong>Nombre:</strong> %s</p>
        <p><strong>Organizacion:</strong> %s</p>
        <p><strong>Email:</strong> %s</p>
        <p><strong>Mensaje:</strong><br>%s</p>',
        escape_html($name),
        escape_html($company),
        escape_html($email),
        nl2br(escape_html($message))
    );
    $mail->AltBody = "Nombre: {$name}\nOrganizacion: {$company}\nEmail: {$email}\nMensaje:\n{$message}";

    $mail->send();
    echo 'OK';
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error al enviar';
}
?>
