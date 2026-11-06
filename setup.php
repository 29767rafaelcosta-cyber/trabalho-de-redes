<?php
/**
 * Assistente de Instalação e Teste de Ambiente
 * AutoPeças Pro - Projeto Académico
 */

require_once __DIR__ . '/config/config.php';

$step = $_GET['step'] ?? 'welcome';
$error = null;
$success = null;

$dbHost = $_POST['host'] ?? 'localhost';
$dbPort = $_POST['port'] ?? '3306';
$dbUser = $_POST['user'] ?? 'root';
$dbPass = $_POST['pass'] ?? '';
$dbName = $_POST['dbname'] ?? 'autoparts_db';

// Verificação de Extensões do PHP
$phpVersion = phpversion();
$hasPdo = extension_loaded('pdo');
$hasPdoMysql = extension_loaded('pdo_mysql');
$hasMbstring = extension_loaded('mbstring');

// Processar Ação de Instalação (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    try {
        // 1. Tentar conectar ao servidor MySQL sem base de dados
        $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);

        // 2. Criar Base de Dados se não existir
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");

        // 3. Ler ficheiro schema.sql
        $schemaPath = __DIR__ . '/database/schema.sql';
        if (!file_exists($schemaPath)) {
            throw new Exception("O ficheiro 'database/schema.sql' não foi encontrado no caminho especificado.");
        }

        $sqlContent = file_get_contents($schemaPath);
        if (empty($sqlContent)) {
            throw new Exception("O ficheiro 'database/schema.sql' está vazio.");
        }

        // 4. Executar script SQL
        $pdo->exec($sqlContent);

        // 5. Verificar tabelas criadas
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $totalTables = count($tables);

        // 6. Verificar contagem de produtos e utilizador admin
        $productsCount = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $adminExists = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE email = 'admin@autoparts.pt'")->fetchColumn();

        $success = "Base de dados <strong>'{$dbName}'</strong> instalada com sucesso! Foram criadas <strong>{$totalTables}</strong> tabelas, <strong>{$productsCount}</strong> peças de automóvel e o utilizador Administrador pronto para login.";
        $step = 'finished';

    } catch (PDOException $e) {
        $error = "Erro ao conectar ao MySQL: " . $e->getMessage() . "<br><span class='text-xs text-gray-400'>Dica: Certifique-se de que o MySQL está em execução no XAMPP ou servidor local.</span>";
    } catch (Exception $e) {
        $error = "Erro durante a instalação: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador & Setup da Base de Dados - AutoPeças Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: #f8fafc; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6">

    <div class="max-w-2xl w-full bg-gray-800 rounded-3xl border border-gray-700 shadow-2xl p-6 sm:p-10 space-y-6">
        
        <!-- Cabeçalho -->
        <div class="flex items-center space-x-4 border-b border-gray-700 pb-6">
            <div class="w-14 h-14 rounded-2xl bg-blue-600 flex items-center justify-center text-white text-2xl shadow-lg">
                <i class="fa-solid fa-database"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white">Assistente de Instalação & Setup</h1>
                <p class="text-xs text-gray-400">AutoPeças Pro &bull; Loja Online de Peças de Carros & Notícias</p>
            </div>
        </div>

        <!-- Alertas de Erro / Sucesso -->
        <?php if ($error): ?>
            <div class="bg-red-900 bg-opacity-50 border border-red-500 rounded-2xl p-4 text-red-200 text-xs">
                <div class="font-bold mb-1 flex items-center"><i class="fa-solid fa-triangle-exclamation mr-2"></i> Falha na Ligação</div>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-900 bg-opacity-50 border border-green-500 rounded-2xl p-5 text-green-200 text-sm">
                <div class="font-bold text-base mb-2 flex items-center"><i class="fa-solid fa-circle-check mr-2 text-green-400"></i> Sucesso Total!</div>
                <?= $success ?>
            </div>
        <?php endif; ?>

        <!-- Verificação de Requisitos do Sistema -->
        <div class="bg-gray-900 rounded-2xl p-5 border border-gray-700 space-y-3">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center">
                <i class="fa-solid fa-server mr-2 text-blue-400"></i> Diagnóstico do Ambiente PHP
            </h3>
            
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div class="bg-gray-800 p-2.5 rounded-xl border border-gray-700 text-center">
                    <span class="block text-gray-400 text-[10px]">PHP Version</span>
                    <span class="font-bold <?= version_compare($phpVersion, '7.4.0', '>=') ? 'text-green-400' : 'text-red-400' ?>">
                        <?= $phpVersion ?>
                    </span>
                </div>

                <div class="bg-gray-800 p-2.5 rounded-xl border border-gray-700 text-center">
                    <span class="block text-gray-400 text-[10px]">Extensão PDO</span>
                    <span class="font-bold <?= $hasPdo ? 'text-green-400' : 'text-red-400' ?>">
                        <?= $hasPdo ? 'Ativa' : 'Em Falta' ?>
                    </span>
                </div>

                <div class="bg-gray-800 p-2.5 rounded-xl border border-gray-700 text-center">
                    <span class="block text-gray-400 text-[10px]">PDO MySQL</span>
                    <span class="font-bold <?= $hasPdoMysql ? 'text-green-400' : 'text-red-400' ?>">
                        <?= $hasPdoMysql ? 'Ativa' : 'Em Falta' ?>
                    </span>
                </div>

                <div class="bg-gray-800 p-2.5 rounded-xl border border-gray-700 text-center">
                    <span class="block text-gray-400 text-[10px]">Mbstring</span>
                    <span class="font-bold <?= $hasMbstring ? 'text-green-400' : 'text-red-400' ?>">
                        <?= $hasMbstring ? 'Ativa' : 'Em Falta' ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if ($step === 'finished'): ?>
            <!-- Tela de Conclusão com Credenciais -->
            <div class="space-y-4">
                <div class="bg-gray-900 p-5 rounded-2xl border border-gray-700 text-xs text-gray-300 space-y-3">
                    <h4 class="font-bold text-white text-sm">Credenciais Criadas para Teste:</h4>
                    <div class="p-3 bg-gray-800 rounded-xl font-mono text-xs space-y-1">
                        <p><strong class="text-red-400">Administrador:</strong> admin@autoparts.pt | Senha: <strong>admin123</strong></p>
                        <p><strong class="text-blue-400">Cliente Teste:</strong> cliente@autoparts.pt | Senha: <strong>cliente123</strong></p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <a href="<?= url('index.php') ?>" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-bold py-3.5 px-6 rounded-xl text-center text-xs transition shadow-lg">
                        <i class="fa-solid fa-store mr-2"></i> Abrir Loja Online
                    </a>
                    <a href="<?= url('login.php') ?>" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white font-bold py-3.5 px-6 rounded-xl text-center text-xs transition">
                        <i class="fa-solid fa-lock mr-2"></i> Ir para Login Admin
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Formulário de Instalação Automática -->
            <form action="<?= url('setup.php') ?>" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="install">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-300 uppercase mb-1">Host MySQL</label>
                        <input type="text" name="host" value="<?= e($dbHost) ?>" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-xs text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-300 uppercase mb-1">Porta MySQL</label>
                        <input type="text" name="port" value="<?= e($dbPort) ?>" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-xs text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-300 uppercase mb-1">Utilizador MySQL</label>
                        <input type="text" name="user" value="<?= e($dbUser) ?>" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-xs text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-300 uppercase mb-1">Palavra-passe MySQL</label>
                        <input type="password" name="pass" value="<?= e($dbPass) ?>" placeholder="Em branco no XAMPP" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-xs text-white">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-300 uppercase mb-1">Nome da Base de Dados</label>
                        <input type="text" name="dbname" value="<?= e($dbName) ?>" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-xs text-white font-mono">
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-4 px-6 rounded-2xl text-sm transition shadow-xl shadow-blue-600/30 flex items-center justify-center space-x-2">
                        <i class="fa-solid fa-bolt"></i>
                        <span>Instalar Base de Dados com 1-Clique (schema.sql)</span>
                    </button>
                    <p class="text-[11px] text-gray-400 text-center mt-2">
                        Isto criará a base de dados, as tabelas com chaves estrangeiras e inserirá todas as peças, marcas, notícias e contas de teste.
                    </p>
                </div>
            </form>
        <?php endif; ?>

    </div>

</body>
</html>

