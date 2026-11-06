<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = Database::getConnection();
$orderId = (int)($_GET['id'] ?? 0);
$orderRef = trim($_GET['ref'] ?? '');

if ($orderId <= 0) {
    header('Location: ' . url('index.php'));
    exit;
}

// Obter dados da encomenda
$stmt = $db->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $orderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . url('index.php'));
    exit;
}

// Obter itens da encomenda
$stmtItems = $db->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
$stmtItems->execute([':order_id' => $orderId]);
$orderItems = $stmtItems->fetchAll();

$pageTitle = "Encomenda Confirmada " . $order['order_number'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-12">
    
    <!-- Cabeçalho de Sucesso -->
    <div class="bg-white rounded-3xl border border-gray-200 p-8 sm:p-12 text-center shadow-sm mb-8">
        <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-5 text-4xl shadow-inner">
            <i class="fa-solid fa-check"></i>
        </div>
        <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Muito Obrigado pela sua Encomenda!</h1>
        <p class="text-sm text-gray-500 max-w-md mx-auto mb-6">
            O seu pedido foi registado com sucesso no nosso sistema sob a referência:
        </p>
        <div class="inline-block bg-gray-900 text-white font-mono font-bold text-lg px-6 py-2.5 rounded-2xl shadow">
            <?= e($order['order_number']) ?>
        </div>
        <p class="text-xs text-gray-400 mt-4">
            Enviámos uma cópia da confirmação para o email <strong><?= e($order['customer_email']) ?></strong>.
        </p>
    </div>

    <!-- Dados de Pagamento e Envio -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        
        <!-- Cartão de Pagamento -->
        <div class="bg-white rounded-3xl border border-gray-200 p-6 sm:p-8 shadow-sm">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 flex items-center">
                <i class="fa-solid fa-wallet text-blue-600 mr-2"></i> Instruções de Pagamento
            </h3>
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-xs text-gray-400 block">Método Escolhido:</span>
                    <span class="font-bold text-gray-800"><?= get_payment_method_name($order['payment_method']) ?></span>
                </div>

                <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                    <span class="text-xs text-gray-400 block mb-1">Dados / Referência:</span>
                    <div class="font-mono font-bold text-base text-gray-900">
                        <?= e($order['payment_reference']) ?>
                    </div>
                    <div class="mt-2 text-xs text-gray-500">
                        Montante a pagar: <strong class="text-blue-600 font-bold"><?= format_price((float)$order['total']) ?></strong>
                    </div>
                </div>

                <div class="flex items-center space-x-2 text-xs">
                    <span class="text-gray-400">Estado do Pagamento:</span>
                    <?php if ($order['payment_status'] === 'paid'): ?>
                        <span class="bg-green-100 text-green-800 font-bold px-2.5 py-0.5 rounded-full">Confirmado</span>
                    <?php else: ?>
                        <span class="bg-yellow-100 text-yellow-800 font-bold px-2.5 py-0.5 rounded-full">Aguardando Pagamento</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cartão de Destino / Morada -->
        <div class="bg-white rounded-3xl border border-gray-200 p-6 sm:p-8 shadow-sm">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 flex items-center">
                <i class="fa-solid fa-location-dot text-blue-600 mr-2"></i> Morada de Entrega
            </h3>
            <div class="space-y-2 text-sm text-gray-700">
                <p class="font-bold text-gray-900"><?= e($order['customer_name']) ?></p>
                <p><?= e($order['shipping_address']) ?></p>
                <p><?= e($order['shipping_postal_code']) ?> <?= e($order['shipping_city']) ?></p>
                <p class="text-xs text-gray-400 pt-2"><i class="fa-solid fa-phone mr-1.5"></i> <?= e($order['customer_phone']) ?></p>
                <?php if (!empty($order['customer_nif'])): ?>
                    <p class="text-xs text-gray-400"><i class="fa-solid fa-file-invoice mr-1.5"></i> NIF: <?= e($order['customer_nif']) ?></p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Detalhe dos Artigos da Encomenda -->
    <div class="bg-white rounded-3xl border border-gray-200 overflow-hidden shadow-sm mb-8">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">
                Recibo / Artigos Encomendados
            </h3>
            <button onclick="window.print()" class="text-xs font-semibold text-blue-600 hover:text-blue-800 flex items-center">
                <i class="fa-solid fa-print mr-1.5"></i> Imprimir Recibo
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="p-4 pl-6">Peça / Produto</th>
                        <th class="p-4 text-center">Preço Un.</th>
                        <th class="p-4 text-center">Qtd</th>
                        <th class="p-4 pr-6 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td class="p-4 pl-6 font-semibold text-gray-900"><?= e($item['product_name']) ?></td>
                            <td class="p-4 text-center"><?= format_price((float)$item['product_price']) ?></td>
                            <td class="p-4 text-center"><?= $item['quantity'] ?></td>
                            <td class="p-4 pr-6 text-right font-bold"><?= format_price((float)$item['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-50 text-xs divide-y divide-gray-200">
                    <tr>
                        <td colspan="3" class="p-3 pl-6 text-right font-medium text-gray-500">Subtotal de Peças:</td>
                        <td class="p-3 pr-6 text-right font-bold text-gray-900"><?= format_price((float)$order['subtotal']) ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="p-3 pl-6 text-right font-medium text-gray-500">IVA (23% incluído):</td>
                        <td class="p-3 pr-6 text-right text-gray-600"><?= format_price((float)$order['tax_amount']) ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="p-3 pl-6 text-right font-medium text-gray-500">Portes de Envio:</td>
                        <td class="p-3 pr-6 text-right text-gray-900">
                            <?= (float)$order['shipping_cost'] == 0 ? '<span class="text-green-600 font-bold">Grátis</span>' : format_price((float)$order['shipping_cost']) ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="p-4 pl-6 text-right text-sm font-extrabold text-gray-900">Total Pago / A Pagar:</td>
                        <td class="p-4 pr-6 text-right text-lg font-black text-blue-600"><?= format_price((float)$order['total']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Botões de Ação Final -->
    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
        <a href="<?= url('index.php') ?>" class="w-full sm:w-auto bg-gray-900 hover:bg-gray-800 text-white font-bold py-3.5 px-8 rounded-2xl text-xs text-center transition">
            <i class="fa-solid fa-arrow-left mr-2"></i> Voltar à Loja
        </a>
        <a href="<?= url('profile.php#orders') ?>" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 px-8 rounded-2xl text-xs text-center transition shadow-md">
            <i class="fa-solid fa-box-open mr-2"></i> Ver no Meu Histórico
        </a>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

