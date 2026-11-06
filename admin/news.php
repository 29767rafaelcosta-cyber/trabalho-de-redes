<?php
$pageTitle = "Gestão de Notícias & Blog";
require_once __DIR__ . '/../includes/admin_header.php';

// Processar Eliminação (POST com CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Token de segurança inválido.');
    } else {
        $delId = (int)($_POST['news_id'] ?? 0);
        $stmtDel = $db->prepare("DELETE FROM news WHERE id = :id");
        $stmtDel->execute([':id' => $delId]);
        set_flash('success', 'Artigo de notícia eliminado com sucesso.');
    }
    header('Location: ' . url('admin/news.php'));
    exit;
}

$stmt = $db->query("
    SELECT n.*, u.name AS author_name 
    FROM news n
    LEFT JOIN users u ON n.author_id = u.id
    ORDER BY n.id DESC
");
$allNews = $stmt->fetchAll();
?>

<div class="space-y-6">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Módulo de Notícias & Artigos Automóveis</h2>
            <p class="text-xs text-gray-500">Faça a publicação e gestão dos artigos informativos e dicas técnicas para clientes.</p>
        </div>

        <a href="<?= url('admin/news-form.php') ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition flex items-center shadow-md">
            <i class="fa-solid fa-plus mr-1.5"></i> Escrever Nova Notícia
        </a>
    </div>

    <!-- Tabela de Notícias -->
    <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-admin">
                <thead>
                    <tr>
                        <th class="p-4 pl-6">Capa</th>
                        <th class="p-4">Título do Artigo</th>
                        <th class="p-4">Categoria</th>
                        <th class="p-4">Autor</th>
                        <th class="p-4 text-center">Visualizações</th>
                        <th class="p-4 text-center">Estado</th>
                        <th class="p-4">Data Publicação</th>
                        <th class="p-4 pr-6 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    <?php if (empty($allNews)): ?>
                        <tr><td colspan="8" class="p-8 text-center text-gray-400">Nenhum artigo publicado de momento.</td></tr>
                    <?php else: ?>
                        <?php foreach ($allNews as $n): ?>
                            <tr>
                                <td class="p-4 pl-6">
                                    <div class="w-14 h-10 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center">
                                        <img src="<?= e($n['image_url'] ?: 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=100&auto=format&fit=crop&q=80') ?>" alt="<?= e($n['title']) ?>" class="w-full h-full object-cover">
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="font-bold text-gray-900 block max-w-sm line-clamp-1"><?= e($n['title']) ?></span>
                                    <span class="text-gray-400 text-[10px] font-mono">/<?= e($n['slug']) ?></span>
                                </td>
                                <td class="p-4">
                                    <span class="bg-blue-50 text-blue-700 font-bold px-2 py-0.5 rounded-full text-[10px]">
                                        <?= e($n['category']) ?>
                                    </span>
                                </td>
                                <td class="p-4 font-medium text-gray-600">
                                    <?= e($n['author_name'] ?: 'Administração') ?>
                                </td>
                                <td class="p-4 text-center font-bold text-gray-800">
                                    <i class="fa-regular fa-eye mr-1 text-gray-400"></i> <?= $n['views'] ?>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if ($n['is_published']): ?>
                                        <span class="text-green-600 font-bold text-[10px]"><i class="fa-solid fa-circle text-[8px] mr-1"></i> Publicado</span>
                                    <?php else: ?>
                                        <span class="text-yellow-600 font-bold text-[10px]"><i class="fa-solid fa-circle text-[8px] mr-1"></i> Rascunho</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-gray-500">
                                    <?= format_date($n['published_at']) ?>
                                </td>
                                <td class="p-4 pr-6 text-right space-x-2">
                                    <a href="<?= url('news-detail.php?slug=' . urlencode($n['slug'])) ?>" target="_blank" class="text-gray-400 hover:text-gray-700" title="Ver Artigo">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                    <a href="<?= url('admin/news-form.php?id=' . $n['id']) ?>" class="bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white px-2.5 py-1 rounded-lg font-bold transition">
                                        Editar
                                    </a>
                                    <form action="<?= url('admin/news.php') ?>" method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="news_id" value="<?= $n['id'] ?>">
                                        <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white px-2.5 py-1 rounded-lg font-bold transition confirm-action" data-confirm="Tem a certeza que deseja eliminar este artigo de notícia?">
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

