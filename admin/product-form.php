<?php
require_once __DIR__ . '/../includes/admin_header.php';

$productId = (int)($_GET['id'] ?? 0);
$isEditing = $productId > 0;

$product = [
    'id'             => 0,
    'category_id'    => 1,
    'brand_id'       => null,
    'name'           => '',
    'slug'           => '',
    'oem_reference'  => '',
    'manufacturer'   => '',
    'description'    => '',
    'specifications' => '',
    'compatibility'  => '',
    'price'          => '',
    'discount_price' => '',
    'stock'          => 10,
    'image_url'      => '',
    'is_featured'    => 0,
    'is_active'      => 1
];

if ($isEditing) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $productId]);
    $fetched = $stmt->fetch();
    if ($fetched) {
        $product = $fetched;
    } else {
        set_flash('danger', 'Produto não encontrado.');
        header('Location: ' . url('admin/products.php'));
        exit;
    }
}

$errors = [];

// Submissão do Formulário (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Token de segurança inválido.';
    }

    $product['name']           = trim($_POST['name'] ?? '');
    $product['category_id']    = (int)($_POST['category_id'] ?? 0);
    $product['brand_id']       = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
    $product['oem_reference']  = trim($_POST['oem_reference'] ?? '');
    $product['manufacturer']   = trim($_POST['manufacturer'] ?? 'Genérico');
    $product['description']    = trim($_POST['description'] ?? '');
    $product['specifications'] = trim($_POST['specifications'] ?? '');
    $product['compatibility']  = trim($_POST['compatibility'] ?? '');
    $product['price']          = (float)str_replace(',', '.', $_POST['price'] ?? 0);
    $product['discount_price'] = !empty($_POST['discount_price']) ? (float)str_replace(',', '.', $_POST['discount_price']) : null;
    $product['stock']          = max(0, (int)($_POST['stock'] ?? 0));
    $product['image_url']      = trim($_POST['image_url'] ?? '');
    $product['is_featured']    = !empty($_POST['is_featured']) ? 1 : 0;
    $product['is_active']      = !empty($_POST['is_active']) ? 1 : 0;

    if (empty($product['name'])) $errors[] = 'O nome da peça é obrigatório.';
    if ($product['category_id'] <= 0) $errors[] = 'Selecione uma categoria válida.';
    if ($product['price'] <= 0) $errors[] = 'O preço deve ser superior a 0.';
    if (empty($product['description'])) $errors[] = 'A descrição da peça é obrigatória.';

    if (empty($errors)) {
        // Gerar slug único
        $baseSlug = slugify($product['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $stmtSlug = $db->prepare("SELECT id FROM products WHERE slug = :slug AND id != :id LIMIT 1");
            $stmtSlug->execute([':slug' => $slug, ':id' => $productId]);
            if (!$stmtSlug->fetch()) {
                break;
            }
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        if ($isEditing) {
            $stmtUpdate = $db->prepare("
                UPDATE products SET
                    category_id = :category_id,
                    brand_id = :brand_id,
                    name = :name,
                    slug = :slug,
                    oem_reference = :oem_reference,
                    manufacturer = :manufacturer,
                    description = :description,
                    specifications = :specifications,
                    compatibility = :compatibility,
                    price = :price,
                    discount_price = :discount_price,
                    stock = :stock,
                    image_url = :image_url,
                    is_featured = :is_featured,
                    is_active = :is_active
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                ':category_id'    => $product['category_id'],
                ':brand_id'       => $product['brand_id'],
                ':name'           => $product['name'],
                ':slug'           => $slug,
                ':oem_reference'  => $product['oem_reference'] ?: null,
                ':manufacturer'   => $product['manufacturer'],
                ':description'    => $product['description'],
                ':specifications' => $product['specifications'] ?: null,
                ':compatibility'  => $product['compatibility'] ?: null,
                ':price'          => $product['price'],
                ':discount_price' => $product['discount_price'],
                ':stock'          => $product['stock'],
                ':image_url'      => $product['image_url'] ?: null,
                ':is_featured'    => $product['is_featured'],
                ':is_active'      => $product['is_active'],
                ':id'             => $productId
            ]);

            set_flash('success', 'Peça atualizada com sucesso!');
        } else {
            $stmtInsert = $db->prepare("
                INSERT INTO products (
                    category_id, brand_id, name, slug, oem_reference, manufacturer,
                    description, specifications, compatibility, price, discount_price,
                    stock, image_url, is_featured, is_active, created_at
                ) VALUES (
                    :category_id, :brand_id, :name, :slug, :oem_reference, :manufacturer,
                    :description, :specifications, :compatibility, :price, :discount_price,
                    :stock, :image_url, :is_featured, :is_active, NOW()
                )
            ");
            $stmtInsert->execute([
                ':category_id'    => $product['category_id'],
                ':brand_id'       => $product['brand_id'],
                ':name'           => $product['name'],
                ':slug'           => $slug,
                ':oem_reference'  => $product['oem_reference'] ?: null,
                ':manufacturer'   => $product['manufacturer'],
                ':description'    => $product['description'],
                ':specifications' => $product['specifications'] ?: null,
                ':compatibility'  => $product['compatibility'] ?: null,
                ':price'          => $product['price'],
                ':discount_price' => $product['discount_price'],
                ':stock'          => $product['stock'],
                ':image_url'      => $product['image_url'] ?: null,
                ':is_featured'    => $product['is_featured'],
                ':is_active'      => $product['is_active']
            ]);

            set_flash('success', 'Nova peça adicionada ao catálogo com sucesso!');
        }

        header('Location: ' . url('admin/products.php'));
        exit;
    }
}

$categories = get_all_categories();
$brands = get_all_brands();
$pageTitle = $isEditing ? "Editar Peça: " . $product['name'] : "Adicionar Nova Peça";
?>

<div class="max-w-4xl mx-auto space-y-6">
    
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900"><?= $isEditing ? 'Editar Peça' : 'Criar Nova Peça' ?></h2>
            <p class="text-xs text-gray-500">Preencha as informações detalhadas e parâmetros técnicos da peça.</p>
        </div>
        <a href="<?= url('admin/products.php') ?>" class="text-xs font-bold text-gray-600 hover:text-gray-900 bg-white border border-gray-300 px-3 py-2 rounded-xl transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Voltar à Lista
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 text-red-800 text-xs space-y-1">
            <?php foreach ($errors as $e): ?>
                <p><i class="fa-solid fa-circle-exclamation mr-1.5"></i> <?= e($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" class="bg-white p-6 sm:p-8 rounded-3xl border border-gray-200 shadow-sm space-y-6">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nome da Peça / Produto *</label>
                <input type="text" name="name" required value="<?= e($product['name']) ?>" placeholder="Ex: Discos de Travão Brembo Max Ventilados" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Categoria de Peça *</label>
                <select name="category_id" required class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Marca do Carro Compatível</label>
                <select name="brand_id" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Universal / Várias Marcas</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= $brand['id'] ?>" <?= $product['brand_id'] == $brand['id'] ? 'selected' : '' ?>>
                            <?= e($brand['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Referência OEM / Código Peça</label>
                <input type="text" name="oem_reference" value="<?= e($product['oem_reference']) ?>" placeholder="Ex: 09.C399.13" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2 text-sm font-mono">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Fabricante da Peça</label>
                <input type="text" name="manufacturer" value="<?= e($product['manufacturer']) ?>" placeholder="Ex: Brembo, Bosch, Castrol, Continental" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Preço Normal (€) *</label>
                <input type="number" step="0.01" name="price" required value="<?= e($product['price']) ?>" placeholder="0.00" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2 text-sm font-bold">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Preço Promocional (€) (Opcional)</label>
                <input type="number" step="0.01" name="discount_price" value="<?= e($product['discount_price']) ?>" placeholder="Deixe vazio se sem promoção" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2 text-sm text-green-700 font-bold">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Stock Disponível (Unidades) *</label>
                <input type="number" name="stock" required value="<?= (int)$product['stock'] ?>" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2 text-sm font-bold">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">URL da Imagem da Peça</label>
                <input type="text" name="image_url" value="<?= e($product['image_url']) ?>" placeholder="https://... ou caminho local" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2 text-sm">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Descrição Geral da Peça *</label>
                <textarea name="description" rows="3" required class="w-full bg-gray-50 border border-gray-300 rounded-xl p-3 text-sm"><?= e($product['description']) ?></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Especificações Técnicas (1 por linha)</label>
                <textarea name="specifications" rows="3" placeholder="Diâmetro: 340mm&#10;Espessura: 30mm&#10;Eixo: Dianteiro" class="w-full bg-gray-50 border border-gray-300 rounded-xl p-3 text-xs font-mono"><?= e($product['specifications']) ?></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Veículos Compatíveis (1 por linha)</label>
                <textarea name="compatibility" rows="3" placeholder="BMW Série 3 (F30) 320d (2012-2018)&#10;BMW Série 4 (F32) 420d" class="w-full bg-gray-50 border border-gray-300 rounded-xl p-3 text-xs"><?= e($product['compatibility']) ?></textarea>
            </div>

            <div class="sm:col-span-2 flex items-center space-x-6 pt-2">
                <label class="flex items-center text-xs font-bold text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : '' ?> class="h-4 w-4 text-blue-600 rounded mr-2">
                    Destacar na Página Inicial
                </label>
                <label class="flex items-center text-xs font-bold text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" <?= $product['is_active'] ? 'checked' : '' ?> class="h-4 w-4 text-blue-600 rounded mr-2">
                    Peça Ativa / Visível na Loja
                </label>
            </div>

        </div>

        <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
            <a href="<?= url('admin/products.php') ?>" class="px-5 py-2.5 rounded-xl text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
                Cancelar
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl text-xs transition shadow-md">
                <?= $isEditing ? 'Guardar Alterações' : 'Criar Peça' ?>
            </button>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

