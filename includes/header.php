<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$currentUser = current_user();
$cartCount = get_cart_count();
$flashes = get_flashes();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | AutoPeças TURBO' : 'AutoPeças TURBO 🔥 As Melhores Peças ao Melhor Preço' ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Customizado com Cores Vivas & Tema Turbo -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="bg-gray-950 text-gray-100 flex flex-col min-h-screen antialiased selection:bg-orange-500 selection:text-white">

    <!-- BARRA DE OFERTAS & PAGAMENTOS (SUPER APELATIVA) -->
    <div class="bg-gradient-to-r from-orange-600 via-red-600 to-purple-700 text-white text-xs py-2 px-4 shadow-lg">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-2">
            <div class="flex items-center space-x-3 font-extrabold tracking-wide">
                <span><i class="fa-solid fa-bolt text-yellow-300 animate-bounce"></i> PROMOÇÕES ATÉ -40%</span>
                <span class="text-white/60">|</span>
                <span><i class="fa-solid fa-truck-fast text-green-300"></i> Portes Grátis &gt; 50€</span>
                <span class="hidden md:inline text-white/60">|</span>
                <span class="hidden md:inline"><i class="fa-solid fa-credit-card text-yellow-200"></i> Pague em 3x sem juros</span>
            </div>
            <div class="flex items-center space-x-3 text-xs font-bold">
                <span class="bg-black/30 px-2 py-0.5 rounded text-[11px]"><i class="fa-solid fa-mobile-screen mr-1 text-green-400"></i> MB WAY</span>
                <span class="bg-black/30 px-2 py-0.5 rounded text-[11px]"><i class="fa-solid fa-receipt mr-1 text-yellow-300"></i> Multibanco</span>
                <span class="bg-black/30 px-2 py-0.5 rounded text-[11px]"><i class="fa-solid fa-shield-halved mr-1 text-blue-300"></i> 100% Seguro</span>
            </div>
        </div>
    </div>

    <!-- Navegação Principal -->
    <header class="bg-gray-900/95 backdrop-blur-md border-b border-gray-800 sticky top-0 z-40 shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                
                <!-- Logótipo Vibrante -->
                <div class="flex items-center space-x-3">
                    <a href="<?= url('index.php') ?>" class="flex items-center space-x-3 group">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-orange-500 via-red-600 to-yellow-400 flex items-center justify-center text-white shadow-lg shadow-orange-600/40 transform group-hover:scale-105 group-hover:rotate-3 transition duration-300">
                            <i class="fa-solid fa-fire text-2xl text-yellow-200 animate-pulse"></i>
                        </div>
                        <div>
                            <span class="text-2xl font-black tracking-tight text-white flex items-center gap-1.5">
                                AUTO<span class="text-orange-500">PEÇAS</span>
                                <span class="bg-gradient-to-r from-orange-500 to-red-600 text-white text-[10px] uppercase font-black px-2 py-0.5 rounded-lg shadow-md tracking-wider">TURBO</span>
                            </span>
                            <span class="block text-[11px] text-orange-400/80 font-semibold tracking-wide">Acelera com as Melhores Peças 🏎️💨</span>
                        </div>
                    </a>
                </div>

                <!-- Barra de Pesquisa Rápida -->
                <div class="hidden md:flex flex-1 max-w-lg mx-6">
                    <form action="<?= url('products.php') ?>" method="GET" class="w-full relative">
                        <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Pesquise peça, marca de carro, ref. OEM ou travões..." class="w-full pl-11 pr-24 py-2.5 rounded-2xl border border-gray-700 bg-gray-800/80 text-white placeholder-gray-400 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition shadow-inner">
                        <div class="absolute left-4 top-3 text-orange-500">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <button type="submit" class="absolute right-1.5 top-1.5 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white px-4 py-1.5 rounded-xl text-xs font-black shadow-md shadow-orange-600/30 transition">
                            Pesquisar
                        </button>
                    </form>
                </div>

                <!-- Links Rápidos, Carrinho & Perfil -->
                <div class="flex items-center space-x-4 sm:space-x-5">
                    
                    <nav class="hidden lg:flex items-center space-x-6 text-xs font-black uppercase tracking-wider text-gray-300">
                        <a href="<?= url('products.php') ?>" class="hover:text-orange-400 transition flex items-center gap-1.5">
                            <i class="fa-solid fa-boxes-stacked text-orange-500"></i> Catálogo
                        </a>
                        <a href="<?= url('news.php') ?>" class="hover:text-orange-400 transition flex items-center gap-1.5">
                            <i class="fa-solid fa-newspaper text-red-500"></i> Notícias & Dicas
                        </a>
                    </nav>

                    <!-- Carrinho de Compras Chamativo -->
                    <a href="<?= url('cart.php') ?>" class="relative p-2.5 bg-gray-800 hover:bg-gray-700 text-white transition rounded-2xl border border-gray-700 flex items-center shadow-lg group">
                        <i class="fa-solid fa-cart-shopping text-lg text-orange-400 group-hover:scale-110 transition"></i>
                        <?php if ($cartCount > 0): ?>
                            <span class="absolute -top-1.5 -right-1.5 bg-gradient-to-r from-red-600 to-orange-500 text-white text-[11px] font-black rounded-full h-5 w-5 flex items-center justify-center shadow-lg shadow-red-600/50 animate-bounce">
                                <?= $cartCount ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <!-- Utilizador Autenticado ou Botão Login -->
                    <?php if ($currentUser): ?>
                        <div class="relative group">
                            <button class="flex items-center space-x-2 py-1.5 px-3 rounded-2xl bg-gray-800 hover:bg-gray-700 border border-gray-700 transition focus:outline-none">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-orange-500 to-red-600 text-white font-black flex items-center justify-center text-xs shadow-md">
                                    <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                                </div>
                                <div class="hidden xl:block text-left">
                                    <div class="text-xs font-bold text-white leading-tight truncate max-w-[110px]"><?= e($currentUser['name']) ?></div>
                                    <div class="text-[10px] font-black uppercase text-orange-400"><?= $currentUser['role'] === 'admin' ? '⚙️ Admin' : '⭐ Cliente' ?></div>
                                </div>
                                <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                            </button>

                            <!-- Dropdown -->
                            <div class="absolute right-0 mt-2 w-56 bg-gray-900 rounded-2xl shadow-2xl border border-gray-800 py-2 hidden group-hover:block z-50">
                                <div class="px-4 py-2 border-b border-gray-800 text-xs text-gray-400">
                                    Conectado como <strong class="text-white block truncate"><?= e($currentUser['email']) ?></strong>
                                </div>

                                <?php if ($currentUser['role'] === 'admin'): ?>
                                    <a href="<?= url('admin/index.php') ?>" class="flex items-center px-4 py-2.5 text-xs text-red-400 hover:bg-gray-800 font-bold transition">
                                        <i class="fa-solid fa-gauge-high mr-2 text-red-500"></i> Painel de Administração
                                    </a>
                                    <div class="border-t border-gray-800 my-1"></div>
                                <?php endif; ?>

                                <a href="<?= url('profile.php') ?>" class="flex items-center px-4 py-2 text-xs text-gray-300 hover:bg-gray-800 transition">
                                    <i class="fa-solid fa-user-circle mr-2 text-orange-400"></i> A Minha Conta
                                </a>
                                <a href="<?= url('profile.php#orders') ?>" class="flex items-center px-4 py-2 text-xs text-gray-300 hover:bg-gray-800 transition">
                                    <i class="fa-solid fa-box-open mr-2 text-orange-400"></i> As Minhas Encomendas
                                </a>

                                <div class="border-t border-gray-800 my-1"></div>
                                <a href="<?= url('logout.php') ?>" class="flex items-center px-4 py-2 text-xs text-red-400 hover:bg-gray-800 transition font-bold">
                                    <i class="fa-solid fa-arrow-right-from-bracket mr-2"></i> Terminar Sessão
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center space-x-2">
                            <a href="<?= url('login.php') ?>" class="text-xs font-bold text-gray-300 hover:text-white px-3 py-2 rounded-xl transition">
                                Entrar
                            </a>
                            <a href="<?= url('register.php') ?>" class="btn-turbo text-xs px-4 py-2 rounded-xl transition font-black">
                                Criar Conta 🔥
                            </a>
                        </div>
                    <?php endif; ?>

                    <button id="mobile-menu-btn" class="md:hidden text-gray-300 hover:text-white p-2">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                </div>

            </div>
        </div>

        <!-- Menu Mobile -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-gray-800 bg-gray-900 px-4 pt-3 pb-5 space-y-3">
            <form action="<?= url('products.php') ?>" method="GET" class="relative">
                <input type="text" name="q" placeholder="Pesquisar peças..." class="w-full pl-9 pr-3 py-2 rounded-xl bg-gray-800 border border-gray-700 text-xs text-white">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-orange-500 text-xs"></i>
            </form>
            <div class="flex flex-col space-y-2 text-xs font-bold pt-2">
                <a href="<?= url('index.php') ?>" class="px-3 py-2 text-gray-300 hover:bg-gray-800 rounded-xl">Página Inicial</a>
                <a href="<?= url('products.php') ?>" class="px-3 py-2 text-gray-300 hover:bg-gray-800 rounded-xl">Catálogo de Peças</a>
                <a href="<?= url('news.php') ?>" class="px-3 py-2 text-gray-300 hover:bg-gray-800 rounded-xl">Notícias & Dicas</a>
                <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
                    <a href="<?= url('admin/index.php') ?>" class="px-3 py-2 text-red-400 bg-red-950/40 rounded-xl">⚙️ Painel de Administração</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Exibição de Mensagens Flash -->
    <?php if (!empty($flashes)): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 space-y-2">
            <?php foreach ($flashes as $flash): 
                $alertBg = 'bg-blue-900/60 border-blue-500 text-blue-200';
                $icon = 'fa-info-circle text-blue-400';
                if ($flash['type'] === 'success') {
                    $alertBg = 'bg-green-900/60 border-green-500 text-green-200';
                    $icon = 'fa-check-circle text-green-400';
                } elseif ($flash['type'] === 'danger' || $flash['type'] === 'error') {
                    $alertBg = 'bg-red-900/60 border-red-500 text-red-200';
                    $icon = 'fa-triangle-exclamation text-red-400';
                } elseif ($flash['type'] === 'warning') {
                    $alertBg = 'bg-yellow-900/60 border-yellow-500 text-yellow-200';
                    $icon = 'fa-exclamation-circle text-yellow-400';
                }
            ?>
                <div class="flash-alert flex items-center justify-between p-4 rounded-2xl border <?= $alertBg ?> shadow-xl backdrop-blur-md">
                    <div class="flex items-center space-x-3 text-xs font-bold">
                        <i class="fa-solid <?= $icon ?> text-base"></i>
                        <span><?= e($flash['message']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-white">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <main class="flex-grow">
