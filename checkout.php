<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$cartItems = $_SESSION['cart'] ?? [];
if (empty($cartItems)) {
    set_flash('warning', 'O seu carrinho está vazio. Adicione peças antes de finalizar a encomenda.');
    header('Location: ' . url('products.php'));
    exit;
}

$db = Database::getConnection();
$currentUser = current_user();
$totals = get_cart_totals();

// Pré-preenchimento com dados do utilizador se tiver sessão iniciada
$formData = [
    'name'        => $currentUser['name'] ?? '',
    'email'       => $currentUser['email'] ?? '',
    'phone'       => $currentUser['phone'] ?? '',
    'nif'         => $currentUser['nif'] ?? '',
    'address'     => '',
    'postal_code' => '',
    'city'        => '',
    'notes'       => ''
];

// Se logado, carregar morada registada da base de dados
if ($currentUser) {
    $stmtUser = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmtUser->execute([':id' => $currentUser['id']]);
    $uData = $stmtUser->fetch();
    if ($uData) {
        $formData['address'] = $uData['address'] ?? '';
        $formData['postal_code'] = $uData['postal_code'] ?? '';
        $formData['city'] = $uData['city'] ?? '';
        $formData['phone'] = $uData['phone'] ?? $formData['phone'];
        $formData['nif'] = $uData['nif'] ?? $formData['nif'];
    }
}

$errors = [];

// Processar Encomenda (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Token de segurança inválido. Por favor recarregue a página.';
    }

    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $formData['nif'] = trim($_POST['nif'] ?? '');
    $formData['address'] = trim($_POST['address'] ?? '');
    $formData['postal_code'] = trim($_POST['postal_code'] ?? '');
    $formData['city'] = trim($_POST['city'] ?? '');
    $formData['notes'] = trim($_POST['notes'] ?? '');
    $paymentMethod = trim($_POST['payment_method'] ?? 'multibanco');

    // Validações
    if (empty($formData['name'])) $errors[] = 'O nome completo é obrigatório.';
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Insira um endereço de email válido.';
    if (empty($formData['phone'])) $errors[] = 'O contacto telefónico é obrigatório.';
    if (empty($formData['address'])) $errors[] = 'A morada de entrega é obrigatória.';
    if (empty($formData['postal_code'])) $errors[] = 'O código postal é obrigatório.';
    if (empty($formData['city'])) $errors[] = 'A cidade / localidade é obrigatória.';

    if (!in_array($paymentMethod, ['multibanco', 'mbway', 'card', 'transfer'])) {
        $errors[] = 'Método de pagamento inválido.';
    }

    // Se tudo válido, criar transação na Base de Dados
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 1. Gerar Número de Encomenda Único
            $orderNumber = 'ORD-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));

            // 2. Gerar Referência de Pagamento Simulada
            $paymentRef = '';
            $paymentStatus = 'pending';

            if ($paymentMethod === 'multibanco') {
                $refFormatted = sprintf('%03d %03d %03d', rand(100, 999), rand(100, 999), rand(100, 999));
                $paymentRef = "Entidade: 21245 | Ref: {$refFormatted}";
            } elseif ($paymentMethod === 'mbway') {
                $mbwayPhone = trim($_POST['mbway_phone'] ?? $formData['phone']);
                $paymentRef = "MB WAY ({$mbwayPhone}) - Notificação Enviada";
                $paymentStatus = 'paid'; // simulado como pago
            } elseif ($paymentMethod === 'card') {
                $paymentRef = 'Cartão de Crédito/Débito (Simulado Aprovado)';
                $paymentStatus = 'paid';
            } else {
                $paymentRef = 'IBAN: PT50 0033 0000 12345678901 23 (Banco BPI)';
            }

            // 3. Inserir na tabela `orders`
            $stmtOrder = $db->prepare("
                INSERT INTO orders (
                    order_number, user_id, customer_name, customer_email, customer_phone,
                    customer_nif, shipping_address, shipping_postal_code, shipping_city,
                    payment_method, payment_reference, payment_status, subtotal, tax_amount,
                    shipping_cost, total, status, notes, created_at
                ) VALUES (
                    :order_number, :user_id, :customer_name, :customer_email, :customer_phone,
                    :customer_nif, :shipping_address, :shipping_postal_code, :shipping_city,
                    :payment_method, :payment_reference, :payment_status, :subtotal, :tax_amount,
                    :shipping_cost, :total, 'pending', :notes, NOW()
                )
            ");

            $stmtOrder->execute([
                ':order_number'         => $orderNumber,
                ':user_id'              => $currentUser['id'] ?? null,
                ':customer_name'        => $formData['name'],
                ':customer_email'       => $formData['email'],
                ':customer_phone'       => $formData['phone'],
                ':customer_nif'         => $formData['nif'] ?: null,
                ':shipping_address'     => $formData['address'],
                ':shipping_postal_code' => $formData['postal_code'],
                ':shipping_city'        => $formData['city'],
                ':payment_method'       => $paymentMethod,
                ':payment_reference'    => $paymentRef,
                ':payment_status'       => $paymentStatus,
                ':subtotal'             => $totals['subtotal'],
                ':tax_amount'           => $totals['tax'],
                ':shipping_cost'        => $totals['shipping'],
                ':total'                => $totals['total'],
                ':notes'                => $formData['notes'] ?: null
            ]);

            $orderId = (int)$db->lastInsertId();

            // 4. Inserir itens da encomenda e decrementar stock
            $stmtItem = $db->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, subtotal)
                VALUES (:order_id, :product_id, :product_name, :product_price, :quantity, :subtotal)
            ");

            $stmtStock = $db->prepare("
                UPDATE products 
                SET stock = GREATEST(0, stock - :qty)
                WHERE id = :id
            ");

            foreach ($cartItems as $item) {
                $itemSubtotal = (float)$item['price'] * (int)$item['quantity'];

                $stmtItem->execute([
                    ':order_id'       => $orderId,
                    ':product_id'     => $item['id'],
                    ':product_name'   => $item['name'],
                    ':product_price'  => $item['price'],
                    ':quantity'       => $item['quantity'],
                    ':subtotal'       => $itemSubtotal
                ]);

                $stmtStock->execute([
                    ':qty' => $item['quantity'],
                    ':id'  => $item['id']
                ]);
            }

            // Commit da Transação
            $db->commit();

            // Limpar o carrinho
            $_SESSION['cart'] = [];

            // Redirecionar para confirmação com sucesso
            header('Location: ' . url('order-success.php?id=' . $orderId . '&ref=' . urlencode($orderNumber)));
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Ocorreu um erro no processamento da sua encomenda: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Finalizar Encomenda Turbo";
require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-gray-900 py-6 border-b border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <span class="text-orange-500 text-xs font-black uppercase tracking-wider flex items-center gap-1.5">
            <i class="fa-solid fa-lock text-green-400"></i> Checkout Encriptado 256-Bit
        </span>
        <h1 class="text-2xl sm:text-3xl font-black text-white mt-1">
            Finalizar a Tua Encomenda Turbo 🚀
        </h1>
        <p class="text-xs text-gray-400 mt-0.5">
            Indica a tua morada e escolhe o teu método de pagamento preferido (MB WAY, Multibanco ou Cartão).
        </p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <?php if (!empty($errors)): ?>
        <div class="mb-6 bg-red-950/80 border border-red-600 rounded-2xl p-4 text-red-200 text-xs">
            <div class="font-bold mb-1 flex items-center"><i class="fa-solid fa-triangle-exclamation mr-2 text-red-400"></i> Corrige os seguintes campos:</div>
            <ul class="list-disc list-inside space-y-1">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= url('checkout.php') ?>" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <?= csrf_field() ?>

        <!-- Coluna Esquerda: Dados de Envio e Pagamento -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- Secção 1: Dados do Cliente -->
            <div class="bg-gray-900 p-6 sm:p-8 rounded-3xl border border-gray-800 shadow-xl">
                <div class="flex items-center space-x-3 mb-6 pb-4 border-b border-gray-800">
                    <div class="w-8 h-8 rounded-xl bg-orange-600 text-white font-black flex items-center justify-center text-xs shadow-md">1</div>
                    <h2 class="text-base font-black text-white uppercase tracking-wider">Dados de Envio & Destinatário</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-300 uppercase mb-1">Nome Completo *</label>
                        <input type="text" name="name" required value="<?= e($formData['name']) ?>" class="w-full bg-gray-950 border border-gray-700 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-300 uppercase mb-1">Email p/ Confirmação *</label>
                        <input type="email" name="email" required value="<?= e($formData['email']) ?>" class="w-full bg-gray-950 border border-gray-700 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-300 uppercase mb-1">Contacto Telefónico *</label>
                        <input type="text" name="phone" required value="<?= e($formData['phone']) ?>" placeholder="+351 912 345 678" class="w-full bg-gray-950 border border-gray-700 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-300 uppercase mb-1">NIF (Opcional p/ Fatura)</label>
                        <input type="text" name="nif" value="<?= e($formData['nif']) ?>" placeholder="Ex: 500123456" class="w-full bg-gray-950 border border-gray-700 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-300 uppercase mb-1">Morada de Entrega *</label>
                        <input type="text" name="address" required value="<?= e($formData['address']) ?>" placeholder="Rua, Número, Andar" class="w-full bg-gray-950 border border-gray-700 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-300 uppercase mb-1">Código Postal *</label>
                        <input type="text" name="postal_code" required value="<?= e($formData['postal_code']) ?>" placeholder="Ex: 4000-001" class="w-full bg-gray-950 border border-gray-700 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-300 uppercase mb-1">Cidade / Localidade *</label>
                        <input type="text" name="city" required value="<?= e($formData['city']) ?>" placeholder="Ex: Porto" class="w-full bg-gray-950 border border-gray-700 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-300 uppercase mb-1">Notas Especiais de Entrega (Opcional)</label>
                        <textarea name="notes" rows="2" placeholder="Observações para o estafeta..." class="w-full bg-gray-950 border border-gray-700 rounded-xl px-4 py-2 text-xs text-white focus:ring-2 focus:ring-orange-500 focus:outline-none"><?= e($formData['notes']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Secção 2: Método de Pagamento Super Chamativo -->
            <div class="bg-gray-900 p-6 sm:p-8 rounded-3xl border border-gray-800 shadow-xl space-y-4">
                <div class="flex items-center space-x-3 mb-4 pb-4 border-b border-gray-800">
                    <div class="w-8 h-8 rounded-xl bg-green-500 text-gray-950 font-black flex items-center justify-center text-xs shadow-md">2</div>
                    <h2 class="text-base font-black text-white uppercase tracking-wider">Escolhe o Método de Pagamento</h2>
                </div>

                <div class="space-y-3">
                    
                    <!-- Opção MB WAY -->
                    <label class="flex items-center justify-between p-4 rounded-2xl border-2 border-gray-800 hover:border-green-500 bg-gray-950 cursor-pointer transition">
                        <div class="flex items-center space-x-3.5">
                            <input type="radio" name="payment_method" value="mbway" class="text-green-500 focus:ring-green-400 h-4 w-4">
                            <div>
                                <span class="font-black text-sm text-white flex items-center gap-2">
                                    <i class="fa-solid fa-mobile-screen text-green-400 text-base"></i> MB WAY
                                    <span class="bg-green-500/20 text-green-400 text-[10px] px-2 py-0.5 rounded-full font-bold">Imediato</span>
                                </span>
                                <span class="text-[11px] text-gray-400">Receba a notificação no telemóvel e confirme com o seu PIN MB WAY.</span>
                            </div>
                        </div>
                    </label>

                    <div id="info-mbway" class="payment-instructions hidden bg-gray-950 p-4 rounded-2xl border border-gray-800 space-y-2 text-xs text-gray-300">
                        <p class="font-bold text-white">Número de Telemóvel para Notificação MB WAY:</p>
                        <input type="text" name="mbway_phone" value="<?= e($formData['phone']) ?>" placeholder="Ex: 912345678" class="w-full sm:w-64 bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-white font-mono text-xs">
                    </div>

                    <!-- Opção Multibanco -->
                    <label class="flex items-center justify-between p-4 rounded-2xl border-2 border-gray-800 hover:border-yellow-500 bg-gray-950 cursor-pointer transition">
                        <div class="flex items-center space-x-3.5">
                            <input type="radio" name="payment_method" value="multibanco" checked class="text-yellow-500 focus:ring-yellow-400 h-4 w-4">
                            <div>
                                <span class="font-black text-sm text-white flex items-center gap-2">
                                    <i class="fa-solid fa-receipt text-yellow-400 text-base"></i> Referência Multibanco
                                    <span class="bg-yellow-500/20 text-yellow-400 text-[10px] px-2 py-0.5 rounded-full font-bold">ATM / Homebanking</span>
                                </span>
                                <span class="text-[11px] text-gray-400">Gera Entidade, Referência e Montante para pagar em qualquer caixa ou app bancária.</span>
                            </div>
                        </div>
                    </label>

                    <div id="info-multibanco" class="payment-instructions bg-gray-950 p-4 rounded-2xl border border-gray-800 text-xs text-gray-300">
                        Os dados da referência Multibanco serão apresentados logo no ecrã a seguir para pagamento imediato.
                    </div>

                    <!-- Opção Cartão de Crédito / Débito -->
                    <label class="flex items-center justify-between p-4 rounded-2xl border-2 border-gray-800 hover:border-blue-500 bg-gray-950 cursor-pointer transition">
                        <div class="flex items-center space-x-3.5">
                            <input type="radio" name="payment_method" value="card" class="text-blue-500 focus:ring-blue-400 h-4 w-4">
                            <div>
                                <span class="font-black text-sm text-white flex items-center gap-2">
                                    <i class="fa-solid fa-credit-card text-blue-400 text-base"></i> Cartão de Crédito / Débito
                                    <span class="bg-blue-500/20 text-blue-400 text-[10px] px-2 py-0.5 rounded-full font-bold">Visa & Mastercard</span>
                                </span>
                                <span class="text-[11px] text-gray-400">Processamento protegido com autenticação 3D Secure simulada.</span>
                            </div>
                        </div>
                    </label>

                    <div id="info-card" class="payment-instructions hidden bg-gray-950 p-4 rounded-2xl border border-gray-800 space-y-2 text-xs">
                        <div class="grid grid-cols-2 gap-2 max-w-sm">
                            <input type="text" placeholder="Número do Cartão" value="4532 •••• •••• 8821" class="col-span-2 bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-white font-mono text-xs">
                            <input type="text" placeholder="Validade (MM/AA)" value="12/28" class="bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-white font-mono text-xs">
                            <input type="text" placeholder="CVV" value="382" class="bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-white font-mono text-xs">
                        </div>
                    </div>

                    <!-- Opção Transferência SEPA -->
                    <label class="flex items-center justify-between p-4 rounded-2xl border-2 border-gray-800 hover:border-purple-500 bg-gray-950 cursor-pointer transition">
                        <div class="flex items-center space-x-3.5">
                            <input type="radio" name="payment_method" value="transfer" class="text-purple-500 focus:ring-purple-400 h-4 w-4">
                            <div>
                                <span class="font-black text-sm text-white flex items-center gap-2">
                                    <i class="fa-solid fa-building-columns text-purple-400 text-base"></i> Transferência Bancária SEPA
                                </span>
                                <span class="text-[11px] text-gray-400">Transferência direta para o IBAN oficial da nossa oficina.</span>
                            </div>
                        </div>
                    </label>

                </div>
            </div>

        </div>

        <!-- Coluna Direita: Resumo com Totais -->
        <div class="lg:col-span-5">
            <div class="bg-gray-900 p-6 sm:p-8 rounded-3xl border border-gray-800 shadow-xl sticky top-28 space-y-6">
                
                <h3 class="text-base font-black text-white pb-3 border-b border-gray-800 uppercase tracking-wider flex items-center justify-between">
                    <span>Resumo do Pedido</span>
                    <span class="text-orange-400 text-xs font-bold"><?= count($cartItems) ?> artigo(s)</span>
                </h3>

                <!-- Lista resumida de itens -->
                <div class="max-h-60 overflow-y-auto divide-y divide-gray-800 pr-2 space-y-2">
                    <?php foreach ($cartItems as $it): ?>
                        <div class="py-2.5 flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-2.5">
                                <span class="font-black bg-gray-800 text-orange-400 rounded-lg px-2 py-0.5 text-[11px]"><?= $it['quantity'] ?>x</span>
                                <span class="text-gray-200 font-bold truncate max-w-[170px]"><?= e($it['name']) ?></span>
                            </div>
                            <span class="font-black text-white"><?= format_price((float)$it['price'] * (int)$it['quantity']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Totais -->
                <div class="space-y-2.5 text-xs pt-4 border-t border-gray-800">
                    <div class="flex justify-between text-gray-400">
                        <span>Subtotal de Peças</span>
                        <span class="font-bold text-white"><?= format_price($totals['subtotal']) ?></span>
                    </div>
                    <div class="flex justify-between text-gray-400">
                        <span>IVA 23% incluído</span>
                        <span><?= format_price($totals['tax']) ?></span>
                    </div>
                    <div class="flex justify-between text-gray-400">
                        <span>Portes de Envio</span>
                        <?php if ($totals['shipping'] == 0): ?>
                            <span class="font-black text-green-400 uppercase">GRÁTIS 🚚</span>
                        <?php else: ?>
                            <span class="font-bold text-white"><?= format_price($totals['shipping']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="pt-3 border-t border-gray-800 flex justify-between items-baseline">
                        <span class="text-sm font-black text-white uppercase">Total a Pagar</span>
                        <span class="text-3xl font-black text-green-400"><?= format_price($totals['total']) ?></span>
                    </div>
                </div>

                <!-- Botão de Confirmação Vibrante -->
                <button type="submit" class="btn-turbo w-full py-4 px-6 rounded-2xl text-sm transition font-black flex items-center justify-center space-x-2 shadow-xl">
                    <i class="fa-solid fa-bolt text-yellow-300"></i>
                    <span>Confirmar e Finalizar Encomenda 🔥</span>
                </button>

                <div class="pt-2 flex items-center justify-center space-x-3 text-[11px] text-gray-400 font-semibold">
                    <span><i class="fa-solid fa-lock text-green-400 mr-1"></i> SSL Protegido</span>
                    <span>&bull;</span>
                    <span><i class="fa-solid fa-rotate-left text-yellow-400 mr-1"></i> 30 Dias Garantia</span>
                </div>
            </div>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

