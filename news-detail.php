<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = Database::getConnection();
$slug = trim($_GET['slug'] ?? '');

if (empty($slug)) {
    header('Location: ' . url('news.php'));
    exit;
}

$stmt = $db->prepare("
    SELECT n.*, u.name AS author_name, u.email AS author_email 
    FROM news n
    LEFT JOIN users u ON n.author_id = u.id
    WHERE n.slug = :slug AND n.is_published = 1
    LIMIT 1
");
$stmt->execute([':slug' => $slug]);
$article = $stmt->fetch();

if (!$article) {
    set_flash('danger', 'O artigo solicitado não foi encontrado.');
    header('Location: ' . url('news.php'));
    exit;
}

// Incrementar contador de visualizações
$db->prepare("UPDATE news SET views = views + 1 WHERE id = :id")->execute([':id' => $article['id']]);
$article['views']++;

$pageTitle = $article['title'];
require_once __DIR__ . '/includes/header.php';

// Obter peças recomendadas em destaque para o rodapé do artigo
$recommendedParts = get_featured_products(4);
?>

<div class="bg-gray-100 py-4 border-b border-gray-200">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <nav class="text-xs text-gray-500 flex items-center space-x-2 flex-wrap">
            <a href="<?= url('index.php') ?>" class="hover:text-blue-600">Início</a>
            <span>/</span>
            <a href="<?= url('news.php') ?>" class="hover:text-blue-600">Notícias & Dicas</a>
            <span>/</span>
            <span class="text-gray-900 font-medium truncate max-w-xs"><?= e($article['title']) ?></span>
        </nav>
    </div>
</div>

<article class="max-w-4xl mx-auto px-4 sm:px-6 py-10">
    
    <!-- Cabeçalho do Artigo -->
    <header class="mb-8">
        <div class="flex items-center space-x-3 mb-4">
            <span class="bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                <?= e($article['category']) ?>
            </span>
            <span class="text-xs text-gray-400">
                <i class="fa-regular fa-calendar mr-1"></i> Publicado em <?= format_date($article['published_at'], true) ?>
            </span>
            <span class="text-xs text-gray-400">&bull;</span>
            <span class="text-xs text-gray-400">
                <i class="fa-regular fa-eye mr-1"></i> <?= $article['views'] ?> visualizações
            </span>
        </div>

        <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 leading-tight mb-4">
            <?= e($article['title']) ?>
        </h1>

        <p class="text-base text-gray-600 leading-relaxed font-medium italic border-l-4 border-blue-600 pl-4 py-1">
            <?= e($article['excerpt']) ?>
        </p>
    </header>

    <!-- Imagem de Destaque -->
    <?php if (!empty($article['image_url'])): ?>
        <div class="rounded-3xl overflow-hidden shadow-lg mb-8 h-96 bg-gray-100">
            <img src="<?= e($article['image_url']) ?>" alt="<?= e($article['title']) ?>" class="w-full h-full object-cover">
        </div>
    <?php endif; ?>

    <!-- Conteúdo do Artigo -->
    <div class="bg-white rounded-3xl border border-gray-200 p-8 sm:p-12 shadow-sm mb-12">
        <div class="prose max-w-none text-gray-700 leading-relaxed text-base space-y-4">
            <?= $article['content'] // Permite formatação rica de parágrafos e títulos inseridos no backoffice ?>
        </div>

        <!-- Assinatura do Autor -->
        <div class="mt-10 pt-6 border-t border-gray-100 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-sm">
                    <i class="fa-solid fa-wrench"></i>
                </div>
                <div>
                    <span class="block text-xs text-gray-400">Autor do Artigo:</span>
                    <span class="text-sm font-bold text-gray-900"><?= e($article['author_name'] ?: 'Departamento Técnico AutoPeças Pro') ?></span>
                </div>
            </div>

            <a href="<?= url('news.php') ?>" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition flex items-center">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Voltar às Notícias
            </a>
        </div>
    </div>

    <!-- Peças Recomendadas da Loja para este tipo de intervenção -->
    <div class="mt-12 bg-gray-50 rounded-3xl border border-gray-200 p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <span class="text-xs font-bold uppercase text-blue-600 tracking-wider">Loja Online</span>
                <h3 class="text-xl font-bold text-gray-900">Precisa de Peças para esta Manutenção?</h3>
            </div>
            <a href="<?= url('products.php') ?>" class="text-xs font-bold text-blue-600 hover:underline">Ver todas &rarr;</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($recommendedParts as $rp): ?>
                <div class="bg-white p-4 rounded-2xl border border-gray-200 hover:shadow-md transition flex flex-col justify-between">
                    <div class="h-28 bg-gray-50 rounded-xl flex items-center justify-center mb-2 p-2">
                        <img src="<?= e($rp['image_url'] ?: 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=200&auto=format&fit=crop&q=80') ?>" alt="<?= e($rp['name']) ?>" class="max-h-full object-contain">
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 line-clamp-2 mb-1"><?= e($rp['name']) ?></h4>
                        <span class="text-xs font-black text-blue-600"><?= format_price((float)$rp['price']) ?></span>
                    </div>
                    <a href="<?= url('product-detail.php?slug=' . urlencode($rp['slug'])) ?>" class="mt-3 block text-center bg-gray-100 hover:bg-blue-600 hover:text-white text-[11px] font-bold py-1.5 rounded-lg transition">
                        Ver Peça
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</article>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

