<?php
/**
 * Plantilla de configuracion SMTP para send.php
 *
 * Pasos para activar:
 *  1. Copiar este archivo como config.local.php en el mismo directorio
 *  2. Completar username + password con el App Password de Google Workspace
 *     (https://myaccount.google.com/apppasswords)
 *  3. config.local.php NO se commitea (esta en .gitignore)
 *
 * Solo es necesario setear username y password. Los demas campos ya tienen
 * defaults razonables en send.php.
 */

return [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'username'   => 'jorge@nativosconsultora.com.ar',
    'password'   => 'PEGAR-APP-PASSWORD-DE-16-CARACTERES-AQUI',
    'from_email' => 'jorge@nativosconsultora.com.ar',
    'from_name'  => 'Web Nativos',
    'to_email'   => 'jorge@nativosconsultora.com.ar',
    'to_name'    => 'Jorge Francesia',
];
