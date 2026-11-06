<?php
$pageTitle = "Catálogo de Peças Automóveis";
require_once __DIR__ . '/includes/header.php';

$db = Database::getConnection();

// Parâmetros de Filtro
$search = trim($_GET['q'] ?? '');
$categorySlug = trim($_GET['categoria'] ?? '');
$brandSlug = trim($_GET['marca'] ?? '');
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$inStockOnly = !empty($_GET['stock']);
$promoOnly = !empty($_GET['promo']);
$sort = trim($_GET['sort'] ?? 'latest');

// Construção dinâmica e segura da consulta com PDO
$where = ["p.is_active = 1"];
$params = [];

if ($search !== '') {
    $where[] = "(p.name LIKE :search OR p.oem_reference LIKE :search OR p.description LIKE :search OR p.manufacturer LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($categorySlug !== '') {
    $where[] = "c.slug = :category_slug";
    $params[':category_slug'] = $categorySlug;
}

if ($brandSlug !== '') {
    $where[] = "b.slug = :brand_slug";
    $params[':brand_slug'] = $brandSlug;
}

if ($minPrice !== null && $minPrice > 0) {
    $where[] = "COALESCE(p.discount_price, p.price) >= :min_price";
    $params[':min_price'] = $minPrice;
}

if ($maxPrice !== null && $maxPrice > 0) {
    $where[] = "COALESCE(p.discount_price, p.price) <= :max_price";
    $params[':max_price'] = $maxPrice;
}

if ($inStockOnly) {
    $where[] = "p.stock > 0";
}

if ($promoOnly) {
    $where[] = "p.discount_price IS NOT NULL AND p.discount_price < p.price";
}

// Ordenação
$orderBy = "p.id DESC";
switch ($sort) {
    case 'price_asc':
        $orderBy = "COALESCE(p.discount_price, p.price) ASC";
        break;
    case 'price_desc':
        $orderBy = "COALESCE(p.discount_price, p.price) DESC";
        break;
    case 'name_asc':
        $orderBy = "p.name ASC";
        break;
    case 'name_desc':
        $orderBy = "p.name DESC";
        break;
    case 'latest':
    default:
        $orderBy = "p.id DESC";
        break;
}

$whereClause = implode(" AND ", $where);
$sql = "
    SELECT p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name, b.slug AS brand_slug
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE {$whereClause}
    ORDER BY {$orderBy}
";

$stmt = $db->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$products = $stmt->fetchAll();
$totalResults = count($products);

// Listas para a barra lateral de filtros
$allCategories = get_all_categories();
$allBrands = get_all_brands();
?>

<div class="bg-gray-100 py-6 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <nav class="text-xs text-gray-500 mb-1 flex items-center space-x-2">
                    <a href="<?= url('index.php') ?>" class="hover:text-blue-600">Início</a>
                    <span>/</span>
                    <span class="text-gray-800 font-medium">Catálogo de Peças</span>
                    <?php if ($categorySlug): ?>
                        <span>/</span>
                        <span class="text-blue-600 font-medium"><?= e($categorySlug) ?></span>
                    <?php endif; ?>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900">
                    Catálogo de Peças Automóveis
                </h1>
            </div>
            
            <div class="text-sm text-gray-500">
                <span class="font-bold text-gray-900"><?= $totalResults ?></span> peças encontradas
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- BARRA LATERAL DE FILTROS -->
        <aside class="lg:col-span-1 space-y-6">
            <form action="<?= url('products.php') ?>" method="GET" class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-6">
                
                <!-- Manter pesquisa textual se houver -->
                <?php if ($search !== ''): ?>
                    <input type="hidden" name="q" value="<?= e($search) ?>">
                <?php endif; ?>

                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wider">Filtros de Pesquisa</h3>
                    <a href="<?= url('products.php') ?>" class="text-xs text-blue-600 hover:text-blue-800 font-semibold">Limpar</a>
                </div>

                <!-- Filtro por Categoria -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Categoria</label>
                    <select name="categoria" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas as Categorias</option>
                        <?php foreach ($allCategories as $c): ?>
                            <option value="<?= e($c['slug']) ?>" <?= $categorySlug === $c['slug'] ? 'selected' : '' ?>>
                                <?= e($c['name']) ?> (<?= $c['products_count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro por Marca do Carro -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Marca do Carro</label>
                    <select name="marca" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas as Marcas</option>
                        <?php foreach ($allBrands as $b): ?>
                            <option value="<?= e($b['slug']) ?>" <?= $brandSlug === $b['slug'] ? 'selected' : '' ?>>
                                <?= e($b['name']) ?> (<?= $b['products_count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro de Preço -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Intervalo de Preço (€)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" step="0.01" name="min_price" value="<?= e($_GET['min_price'] ?? '') ?>" placeholder="Mín" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500">
                        <input type="number" step="0.01" name="max_price" value="<?= e($_GET['max_price'] ?? '') ?>" placeholder="Máx" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Checkboxes de Disponibilidade -->
                <div class="space-y-2 pt-2 border-t border-gray-100">
                    <label class="flex items-center text-xs font-medium text-gray-700 cursor-pointer">
                        <input type="checkbox" name="stock" value="1" <?= $inStockOnly ? 'checked' : '' ?> class="rounded text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                        Apenas peças em stock
                    </label>
                    <label class="flex items-center text-xs font-medium text-gray-700 cursor-pointer">
                        <input type="checkbox" name="promo" value="1" <?= $promoOnly ? 'checked' : '' ?> class="rounded text-blue-600 focus:ring-blue-500 mr-2 h-4 w-4">
                        Apenas promoções ativas
                    </label>
                </div>

                <!-- Botão Aplicar -->
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition duration-200 shadow-md">
                    Aplicar Filtros
                </button>
            </form>
        </aside>

        <!-- LISTA DE PRODUTOS -->
        <main class="lg:col-span-3">
            
            <!-- Barra Superior de Ordenação -->
            <div class="bg-white p-4 rounded-2xl border border-gray-200 mb-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-xs text-gray-500">
                    <?php if ($search): ?>
                        Resultados para: <span class="font-bold text-gray-800">"<?= e($search) ?>"</span>
                    <?php endif; ?>
                </div>

                <div class="flex items-center space-x-2 text-xs">
                    <span class="text-gray-500 font-semibold">Ordenar por:</span>
                    <form id="sort-form" method="GET" class="inline">
                        <?php foreach ($_GET as $key => $val): ?>
                            <?php if ($key !== 'sort'): ?>
                                <input type="hidden" name="<?= e($key) ?>" value="<?= e($val) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <select name="sort" onchange="this.form.submit()" class="bg-gray-50 border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>Mais Recentes</option>
                            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Menor Preço</option>
                            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Maior Preço</option>
                            <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Nome (A-Z)</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Grelha de Produtos -->
            <?php if (empty($products)): ?>
                <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Nenhuma peça encontrada</h3>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto mb-6">
                        Tente ajustar os critérios de pesquisa ou limpar os filtros selecionados para ver mais peças.
                    </p>
                    <a href="<?= url('products.php') ?>" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs py-2.5 px-6 rounded-xl transition">
                        Ver Todo o Catálogo
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach ($products as $product): 
                        $price = (float)$product['price'];
                        $discountPrice = !empty($product['discount_price']) ? (float)$product['discount_price'] : null;
                        $finalPrice = $discountPrice ?? $price;
                    ?>
                        <div class="car-card bg-white rounded-2xl border border-gray-200 flex flex-col justify-between overflow-hidden shadow-sm hover:shadow-lg transition">
                            <div>
                                <!-- Imagem e Badges -->
                                <div class="product-image-container relative h-48 bg-gray-100 flex items-center justify-center p-4">
                                    <?php if ($discountPrice): ?>
                                        <span class="badge-racing absolute top-3 left-3 text-[10px] px-2.5 py-1 rounded-full shadow">
                                            Promoção
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($product['oem_reference'])): ?>
                                        <span class="absolute top-3 right-3 bg-gray-900 text-white text-[10px] font-mono px-2 py-0.5 rounded opacity-80">
                                            REF: <?= e($product['oem_reference']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <a href="<?= url('product-detail.php?slug=' . urlencode($product['slug'])) ?>" class="w-full h-full flex items-center justify-center">
                                        <img src="<?= e($product['image_url'] ?: 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=600&auto=format&fit=crop&q=80') ?>" alt="<?= e($product['name']) ?>" class="max-h-full object-contain">
                                    </a>
                                </div>

                                <!-- Detalhes -->
                                <div class="p-5">
                                    <div class="flex items-center space-x-2 text-xs text-gray-400 mb-1">
                                        <span class="font-semibold text-blue-600"><?= e($product['manufacturer']) ?></span>
                                        <span>&bull;</span>
                                        <span><?= e($product['category_name']) ?></span>
                                    </div>

                                    <h3 class="font-bold text-gray-900 text-sm line-clamp-2 hover:text-blue-600 transition mb-2">
                                        <a href="<?= url('product-detail.php?slug=' . urlencode($product['slug'])) ?>">
                                            <?= e($product['name']) ?>
                                        </a>
                                    </h3>

                                    <?php if (!empty($product['compatibility'])): ?>
                                        <p class="text-xs text-gray-500 line-clamp-1 mb-2">
                                            <i class="fa-solid fa-car text-[10px] mr-1 text-gray-400"></i> <?= e($product['compatibility']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="flex items-center space-x-2 text-xs mt-3">
                                        <?php if ($product['stock'] > 5): ?>
                                            <span class="text-green-600 font-semibold flex items-center"><i class="fa-solid fa-check-circle mr-1"></i> Em Stock</span>
                                        <?php elseif ($product['stock'] > 0): ?>
                                            <span class="text-yellow-600 font-semibold flex items-center"><i class="fa-solid fa-clock mr-1"></i> Últimas <?= $product['stock'] ?> un.</span>
                                        <?php else: ?>
                                            <span class="text-red-500 font-semibold flex items-center"><i class="fa-solid fa-circle-xmark mr-1"></i> Sem Stock</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Preço e Compra -->
                            <div class="p-5 pt-0 border-t border-gray-100 mt-2">
                                <div class="flex items-baseline space-x-2 mb-3 mt-3">
                                    <span class="text-xl font-black text-gray-900"><?= format_price($finalPrice) ?></span>
                                    <?php if ($discountPrice): ?>
                                        <span class="text-xs text-gray-400 line-through"><?= format_price($price) ?></span>
                                    <?php endif; ?>
                                </div>

                                <form action="<?= url('cart.php') ?>" method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    
                                    <?php if ($product['stock'] > 0): ?>
                                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-3 rounded-xl text-xs flex items-center justify-center space-x-2 transition shadow-sm">
                                            <i class="fa-solid fa-cart-plus"></i>
                                            <span>Adicionar</span>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" disabled class="w-full bg-gray-200 text-gray-500 font-semibold py-2.5 px-3 rounded-xl text-xs cursor-not-allowed">
                                            Esgotado
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

