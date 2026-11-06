<?php
$pageTitle = "Peças Auto Turbo & Acessórios ao Melhor Preço";
require_once __DIR__ . '/includes/header.php';

$categories = get_all_categories();
$brands = get_all_brands();
$featuredProducts = get_featured_products(8);
$recentNews = get_recent_news(3);
?>

<!-- HERO SECTION: ULTRA APELATIVO & RACING -->
<section class="bg-dark-mesh py-12 lg:py-20 relative overflow-hidden border-b border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                
                <div class="inline-flex items-center space-x-2 bg-gradient-to-r from-orange-500/20 to-red-600/20 border border-orange-500/40 px-4 py-1.5 rounded-full text-xs font-black text-orange-400 shadow-lg shadow-orange-500/10">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-ping"></span>
                    <span>⚡ FLASH SALE DE OUTONO: ATÉ -40% DE DESCONTO EM PEÇAS OEM</span>
                </div>
                
                <h1 class="text-4xl sm:text-6xl font-black tracking-tight text-white leading-tight">
                    Acelera a Tua Máquina com Peças de <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 via-red-500 to-yellow-400">Alta Performance</span>
                </h1>
                
                <p class="text-sm sm:text-base text-gray-300 max-w-2xl leading-relaxed">
                    Travões Brembo, kits de distribuição Continental, amortecedores Bilstein e óleos sintéticos Castrol. Peças certificadas com garantia total e <strong>pagamentos em 3x sem juros</strong>.
                </p>

                <!-- Seletor Rápido de Peças com Design Turbo -->
                <div class="glass-panel p-5 sm:p-6 rounded-3xl shadow-2xl border border-gray-700/80">
                    <form action="<?= url('products.php') ?>" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-black uppercase text-orange-400 mb-1.5 flex items-center">
                                <i class="fa-solid fa-car mr-1.5"></i> Marca do Carro
                            </label>
                            <select name="marca" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2.5 text-xs text-white font-bold focus:ring-2 focus:ring-orange-500 focus:outline-none">
                                <option value="">Todas as Marcas (BMW, Audi, VW...)</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?= e($b['slug']) ?>"><?= e($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase text-orange-400 mb-1.5 flex items-center">
                                <i class="fa-solid fa-wrench mr-1.5"></i> Categoria
                            </label>
                            <select name="categoria" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2.5 text-xs text-white font-bold focus:ring-2 focus:ring-orange-500 focus:outline-none">
                                <option value="">Todas as Categorias</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= e($c['slug']) ?>"><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="btn-turbo w-full py-2.5 px-4 rounded-xl text-xs font-black flex items-center justify-center space-x-2">
                                <i class="fa-solid fa-bolt text-yellow-300"></i>
                                <span>Ver Peças Compatíveis</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Selos de Confiança Rápidos -->
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 pt-2 text-xs font-bold text-gray-300">
                    <span class="flex items-center text-green-400"><i class="fa-solid fa-circle-check mr-1.5"></i> Envio em 24h</span>
                    <span class="flex items-center text-blue-400"><i class="fa-solid fa-shield-halved mr-1.5"></i> Peças 100% Originais</span>
                    <span class="flex items-center text-yellow-400"><i class="fa-solid fa-rotate-left mr-1.5"></i> 30 Dias Devolução Grátis</span>
                </div>

            </div>

            <!-- Card Destaque Promocional Hero -->
            <div class="lg:col-span-5 relative hidden lg:block">
                <div class="relative mx-auto max-w-md">
                    <div class="absolute -inset-1.5 bg-gradient-to-r from-orange-500 via-red-600 to-yellow-400 rounded-3xl blur-xl opacity-50 animate-pulse"></div>
                    <div class="relative bg-gray-900 rounded-3xl p-6 border border-gray-800 shadow-2xl">
                        <div class="flex justify-between items-center mb-3">
                            <span class="badge-flame text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">
                                🔥 SUPER OFERTA DO DIA
                            </span>
                            <span class="text-xs font-bold text-green-400"><i class="fa-solid fa-bolt mr-1"></i> Stock Imediato</span>
                        </div>

                        <img src="https://images.unsplash.com/photo-1600790142055-619df03207e6?w=600&auto=format&fit=crop&q=80" alt="Discos Brembo" class="rounded-2xl object-cover w-full h-56 mb-4 border border-gray-800">
                        
                        <div class="flex justify-between items-end">
                            <div>
                                <span class="text-xs text-orange-400 font-bold uppercase">Brembo Racing Max</span>
                                <h3 class="text-base font-extrabold text-white">Discos Ventilados Perfurados</h3>
                                <p class="text-[11px] text-gray-400">BMW Série 3 & 4 (F30 / F32)</p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-gray-500 line-through">149,90 €</span>
                                <div class="text-2xl font-black text-green-400">129,90 €</div>
                            </div>
                        </div>

                        <!-- Pagamento em Destaque no Card -->
                        <div class="mt-4 pt-3 border-t border-gray-800 flex items-center justify-between text-[11px] text-gray-400 font-semibold">
                            <span>Ou <strong>3x de 43,30 €</strong> sem juros</span>
                            <span class="text-green-400 font-bold">Portes Grátis</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- FAIXA DE PAGAMENTOS & VANTAGENS DO CLIENTE (MUITO CHAMATIVA) -->
<section class="bg-gray-900 border-b border-gray-800 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            
            <div class="p-4 rounded-2xl bg-gray-950/80 border border-gray-800 flex items-center space-x-3.5 hover:border-orange-500/50 transition">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-green-500 to-emerald-700 text-white flex items-center justify-center text-xl flex-shrink-0 shadow-lg shadow-green-500/20">
                    <i class="fa-solid fa-mobile-screen"></i>
                </div>
                <div>
                    <h4 class="font-black text-white text-xs">Pague com MB WAY</h4>
                    <p class="text-[11px] text-gray-400">Confirmação instantânea na app</p>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-gray-950/80 border border-gray-800 flex items-center space-x-3.5 hover:border-orange-500/50 transition">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-orange-500 to-red-600 text-white flex items-center justify-center text-xl flex-shrink-0 shadow-lg shadow-orange-500/20">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div>
                    <h4 class="font-black text-white text-xs">Multibanco & Cartões</h4>
                    <p class="text-[11px] text-gray-400">Entidade / Ref. ou Visa / MC</p>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-gray-950/80 border border-gray-800 flex items-center space-x-3.5 hover:border-orange-500/50 transition">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-500 to-indigo-600 text-white flex items-center justify-center text-xl flex-shrink-0 shadow-lg shadow-blue-500/20">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <div>
                    <h4 class="font-black text-white text-xs">Envio Expresso 24h</h4>
                    <p class="text-[11px] text-gray-400">Portes grátis acima de 50€</p>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-gray-950/80 border border-gray-800 flex items-center space-x-3.5 hover:border-orange-500/50 transition">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-pink-500 to-purple-600 text-white flex items-center justify-center text-xl flex-shrink-0 shadow-lg shadow-pink-500/20">
                    <i class="fa-solid fa-credit-card"></i>
                </div>
                <div>
                    <h4 class="font-black text-white text-xs">Klarna 3x Sem Juros</h4>
                    <p class="text-[11px] text-gray-400">Divida o valor da sua compra</p>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- PEÇAS EM DESTAQUE COM CORES APELATIVAS -->
<section class="py-16 bg-gray-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-10">
            <div>
                <span class="text-orange-500 text-xs font-black uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-fire text-red-500"></i> Top Vendas & Ofertas da Semana
                </span>
                <h2 class="text-3xl font-black text-white mt-1">Peças em Destaque com Desconto</h2>
            </div>
            <a href="<?= url('products.php') ?>" class="text-xs font-black text-orange-400 hover:text-orange-300 transition flex items-center gap-1 bg-gray-900 border border-gray-800 px-4 py-2 rounded-xl">
                Ver Catálogo Completo <i class="fa-solid fa-arrow-right ml-1"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($featuredProducts as $product): 
                $price = (float)$product['price'];
                $discountPrice = !empty($product['discount_price']) ? (float)$product['discount_price'] : null;
                $finalPrice = $discountPrice ?? $price;
            ?>
                <div class="part-card flex flex-col justify-between overflow-hidden shadow-xl">
                    <div>
                        <!-- Caixa da Imagem -->
                        <div class="part-img-box h-48 flex items-center justify-center p-4">
                            <?php if ($discountPrice): ?>
                                <span class="badge-flame absolute top-3 left-3 text-[10px] font-black px-2.5 py-1 rounded-full shadow-lg">
                                    PROMO -<?= round((($price - $discountPrice) / $price) * 100) ?>%
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($product['oem_reference'])): ?>
                                <span class="absolute top-3 right-3 bg-black/80 text-yellow-300 text-[10px] font-mono px-2 py-0.5 rounded border border-yellow-500/30">
                                    REF: <?= e($product['oem_reference']) ?>
                                </span>
                            <?php endif; ?>

                            <a href="<?= url('product-detail.php?slug=' . urlencode($product['slug'])) ?>" class="w-full h-full flex items-center justify-center">
                                <img src="<?= e($product['image_url'] ?: 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=600&auto=format&fit=crop&q=80') ?>" alt="<?= e($product['name']) ?>" class="max-h-full object-contain">
                            </a>
                        </div>

                        <!-- Detalhes do Produto -->
                        <div class="p-5">
                            <div class="flex items-center space-x-2 text-[10px] font-bold text-orange-400 uppercase mb-1">
                                <span><?= e($product['manufacturer']) ?></span>
                                <span>&bull;</span>
                                <span class="text-gray-400"><?= e($product['category_name']) ?></span>
                            </div>

                            <h3 class="font-extrabold text-white text-xs line-clamp-2 hover:text-orange-400 transition mb-2">
                                <a href="<?= url('product-detail.php?slug=' . urlencode($product['slug'])) ?>">
                                    <?= e($product['name']) ?>
                                </a>
                            </h3>

                            <?php if (!empty($product['compatibility'])): ?>
                                <p class="text-[11px] text-gray-400 line-clamp-1 mb-3">
                                    <i class="fa-solid fa-car text-[10px] mr-1 text-gray-500"></i> <?= e($product['compatibility']) ?>
                                </p>
                            <?php endif; ?>

                            <div class="text-[10px] font-bold">
                                <?php if ($product['stock'] > 5): ?>
                                    <span class="text-green-400"><i class="fa-solid fa-circle-check mr-1"></i> Em Stock Imediato</span>
                                <?php elseif ($product['stock'] > 0): ?>
                                    <span class="text-yellow-400"><i class="fa-solid fa-clock mr-1"></i> Últimas <?= $product['stock'] ?> unidades</span>
                                <?php else: ?>
                                    <span class="text-red-400"><i class="fa-solid fa-circle-xmark mr-1"></i> Esgotado</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Preço e Botão Adicionar -->
                    <div class="p-5 pt-0 border-t border-gray-800 mt-2">
                        <div class="flex items-baseline space-x-2 mb-3 mt-3">
                            <span class="text-2xl font-black text-green-400"><?= format_price($finalPrice) ?></span>
                            <?php if ($discountPrice): ?>
                                <span class="text-xs text-gray-500 line-through"><?= format_price($price) ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Facilidade de Pagamento -->
                        <div class="text-[10px] text-gray-400 mb-3 flex items-center justify-between font-semibold">
                            <span>MB WAY / Multibanco</span>
                            <span class="text-orange-400">3x sem juros</span>
                        </div>

                        <form action="<?= url('cart.php') ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            
                            <?php if ($product['stock'] > 0): ?>
                                <button type="submit" class="btn-turbo w-full py-2.5 px-3 rounded-xl text-xs flex items-center justify-center space-x-2 font-black">
                                    <i class="fa-solid fa-cart-plus"></i>
                                    <span>Comprar Agora 🔥</span>
                                </button>
                            <?php else: ?>
                                <button type="button" disabled class="w-full bg-gray-800 text-gray-500 font-bold py-2.5 px-3 rounded-xl text-xs cursor-not-allowed">
                                    Sem Stock
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<!-- CATEGORIAS DE PEÇAS COM DESIGN DINÂMICO -->
<section class="py-14 bg-gray-900 border-t border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex justify-between items-end mb-8">
            <div>
                <span class="text-orange-400 text-xs font-black uppercase tracking-wider">Secções da Oficina</span>
                <h2 class="text-2xl font-black text-white mt-1">Explora por Categoria</h2>
            </div>
            <a href="<?= url('products.php') ?>" class="text-xs font-bold text-orange-400 hover:underline">Ver Todas &rarr;</a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= url('products.php?categoria=' . urlencode($cat['slug'])) ?>" class="bg-gray-950 p-4 rounded-2xl border border-gray-800 hover:border-orange-500 group flex flex-col items-center text-center transition">
                    <div class="w-12 h-12 rounded-2xl bg-gray-900 text-orange-400 flex items-center justify-center text-xl mb-3 group-hover:bg-gradient-to-r group-hover:from-orange-500 group-hover:to-red-600 group-hover:text-white transition duration-200 shadow-md">
                        <i class="fa-solid <?= e($cat['icon'] ?: 'fa-cogs') ?>"></i>
                    </div>
                    <h3 class="font-bold text-xs text-white group-hover:text-orange-400 transition leading-tight mb-1">
                        <?= e($cat['name']) ?>
                    </h3>
                    <span class="text-[10px] text-gray-500 font-semibold"><?= $cat['products_count'] ?> peças</span>
                </a>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<!-- MÓDULO DE NOTÍCIAS & DICAS DE MECÂNICA -->
<section class="py-16 bg-gray-950 border-t border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex justify-between items-end mb-10">
            <div>
                <span class="text-red-500 text-xs font-black uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-book-open"></i> Mundo Motor & Dicas Práticas
                </span>
                <h2 class="text-3xl font-black text-white mt-1">Notícias & Dicas de Manutenção</h2>
            </div>
            <a href="<?= url('news.php') ?>" class="text-xs font-black text-orange-400 hover:underline">Ver Todos os Artigos &rarr;</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($recentNews as $article): ?>
                <article class="bg-gray-900 rounded-3xl border border-gray-800 overflow-hidden shadow-lg flex flex-col justify-between hover:border-orange-500/60 transition">
                    <div>
                        <a href="<?= url('news-detail.php?slug=' . urlencode($article['slug'])) ?>" class="block relative h-48 overflow-hidden bg-gray-800">
                            <img src="<?= e($article['image_url'] ?: 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=800&auto=format&fit=crop&q=80') ?>" alt="<?= e($article['title']) ?>" class="w-full h-full object-cover transform hover:scale-105 transition duration-300">
                            <span class="badge-flame absolute top-3 left-3 text-[10px] font-black px-3 py-1 rounded-full shadow">
                                <?= e($article['category']) ?>
                            </span>
                        </a>

                        <div class="p-6">
                            <div class="flex items-center space-x-2 text-[11px] text-gray-400 mb-2">
                                <span><i class="fa-regular fa-calendar mr-1 text-orange-400"></i> <?= format_date($article['published_at']) ?></span>
                                <span>&bull;</span>
                                <span><i class="fa-regular fa-eye mr-1 text-orange-400"></i> <?= $article['views'] ?> vistas</span>
                            </div>

                            <h3 class="font-extrabold text-white text-base hover:text-orange-400 transition leading-snug mb-2">
                                <a href="<?= url('news-detail.php?slug=' . urlencode($article['slug'])) ?>">
                                    <?= e($article['title']) ?>
                                </a>
                            </h3>

                            <p class="text-xs text-gray-400 line-clamp-3 leading-relaxed">
                                <?= e($article['excerpt']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="px-6 pb-6 pt-0 border-t border-gray-800 mt-2 flex justify-between items-center text-xs">
                        <span class="text-gray-500 text-[11px]"><i class="fa-solid fa-user-pen mr-1 text-orange-400"></i> <?= e($article['author_name'] ?: 'Oficina Pro') ?></span>
                        <a href="<?= url('news-detail.php?slug=' . urlencode($article['slug'])) ?>" class="font-black text-orange-400 hover:text-orange-300">
                            Ler Artigo &rarr;
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
