<?php
/**
 * Devuelve el HTML del modal de detalle para un producto dado.
 * Llamado vía fetch desde el front al hacer click en una tarjeta.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/data.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$id = $_GET['id'] ?? '';
$product = null;
foreach ($PRODUCTS as $p) {
    if ($p['id'] === $id) { $product = $p; break; }
}

if (!$product) {
    http_response_code(404);
    echo '<div class="p-8 text-center text-gray-400">Producto no encontrado.</div>';
    exit;
}

include __DIR__ . '/../includes/product_modal.php';
