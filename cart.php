<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = Database::getConnection();

// Processar Ações POST do Carrinho
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Token de segurança inválido. Por favor tente novamente.');
        header('Location: ' . url('cart.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. Adicionar ao Carrinho
    if ($action === 'add') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        $stmt = $db->prepare("SELECT * FROM products WHERE id = :id AND is_active = 1 LIMIT 1");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();

        if (!$product) {
            set_flash('danger', 'A peça selecionada não existe ou está desativada.');
        } elseif ($product['stock'] < 1) {
            set_flash('warning', 'A peça selecionada encontra-se atualmente esgotada.');
        } else {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            $price = !empty($product['discount_price']) ? (float)$product['discount_price'] : (float)$product['price'];
            $currentQty = isset($_SESSION['cart'][$productId]) ? (int)$_SESSION['cart'][$productId]['quantity'] : 0;
            $newQty = $currentQty + $quantity;

            if ($newQty > $product['stock']) {
                $newQty = $product['stock'];
                set_flash('warning', 'Ajustámos a quantidade para ' . $product['stock'] . ' (limite de stock disponível).');
            } else {
                set_flash('success', 'Peça adicionada ao carrinho com sucesso!');
            }

            $_SESSION['cart'][$productId] = [
                'id'            => $product['id'],
                'name'          => $product['name'],
                'slug'          => $product['slug'],
                'oem_reference' => $product['oem_reference'],
                'price'         => $price,
                'quantity'      => $newQty,
                'image_url'     => $product['image_url'],
                'stock'         => (int)$product['stock']
            ];
        }

        // Se veio de outra página (ex: catálogo ou detalhe), podemos regressar ou ir ao carrinho
        $redirect = !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'product-detail') !== false 
            ? url('cart.php') 
            : ($_SERVER['HTTP_REFERER'] ?? url('cart.php'));
        header('Location: ' . $redirect);
        exit;
    }

    // 2. Atualizar Quantidades
    if ($action === 'update') {
        $quantities = $_POST['quantities'] ?? [];
        if (is_array($quantities)) {
            foreach ($quantities as $pId => $qty) {
                $pId = (int)$pId;
                $qty = (int)$qty;
                if (isset($_SESSION['cart'][$pId])) {
                    if ($qty <= 0) {
                        unset($_SESSION['cart'][$pId]);
                    } else {
                        // Validar contra o stock real na base de dados
                        $stmt = $db->prepare("SELECT stock FROM products WHERE id = :id");
                        $stmt->execute([':id' => $pId]);
                        $stock = (int)$stmt->fetchColumn();

                        if ($qty > $stock) {
                            $_SESSION['cart'][$pId]['quantity'] = $stock;
                            set_flash('warning', 'Algumas quantidades foram ajustadas ao limite de stock.');
                        } else {
                            $_SESSION['cart'][$pId]['quantity'] = $qty;
                        }
                    }
                }
            }
            set_flash('success', 'Carrinho de compras atualizado com sucesso.');
        }
        header('Location: ' . url('cart.php'));
        exit;
    }

    // 3. Remover Item Específico
    if ($action === 'remove') {
        $productId = (int)($_POST['product_id'] ?? 0);
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
            set_flash('info', 'Item removido do seu carrinho.');
        }
        header('Location: ' . url('cart.php'));
        exit;
    }

    // 4. Limpar Carrinho Inteiro
    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        set_flash('info', 'O seu carrinho foi esvaziado.');
        header('Location: ' . url('cart.php'));
        exit;
    }
}

// Visualização do Carrinho (GET)
$pageTitle = "O Teu Carrinho Turbo";
require_once __DIR__ . '/includes/header.php';

$cartItems = $_SESSION['cart'] ?? [];
$totals = get_cart_totals();
$differenceForFreeShipping = FREE_SHIPPING_MIN - $totals['subtotal'];
?>

<div class="bg-gray-900 py-6 border-b border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl sm:text-3xl font-black text-white flex items-center gap-2">
            <span>O Teu Carrinho de Compras</span>
            <span class="text-orange-500">🛒</span>
        </h1>
        <p class="text-xs text-gray-400 mt-1">
            Revê as tuas peças antes de avançar para o pagamento seguro.
        </p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <?php if (empty($cartItems)): ?>
        <div class="bg-gray-900 rounded-3xl border border-gray-800 p-12 text-center max-w-xl mx-auto shadow-2xl">
            <div class="w-20 h-20 bg-orange-500/20 text-orange-500 rounded-3xl flex items-center justify-center mx-auto mb-4 text-3xl border border-orange-500/30">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <h2 class="text-2xl font-black text-white mb-2">O teu carrinho está vazio!</h2>
            <p class="text-xs text-gray-400 mb-6">
                Ainda não adicionaste nenhuma peça ao cesto. Temos travões, óleos e filtros em promoção!
            </p>
            <a href="<?= url('products.php') ?>" class="btn-turbo inline-flex items-center space-x-2 py-3 px-6 rounded-2xl text-xs font-black transition">
                <i class="fa-solid fa-bolt text-yellow-300"></i>
                <span>Explorar Peças em Destaque</span>
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Lista de Produtos no Carrinho -->
            <div class="lg:col-span-8">
                <form action="<?= url('cart.php') ?>" method="POST" class="bg-gray-900 rounded-3xl border border-gray-800 shadow-xl overflow-hidden">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">

                    <div class="p-6 border-b border-gray-800 flex items-center justify-between">
                        <span class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-boxes-stacked text-orange-500"></i> Artigos Selecionados (<?= count($cartItems) ?>)
                        </span>
                        
                        <button type="submit" form="clear-cart-form" class="text-xs text-red-400 hover:text-red-300 font-bold flex items-center confirm-action" data-confirm="Queres mesmo esvaziar todo o carrinho?">
                            <i class="fa-solid fa-trash-can mr-1"></i> Esvaziar
                        </button>
                    </div>

                    <div class="divide-y divide-gray-800">
                        <?php foreach ($cartItems as $id => $item): 
                            $lineTotal = (float)$item['price'] * (int)$item['quantity'];
                        ?>
                            <div class="p-6 flex flex-col sm:flex-row items-center justify-between gap-4 hover:bg-gray-850 transition">
                                <div class="flex items-center space-x-4 w-full sm:w-auto">
                                    <div class="w-16 h-16 bg-gray-950 rounded-2xl p-2 flex items-center justify-center flex-shrink-0 border border-gray-800">
                                        <img src="<?= e($item['image_url'] ?: 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=200&auto=format&fit=crop&q=80') ?>" alt="<?= e($item['name']) ?>" class="max-h-full object-contain">
                                    </div>
                                    <div>
                                        <h3 class="font-extrabold text-white text-xs hover:text-orange-400 transition">
                                            <a href="<?= url('product-detail.php?slug=' . urlencode($item['slug'])) ?>">
                                                <?= e($item['name']) ?>
                                            </a>
                                        </h3>
                                        <?php if (!empty($item['oem_reference'])): ?>
                                            <span class="text-[10px] font-mono text-yellow-400/90 block mt-0.5">OEM: <?= e($item['oem_reference']) ?></span>
                                        <?php endif; ?>
                                        <span class="text-xs font-black text-green-400 mt-1 block">
                                            <?= format_price((float)$item['price']) ?> <span class="text-gray-500 font-normal">/ un.</span>
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between w-full sm:w-auto space-x-4">
                                    <!-- Quantidade -->
                                    <div class="w-20">
                                        <input type="number" name="quantities[<?= $id ?>]" value="<?= (int)$item['quantity'] ?>" min="1" max="<?= (int)$item['stock'] ?>" class="w-full text-center py-2 bg-gray-950 border border-gray-700 rounded-xl text-xs font-black text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                                    </div>

                                    <!-- Subtotal da Linha -->
                                    <div class="text-right w-24">
                                        <span class="font-black text-white text-sm"><?= format_price($lineTotal) ?></span>
                                    </div>

                                    <!-- Remover Linha -->
                                    <button type="submit" form="remove-item-<?= $id ?>" class="text-gray-500 hover:text-red-400 transition p-2" title="Remover item">
                                        <i class="fa-solid fa-xmark text-base"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Rodapé do Cartão com Atualização -->
                    <div class="p-5 bg-gray-950 border-t border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <a href="<?= url('products.php') ?>" class="text-xs font-bold text-orange-400 hover:text-orange-300 transition flex items-center">
                            <i class="fa-solid fa-arrow-left mr-1.5"></i> Escolher Mais Peças
                        </a>

                        <button type="submit" class="bg-gray-800 hover:bg-gray-700 text-white font-bold py-2.5 px-5 rounded-xl text-xs transition border border-gray-700">
                            <i class="fa-solid fa-arrows-rotate mr-1.5 text-orange-400"></i> Atualizar Quantidades
                        </button>
                    </div>
                </form>

                <!-- Formulários invisíveis -->
                <?php foreach ($cartItems as $id => $item): ?>
                    <form id="remove-item-<?= $id ?>" action="<?= url('cart.php') ?>" method="POST" class="hidden">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?= $id ?>">
                    </form>
                <?php endforeach; ?>

                <form id="clear-cart-form" action="<?= url('cart.php') ?>" method="POST" class="hidden">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="clear">
                </form>
            </div>

            <!-- Resumo da Encomenda & Pagamento -->
            <div class="lg:col-span-4">
                <div class="bg-gray-900 rounded-3xl border border-gray-800 p-6 sm:p-8 shadow-xl space-y-6 sticky top-28">
                    <h2 class="text-base font-black text-white pb-3 border-b border-gray-800 uppercase tracking-wider">
                        Resumo do Pedido
                    </h2>

                    <!-- Alerta de Portes Grátis -->
                    <?php if ($differenceForFreeShipping > 0): ?>
                        <div class="bg-orange-950/40 border border-orange-500/40 p-4 rounded-2xl text-xs text-orange-200">
                            <div class="flex items-center space-x-2 font-bold mb-1 text-orange-400">
                                <i class="fa-solid fa-truck-fast"></i>
                                <span>Adiciona mais <?= format_price($differenceForFreeShipping) ?> para ter portes grátis!</span>
                            </div>
                            <div class="w-full bg-gray-800 h-2 rounded-full mt-2 overflow-hidden">
                                <?php $pct = min(100, max(0, ($totals['subtotal'] / FREE_SHIPPING_MIN) * 100)); ?>
                                <div class="bg-gradient-to-r from-orange-500 to-red-600 h-full rounded-full transition-all duration-300" style="width: <?= $pct ?>%;"></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-green-950/40 border border-green-500/40 p-3 rounded-2xl text-xs text-green-300 font-bold flex items-center space-x-2">
                            <i class="fa-solid fa-circle-check text-green-400 text-base"></i>
                            <span>Portes Grátis Ativados na tua encomenda! 🚀</span>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between text-gray-400">
                            <span>Subtotal Peças:</span>
                            <span class="font-bold text-white"><?= format_price($totals['subtotal']) ?></span>
                        </div>

                        <div class="flex justify-between text-gray-500">
                            <span>IVA (23% incluído):</span>
                            <span><?= format_price($totals['tax']) ?></span>
                        </div>

                        <div class="flex justify-between text-gray-400">
                            <span>Portes de Envio:</span>
                            <?php if ($totals['shipping'] == 0): ?>
                                <span class="font-black text-green-400 uppercase">Grátis</span>
                            <?php else: ?>
                                <span class="font-bold text-white"><?= format_price($totals['shipping']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="pt-3 border-t border-gray-800 flex justify-between items-baseline">
                            <span class="text-sm font-black text-white uppercase">Total a Pagar:</span>
                            <span class="text-2xl font-black text-green-400"><?= format_price($totals['total']) ?></span>
                        </div>
                    </div>

                    <!-- Métodos de Pagamento Rápidos -->
                    <div class="p-3 bg-gray-950 rounded-2xl border border-gray-800 text-[11px] text-gray-400 space-y-1.5">
                        <span class="font-bold text-gray-300 block text-[10px] uppercase tracking-wider">Pagamentos Aceites:</span>
                        <div class="flex items-center space-x-3 text-white font-bold">
                            <span class="text-green-400"><i class="fa-solid fa-mobile-screen mr-1"></i> MB WAY</span>
                            <span class="text-yellow-400"><i class="fa-solid fa-receipt mr-1"></i> Multibanco</span>
                            <span class="text-blue-400"><i class="fa-solid fa-credit-card mr-1"></i> Cartão</span>
                        </div>
                    </div>

                    <!-- Botão de Finalizar Encomenda -->
                    <a href="<?= url('checkout.php') ?>" class="btn-turbo w-full py-4 px-6 rounded-2xl text-center block transition font-black text-xs shadow-xl">
                        Avançar para o Checkout 🔥
                    </a>

                    <div class="text-center text-[11px] text-gray-500 space-y-1">
                        <p><i class="fa-solid fa-shield-halved text-green-400 mr-1"></i> Transação 100% Protegida</p>
                        <p>Fatura digital com NIF emitida automaticamente</p>
                    </div>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

