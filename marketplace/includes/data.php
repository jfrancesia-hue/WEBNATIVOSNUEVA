<?php
/**
 * Carga catálogo, testimonios y tech stack desde Supabase (con caché).
 * Expone las mismas variables que el resto de la app ya consume:
 *   $PRODUCTS, $TESTIMONIALS, $TECH_STACK
 *
 * Si Supabase no está configurado o falla y no hay caché, las variables
 * quedan como arrays vacíos y la página renderiza vacía sin romperse.
 */
declare(strict_types=1);

require_once __DIR__ . '/supabase.php';

try {
    $rawProducts = cached('products', fn() =>
        supabase_get('products', 'select=*&order=sort_order.asc')
    );
    $PRODUCTS = array_map('map_product_row', $rawProducts);
} catch (Throwable $e) {
    error_log('[data] productos no disponibles: ' . $e->getMessage());
    $PRODUCTS = [];
}

try {
    $rawTestimonials = cached('testimonials', fn() =>
        supabase_get('testimonials', 'select=name,role,image,quote&order=sort_order.asc')
    );
    $TESTIMONIALS = array_map(fn($t) => [
        'name'  => (string)($t['name']  ?? ''),
        'role'  => (string)($t['role']  ?? ''),
        'image' => (string)($t['image'] ?? ''),
        'quote' => (string)($t['quote'] ?? ''),
    ], $rawTestimonials);
} catch (Throwable $e) {
    error_log('[data] testimonios no disponibles: ' . $e->getMessage());
    $TESTIMONIALS = [];
}

try {
    $rawTech = cached('tech_stack', fn() =>
        supabase_get('tech_stack', 'select=name&order=sort_order.asc')
    );
    $TECH_STACK = array_map(fn($t) => (string)($t['name'] ?? ''), $rawTech);
    $TECH_STACK = array_values(array_filter($TECH_STACK, fn($n) => $n !== ''));
} catch (Throwable $e) {
    error_log('[data] tech stack no disponible: ' . $e->getMessage());
    $TECH_STACK = [];
}
