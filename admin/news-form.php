<?php
require_once __DIR__ . '/../includes/admin_header.php';

$newsId = (int)($_GET['id'] ?? 0);
$isEditing = $newsId > 0;

$article = [
    'id'           => 0,
    'title'        => '',
    'slug'         => '',
    'category'     => 'Dicas de Manutenção',
    'excerpt'      => '',
    'content'      => '',
    'image_url'    => '',
    'is_published' => 1
];

if ($isEditing) {
    $stmt = $db->prepare("SELECT * FROM news WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $newsId]);
    $fetched = $stmt->fetch();
    if ($fetched) {
        $article = $fetched;
    } else {
        set_flash('danger', 'Artigo de notícia não encontrado.');
        header('Location: ' . url('admin/news.php'));
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

    $article['title']        = trim($_POST['title'] ?? '');
    $article['category']     = trim($_POST['category'] ?? 'Dicas de Manutenção');
    $article['excerpt']      = trim($_POST['excerpt'] ?? '');
    $article['content']      = trim($_POST['content'] ?? '');
    $article['image_url']    = trim($_POST['image_url'] ?? '');
    $article['is_published'] = !empty($_POST['is_published']) ? 1 : 0;

    if (empty($article['title'])) $errors[] = 'O título do artigo é obrigatório.';
    if (empty($article['excerpt'])) $errors[] = 'O resumo / introdução é obrigatório.';
    if (empty($article['content'])) $errors[] = 'O conteúdo do artigo não pode estar vazio.';

    if (empty($errors)) {
        // Gerar slug único
        $baseSlug = slugify($article['title']);
        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $stmtSlug = $db->prepare("SELECT id FROM news WHERE slug = :slug AND id != :id LIMIT 1");
            $stmtSlug->execute([':slug' => $slug, ':id' => $newsId]);
            if (!$stmtSlug->fetch()) {
                break;
            }
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        if ($isEditing) {
            $stmtUpdate = $db->prepare("
                UPDATE news SET
                    title = :title,
                    slug = :slug,
                    category = :category,
                    excerpt = :excerpt,
                    content = :content,
                    image_url = :image_url,
                    is_published = :is_published
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                ':title'        => $article['title'],
                ':slug'         => $slug,
                ':category'     => $article['category'],
                ':excerpt'      => $article['excerpt'],
                ':content'      => $article['content'],
                ':image_url'    => $article['image_url'] ?: null,
                ':is_published' => $article['is_published'],
                ':id'           => $newsId
            ]);

            set_flash('success', 'Artigo de notícia atualizado com sucesso!');
        } else {
            $stmtInsert = $db->prepare("
                INSERT INTO news (
                    title, slug, category, author_id, excerpt, content,
                    image_url, is_published, published_at, created_at
                ) VALUES (
                    :title, :slug, :category, :author_id, :excerpt, :content,
                    :image_url, :is_published, NOW(), NOW()
                )
            ");
            $stmtInsert->execute([
                ':title'        => $article['title'],
                ':slug'         => $slug,
                ':category'     => $article['category'],
                ':author_id'    => $currentUser['id'],
                ':excerpt'      => $article['excerpt'],
                ':content'      => $article['content'],
                ':image_url'    => $article['image_url'] ?: null,
                ':is_published' => $article['is_published']
            ]);

            set_flash('success', 'Artigo publicado com sucesso no módulo de notícias!');
        }

        header('Location: ' . url('admin/news.php'));
        exit;
    }
}

$pageTitle = $isEditing ? "Editar Notícia: " . $article['title'] : "Escrever Nova Notícia";
?>

<div class="max-w-4xl mx-auto space-y-6">
    
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900"><?= $isEditing ? 'Editar Notícia' : 'Escrever Novo Artigo' ?></h2>
            <p class="text-xs text-gray-500">Módulo de Gestão de Conteúdo e Dicas da Oficina.</p>
        </div>
        <a href="<?= url('admin/news.php') ?>" class="text-xs font-bold text-gray-600 hover:text-gray-900 bg-white border border-gray-300 px-3 py-2 rounded-xl transition">
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

        <div class="space-y-4">
            
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Título do Artigo *</label>
                <input type="text" name="title" required value="<?= e($article['title']) ?>" placeholder="Ex: Como identificar quando mudar as pastilhas de travão" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 font-bold">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Categoria do Artigo *</label>
                    <select name="category" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="Dicas de Manutenção" <?= $article['category'] === 'Dicas de Manutenção' ? 'selected' : '' ?>>Dicas de Manutenção</option>
                        <option value="Guias Técnicos" <?= $article['category'] === 'Guias Técnicos' ? 'selected' : '' ?>>Guias Técnicos</option>
                        <option value="Novidades do Setor" <?= $article['category'] === 'Novidades do Setor' ? 'selected' : '' ?>>Novidades do Setor</option>
                        <option value="Notícias da Loja" <?= $article['category'] === 'Notícias da Loja' ? 'selected' : '' ?>>Notícias da Loja</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">URL da Imagem de Capa</label>
                    <input type="text" name="image_url" value="<?= e($article['image_url']) ?>" placeholder="https://images.unsplash.com/..." class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Resumo / Lead do Artigo *</label>
                <textarea name="excerpt" rows="2" required placeholder="Pequena introdução que aparece nos cartões e na listagem..." class="w-full bg-gray-50 border border-gray-300 rounded-xl p-3 text-xs"><?= e($article['excerpt']) ?></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Conteúdo Completo do Artigo (Suporta HTML: &lt;p&gt;, &lt;h3&gt;, &lt;strong&gt;) *</label>
                <textarea name="content" rows="12" required placeholder="Escreva o texto completo do artigo..." class="w-full bg-gray-50 border border-gray-300 rounded-xl p-4 text-xs font-mono leading-relaxed"><?= e($article['content']) ?></textarea>
            </div>

            <div>
                <label class="flex items-center text-xs font-bold text-gray-700 cursor-pointer pt-2">
                    <input type="checkbox" name="is_published" value="1" <?= $article['is_published'] ? 'checked' : '' ?> class="h-4 w-4 text-blue-600 rounded mr-2">
                    Publicar Imediatamente (Se desmarcado, fica guardado como Rascunho)
                </label>
            </div>

        </div>

        <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
            <a href="<?= url('admin/news.php') ?>" class="px-5 py-2.5 rounded-xl text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
                Cancelar
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl text-xs transition shadow-md">
                <?= $isEditing ? 'Guardar Notícia' : 'Publicar Notícia' ?>
            </button>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

