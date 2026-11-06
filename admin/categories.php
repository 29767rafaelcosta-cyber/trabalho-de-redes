<?php
$pageTitle = "Gestão de Categorias";
require_once __DIR__ . '/../includes/admin_header.php';

$editId = (int)($_GET['edit'] ?? 0);
$editCategory = null;

if ($editId > 0) {
    $stmtE = $db->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
    $stmtE->execute([':id' => $editId]);
    $editCategory = $stmtE->fetch();
}

// Processar formulário (Criar / Editar / Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Token de segurança inválido.');
        header('Location: ' . url('admin/categories.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id          = (int)($_POST['category_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon        = trim($_POST['icon'] ?? 'fa-cogs');

        if (empty($name)) {
            set_flash('danger', 'O nome da categoria é obrigatório.');
        } else {
            $slug = slugify($name);

            if ($id > 0) {
                $stmtUp = $db->prepare("
                    UPDATE categories SET name = :name, slug = :slug, description = :desc, icon = :icon WHERE id = :id
                ");
                $stmtUp->execute([':name' => $name, ':slug' => $slug, ':desc' => $description, ':icon' => $icon, ':id' => $id]);
                set_flash('success', 'Categoria atualizada com sucesso!');
            } else {
                $stmtIn = $db->prepare("
                    INSERT INTO categories (name, slug, description, icon) VALUES (:name, :slug, :desc, :icon)
                ");
                $stmtIn->execute([':name' => $name, ':slug' => $slug, ':desc' => $description, ':icon' => $icon]);
                set_flash('success', 'Nova categoria criada com sucesso!');
            }
        }
        header('Location: ' . url('admin/categories.php'));
        exit;
    }

    if ($action === 'delete') {
        $delId = (int)($_POST['category_id'] ?? 0);
        // Verificar se existem peças associadas
        $count = (int)$db->prepare("SELECT COUNT(*) FROM products WHERE category_id = :id")->execute([':id' => $delId]) ? $db->query("SELECT COUNT(*) FROM products WHERE category_id = {$delId}")->fetchColumn() : 0;

        if ($count > 0) {
            set_flash('danger', "Não é possível eliminar esta categoria porque existem {$count} peça(s) associadas.");
        } else {
            $db->prepare("DELETE FROM categories WHERE id = :id")->execute([':id' => $delId]);
            set_flash('success', 'Categoria eliminada com sucesso.');
        }
        header('Location: ' . url('admin/categories.php'));
        exit;
    }
}

$categories = get_all_categories();
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    
    <!-- Formulário de Criar / Editar Categoria -->
    <div class="lg:col-span-4">
        <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm sticky top-28">
            <h2 class="text-base font-bold text-gray-900 pb-3 border-b border-gray-100 mb-4">
                <?= $editCategory ? 'Editar Categoria' : 'Adicionar Nova Categoria' ?>
            </h2>

            <form action="<?= url('admin/categories.php') ?>" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="category_id" value="<?= $editCategory['id'] ?? 0 ?>">

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nome da Categoria *</label>
                    <input type="text" name="name" required value="<?= e($editCategory['name'] ?? '') ?>" placeholder="Ex: Sistema de Travões" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ícone FontAwesome</label>
                    <input type="text" name="icon" value="<?= e($editCategory['icon'] ?? 'fa-cogs') ?>" placeholder="Ex: fa-circle-stop, fa-filter, fa-car" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm font-mono text-xs">
                    <span class="text-[10px] text-gray-400">Classes FontAwesome como fa-cogs, fa-oil-can, fa-bolt</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Descrição</label>
                    <textarea name="description" rows="3" placeholder="Breve descrição da categoria..." class="w-full bg-gray-50 border border-gray-300 rounded-xl p-3 text-xs"><?= e($editCategory['description'] ?? '') ?></textarea>
                </div>

                <div class="flex items-center space-x-2 pt-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition shadow-md">
                        <?= $editCategory ? 'Guardar Alterações' : 'Criar Categoria' ?>
                    </button>
                    <?php if ($editCategory): ?>
                        <a href="<?= url('admin/categories.php') ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-3 rounded-xl text-xs transition">
                            Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Categorias Existentes -->
    <div class="lg:col-span-8">
        <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 text-base">Categorias de Peças</h3>
                    <p class="text-xs text-gray-500">Estrutura de organização do catálogo da loja.</p>
                </div>
                <span class="text-xs font-bold bg-gray-100 px-3 py-1 rounded-full text-gray-700"><?= count($categories) ?> Categorias</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs table-admin">
                    <thead>
                        <tr>
                            <th class="p-4 pl-6">Ícone</th>
                            <th class="p-4">Nome da Categoria</th>
                            <th class="p-4">Slug</th>
                            <th class="p-4 text-center">Peças</th>
                            <th class="p-4 pr-6 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        <?php foreach ($categories as $c): ?>
                            <tr>
                                <td class="p-4 pl-6">
                                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                                        <i class="fa-solid <?= e($c['icon'] ?: 'fa-cogs') ?>"></i>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="font-bold text-gray-900 text-sm block"><?= e($c['name']) ?></span>
                                    <span class="text-gray-400 text-[11px] line-clamp-1"><?= e($c['description']) ?></span>
                                </td>
                                <td class="p-4 font-mono text-[11px] text-gray-500">
                                    <?= e($c['slug']) ?>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="bg-gray-100 text-gray-800 font-bold px-2.5 py-1 rounded-full text-[10px]">
                                        <?= $c['products_count'] ?> un.
                                    </span>
                                </td>
                                <td class="p-4 pr-6 text-right space-x-2">
                                    <a href="<?= url('admin/categories.php?edit=' . $c['id']) ?>" class="bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white px-2.5 py-1 rounded-lg font-bold transition">
                                        Editar
                                    </a>
                                    <form action="<?= url('admin/categories.php') ?>" method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="category_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white px-2.5 py-1 rounded-lg font-bold transition confirm-action" data-confirm="Tem a certeza que deseja eliminar esta categoria?">
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

