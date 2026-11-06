<?php
require_once __DIR__ . '/../includes/admin_header.php';

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    header('Location: ' . url('admin/orders.php'));
    exit;
}

// Buscar encomenda
$stmt = $db->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $orderId]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Encomenda não encontrada.');
    header('Location: ' . url('admin/orders.php'));
    exit;
}

// Atualização de Estado (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Token de segurança inválido.');
    } else {
        $newStatus        = trim($_POST['status'] ?? 'pending');
        $newPaymentStatus = trim($_POST['payment_status'] ?? 'pending');
        $notes            = trim($_POST['notes'] ?? '');

        if (in_array($newStatus, ['pending', 'processing', 'shipped', 'completed', 'cancelled']) &&
            in_array($newPaymentStatus, ['pending', 'paid', 'failed'])) {
            
            $stmtUp = $db->prepare("
                UPDATE orders 
                SET status = :status, payment_status = :p_status, notes = :notes 
                WHERE id = :id
            ");
            $stmtUp->execute([
                ':status'   => $newStatus,
                ':p_status' => $newPaymentStatus,
                ':notes'    => $notes ?: null,
                ':id'       => $orderId
            ]);

            set_flash('success', 'Estado da encomenda atualizado com sucesso!');
            header('Location: ' . url('admin/order-detail.php?id=' . $orderId));
            exit;
        }
    }
}

// Buscar itens
$stmtItems = $db->prepare("
    SELECT oi.*, p.slug, p.image_url, p.oem_reference 
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = :oid
");
$stmtItems->execute([':oid' => $orderId]);
$orderItems = $stmtItems->fetchAll();

$pageTitle = "Gerir Encomenda " . $order['order_number'];
$stInfo = get_order_status_info($order['status']);
?>

<div class="max-w-5xl mx-auto space-y-6">
    
    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-gray-500 mb-1">
                <a href="<?= url('admin/orders.php') ?>" class="hover:underline">Encomendas</a>
                <span>/</span>
                <span class="text-gray-800 font-bold"><?= e($order['order_number']) ?></span>
            </div>
            <h2 class="text-2xl font-black text-gray-900 flex items-center gap-3">
                <span>Encomenda <?= e($order['order_number']) ?></span>
                <span class="text-xs px-3 py-1 rounded-full border <?= $stInfo['class'] ?> font-bold">
                    <?= $stInfo['label'] ?>
                </span>
            </h2>
        </div>

        <div class="flex items-center space-x-3">
            <a href="<?= url('order-view.php?id=' . $order['id']) ?>" target="_blank" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-bold px-4 py-2 rounded-xl text-xs transition flex items-center">
                <i class="fa-solid fa-file-invoice mr-1.5"></i> Fatura / Recibo
            </a>
            <a href="<?= url('admin/orders.php') ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold px-4 py-2 rounded-xl text-xs transition">
                Voltar à Lista
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Formulário de Atualização do Estado -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 flex items-center">
                    <i class="fa-solid fa-pen-to-square text-blue-600 mr-2"></i> Atualizar Estado
                </h3>

                <form action="" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Estado do Pedido</label>
                        <select name="status" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm font-bold focus:ring-2 focus:ring-blue-500">
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pendente (Aguardando)</option>
                            <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Em Processamento (Na Oficina/Armazém)</option>
                            <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Enviado (Com a Transportadora)</option>
                            <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Concluído (Entregue ao Cliente)</option>
                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Estado do Pagamento</label>
                        <select name="payment_status" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm font-bold focus:ring-2 focus:ring-blue-500">
                            <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Pendente</option>
                            <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>Pago / Confirmado</option>
                            <option value="failed" <?= $order['payment_status'] === 'failed' ? 'selected' : '' ?>>Falhado / Rejeitado</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Notas Internas da Encomenda</label>
                        <textarea name="notes" rows="3" placeholder="Observações de envio, código de rastreio CTT, etc..." class="w-full bg-gray-50 border border-gray-300 rounded-xl p-3 text-xs"><?= e($order['notes']) ?></textarea>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-xs transition shadow-md">
                        Guardar Alterações
                    </button>
                </form>
            </div>

            <!-- Dados do Cliente e Envio -->
            <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm text-xs space-y-3">
                <h3 class="font-bold text-gray-900 uppercase tracking-wider pb-2 border-b border-gray-100 flex items-center">
                    <i class="fa-solid fa-user text-gray-600 mr-2"></i> Destinatário
                </h3>
                <p class="font-bold text-sm text-gray-900"><?= e($order['customer_name']) ?></p>
                <p class="text-gray-600"><?= e($order['shipping_address']) ?></p>
                <p class="text-gray-600"><?= e($order['shipping_postal_code']) ?> <?= e($order['shipping_city']) ?></p>
                <p class="text-gray-500 pt-2"><i class="fa-solid fa-phone mr-1"></i> <?= e($order['customer_phone']) ?></p>
                <p class="text-gray-500"><i class="fa-regular fa-envelope mr-1"></i> <?= e($order['customer_email']) ?></p>
                <?php if (!empty($order['customer_nif'])): ?>
                    <p class="text-gray-500 font-mono"><i class="fa-solid fa-id-card mr-1"></i> NIF: <?= e($order['customer_nif']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabela de Itens Comprados e Resumo Financeiro -->
        <div class="lg:col-span-7 space-y-6">
            <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900 text-base">Peças Encomendadas</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs table-admin">
                        <thead>
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
                                        <?= e($it['product_name']) ?>
                                        <?php if (!empty($it['oem_reference'])): ?>
                                            <span class="text-[10px] text-gray-400 font-mono block">REF: <?= e($it['oem_reference']) ?></span>
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
                                <td colspan="3" class="p-3 pl-6 text-right text-gray-500">Subtotal de Peças:</td>
                                <td class="p-3 pr-6 text-right font-bold text-gray-900"><?= format_price((float)$order['subtotal']) ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="p-3 pl-6 text-right text-gray-500">IVA (23%):</td>
                                <td class="p-3 pr-6 text-right text-gray-600"><?= format_price((float)$order['tax_amount']) ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="p-3 pl-6 text-right text-gray-500">Portes de Envio:</td>
                                <td class="p-3 pr-6 text-right font-bold text-gray-900">
                                    <?= (float)$order['shipping_cost'] == 0 ? '<span class="text-green-600">Grátis</span>' : format_price((float)$order['shipping_cost']) ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="p-4 pl-6 text-right text-sm font-extrabold text-gray-900">Total Encomenda:</td>
                                <td class="p-4 pr-6 text-right text-base font-black text-blue-600"><?= format_price((float)$order['total']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Dados de Pagamento -->
            <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm text-xs">
                <h3 class="font-bold text-gray-900 uppercase tracking-wider mb-2">Dados de Pagamento Registados</h3>
                <p><strong>Método:</strong> <?= get_payment_method_name($order['payment_method']) ?></p>
                <p class="font-mono mt-1"><strong>Referência:</strong> <?= e($order['payment_reference']) ?></p>
                <p class="text-gray-400 mt-1">Registo Efetuado: <?= format_date($order['created_at'], true) ?></p>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

