<?php
$pageTitle = "Dashboard Geral";
require_once __DIR__ . '/../includes/admin_header.php';

// Cálculos estatísticos reais via BD
$totalSales = (float)$db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'paid' OR status = 'completed'")->fetchColumn();
$totalOrders = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalProducts = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStockCount = (int)$db->query("SELECT COUNT(*) FROM products WHERE stock <= 5 AND is_active = 1")->fetchColumn();
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalNews = (int)$db->query("SELECT COUNT(*) FROM news WHERE is_published = 1")->fetchColumn();

// Encomendas recentes
$recentOrders = $db->query("
    SELECT * FROM orders 
    ORDER BY id DESC 
    LIMIT 6
")->fetchAll();

// Produtos com stock mais baixo (Alerta de Stock)
$lowStockProducts = $db->query("
    SELECT p.*, c.name AS category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1 AND p.stock <= 8
    ORDER BY p.stock ASC 
    LIMIT 5
")->fetchAll();

// Notícias mais lidas
$topNews = $db->query("
    SELECT * FROM news 
    WHERE is_published = 1 
    ORDER BY views DESC 
    LIMIT 4
")->fetchAll();
?>

<!-- KPI Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- Total Vendas -->
    <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Volume de Vendas</span>
            <span class="text-2xl font-black text-gray-900"><?= format_price($totalSales) ?></span>
            <span class="text-[11px] text-green-600 block mt-1"><i class="fa-solid fa-arrow-trend-up mr-1"></i> Faturas liquidadas</span>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center text-xl">
            <i class="fa-solid fa-euro-sign"></i>
        </div>
    </div>

    <!-- Total Encomendas -->
    <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Encomendas Totais</span>
            <span class="text-2xl font-black text-gray-900"><?= $totalOrders ?></span>
            <span class="text-[11px] text-blue-600 block mt-1"><i class="fa-solid fa-cart-shopping mr-1"></i> <?= $pendingOrdersCount ?> pendente(s)</span>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
            <i class="fa-solid fa-boxes-packing"></i>
        </div>
    </div>

    <!-- Catálogo de Peças -->
    <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Peças em Catálogo</span>
            <span class="text-2xl font-black text-gray-900"><?= $totalProducts ?></span>
            <span class="text-[11px] text-purple-600 block mt-1"><i class="fa-solid fa-wrench mr-1"></i> Peças cadastradas</span>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
            <i class="fa-solid fa-screwdriver-wrench"></i>
        </div>
    </div>

    <!-- Alertas de Stock Baixo -->
    <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Alerta de Stock (&le; 5)</span>
            <span class="text-2xl font-black <?= $lowStockCount > 0 ? 'text-red-600' : 'text-gray-900' ?>"><?= $lowStockCount ?></span>
            <span class="text-[11px] text-gray-500 block mt-1">Peças a precisar reposição</span>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-xl">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
    </div>

</div>

<!-- Ações Rápidas -->
<div class="bg-gradient-to-r from-gray-900 to-blue-900 text-white rounded-3xl p-6 mb-8 flex flex-wrap items-center justify-between gap-4 shadow-md">
    <div>
        <h3 class="font-extrabold text-lg">Painel de Acesso Rápido</h3>
        <p class="text-xs text-gray-300">Crie novos artigos, peças no catálogo ou consulte pedidos em aberto.</p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="<?= url('admin/product-form.php') ?>" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold py-2.5 px-4 rounded-xl transition flex items-center">
            <i class="fa-solid fa-plus mr-1.5"></i> Nova Peça
        </a>
        <a href="<?= url('admin/news-form.php') ?>" class="bg-red-600 hover:bg-red-500 text-white text-xs font-bold py-2.5 px-4 rounded-xl transition flex items-center">
            <i class="fa-solid fa-pen-nib mr-1.5"></i> Nova Notícia / Artigo
        </a>
        <a href="<?= url('admin/orders.php?status=pending') ?>" class="bg-yellow-500 hover:bg-yellow-400 text-gray-900 text-xs font-bold py-2.5 px-4 rounded-xl transition flex items-center">
            <i class="fa-solid fa-clock mr-1.5"></i> Ver Encomendas Pendentes
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
    
    <!-- Tabela de Encomendas Recentes -->
    <div class="lg:col-span-8 bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-900 text-base flex items-center">
                <i class="fa-solid fa-clock-rotate-left text-blue-600 mr-2"></i> Encomendas Recentes
            </h3>
            <a href="<?= url('admin/orders.php') ?>" class="text-xs font-bold text-blue-600 hover:underline">Ver Todas &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-admin">
                <thead>
                    <tr>
                        <th class="p-4 pl-6">Ref.</th>
                        <th class="p-4">Cliente</th>
                        <th class="p-4">Total</th>
                        <th class="p-4">Pagamento</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4 pr-6 text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="6" class="p-6 text-center text-gray-400">Nenhuma encomenda registada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $ro): 
                            $stInfo = get_order_status_info($ro['status']);
                        ?>
                            <tr>
                                <td class="p-4 pl-6 font-mono font-bold text-gray-900"><?= e($ro['order_number']) ?></td>
                                <td class="p-4 font-semibold text-gray-800"><?= e($ro['customer_name']) ?></td>
                                <td class="p-4 font-black text-gray-900"><?= format_price((float)$ro['total']) ?></td>
                                <td class="p-4">
                                    <span class="uppercase text-[10px] font-bold <?= $ro['payment_status'] === 'paid' ? 'text-green-600' : 'text-yellow-600' ?>">
                                        <?= e($ro['payment_method']) ?> (<?= $ro['payment_status'] ?>)
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold border <?= $stInfo['class'] ?>">
                                        <?= $stInfo['label'] ?>
                                    </span>
                                </td>
                                <td class="p-4 pr-6 text-right">
                                    <a href="<?= url('admin/order-detail.php?id=' . $ro['id']) ?>" class="bg-gray-100 hover:bg-blue-600 hover:text-white px-3 py-1 rounded-lg font-bold text-gray-700 transition">
                                        Gerir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Alertas de Reposição de Peças -->
    <div class="lg:col-span-4 bg-white rounded-3xl border border-gray-200 shadow-sm p-6">
        <h3 class="font-bold text-gray-900 text-base mb-4 flex items-center text-red-600">
            <i class="fa-solid fa-boxes-stacked mr-2"></i> Stock Crítico (&le; 8 un.)
        </h3>

        <?php if (empty($lowStockProducts)): ?>
            <p class="text-xs text-gray-400 italic">Todos os produtos têm níveis saudáveis de stock.</p>
        <?php else: ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($lowStockProducts as $lsp): ?>
                    <div class="py-3 flex items-center justify-between text-xs">
                        <div class="max-w-[180px]">
                            <h4 class="font-bold text-gray-900 truncate"><?= e($lsp['name']) ?></h4>
                            <span class="text-[10px] text-gray-400 font-mono">REF: <?= e($lsp['oem_reference']) ?></span>
                        </div>
                        <div class="text-right">
                            <span class="font-bold text-xs <?= $lsp['stock'] <= 5 ? 'text-red-600 font-black' : 'text-yellow-600' ?>">
                                <?= $lsp['stock'] ?> un.
                            </span>
                            <a href="<?= url('admin/product-form.php?id=' . $lsp['id']) ?>" class="block text-[10px] text-blue-600 hover:underline">Editar</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a href="<?= url('admin/products.php') ?>" class="mt-6 block text-center bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-700 font-bold py-2 rounded-xl text-xs transition">
            Ver Todo o Catálogo &rarr;
        </a>
    </div>

</div>

<!-- Linha Inferior: Artigos do Blog Mais Vistos -->
<div class="bg-white rounded-3xl border border-gray-200 p-6 shadow-sm">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-gray-900 text-base flex items-center">
            <i class="fa-solid fa-newspaper text-blue-600 mr-2"></i> Artigos Mais Lidos no Módulo de Notícias
        </h3>
        <a href="<?= url('admin/news.php') ?>" class="text-xs font-bold text-blue-600 hover:underline">Gerir Notícias &rarr;</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach ($topNews as $tn): ?>
            <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100 flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-bold text-blue-600 uppercase"><?= e($tn['category']) ?></span>
                    <h4 class="font-bold text-xs text-gray-900 line-clamp-2 mt-1 mb-2"><?= e($tn['title']) ?></h4>
                </div>
                <div class="flex items-center justify-between text-xs text-gray-400 pt-2 border-t border-gray-200">
                    <span><i class="fa-regular fa-eye mr-1"></i> <?= $tn['views'] ?> vistas</span>
                    <a href="<?= url('admin/news-form.php?id=' . $tn['id']) ?>" class="font-bold text-blue-600 hover:underline">Editar</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

