<?php
/**
 * Tarjeta de producto del listado. Espera la variable $product en el scope.
 * @var array $product
 */
?>
<article
    class="product-card glass-card rounded-3xl overflow-hidden group hover:border-gold-400/30 transition-all duration-500 cursor-pointer"
    data-product-id="<?= e($product['id']) ?>"
>
    <div class="relative h-64 overflow-hidden">
        <img
            src="<?= e($product['image']) ?>"
            alt="<?= e($product['title']) ?>"
            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
            referrerpolicy="no-referrer"
            loading="lazy"
        />
        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/40 transition-colors"></div>
        <div class="absolute top-4 right-4 bg-black/60 backdrop-blur-md px-4 py-1 rounded-full text-xs font-bold text-gold-400 border border-white/10">
            <?= e($product['price']) ?>
        </div>
        <?php if (!empty($product['isVerified'])): ?>
            <div class="absolute top-4 left-4 bg-gold-400 text-black px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 shadow-lg">
                <?= icon('check-circle', 'w-3 h-3') ?>
                Verificado
            </div>
        <?php endif; ?>
    </div>
    <div class="p-8">
        <span class="text-gold-400 text-[10px] font-bold uppercase tracking-widest"><?= e($product['category']) ?></span>
        <h3 class="text-2xl font-serif font-bold mt-2 mb-4"><?= e($product['title']) ?></h3>
        <button
            type="button"
            class="open-product w-full py-3 rounded-xl border border-white/10 text-sm font-medium hover:bg-white hover:text-black transition-all duration-300"
            data-product-id="<?= e($product['id']) ?>"
        >
            Ver detalles
        </button>
    </div>
</article>
