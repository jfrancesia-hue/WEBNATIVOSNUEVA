# Nativos Consultora Digital

Sitio institucional estatico con formulario PHP y modulo de analiticas.

## Estructura

- `index.html`: landing principal.
- `send.php`: endpoint del formulario de contacto.
- `config.example.php`: plantilla de configuracion SMTP.
- `Analiticas/`: dashboard de analiticas.
- `api/metrics.php`: API PHP para metricas.
- `assets/visuals/`: imagenes optimizadas usadas por el sitio.

## Configuracion del formulario

Copiar `config.example.php` como `config.local.php` en el servidor y completar las credenciales SMTP.
`config.local.php` esta ignorado por git para evitar publicar credenciales.

Tambien se pueden usar variables de entorno:

- `SMTP_HOST`
- `SMTP_USERNAME`
- `SMTP_PASSWORD`
- `SMTP_PORT`
- `SMTP_FROM_EMAIL`
- `SMTP_FROM_NAME`
- `SMTP_TO_EMAIL`
- `SMTP_TO_NAME`

