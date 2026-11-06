<?php
$pageTitle = "Notícias & Dicas do Mundo Automóvel";
require_once __DIR__ . '/includes/header.php';

$db = Database::getConnection();

$category = trim($_GET['categoria'] ?? '');
$search = trim($_GET['q'] ?? '');

$where = ["is_published = 1"];
$params = [];

if ($category !== '') {
    $where[] = "category = :category";
    $params[':category'] = $category;
}

if ($search !== '') {
    $where[] = "(title LIKE :search OR excerpt LIKE :search OR content LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$whereClause = implode(" AND ", $where);
$stmt = $db->prepare("
    SELECT n.*, u.name AS author_name 
    FROM news n
    LEFT JOIN users u ON n.author_id = u.id
    WHERE {$whereClause}
    ORDER BY n.published_at DESC
");
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$articles = $stmt->fetchAll();

// Obter lista de categorias de notícias existentes
$categoriesStmt = $db->query("SELECT DISTINCT category FROM news WHERE is_published = 1 ORDER BY category ASC");
$newsCategories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="bg-gray-900 text-white py-12 border-b border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center sm:text-left">
        <span class="text-blue-400 font-bold uppercase text-xs tracking-wider">Blog & Conhecimento Mecânico</span>
        <h1 class="text-3xl sm:text-4xl font-black mt-2">Notícias e Dicas de Manutenção Automóvel</h1>
        <p class="text-gray-400 text-sm max-w-2xl mt-2">
            Mantenha o seu automóvel em excelente estado com guias práticos, conselhos de oficina e novidades sobre o mercado de peças.
        </p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <!-- Filtros e Barra de Pesquisa de Notícias -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        
        <!-- Categorias em Pílulas -->
        <div class="flex flex-wrap gap-2">
            <a href="<?= url('news.php') ?>" class="px-4 py-2 rounded-xl text-xs font-bold transition <?= empty($category) ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' ?>">
                Todas as Notícias
            </a>
            <?php foreach ($newsCategories as $cat): ?>
                <a href="<?= url('news.php?categoria=' . urlencode($cat)) ?>" class="px-4 py-2 rounded-xl text-xs font-bold transition <?= $category === $cat ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' ?>">
                    <?= e($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Pesquisa -->
        <form action="<?= url('news.php') ?>" method="GET" class="relative max-w-xs w-full">
            <?php if ($category): ?>
                <input type="hidden" name="categoria" value="<?= e($category) ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Pesquisar artigos..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-300 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-xs"></i>
        </form>

    </div>

    <!-- Grelha de Artigos -->
    <?php if (empty($articles)): ?>
        <div class="bg-white rounded-3xl border border-gray-200 p-12 text-center max-w-md mx-auto">
            <i class="fa-regular fa-newspaper text-4xl text-gray-400 mb-3 block"></i>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Nenhum artigo encontrado</h3>
            <p class="text-xs text-gray-500 mb-4">Não existem publicações que correspondam ao filtro selecionado.</p>
            <a href="<?= url('news.php') ?>" class="text-xs bg-blue-600 text-white px-4 py-2 rounded-xl font-bold inline-block">Ver todos os artigos</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($articles as $article): ?>
                <article class="bg-white rounded-3xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-lg transition duration-200 flex flex-col justify-between">
                    <div>
                        <a href="<?= url('news-detail.php?slug=' . urlencode($article['slug'])) ?>" class="block relative h-52 overflow-hidden bg-gray-100">
                            <img src="<?= e($article['image_url'] ?: 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=800&auto=format&fit=crop&q=80') ?>" alt="<?= e($article['title']) ?>" class="w-full h-full object-cover transform hover:scale-105 transition duration-300">
                            <span class="absolute top-4 left-4 bg-blue-600 text-white text-[11px] font-bold px-3 py-1 rounded-full shadow">
                                <?= e($article['category']) ?>
                            </span>
                        </a>

                        <div class="p-6">
                            <div class="flex items-center space-x-3 text-xs text-gray-400 mb-3">
                                <span><i class="fa-regular fa-calendar mr-1"></i> <?= format_date($article['published_at']) ?></span>
                                <span>&bull;</span>
                                <span><i class="fa-regular fa-eye mr-1"></i> <?= $article['views'] ?> visualizações</span>
                            </div>

                            <h2 class="font-bold text-gray-900 text-lg hover:text-blue-600 transition leading-snug mb-3">
                                <a href="<?= url('news-detail.php?slug=' . urlencode($article['slug'])) ?>">
                                    <?= e($article['title']) ?>
                                </a>
                            </h2>

                            <p class="text-xs text-gray-600 line-clamp-3 leading-relaxed mb-4">
                                <?= e($article['excerpt']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="px-6 pb-6 pt-0 border-t border-gray-100 mt-2 flex items-center justify-between">
                        <div class="flex items-center space-x-2 text-xs text-gray-500 pt-3">
                            <i class="fa-solid fa-user-pen text-blue-500"></i>
                            <span><?= e($article['author_name'] ?: 'Equipa Técnica AutoPeças') ?></span>
                        </div>
                        <a href="<?= url('news-detail.php?slug=' . urlencode($article['slug'])) ?>" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition pt-3 flex items-center">
                            Ler mais <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

