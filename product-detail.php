<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = Database::getConnection();
$slug = trim($_GET['slug'] ?? '');

if (empty($slug)) {
    header('Location: ' . url('products.php'));
    exit;
}

// Obter produto
$stmt = $db->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name, b.slug AS brand_slug
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE p.slug = :slug AND p.is_active = 1
    LIMIT 1
");
$stmt->execute([':slug' => $slug]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'A peça solicitada não foi encontrada ou já não está ativa.');
    header('Location: ' . url('products.php'));
    exit;
}

$pageTitle = $product['name'];
require_once __DIR__ . '/includes/header.php';

$price = (float)$product['price'];
$discountPrice = !empty($product['discount_price']) ? (float)$product['discount_price'] : null;
$finalPrice = $discountPrice ?? $price;

// Obter produtos relacionados da mesma categoria
$stmtRel = $db->prepare("
    SELECT p.*, c.name AS category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.category_id = :cat_id AND p.id != :prod_id AND p.is_active = 1
    ORDER BY p.id DESC
    LIMIT 4
");
$stmtRel->execute([':cat_id' => $product['category_id'], ':prod_id' => $product['id']]);
$relatedProducts = $stmtRel->fetchAll();
?>

<!-- Breadcrumbs -->
<div class="bg-gray-100 py-4 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="text-xs text-gray-500 flex items-center space-x-2 flex-wrap">
            <a href="<?= url('index.php') ?>" class="hover:text-blue-600">Início</a>
            <span>/</span>
            <a href="<?= url('products.php') ?>" class="hover:text-blue-600">Catálogo</a>
            <span>/</span>
            <a href="<?= url('products.php?categoria=' . urlencode($product['category_slug'])) ?>" class="hover:text-blue-600"><?= e($product['category_name']) ?></a>
            <span>/</span>
            <span class="text-gray-900 font-medium truncate max-w-xs"><?= e($product['name']) ?></span>
        </nav>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 bg-white p-6 sm:p-10 rounded-3xl border border-gray-200 shadow-sm">
        
        <!-- Galeria / Imagem da Peça -->
        <div class="lg:col-span-6 flex flex-col justify-center items-center bg-gray-50 rounded-2xl p-8 border border-gray-100 relative">
            <?php if ($discountPrice): ?>
                <span class="badge-racing absolute top-4 left-4 text-xs px-3 py-1.5 rounded-full shadow">
                    Desconto Promocional
                </span>
            <?php endif; ?>

            <div class="w-full max-w-md h-80 flex items-center justify-center">
                <img src="<?= e($product['image_url'] ?: 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=800&auto=format&fit=crop&q=80') ?>" alt="<?= e($product['name']) ?>" class="max-h-full max-w-full object-contain rounded-xl shadow-inner">
            </div>

            <div class="mt-4 flex items-center space-x-3 text-xs text-gray-400">
                <span><i class="fa-solid fa-camera mr-1"></i> Fotografia real da peça</span>
                <span>&bull;</span>
                <span><i class="fa-solid fa-box mr-1"></i> Embalagem original lacrada</span>
            </div>
        </div>

        <!-- Informação e Compra -->
        <div class="lg:col-span-6 flex flex-col justify-between">
            <div>
                <!-- Fabricante e Referência OEM -->
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <span class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                        <?= e($product['manufacturer']) ?>
                    </span>
                    
                    <?php if (!empty($product['brand_name'])): ?>
                        <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-3 py-1 rounded-full">
                            Veículo: <?= e($product['brand_name']) ?>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($product['oem_reference'])): ?>
                        <span class="bg-gray-900 text-yellow-400 text-xs font-mono font-bold px-3 py-1 rounded-full">
                            OEM: <?= e($product['oem_reference']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-snug mb-4">
                    <?= e($product['name']) ?>
                </h1>

                <!-- Preço e Estado do Stock -->
                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100 mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <span class="text-xs text-gray-400 block mb-1">Preço com IVA (23%) incluído:</span>
                        <div class="flex items-baseline space-x-3">
                            <span class="text-3xl sm:text-4xl font-black text-gray-900"><?= format_price($finalPrice) ?></span>
                            <?php if ($discountPrice): ?>
                                <span class="text-base text-gray-400 line-through"><?= format_price($price) ?></span>
                                <span class="text-xs bg-red-100 text-red-700 font-bold px-2 py-0.5 rounded">
                                    -<?= round((($price - $discountPrice) / $price) * 100) ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="text-right">
                        <?php if ($product['stock'] > 5): ?>
                            <span class="inline-flex items-center text-xs font-bold text-green-700 bg-green-100 px-3 py-1.5 rounded-full">
                                <span class="w-2 h-2 rounded-full bg-green-500 mr-2 animate-ping"></span>
                                Em Stock (<?= $product['stock'] ?> un.)
                            </span>
                        <?php elseif ($product['stock'] > 0): ?>
                            <span class="inline-flex items-center text-xs font-bold text-yellow-800 bg-yellow-100 px-3 py-1.5 rounded-full">
                                <i class="fa-solid fa-triangle-exclamation mr-1.5"></i>
                                Stock Baixo (<?= $product['stock'] ?> un.)
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center text-xs font-bold text-red-700 bg-red-100 px-3 py-1.5 rounded-full">
                                <i class="fa-solid fa-circle-xmark mr-1.5"></i> Esgotado
                            </span>
                        <?php endif; ?>
                        <span class="block text-[11px] text-gray-400 mt-1">Expedição em 24h</span>
                    </div>
                </div>

                <!-- Formulário de Adicionar ao Carrinho -->
                <form action="<?= url('cart.php') ?>" method="POST" class="space-y-4 mb-6">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                    <div class="flex items-center gap-4">
                        <div class="w-32">
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Quantidade</label>
                            <input type="number" name="quantity" value="1" min="1" max="<?= max(1, $product['stock']) ?>" <?= $product['stock'] <= 0 ? 'disabled' : '' ?> class="w-full text-center py-3 px-3 bg-gray-50 border border-gray-300 rounded-xl font-bold text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="flex-1 self-end">
                            <?php if ($product['stock'] > 0): ?>
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 px-6 rounded-xl text-sm flex items-center justify-center space-x-3 transition shadow-lg shadow-blue-600/30">
                                    <i class="fa-solid fa-cart-plus text-base"></i>
                                    <span>Adicionar ao Carrinho</span>
                                </button>
                            <?php else: ?>
                                <button type="button" disabled class="w-full bg-gray-200 text-gray-500 font-bold py-3.5 px-6 rounded-xl text-sm cursor-not-allowed">
                                    Indisponível de Momento
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <!-- Destaques de Garantia e Confiança -->
                <div class="grid grid-cols-3 gap-3 pt-6 border-t border-gray-100 text-center">
                    <div class="p-3 rounded-xl bg-gray-50">
                        <i class="fa-solid fa-truck-fast text-blue-600 text-lg mb-1"></i>
                        <span class="block text-[11px] font-bold text-gray-800">Envio 24/48h</span>
                        <span class="block text-[10px] text-gray-400">Embalagem Segura</span>
                    </div>
                    <div class="p-3 rounded-xl bg-gray-50">
                        <i class="fa-solid fa-rotate-left text-green-600 text-lg mb-1"></i>
                        <span class="block text-[11px] font-bold text-gray-800">30 Dias Devolução</span>
                        <span class="block text-[10px] text-gray-400">Sem Complicações</span>
                    </div>
                    <div class="p-3 rounded-xl bg-gray-50">
                        <i class="fa-solid fa-award text-yellow-600 text-lg mb-1"></i>
                        <span class="block text-[11px] font-bold text-gray-800">Garantia 2 Anos</span>
                        <span class="block text-[10px] text-gray-400">Direta do Fabricante</span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Separadores Técnicos: Descrição, Especificações, Compatibilidade -->
    <div class="mt-12 bg-white rounded-3xl border border-gray-200 p-8 shadow-sm">
        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <i class="fa-solid fa-screwdriver-wrench text-blue-600 mr-2.5"></i> Ficha Técnica e Compatibilidade da Peça
        </h2>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Descrição Completa -->
            <div>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3">Descrição Detalhada</h3>
                <div class="prose text-sm text-gray-600 leading-relaxed space-y-3">
                    <p><?= nl2br(e($product['description'])) ?></p>
                </div>

                <!-- Especificações Técnicas -->
                <?php if (!empty($product['specifications'])): ?>
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mt-6 mb-3">Especificações Físicas / Parâmetros</h3>
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 text-xs font-mono space-y-2 text-gray-700">
                        <?= nl2br(e($product['specifications'])) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Compatibilidade de Veículos -->
            <div>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3">Viaturas Compatíveis</h3>
                <?php if (!empty($product['compatibility'])): ?>
                    <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5">
                        <div class="flex items-start space-x-3 mb-3">
                            <i class="fa-solid fa-circle-check text-blue-600 text-lg mt-0.5"></i>
                            <div>
                                <h4 class="text-xs font-bold text-blue-900 uppercase">Compatibilidade Testada</h4>
                                <p class="text-xs text-blue-700">Esta peça cumpre os requisitos OEM para os seguintes modelos:</p>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl p-4 border border-blue-100 text-xs text-gray-800 whitespace-pre-line leading-relaxed font-medium">
                            <?= e($product['compatibility']) ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-xs text-gray-500 italic">Consulte o nosso apoio técnico com o número de chassis (VIN) para validação exata.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Produtos Relacionados -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="mt-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Peças Relacionadas</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($relatedProducts as $rel): 
                    $relPrice = (float)$rel['price'];
                    $relDiscount = !empty($rel['discount_price']) ? (float)$rel['discount_price'] : null;
                    $relFinal = $relDiscount ?? $relPrice;
                ?>
                    <div class="car-card bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition p-4 flex flex-col justify-between">
                        <div class="h-36 bg-gray-50 rounded-xl flex items-center justify-center p-2 mb-3">
                            <img src="<?= e($rel['image_url'] ?: 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=400&auto=format&fit=crop&q=80') ?>" alt="<?= e($rel['name']) ?>" class="max-h-full object-contain">
                        </div>
                        <div>
                            <span class="text-[10px] text-blue-600 font-bold uppercase"><?= e($rel['manufacturer']) ?></span>
                            <h4 class="font-bold text-xs text-gray-900 line-clamp-2 mb-2">
                                <a href="<?= url('product-detail.php?slug=' . urlencode($rel['slug'])) ?>" class="hover:text-blue-600 transition">
                                    <?= e($rel['name']) ?>
                                </a>
                            </h4>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-gray-100 mt-2">
                            <span class="text-sm font-black text-gray-900"><?= format_price($relFinal) ?></span>
                            <a href="<?= url('product-detail.php?slug=' . urlencode($rel['slug'])) ?>" class="text-xs bg-gray-100 hover:bg-blue-600 hover:text-white px-2.5 py-1.5 rounded-lg font-semibold transition">
                                Ver
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

