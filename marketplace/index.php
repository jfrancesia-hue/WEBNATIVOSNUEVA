<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/contact_handler.php';

$contactState = handle_contact_form();

// --- Filtros y paginación desde la URL ---
$searchQuery       = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$selectedCategories = isset($_GET['cats']) ? (array)$_GET['cats'] : [];
$selectedCategories = array_values(array_filter(array_map('strval', $selectedCategories), fn($c) => $c !== ''));
$showOnlyVerified  = !empty($_GET['verified']);
$currentPage       = max(1, (int)($_GET['page'] ?? 1));
const ITEMS_PER_PAGE = 12;

$allCategories     = unique_categories($PRODUCTS);
$filteredProducts  = filter_products($PRODUCTS, $searchQuery, $selectedCategories, $showOnlyVerified);
$pagination        = paginate($filteredProducts, $currentPage, ITEMS_PER_PAGE);
$paginatedProducts = $pagination['items'];
$totalPages        = $pagination['totalPages'];
$currentPage       = $pagination['page'];

$hasActiveFilters = $searchQuery !== '' || !empty($selectedCategories) || $showOnlyVerified;
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nativos Launchpad · Negocios digitales listos para operar</title>
<meta name="description" content="Plataforma premium para la adquisición de negocios digitales operativos: SaaS, marketplaces y e-commerce listos para escalar.">

<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">

<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        serif: ['"Playfair Display"', 'ui-serif', 'Georgia', 'serif'],
      },
      colors: {
        gold: {
          50:'#fbf8eb',100:'#f4edcc',200:'#ead99d',300:'#ddbf66',
          400:'#d2a63d',500:'#b8862d',600:'#9e6825',700:'#7e4d20',
          800:'#683f20',900:'#57351d',950:'#321c0d',
        },
      },
    },
  },
};
</script>

<link rel="stylesheet" href="assets/css/styles.css">
</head>

<body class="bg-black text-white antialiased font-sans selection:bg-gold-400 selection:text-black">

<!-- Navbar -->
<nav id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-transparent py-6">
  <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
    <a href="#top" class="flex items-center gap-2">
      <div class="w-8 h-8 bg-gradient-to-br from-gold-400 to-gold-600 rounded-lg flex items-center justify-center text-black">
        <?= icon('trending-up', 'w-5 h-5') ?>
      </div>
      <span class="font-serif font-bold text-xl tracking-tight leading-none">
        NATIVOS
        <span class="text-gold-400 text-sm block -mt-1 font-sans font-normal">LAUNCHPAD</span>
      </span>
    </a>

    <div class="hidden md:flex items-center gap-8">
      <a href="#how-it-works" class="text-sm font-medium hover:text-gold-400 transition-colors">Cómo Funciona</a>
      <a href="#portfolio" class="text-sm font-medium hover:text-gold-400 transition-colors">Catálogo</a>
      <a href="#portfolio" class="text-sm font-medium hover:text-gold-400 transition-colors">Seguridad</a>
     <a 
  href="https://wa.me/5493813005807"
  target="_blank"
  class="micro-pop px-6 py-2 rounded-full border border-gold-400/30 text-sm font-medium hover:bg-gold-400 hover:text-black transition-all duration-300"
>
  Contactar
</a>
    </div>

    <button id="mobile-toggle" class="md:hidden text-white hover:text-gold-400 transition-colors" aria-label="Menú">
      <?= icon('menu', 'w-6 h-6') ?>
    </button>
  </div>

  <!-- Mobile menu -->
  <div id="mobile-menu" class="hidden md:hidden border-t border-white/10 bg-black/80 backdrop-blur-lg">
    <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col gap-4 text-sm">
      <a href="#how-it-works" class="hover:text-gold-400">Cómo Funciona</a>
      <a href="#portfolio" class="hover:text-gold-400">Catálogo</a>
      <a href="#contact" class="hover:text-gold-400">Contactar</a>
    </div>
  </div>
</nav>

<!-- Hero -->
<section id="top" class="relative min-h-screen flex items-center pt-20 overflow-hidden">
  <div class="absolute inset-0 z-0">
    <img
      src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=2000"
      alt="" class="w-full h-full object-cover opacity-40" referrerpolicy="no-referrer">
    <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/80 to-black"></div>
  </div>

  <div class="relative z-10 max-w-7xl mx-auto px-6 w-full text-center">
    <div class="fade-up">
      <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gold-400/10 border border-gold-400/20 text-gold-400 text-xs font-bold uppercase tracking-widest mb-8">
        <span class="text-gold-400" style="fill:currentColor"><?= icon('star', 'w-3 h-3 fill-current') ?></span>
        Negocios Digitales Premium
      </div>

      <h1 class="text-5xl md:text-7xl lg:text-8xl font-serif font-bold leading-[1.1] mb-8">
        Adquirí un negocio <br>
        <span class="italic font-normal gold-gradient">listo para facturar</span>
      </h1>

      <p class="max-w-2xl mx-auto text-gray-400 text-lg md:text-xl mb-12">
        Plataforma exclusiva para la compra y lanzamiento inmediato de empresas digitales operativas.
      </p>

      <div class="flex flex-col sm:flex-row items-center justify-center gap-12 mb-16">
        <div class="text-center">
          <div class="text-3xl font-bold text-white"><?= count($PRODUCTS) ?>+</div>
          <div class="text-xs text-gray-500 uppercase tracking-widest mt-1">Productos</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold text-white">100%</div>
          <div class="text-xs text-gray-500 uppercase tracking-widest mt-1">Funcionales</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold text-white">&infin;</div>
          <div class="text-xs text-gray-500 uppercase tracking-widest mt-1">Potencial</div>
        </div>
      </div>

      <div class="flex flex-wrap justify-center gap-4">
        <a href="#portfolio" class="gold-button micro-pop px-10 py-4 rounded-full text-lg flex items-center gap-2 group">
          Explorar catálogo
          <?= icon('chevron-right', 'w-5 h-5 group-hover:translate-x-1 transition-transform') ?>
        </a>
      </div>
    </div>
  </div>

  <div class="absolute bottom-0 left-0 right-0 py-6 bg-white/5 backdrop-blur-sm border-t border-white/10 overflow-hidden">
    <div class="flex whitespace-nowrap animate-marquee">
      <?php foreach (array_merge($TECH_STACK, $TECH_STACK) as $tech): ?>
        <div class="flex items-center gap-2 mx-8">
          <div class="w-1.5 h-1.5 bg-gold-400 rounded-full"></div>
          <span class="text-xs font-medium text-gray-400 uppercase tracking-widest"><?= e($tech) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Why choose us -->
<section class="py-32 bg-black">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid lg:grid-cols-2 gap-20 items-center">
      <div class="reveal" data-reveal="left">
        <h2 class="text-4xl md:text-5xl font-serif font-bold mb-12">
          ¿Por qué elegir <br> Nativos?
        </h2>
        <div class="space-y-6">
          <?php
          $reasons = [
              ['01', 'Selección Curada.', 'Negocios auditados, rentables y escalables.'],
              ['02', 'Launchpad Integrado.', 'Todo listo para operar desde el día uno.'],
              ['03', 'Soporte Premium.', 'Asistencia técnica y estratégica post-adquisición.'],
          ];
          foreach ($reasons as $r): ?>
            <div class="glass-card p-6 rounded-2xl flex gap-6 group hover:border-gold-400/50 transition-colors">
              <div class="text-2xl font-serif font-bold text-gold-400 opacity-50 group-hover:opacity-100 transition-opacity"><?= e($r[0]) ?></div>
              <div>
                <h3 class="text-lg font-bold mb-1"><?= e($r[1]) ?></h3>
                <p class="text-gray-400 text-sm"><?= e($r[2]) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="reveal relative" data-reveal="right">
        <div class="aspect-square rounded-3xl overflow-hidden relative group">
          <img
            src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&q=80&w=1000"
            alt="Equipo" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
            referrerpolicy="no-referrer">
          <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent"></div>
          <div class="absolute bottom-10 left-10 right-10">
            <div class="text-5xl font-serif font-bold mb-2">48hs</div>
            <div class="text-2xl font-bold text-gold-400 mb-4">Time to Market</div>
            <p class="text-gray-300 text-sm">Lanzamiento rápido y seguro.</p>
          </div>
        </div>
        <div class="absolute -top-6 -right-6 w-32 h-32 border-t-2 border-r-2 border-gold-400/30 rounded-tr-3xl"></div>
        <div class="absolute -bottom-6 -left-6 w-32 h-32 border-b-2 border-l-2 border-gold-400/30 rounded-bl-3xl"></div>
      </div>
    </div>
  </div>
</section>

<!-- How it works / Video placeholder -->
<section id="how-it-works" class="py-32 bg-black relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid lg:grid-cols-2 gap-20 items-center">
      <div class="reveal" data-reveal="left">
        <h2 class="text-4xl md:text-5xl font-serif font-bold mb-8">
          Adquiere tu próximo negocio en <br>
          <span class="gold-gradient italic font-normal">cuestión de segundos</span>
        </h2>
        <p class="text-gray-400 text-lg mb-12 max-w-md">
          Nuestra plataforma simplifica el proceso de adquisición, garantizando seguridad y rapidez en cada paso.
        </p>
        <div class="space-y-6">
          <?php
          $steps = [
              ['01', 'Explora el Catálogo', 'Encuentra negocios auditados y listos para operar.'],
              ['02', 'Solicita Acceso', 'Revisa datos financieros detallados bajo NDA.'],
              ['03', 'Cierra el Trato', 'Transferencia segura mediante nuestro sistema de Escrow.'],
          ];
          foreach ($steps as $s): ?>
            <div class="flex gap-6">
              <div class="text-2xl font-serif font-bold text-gold-400/30"><?= e($s[0]) ?></div>
              <div>
                <h4 class="text-white font-bold mb-1"><?= e($s[1]) ?></h4>
                <p class="text-gray-500 text-sm"><?= e($s[2]) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="reveal relative" data-reveal="right">
        <div class="aspect-video bg-white/5 rounded-3xl border border-white/10 overflow-hidden group relative flex items-center justify-center">
          <img
            src="https://images.unsplash.com/photo-1551434678-e076c223a692?auto=format&fit=crop&q=80&w=1200"
            alt="Proceso" class="w-full h-full object-cover opacity-40 group-hover:scale-105 transition-transform duration-700"
            referrerpolicy="no-referrer">
          <div class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center">
            <button type="button" class="micro-pop w-20 h-20 bg-gold-400 rounded-full flex items-center justify-center shadow-2xl shadow-gold-400/20 mb-6 text-black">
              <?= icon('play-circle', 'w-10 h-10') ?>
            </button>
            <h3 class="text-xl font-bold text-white mb-2">Ver Proceso en Acción</h3>
            <p class="text-sm text-gray-400 max-w-xs">Recorre el proceso de adquisición paso a paso.</p>
          </div>
        </div>
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-gold-400/10 blur-3xl rounded-full"></div>
        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-gold-400/5 blur-3xl rounded-full"></div>
      </div>
    </div>
  </div>
</section>

<!-- Portfolio -->
<section id="portfolio" class="py-32 bg-[#080808]">
  <div class="max-w-7xl mx-auto px-6">
    <div class="text-center mb-12 reveal">
      <h2 class="text-4xl md:text-5xl font-serif font-bold mb-4">Portfolio de oportunidades</h2>
      <p class="text-gray-500 max-w-xl mx-auto">Explora nuestras empresas digitales listas para ser adquiridas y operadas.</p>
    </div>

    <!-- Filtros -->
    <form method="get" action="#portfolio" class="max-w-4xl mx-auto mb-16 space-y-8">
      <div class="relative group">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 group-focus-within:text-gold-400 transition-colors">
          <?= icon('search', 'w-5 h-5') ?>
        </span>
        <input
          type="text" name="q" value="<?= e($searchQuery) ?>"
          placeholder="Buscar por nombre o categoría..."
          class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 pl-12 pr-6 text-sm focus:outline-none focus:border-gold-400/50 focus:bg-white/10 transition-all">
      </div>

      <div class="flex flex-col md:flex-row gap-6 items-start md:items-center justify-between">
        <div class="flex flex-wrap gap-2 items-center">
          <div class="flex items-center gap-2 mr-2 text-gray-500 text-xs uppercase tracking-widest font-bold">
            <?= icon('filter', 'w-3 h-3') ?> Filtros:
          </div>
          <?php foreach ($allCategories as $cat):
            $active = in_array($cat, $selectedCategories, true); ?>
            <label class="cursor-pointer px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider transition-all border <?= $active ? 'bg-gold-400 border-gold-400 text-black' : 'bg-white/5 border-white/10 text-gray-400 hover:border-white/30' ?>">
              <input type="checkbox" name="cats[]" value="<?= e($cat) ?>" <?= $active ? 'checked' : '' ?> class="sr-only filter-input">
              <?= e($cat) ?>
            </label>
          <?php endforeach; ?>
        </div>

        <label class="cursor-pointer flex items-center gap-2 px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider transition-all border shrink-0 <?= $showOnlyVerified ? 'bg-gold-400/20 border-gold-400 text-gold-400' : 'bg-white/5 border-white/10 text-gray-400 hover:border-white/30' ?>">
          <input type="checkbox" name="verified" value="1" <?= $showOnlyVerified ? 'checked' : '' ?> class="sr-only filter-input">
          <span class="w-3 h-3 rounded-sm border flex items-center justify-center transition-colors <?= $showOnlyVerified ? 'bg-gold-400 border-gold-400 text-black' : 'border-gray-600' ?>">
            <?php if ($showOnlyVerified): ?><?= icon('check', 'w-2 h-2') ?><?php endif; ?>
          </span>
          Solo Verificados
        </label>
      </div>

      <div class="flex items-center justify-center gap-4">
        <button type="submit" class="text-[10px] font-bold uppercase tracking-widest text-gold-400 hover:underline">Aplicar filtros</button>
        <?php if ($hasActiveFilters): ?>
          <a href="?#portfolio" class="text-[10px] font-bold text-gray-500 uppercase tracking-widest hover:text-gold-400 transition-colors">
            Limpiar todos los filtros
          </a>
        <?php endif; ?>
      </div>
    </form>

    <!-- Security banner -->
    <div class="mb-16 bg-gold-400/5 border border-gold-400/20 rounded-3xl p-8 flex flex-col md:flex-row items-center justify-between gap-8">
      <div class="flex items-center gap-6">
        <div class="w-16 h-16 rounded-2xl bg-gold-400/10 flex items-center justify-center text-gold-400">
          <?= icon('shield-check', 'w-8 h-8') ?>
        </div>
        <div class="text-left">
          <h3 class="text-xl font-bold text-white mb-1">Transacciones 100% Protegidas</h3>
          <p class="text-sm text-gray-500">Protocolos de Due Diligence y Escrow para garantizar la seguridad de cada adquisición.</p>
        </div>
      </div>
      <div class="flex gap-8">
        <div class="flex flex-col items-center">
          <div class="text-2xl font-bold text-gold-400">100%</div>
          <div class="text-[10px] text-gray-500 uppercase tracking-widest">Auditado</div>
        </div>
        <div class="w-px h-10 bg-white/10 hidden md:block"></div>
        <div class="flex flex-col items-center">
          <div class="text-2xl font-bold text-gold-400">KYC</div>
          <div class="text-[10px] text-gray-500 uppercase tracking-widest">Vendedores</div>
        </div>
      </div>
    </div>

    <!-- Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
      <?php if (!empty($paginatedProducts)): ?>
        <?php foreach ($paginatedProducts as $product): include __DIR__ . '/includes/product_card.php'; endforeach; ?>
      <?php else: ?>
        <div class="col-span-full py-20 text-center">
          <p class="text-gray-500 text-lg">No se encontraron productos que coincidan con tu búsqueda.</p>
          <a href="?#portfolio" class="mt-4 inline-block text-gold-400 hover:underline text-sm">Limpiar búsqueda</a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div class="mt-16 flex items-center justify-center gap-4">
        <?php $prev = max(1, $currentPage - 1); ?>
        <a href="?<?= e(build_query(['page' => $prev])) ?>#portfolio"
           class="p-2 rounded-full border border-white/10 text-gray-500 hover:text-gold-400 hover:border-gold-400/50 transition-all <?= $currentPage === 1 ? 'pointer-events-none opacity-30' : '' ?>">
          <?= icon('chevron-left', 'w-5 h-5') ?>
        </a>

        <div class="flex items-center gap-2">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?<?= e(build_query(['page' => $p])) ?>#portfolio"
               class="w-10 h-10 rounded-full text-sm font-medium transition-all flex items-center justify-center <?= $p === $currentPage ? 'bg-gold-400 text-black' : 'text-gray-500 hover:text-white hover:bg-white/5' ?>">
              <?= $p ?>
            </a>
          <?php endfor; ?>
        </div>

        <?php $next = min($totalPages, $currentPage + 1); ?>
        <a href="?<?= e(build_query(['page' => $next])) ?>#portfolio"
           class="p-2 rounded-full border border-white/10 text-gray-500 hover:text-gold-400 hover:border-gold-400/50 transition-all <?= $currentPage === $totalPages ? 'pointer-events-none opacity-30' : '' ?>">
          <?= icon('chevron-right', 'w-5 h-5') ?>
        </a>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Testimonials -->
<section class="py-32 bg-black">
  <div class="max-w-7xl mx-auto px-6">
    <div class="text-center mb-20 reveal">
      <h2 class="text-4xl md:text-5xl font-serif font-bold mb-4">Lo que dicen nuestros clientes</h2>
    </div>
    <div class="grid md:grid-cols-3 gap-8">
      <?php foreach ($TESTIMONIALS as $t): ?>
        <div class="glass-card p-8 rounded-3xl relative reveal">
          <span class="absolute top-8 right-8 text-gold-400/20"><?= icon('quote', 'w-8 h-8') ?></span>
          <div class="flex items-center gap-4 mb-6">
            <img src="<?= e($t['image']) ?>" alt="<?= e($t['name']) ?>" class="w-12 h-12 rounded-full object-cover border border-gold-400/30" referrerpolicy="no-referrer">
            <div>
              <div class="font-bold text-sm"><?= e($t['name']) ?></div>
              <div class="text-xs text-gray-500"><?= e($t['role']) ?></div>
            </div>
          </div>
          <p class="text-gray-400 text-sm italic leading-relaxed">"<?= e($t['quote']) ?>"</p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Contact -->
<section id="contact" class="py-32 bg-[#080808]">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid lg:grid-cols-2 gap-20">
      <div class="reveal" data-reveal="left">
        <h2 class="text-4xl md:text-5xl font-serif font-bold mb-8">
          ¿Listo para dar el <br><span class="gold-gradient italic font-normal">siguiente paso?</span>
        </h2>
        <p class="text-gray-400 text-lg mb-12 max-w-md">
          Nuestro equipo de expertos está listo para asesorarte en la adquisición de tu próximo activo digital.
        </p>
        <div class="space-y-8">
          <?php
          $contacts = [
              ['mail', 'Escríbenos', 'jfrancesia@gmail.com'],
              ['globe', 'Ubicación', 'Catamarca, Argentina'],
              ['shield-check', 'Privacidad', 'NDA Automático Garantizado'],
          ];
          foreach ($contacts as $c): ?>
            <div class="flex items-center gap-6">
              <div class="w-12 h-12 rounded-2xl bg-gold-400/10 flex items-center justify-center border border-gold-400/20 text-gold-400">
                <?= icon($c[0], 'w-6 h-6') ?>
              </div>
              <div>
                <div class="text-xs text-gray-500 uppercase tracking-widest"><?= e($c[1]) ?></div>
                <div class="text-white font-medium"><?= e($c[2]) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="reveal" data-reveal="right">
        <div class="glass-card p-8 md:p-10 rounded-3xl border-white/5">
          <?php if ($contactState['success']): ?>
            <div class="text-center py-12">
              <div class="w-20 h-20 bg-gold-400/20 rounded-full flex items-center justify-center mx-auto mb-6 text-gold-400">
                <?= icon('check-circle', 'w-10 h-10') ?>
              </div>
              <h3 class="text-2xl font-serif font-bold mb-2">¡Mensaje enviado!</h3>
              <p class="text-gray-400">Nos pondremos en contacto contigo a la brevedad.</p>
              <a href="#contact" class="mt-8 inline-block text-gold-400 text-sm hover:underline">Enviar otro mensaje</a>
            </div>
          <?php else: ?>
            <form method="post" action="#contact" class="space-y-6" novalidate>
              <input type="hidden" name="form" value="contact">
              <div class="space-y-2">
                <label class="text-xs text-gray-500 uppercase tracking-widest ml-1">Nombre completo</label>
                <input type="text" name="name" value="<?= e($contactState['data']['name']) ?>" placeholder="Tu nombre"
                  class="w-full bg-white/5 border <?= isset($contactState['errors']['name']) ? 'border-red-500/50' : 'border-white/10' ?> rounded-xl py-4 px-6 text-sm focus:outline-none focus:border-gold-400/50 transition-all">
                <?php if (isset($contactState['errors']['name'])): ?>
                  <p class="text-red-400 text-[10px] ml-1"><?= e($contactState['errors']['name']) ?></p>
                <?php endif; ?>
              </div>
              <div class="space-y-2">
                <label class="text-xs text-gray-500 uppercase tracking-widest ml-1">Correo electrónico</label>
                <input type="email" name="email" value="<?= e($contactState['data']['email']) ?>" placeholder="tu@email.com"
                  class="w-full bg-white/5 border <?= isset($contactState['errors']['email']) ? 'border-red-500/50' : 'border-white/10' ?> rounded-xl py-4 px-6 text-sm focus:outline-none focus:border-gold-400/50 transition-all">
                <?php if (isset($contactState['errors']['email'])): ?>
                  <p class="text-red-400 text-[10px] ml-1"><?= e($contactState['errors']['email']) ?></p>
                <?php endif; ?>
              </div>
              <div class="space-y-2">
                <label class="text-xs text-gray-500 uppercase tracking-widest ml-1">Mensaje</label>
                <textarea name="message" rows="4" placeholder="¿En qué podemos ayudarte?"
                  class="w-full bg-white/5 border <?= isset($contactState['errors']['message']) ? 'border-red-500/50' : 'border-white/10' ?> rounded-xl py-4 px-6 text-sm focus:outline-none focus:border-gold-400/50 transition-all resize-none"><?= e($contactState['data']['message']) ?></textarea>
                <?php if (isset($contactState['errors']['message'])): ?>
                  <p class="text-red-400 text-[10px] ml-1"><?= e($contactState['errors']['message']) ?></p>
                <?php endif; ?>
              </div>
              <button type="submit" class="micro-pop w-full gold-button py-4 rounded-xl flex items-center justify-center gap-2 group">
                Enviar mensaje
                <?= icon('send', 'w-4 h-4 group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform') ?>
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="py-12 border-t border-white/10 bg-black">
  <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-8">
    <div class="flex items-center gap-2">
      <div class="w-6 h-6 bg-gold-400 rounded flex items-center justify-center text-black">
        <?= icon('trending-up', 'w-4 h-4') ?>
      </div>
      <span class="font-serif font-bold text-lg tracking-tight leading-none">
        NATIVOS
        <span class="text-gold-400 text-[10px] block -mt-1 font-sans font-normal uppercase">LAUNCHPAD</span>
      </span>
    </div>
    <div class="text-gray-500 text-xs">Catamarca, Argentina</div>
    <div class="flex items-center gap-4">
      <div class="flex items-center gap-1.5 text-[10px] text-gray-500 uppercase tracking-widest">
        <span class="text-gold-400"><?= icon('shield-check', 'w-3.5 h-3.5') ?></span> Escrow Activo
      </div>
      <div class="w-px h-3 bg-white/10"></div>
      <div class="flex items-center gap-1.5 text-[10px] text-gray-500 uppercase tracking-widest">
        <span class="text-gold-400"><?= icon('lock', 'w-3.5 h-3.5') ?></span> SSL Encrypted
      </div>
    </div>
    <div class="text-gray-500 text-[10px] uppercase tracking-widest">
      © <?= date('Y') ?> Nativos Launchpad. Todos los derechos reservados.
    </div>
  </div>
</footer>

<!-- Product modal (oculto por defecto) -->
<div id="product-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 md:p-6">
  <div class="absolute inset-0 bg-black/90 backdrop-blur-xl" data-close-modal></div>
  <div class="relative w-full max-w-4xl bg-[#121212] border border-white/10 rounded-3xl overflow-hidden shadow-2xl modal-anim">
    <button type="button" class="absolute top-6 right-6 z-10 p-2 bg-white/10 hover:bg-white/20 rounded-full transition-colors text-white" data-close-modal aria-label="Cerrar">
      <?= icon('x', 'w-5 h-5') ?>
    </button>
    <div id="product-modal-body" class="min-h-[300px] flex items-center justify-center">
      <div class="p-8 text-gray-400 flex items-center gap-3"><?= icon('loader', 'w-5 h-5 animate-spin') ?> Cargando…</div>
    </div>
  </div>
</div>

<!-- Chatbot -->
<button id="chat-toggle" class="fixed bottom-6 right-6 z-50 w-14 h-14 bg-gold-400 text-black rounded-full shadow-2xl flex items-center justify-center hover:bg-gold-500 transition-colors" aria-label="Abrir chat">
  <?= icon('message-circle', 'w-6 h-6') ?>
</button>

<div id="chat-window" class="hidden fixed bottom-24 right-6 z-50 w-[90vw] md:w-[400px] h-[500px] bg-[#121212] border border-white/10 rounded-3xl shadow-2xl flex-col overflow-hidden">
  <div class="p-4 bg-white/5 border-b border-white/10 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-full bg-gold-400/20 flex items-center justify-center text-gold-400">
        <?= icon('bot', 'w-4 h-4') ?>
      </div>
      <div>
        <h3 class="text-sm font-bold text-white">Asistente Nativos</h3>
        <p class="text-[10px] text-gold-400">En línea</p>
      </div>
    </div>
    <button id="chat-close" class="p-2 hover:bg-white/10 rounded-full transition-colors text-gray-400" aria-label="Cerrar chat">
      <?= icon('x', 'w-4 h-4') ?>
    </button>
  </div>

  <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-4 no-scrollbar"></div>

  <form id="chat-form" class="p-4 bg-white/5 border-t border-white/10 flex gap-2">
    <input id="chat-input" type="text" placeholder="Escribe tu duda..." autocomplete="off"
      class="flex-1 bg-white/5 border border-white/10 rounded-xl py-2 px-4 text-sm focus:outline-none focus:border-gold-400/50 transition-all">
    <button type="submit" class="p-2 bg-gold-400 text-black rounded-xl disabled:opacity-50 hover:bg-gold-500 transition-colors">
      <?= icon('send', 'w-4 h-4') ?>
    </button>
  </form>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
