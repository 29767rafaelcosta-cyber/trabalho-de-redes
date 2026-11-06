<?php
$pageTitle = "Gestão de Peças & Produtos";
require_once __DIR__ . '/../includes/admin_header.php';

// Processar Eliminação (POST com CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Token de segurança inválido.');
    } else {
        $deleteId = (int)($_POST['product_id'] ?? 0);
        try {
            // Verificar se o produto está associado a encomendas existentes
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = :id");
            $stmtCheck->execute([':id' => $deleteId]);
            $orderCount = (int)$stmtCheck->fetchColumn();

            if ($orderCount > 0) {
                // Se já estiver em encomendas históricas, apenas desativamos para manter integridade
                $stmtDeactivate = $db->prepare("UPDATE products SET is_active = 0 WHERE id = :id");
                $stmtDeactivate->execute([':id' => $deleteId]);
                set_flash('warning', 'O produto foi desativado em vez de eliminado para preservar o histórico de encomendas existentes.');
            } else {
                $stmtDel = $db->prepare("DELETE FROM products WHERE id = :id");
                $stmtDel->execute([':id' => $deleteId]);
                set_flash('success', 'Peça eliminada com sucesso da base de dados.');
            }
        } catch (Exception $e) {
            set_flash('danger', 'Erro ao eliminar peça: ' . $e->getMessage());
        }
    }
    header('Location: ' . url('admin/products.php'));
    exit;
}

// Filtros de listagem
$search = trim($_GET['q'] ?? '');
$catId  = (int)($_GET['category_id'] ?? 0);
$brandId = (int)($_GET['brand_id'] ?? 0);

$where = ["1=1"];
$params = [];

if ($search !== '') {
    $where[] = "(p.name LIKE :search OR p.oem_reference LIKE :search OR p.manufacturer LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($catId > 0) {
    $where[] = "p.category_id = :cat_id";
    $params[':cat_id'] = $catId;
}

if ($brandId > 0) {
    $where[] = "p.brand_id = :brand_id";
    $params[':brand_id'] = $brandId;
}

$whereClause = implode(" AND ", $where);
$stmt = $db->prepare("
    SELECT p.*, c.name AS category_name, b.name AS brand_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE {$whereClause}
    ORDER BY p.id DESC
");
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$products = $stmt->fetchAll();

$categories = get_all_categories();
$brands = get_all_brands();
?>

<div class="space-y-6">
    
    <!-- Top Bar com Botão Criar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Catálogo de Peças</h2>
            <p class="text-xs text-gray-500">Adicione, edite stock, referências OEM e preços dos componentes automóveis.</p>
        </div>

        <a href="<?= url('admin/product-form.php') ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition flex items-center shadow-md">
            <i class="fa-solid fa-plus mr-1.5"></i> Adicionar Nova Peça
        </a>
    </div>

    <!-- Barra de Filtros -->
    <form action="<?= url('admin/products.php') ?>" method="GET" class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row gap-3">
        <div class="flex-1 relative">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Pesquisar por nome, marca ou ref. OEM..." class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-xs"></i>
        </div>

        <div class="w-full md:w-56">
            <select name="category_id" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500">
                <option value="0">Todas as Categorias</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $catId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="w-full md:w-48">
            <select name="brand_id" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500">
                <option value="0">Todas as Marcas</option>
                <?php foreach ($brands as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $brandId == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-5 py-2 rounded-xl text-xs font-bold transition">
            Filtrar
        </button>
    </form>

    <!-- Tabela de Peças -->
    <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-admin">
                <thead>
                    <tr>
                        <th class="p-4 pl-6">Foto</th>
                        <th class="p-4">Peça / Produto</th>
                        <th class="p-4">Fabricante & REF</th>
                        <th class="p-4">Categoria</th>
                        <th class="p-4">Preço</th>
                        <th class="p-4 text-center">Stock</th>
                        <th class="p-4 text-center">Estado</th>
                        <th class="p-4 pr-6 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    <?php if (empty($products)): ?>
                        <tr><td colspan="8" class="p-8 text-center text-gray-400">Nenhuma peça encontrada com estes filtros.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td class="p-4 pl-6">
                                    <div class="w-12 h-12 bg-gray-50 rounded-lg p-1 border border-gray-100 flex items-center justify-center">
                                        <img src="<?= e($p['image_url'] ?: 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=100&auto=format&fit=crop&q=80') ?>" alt="<?= e($p['name']) ?>" class="max-h-full object-contain">
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="font-bold text-gray-900 block"><?= e($p['name']) ?></span>
                                    <?php if ($p['is_featured']): ?>
                                        <span class="text-[9px] bg-red-100 text-red-700 font-bold px-1.5 py-0.5 rounded">Destaque</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4">
                                    <span class="font-semibold text-gray-800"><?= e($p['manufacturer']) ?></span>
                                    <?php if (!empty($p['oem_reference'])): ?>
                                        <span class="font-mono text-[10px] text-gray-500 block">OEM: <?= e($p['oem_reference']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 font-medium text-gray-600">
                                    <?= e($p['category_name']) ?>
                                    <?php if (!empty($p['brand_name'])): ?>
                                        <span class="text-[10px] text-blue-600 block">(<?= e($p['brand_name']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 font-black text-gray-900">
                                    <?= format_price((float)$p['price']) ?>
                                    <?php if (!empty($p['discount_price'])): ?>
                                        <span class="text-green-600 block text-[10px]">Promo: <?= format_price((float)$p['discount_price']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if ($p['stock'] > 5): ?>
                                        <span class="bg-green-100 text-green-800 font-bold px-2 py-0.5 rounded-full text-[10px]"><?= $p['stock'] ?> un.</span>
                                    <?php elseif ($p['stock'] > 0): ?>
                                        <span class="bg-yellow-100 text-yellow-800 font-bold px-2 py-0.5 rounded-full text-[10px]"><?= $p['stock'] ?> un.</span>
                                    <?php else: ?>
                                        <span class="bg-red-100 text-red-800 font-bold px-2 py-0.5 rounded-full text-[10px]">Esgotado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if ($p['is_active']): ?>
                                        <span class="text-green-600 font-bold text-[10px]"><i class="fa-solid fa-circle text-[8px] mr-1"></i> Ativo</span>
                                    <?php else: ?>
                                        <span class="text-gray-400 font-bold text-[10px]"><i class="fa-solid fa-circle text-[8px] mr-1"></i> Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 pr-6 text-right space-x-2">
                                    <a href="<?= url('product-detail.php?slug=' . urlencode($p['slug'])) ?>" target="_blank" class="text-gray-400 hover:text-gray-700" title="Ver na Loja">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                    <a href="<?= url('admin/product-form.php?id=' . $p['id']) ?>" class="bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white px-2.5 py-1 rounded-lg font-bold transition">
                                        Editar
                                    </a>
                                    
                                    <form action="<?= url('admin/products.php') ?>" method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white px-2.5 py-1 rounded-lg font-bold transition confirm-action" data-confirm="Tem a certeza que deseja eliminar esta peça?">
                                            Eliminar
                                        </button>
                                    </form>
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

