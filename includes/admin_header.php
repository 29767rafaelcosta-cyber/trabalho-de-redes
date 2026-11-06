<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Obrigar autenticação de Administrador
require_admin();

$currentUser = current_user();
$flashes = get_flashes();

// Contagem rápida de encomendas pendentes para o badge
$db = Database::getConnection();
$pendingOrdersCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | Painel de Administração' : 'Painel de Administração - ' . APP_NAME ?></title>
    
    <!-- Google Fonts & Tailwind CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="bg-gray-100 flex min-h-screen text-gray-800 antialiased">

    <!-- Barra Lateral (Sidebar) -->
    <aside class="w-64 bg-gray-900 text-gray-300 flex-shrink-0 flex flex-col justify-between hidden md:flex border-r border-gray-800">
        <div>
            <!-- Cabeçalho da Sidebar -->
            <div class="h-20 flex items-center px-6 border-b border-gray-800">
                <a href="<?= url('admin/index.php') ?>" class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-red-600 flex items-center justify-center text-white shadow-md">
                        <i class="fa-solid fa-gauge-high text-xl"></i>
                    </div>
                    <div>
                        <span class="text-lg font-black text-white tracking-tight">Auto<span class="text-blue-400">Peças</span></span>
                        <span class="block text-[11px] text-red-400 uppercase font-bold tracking-wider">Backoffice Pro</span>
                    </div>
                </a>
            </div>

            <!-- Links de Navegação -->
            <nav class="p-4 space-y-1.5 text-sm font-medium">
                <a href="<?= url('admin/index.php') ?>" class="flex items-center px-4 py-3 rounded-xl hover:bg-gray-800 hover:text-white transition <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-600 text-white font-semibold' : '' ?>">
                    <i class="fa-solid fa-chart-line w-6 text-center mr-3"></i> Dashboard
                </a>

                <div class="pt-4 pb-1 px-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Catálogo & Loja</div>
                
                <a href="<?= url('admin/products.php') ?>" class="flex items-center px-4 py-3 rounded-xl hover:bg-gray-800 hover:text-white transition <?= in_array(basename($_SERVER['PHP_SELF']), ['products.php', 'product-form.php']) ? 'bg-blue-600 text-white font-semibold' : '' ?>">
                    <i class="fa-solid fa-boxes-stacked w-6 text-center mr-3"></i> Peças / Produtos
                </a>

                <a href="<?= url('admin/categories.php') ?>" class="flex items-center px-4 py-3 rounded-xl hover:bg-gray-800 hover:text-white transition <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'bg-blue-600 text-white font-semibold' : '' ?>">
                    <i class="fa-solid fa-tags w-6 text-center mr-3"></i> Categorias
                </a>

                <a href="<?= url('admin/brands.php') ?>" class="flex items-center px-4 py-3 rounded-xl hover:bg-gray-800 hover:text-white transition <?= basename($_SERVER['PHP_SELF']) == 'brands.php' ? 'bg-blue-600 text-white font-semibold' : '' ?>">
                    <i class="fa-solid fa-car w-6 text-center mr-3"></i> Marcas de Carros
                </a>

                <a href="<?= url('admin/orders.php') ?>" class="flex items-center justify-between px-4 py-3 rounded-xl hover:bg-gray-800 hover:text-white transition <?= in_array(basename($_SERVER['PHP_SELF']), ['orders.php', 'order-detail.php']) ? 'bg-blue-600 text-white font-semibold' : '' ?>">
                    <div class="flex items-center">
                        <i class="fa-solid fa-cart-flatbed w-6 text-center mr-3"></i> Encomendas
                    </div>
                    <?php if ($pendingOrdersCount > 0): ?>
                        <span class="bg-yellow-500 text-gray-900 text-xs font-bold px-2 py-0.5 rounded-full"><?= $pendingOrdersCount ?></span>
                    <?php endif; ?>
                </a>

                <div class="pt-4 pb-1 px-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Conteúdo & Notícias</div>

                <a href="<?= url('admin/news.php') ?>" class="flex items-center px-4 py-3 rounded-xl hover:bg-gray-800 hover:text-white transition <?= in_array(basename($_SERVER['PHP_SELF']), ['news.php', 'news-form.php']) ? 'bg-blue-600 text-white font-semibold' : '' ?>">
                    <i class="fa-solid fa-newspaper w-6 text-center mr-3"></i> Notícias & Dicas
                </a>

                <div class="pt-4 pb-1 px-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Sistema</div>

                <a href="<?= url('admin/users.php') ?>" class="flex items-center px-4 py-3 rounded-xl hover:bg-gray-800 hover:text-white transition <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'bg-blue-600 text-white font-semibold' : '' ?>">
                    <i class="fa-solid fa-users-gear w-6 text-center mr-3"></i> Utilizadores
                </a>
            </nav>
        </div>

        <!-- Rodapé da Sidebar -->
        <div class="p-4 border-t border-gray-800 space-y-2">
            <a href="<?= url('index.php') ?>" class="flex items-center px-4 py-2.5 text-xs text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Ver Loja Pública
            </a>
            <a href="<?= url('logout.php') ?>" class="flex items-center px-4 py-2.5 text-xs text-red-400 hover:text-red-300 hover:bg-gray-800 rounded-xl transition">
                <i class="fa-solid fa-arrow-right-from-bracket mr-2"></i> Terminar Sessão
            </a>
        </div>
    </aside>

    <!-- Área de Trabalho Principal -->
    <div class="flex-1 flex flex-col min-w-0">
        
        <!-- Barra de Cabeçalho Superior Admin -->
        <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-6 sticky top-0 z-30 shadow-sm">
            <div class="flex items-center space-x-4">
                <a href="<?= url('index.php') ?>" class="md:hidden text-gray-700">
                    <i class="fa-solid fa-bars text-xl"></i>
                </a>
                <h1 class="text-xl font-bold text-gray-800"><?= isset($pageTitle) ? e($pageTitle) : 'Administração' ?></h1>
            </div>

            <div class="flex items-center space-x-4">
                <a href="<?= url('index.php') ?>" target="_blank" class="hidden sm:inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition">
                    <i class="fa-solid fa-external-link mr-1.5"></i> Loja em Nova Janela
                </a>

                <div class="flex items-center space-x-3 pl-3 border-l border-gray-200">
                    <div class="w-9 h-9 rounded-full bg-red-100 text-red-700 font-bold flex items-center justify-center text-sm border border-red-200">
                        <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                    </div>
                    <div class="hidden sm:block text-left">
                        <div class="text-xs font-bold text-gray-800 leading-tight"><?= e($currentUser['name']) ?></div>
                        <div class="text-[10px] uppercase font-bold text-red-600">Admin</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mensagens Flash no Backoffice -->
        <?php if (!empty($flashes)): ?>
            <div class="p-6 pb-0 space-y-2">
                <?php foreach ($flashes as $flash): 
                    $alertColor = 'bg-blue-50 text-blue-800 border-blue-200';
                    $icon = 'fa-info-circle text-blue-500';
                    if ($flash['type'] === 'success') {
                        $alertColor = 'bg-green-50 text-green-800 border-green-200';
                        $icon = 'fa-check-circle text-green-500';
                    } elseif ($flash['type'] === 'danger' || $flash['type'] === 'error') {
                        $alertColor = 'bg-red-50 text-red-800 border-red-200';
                        $icon = 'fa-triangle-exclamation text-red-500';
                    } elseif ($flash['type'] === 'warning') {
                        $alertColor = 'bg-yellow-50 text-yellow-800 border-yellow-200';
                        $icon = 'fa-exclamation-circle text-yellow-500';
                    }
                ?>
                    <div class="flash-alert flex items-center justify-between p-4 rounded-xl border <?= $alertColor ?> shadow-sm">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid <?= $icon ?> text-lg"></i>
                            <span class="text-sm font-medium"><?= e($flash['message']) ?></span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Conteúdo do Painel -->
        <main class="p-6 flex-1">

