<?php
$pageTitle = "Gestão de Encomendas";
require_once __DIR__ . '/../includes/admin_header.php';

// Filtro de estado
$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['q'] ?? '');

$where = ["1=1"];
$params = [];

if ($statusFilter !== '') {
    $where[] = "o.status = :status";
    $params[':status'] = $statusFilter;
}

if ($search !== '') {
    $where[] = "(o.order_number LIKE :search OR o.customer_name LIKE :search OR o.customer_email LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$whereClause = implode(" AND ", $where);
$stmt = $db->prepare("
    SELECT o.*, COUNT(oi.id) AS items_count 
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE {$whereClause}
    GROUP BY o.id
    ORDER BY o.id DESC
");
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$orders = $stmt->fetchAll();
?>

<div class="space-y-6">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Encomendas de Clientes</h2>
            <p class="text-xs text-gray-500">Faça a gestão dos pedidos, validação de pagamentos e estados de envio.</p>
        </div>
    </div>

    <!-- Barra de Filtros e Pesquisa -->
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        
        <div class="flex flex-wrap gap-2 text-xs font-bold">
            <a href="<?= url('admin/orders.php') ?>" class="px-3 py-1.5 rounded-xl transition <?= empty($statusFilter) ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
                Todas
            </a>
            <a href="<?= url('admin/orders.php?status=pending') ?>" class="px-3 py-1.5 rounded-xl transition <?= $statusFilter === 'pending' ? 'bg-yellow-500 text-gray-900' : 'bg-yellow-50 text-yellow-800 hover:bg-yellow-100' ?>">
                Pendentes
            </a>
            <a href="<?= url('admin/orders.php?status=processing') ?>" class="px-3 py-1.5 rounded-xl transition <?= $statusFilter === 'processing' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-800 hover:bg-blue-100' ?>">
                Em Processamento
            </a>
            <a href="<?= url('admin/orders.php?status=shipped') ?>" class="px-3 py-1.5 rounded-xl transition <?= $statusFilter === 'shipped' ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-800 hover:bg-indigo-100' ?>">
                Enviadas
            </a>
            <a href="<?= url('admin/orders.php?status=completed') ?>" class="px-3 py-1.5 rounded-xl transition <?= $statusFilter === 'completed' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-800 hover:bg-green-100' ?>">
                Concluídas
            </a>
            <a href="<?= url('admin/orders.php?status=cancelled') ?>" class="px-3 py-1.5 rounded-xl transition <?= $statusFilter === 'cancelled' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-800 hover:bg-red-100' ?>">
                Canceladas
            </a>
        </div>

        <form action="<?= url('admin/orders.php') ?>" method="GET" class="relative w-full md:w-64">
            <?php if ($statusFilter): ?>
                <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Ref. ou Nome..." class="w-full pl-8 pr-3 py-1.5 bg-gray-50 border border-gray-300 rounded-xl text-xs">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-2 text-gray-400 text-xs"></i>
        </form>

    </div>

    <!-- Tabela de Encomendas -->
    <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-admin">
                <thead>
                    <tr>
                        <th class="p-4 pl-6">Nº Encomenda</th>
                        <th class="p-4">Cliente / Contacto</th>
                        <th class="p-4">Data</th>
                        <th class="p-4">Pagamento</th>
                        <th class="p-4">Total</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4 pr-6 text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" class="p-8 text-center text-gray-400">Nenhuma encomenda encontrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $ord): 
                            $stInfo = get_order_status_info($ord['status']);
                        ?>
                            <tr>
                                <td class="p-4 pl-6 font-mono font-bold text-gray-900">
                                    <a href="<?= url('admin/order-detail.php?id=' . $ord['id']) ?>" class="hover:text-blue-600">
                                        <?= e($ord['order_number']) ?>
                                    </a>
                                </td>
                                <td class="p-4">
                                    <span class="font-bold text-gray-900 block"><?= e($ord['customer_name']) ?></span>
                                    <span class="text-[11px] text-gray-400"><?= e($ord['customer_email']) ?></span>
                                </td>
                                <td class="p-4 text-gray-500">
                                    <?= format_date($ord['created_at']) ?>
                                </td>
                                <td class="p-4">
                                    <span class="font-semibold text-gray-800 block"><?= get_payment_method_name($ord['payment_method']) ?></span>
                                    <span class="uppercase text-[9px] font-black px-1.5 py-0.5 rounded <?= $ord['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                        <?= $ord['payment_status'] === 'paid' ? 'Pago' : 'Pendente' ?>
                                    </span>
                                </td>
                                <td class="p-4 font-black text-gray-900 text-sm">
                                    <?= format_price((float)$ord['total']) ?>
                                </td>
                                <td class="p-4">
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $stInfo['class'] ?>">
                                        <?= $stInfo['label'] ?>
                                    </span>
                                </td>
                                <td class="p-4 pr-6 text-right">
                                    <a href="<?= url('admin/order-detail.php?id=' . $ord['id']) ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-xl font-bold transition shadow-sm">
                                        Gerir Detalhes
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

