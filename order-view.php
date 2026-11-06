<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$currentUser = current_user();
$db = Database::getConnection();

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    header('Location: ' . url('profile.php'));
    exit;
}

// Obter encomenda
$stmt = $db->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $orderId]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Encomenda não encontrada.');
    header('Location: ' . url('profile.php'));
    exit;
}

// Verificar permissões: se não for admin e não pertencer ao cliente, bloquear
if (!is_admin() && $order['user_id'] != $currentUser['id'] && $order['customer_email'] !== $currentUser['email']) {
    set_flash('danger', 'Acesso negado: não tem permissão para visualizar esta encomenda.');
    header('Location: ' . url('profile.php'));
    exit;
}

// Obter itens da encomenda
$stmtItems = $db->prepare("
    SELECT oi.*, p.slug, p.image_url 
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = :oid
");
$stmtItems->execute([':oid' => $orderId]);
$orderItems = $stmtItems->fetchAll();

$pageTitle = "Encomenda " . $order['order_number'];
require_once __DIR__ . '/includes/header.php';

$statusInfo = get_order_status_info($order['status']);
?>

<div class="bg-gray-100 py-6 border-b border-gray-200">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <nav class="text-xs text-gray-500 mb-1 flex items-center space-x-2">
                    <a href="<?= url('index.php') ?>" class="hover:text-blue-600">Início</a>
                    <span>/</span>
                    <a href="<?= url('profile.php#orders') ?>" class="hover:text-blue-600">As Minhas Encomendas</a>
                    <span>/</span>
                    <span class="text-gray-900 font-medium"><?= e($order['order_number']) ?></span>
                </nav>
                <h1 class="text-2xl font-black text-gray-900 flex items-center gap-3">
                    <span>Encomenda <?= e($order['order_number']) ?></span>
                    <span class="text-xs px-3 py-1 rounded-full border <?= $statusInfo['class'] ?> font-bold">
                        <?= $statusInfo['label'] ?>
                    </span>
                </h1>
            </div>

            <div class="flex items-center space-x-3">
                <button onclick="window.print()" class="bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 font-semibold px-4 py-2 rounded-xl text-xs transition flex items-center">
                    <i class="fa-solid fa-print mr-1.5"></i> Imprimir Fatura / Recibo
                </button>
            </div>
        </div>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-10 space-y-8">
    
    <!-- Resumo dos Dados de Envio e Pagamento -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm">
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Morada de Entrega</h3>
            <p class="font-bold text-gray-900 text-sm"><?= e($order['customer_name']) ?></p>
            <p class="text-xs text-gray-600 mt-1"><?= e($order['shipping_address']) ?></p>
            <p class="text-xs text-gray-600"><?= e($order['shipping_postal_code']) ?> <?= e($order['shipping_city']) ?></p>
            <div class="mt-3 pt-3 border-t border-gray-100 text-xs text-gray-500 space-y-1">
                <p><i class="fa-solid fa-phone mr-1.5"></i> <?= e($order['customer_phone']) ?></p>
                <p><i class="fa-regular fa-envelope mr-1.5"></i> <?= e($order['customer_email']) ?></p>
                <?php if (!empty($order['customer_nif'])): ?>
                    <p><i class="fa-solid fa-file-invoice mr-1.5"></i> NIF: <?= e($order['customer_nif']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm">
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Detalhes do Pagamento</h3>
            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-gray-400 block">Forma de Pagamento:</span>
                    <span class="font-bold text-gray-800 text-sm"><?= get_payment_method_name($order['payment_method']) ?></span>
                </div>
                
                <?php if (!empty($order['payment_reference'])): ?>
                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 font-mono text-gray-800 font-bold">
                        <?= e($order['payment_reference']) ?>
                    </div>
                <?php endif; ?>

                <div class="pt-2 flex items-center justify-between">
                    <span class="text-gray-500">Estado Financeiro:</span>
                    <?php if ($order['payment_status'] === 'paid'): ?>
                        <span class="text-green-700 font-bold bg-green-100 px-2 py-0.5 rounded">Liquidado</span>
                    <?php else: ?>
                        <span class="text-yellow-700 font-bold bg-yellow-100 px-2 py-0.5 rounded">Pendente</span>
                    <?php endif; ?>
                </div>

                <div class="text-gray-400 text-[11px] pt-1">
                    Data da Encomenda: <?= format_date($order['created_at'], true) ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Tabela de Itens Comprados -->
    <div class="bg-white rounded-3xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">
                Artigos da Encomenda
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="p-4 pl-6">Peça</th>
                        <th class="p-4 text-center">Preço Un.</th>
                        <th class="p-4 text-center">Qtd</th>
                        <th class="p-4 pr-6 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    <?php foreach ($orderItems as $it): ?>
                        <tr>
                            <td class="p-4 pl-6 font-semibold text-gray-900">
                                <?php if (!empty($it['slug'])): ?>
                                    <a href="<?= url('product-detail.php?slug=' . urlencode($it['slug'])) ?>" class="hover:text-blue-600 transition">
                                        <?= e($it['product_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <?= e($it['product_name']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center"><?= format_price((float)$it['product_price']) ?></td>
                            <td class="p-4 text-center font-bold"><?= $it['quantity'] ?></td>
                            <td class="p-4 pr-6 text-right font-black text-gray-900"><?= format_price((float)$it['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-50 divide-y divide-gray-200">
                    <tr>
                        <td colspan="3" class="p-3 pl-6 text-right text-gray-500">Subtotal:</td>
                        <td class="p-3 pr-6 text-right font-bold text-gray-900"><?= format_price((float)$order['subtotal']) ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="p-3 pl-6 text-right text-gray-500">IVA (23% incluído):</td>
                        <td class="p-3 pr-6 text-right text-gray-600"><?= format_price((float)$order['tax_amount']) ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="p-3 pl-6 text-right text-gray-500">Portes de Envio:</td>
                        <td class="p-3 pr-6 text-right font-bold text-gray-900">
                            <?= (float)$order['shipping_cost'] == 0 ? '<span class="text-green-600">Grátis</span>' : format_price((float)$order['shipping_cost']) ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="p-4 pl-6 text-right text-sm font-extrabold text-gray-900">Total:</td>
                        <td class="p-4 pr-6 text-right text-base font-black text-blue-600"><?= format_price((float)$order['total']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <?php if (!empty($order['notes'])): ?>
        <div class="bg-gray-50 p-5 rounded-2xl border border-gray-200 text-xs text-gray-600">
            <span class="font-bold text-gray-800 block mb-1">Notas / Instruções Especiais:</span>
            <?= nl2br(e($order['notes'])) ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

