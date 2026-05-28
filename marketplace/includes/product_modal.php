<?php
/**
 * Modal de detalle de producto. Espera $product en scope.
 * @var array $product
 */
?>
<div class="product-modal-content flex flex-col h-full max-h-[90vh] overflow-y-auto no-scrollbar">
    <div class="w-full relative">
        <img
            src="<?= e($product['image']) ?>"
            alt="<?= e($product['title']) ?>"
            class="w-full h-64 object-cover"
            referrerpolicy="no-referrer"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-[#121212] via-transparent to-transparent"></div>
    </div>

    <div class="w-full p-8 flex flex-col">
        <div class="mb-2">
            <span class="text-gold-400 text-xs font-bold uppercase tracking-widest"><?= e($product['category']) ?></span>
            <h2 class="text-4xl font-serif font-bold mt-2"><?= e($product['title']) ?></h2>
        </div>

        <div class="text-gray-400 text-sm leading-relaxed mb-8 space-y-4">
            <?php foreach (preg_split('/\n\s*\n/', (string)$product['description']) as $para): ?>
                <?php if (trim($para) !== ''): ?>
                    <p><?= e(trim($para)) ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-8 border-y border-white/5 py-6">
            <?php foreach ($product['stats'] as $stat): ?>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gold-400"><?= e($stat['value']) ?></div>
                    <div class="text-[10px] text-gray-500 uppercase tracking-wider mt-1"><?= e($stat['label']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 gap-6 mb-8">
            <?php foreach ($product['details'] as $detail): ?>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-gold-400">
                        <?= icon($detail['icon'], 'w-4 h-4') ?>
                    </div>
                    <div>
                        <div class="text-[10px] text-gray-500 uppercase"><?= e($detail['label']) ?></div>
                        <div class="text-sm font-medium"><?= e($detail['value']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="bg-white/5 rounded-2xl p-6 mb-8 border border-white/5">
            <h4 class="text-gold-400 text-xs font-bold uppercase mb-4">Incluido</h4>
            <div class="grid grid-cols-1 gap-y-2">
                <?php foreach ($product['included'] as $item): ?>
                    <div class="flex items-center gap-2 text-xs text-gray-300">
                        <div class="w-1.5 h-1.5 bg-gold-400 rounded-full"></div>
                        <?= e($item) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-gold-400/5 rounded-2xl p-6 mb-8 border border-gold-400/10">
            <div class="flex items-center gap-3 mb-4 text-gold-400">
                <?= icon('shield-check', 'w-5 h-5') ?>
                <h4 class="text-xs font-bold uppercase">Seguridad y Confianza</h4>
            </div>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400">Verificación de Activos</span>
                    <div class="flex items-center gap-1.5 text-xs <?= !empty($product['isVerified']) ? 'text-green-400' : 'text-gray-500' ?> font-medium">
                        <?= icon('check-circle', 'w-3.5 h-3.5') ?>
                        <?= !empty($product['isVerified']) ? 'Auditado' : 'Pendiente' ?>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400">Vendedor Verificado</span>
                    <div class="flex items-center gap-1.5 text-xs <?= !empty($product['sellerVerified']) ? 'text-green-400' : 'text-gray-500' ?> font-medium">
                        <?= icon('check-circle', 'w-3.5 h-3.5') ?>
                        <?= !empty($product['sellerVerified']) ? 'KYC Completado' : 'No verificado' ?>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400">Puntuación de Seguridad</span>
                    <div class="flex items-center gap-1">
                        <?php
                        $rating = (float)($product['securityRating'] ?? 0);
                        for ($i = 0; $i < 5; $i++):
                            $filled = $i < floor($rating);
                        ?>
                            <span class="<?= $filled ? 'text-gold-400' : 'text-gray-600' ?>" style="<?= $filled ? 'fill:currentColor' : '' ?>">
                                <?= icon('star', 'w-3 h-3 ' . ($filled ? 'fill-current' : '')) ?>
                            </span>
                        <?php endfor; ?>
                        <span class="text-xs font-bold ml-1"><?= $rating > 0 ? e((string)$rating) : 'N/A' ?></span>
                    </div>
                </div>
                <div class="pt-4 border-t border-white/5">
                    <div class="flex items-start gap-3 text-gray-500">
                        <?= icon('lock', 'w-4 h-4 mt-0.5') ?>
                        <p class="text-[10px] leading-relaxed">
                            Esta transacción está protegida por nuestro sistema de Escrow. Los fondos se liberan únicamente tras la transferencia exitosa de todos los activos digitales.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-auto space-y-4">
            <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl border border-white/5">
                <div class="flex items-center gap-3 text-gold-400">
                    <?= icon('lock', 'w-4 h-4') ?>
                    <span class="text-xs text-gray-400">Datos financieros detallados</span>
                </div>
                <button type="button" class="text-[10px] font-bold text-gold-400 uppercase hover:underline">Solicitar Acceso</button>
            </div>

            <div class="flex items-center justify-between gap-6">
                <div>
                    <div class="text-[10px] text-gray-500 uppercase">Inversión única</div>
                    <div class="text-2xl font-bold text-white"><?= e($product['price']) ?></div>
                </div>
                <button type="button" class="flex-1 gold-button py-4 rounded-xl flex items-center justify-center gap-2">
                    Adquirir ahora
                    <?= icon('arrow-right', 'w-4 h-4') ?>
                </button>
            </div>
        </div>
    </div>
</div>
