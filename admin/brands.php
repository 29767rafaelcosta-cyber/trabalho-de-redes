<?php
$pageTitle = "Gestão de Marcas de Carros";
require_once __DIR__ . '/../includes/admin_header.php';

$editId = (int)($_GET['edit'] ?? 0);
$editBrand = null;

if ($editId > 0) {
    $stmtB = $db->prepare("SELECT * FROM brands WHERE id = :id LIMIT 1");
    $stmtB->execute([':id' => $editId]);
    $editBrand = $stmtB->fetch();
}

// Processar formulário (Criar / Editar / Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Token de segurança inválido.');
        header('Location: ' . url('admin/brands.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id      = (int)($_POST['brand_id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $logoUrl = trim($_POST['logo_url'] ?? '');

        if (empty($name)) {
            set_flash('danger', 'O nome da marca é obrigatório.');
        } else {
            $slug = slugify($name);

            if ($id > 0) {
                $stmtUp = $db->prepare("
                    UPDATE brands SET name = :name, slug = :slug, country = :country, logo_url = :logo_url WHERE id = :id
                ");
                $stmtUp->execute([':name' => $name, ':slug' => $slug, ':country' => $country ?: null, ':logo_url' => $logoUrl ?: null, ':id' => $id]);
                set_flash('success', 'Marca de automóvel atualizada com sucesso!');
            } else {
                $stmtIn = $db->prepare("
                    INSERT INTO brands (name, slug, country, logo_url) VALUES (:name, :slug, :country, :logo_url)
                ");
                $stmtIn->execute([':name' => $name, ':slug' => $slug, ':country' => $country ?: null, ':logo_url' => $logoUrl ?: null]);
                set_flash('success', 'Nova marca adicionada com sucesso!');
            }
        }
        header('Location: ' . url('admin/brands.php'));
        exit;
    }

    if ($action === 'delete') {
        $delId = (int)($_POST['brand_id'] ?? 0);
        $db->prepare("DELETE FROM brands WHERE id = :id")->execute([':id' => $delId]);
        set_flash('success', 'Marca de automóvel eliminada com sucesso.');
        header('Location: ' . url('admin/brands.php'));
        exit;
    }
}

$brands = get_all_brands();
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    
    <!-- Formulário de Criar / Editar Marca -->
    <div class="lg:col-span-4">
        <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm sticky top-28">
            <h2 class="text-base font-bold text-gray-900 pb-3 border-b border-gray-100 mb-4">
                <?= $editBrand ? 'Editar Marca Automóvel' : 'Adicionar Nova Marca' ?>
            </h2>

            <form action="<?= url('admin/brands.php') ?>" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="brand_id" value="<?= $editBrand['id'] ?? 0 ?>">

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nome da Marca *</label>
                    <input type="text" name="name" required value="<?= e($editBrand['name'] ?? '') ?>" placeholder="Ex: BMW, Audi, Mercedes-Benz" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">País de Origem</label>
                    <input type="text" name="country" value="<?= e($editBrand['country'] ?? '') ?>" placeholder="Ex: Alemanha, Japão, França" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">URL do Logótipo / Imagem</label>
                    <input type="text" name="logo_url" value="<?= e($editBrand['logo_url'] ?? '') ?>" placeholder="https://..." class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm">
                </div>

                <div class="flex items-center space-x-2 pt-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition shadow-md">
                        <?= $editBrand ? 'Guardar Alterações' : 'Criar Marca' ?>
                    </button>
                    <?php if ($editBrand): ?>
                        <a href="<?= url('admin/brands.php') ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-3 rounded-xl text-xs transition">
                            Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Marcas -->
    <div class="lg:col-span-8">
        <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 text-base">Marcas de Veículos</h3>
                    <p class="text-xs text-gray-500">Marcas suportadas pelo catálogo de compatibilidade.</p>
                </div>
                <span class="text-xs font-bold bg-gray-100 px-3 py-1 rounded-full text-gray-700"><?= count($brands) ?> Marcas</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs table-admin">
                    <thead>
                        <tr>
                            <th class="p-4 pl-6">Marca</th>
                            <th class="p-4">País</th>
                            <th class="p-4">Slug</th>
                            <th class="p-4 text-center">Peças Associadas</th>
                            <th class="p-4 pr-6 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        <?php foreach ($brands as $b): ?>
                            <tr>
                                <td class="p-4 pl-6 flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500">
                                        <i class="fa-solid fa-car-side"></i>
                                    </div>
                                    <span class="font-bold text-gray-900 text-sm"><?= e($b['name']) ?></span>
                                </td>
                                <td class="p-4 font-medium text-gray-600">
                                    <?= e($b['country'] ?: 'Não especificado') ?>
                                </td>
                                <td class="p-4 font-mono text-[11px] text-gray-500">
                                    <?= e($b['slug']) ?>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="bg-gray-100 text-gray-800 font-bold px-2.5 py-1 rounded-full text-[10px]">
                                        <?= $b['products_count'] ?> un.
                                    </span>
                                </td>
                                <td class="p-4 pr-6 text-right space-x-2">
                                    <a href="<?= url('admin/brands.php?edit=' . $b['id']) ?>" class="bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white px-2.5 py-1 rounded-lg font-bold transition">
                                        Editar
                                    </a>
                                    <form action="<?= url('admin/brands.php') ?>" method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="brand_id" value="<?= $b['id'] ?>">
                                        <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white px-2.5 py-1 rounded-lg font-bold transition confirm-action" data-confirm="Tem a certeza que deseja eliminar esta marca?">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

