<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$currentUser = current_user();
$db = Database::getConnection();

// Buscar dados completos e atualizados do utilizador
$stmtUser = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmtUser->execute([':id' => $currentUser['id']]);
$user = $stmtUser->fetch();

if (!$user) {
    logout_user();
    header('Location: ' . url('login.php'));
    exit;
}

$errors = [];

// Atualização de Perfil (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Token de segurança inválido.';
    }

    $action = $_POST['action'] ?? '';

    // Atualizar dados gerais
    if ($action === 'update_profile' && empty($errors)) {
        $name        = trim($_POST['name'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $nif         = trim($_POST['nif'] ?? '');
        $address     = trim($_POST['address'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $city        = trim($_POST['city'] ?? '');

        if (empty($name)) {
            $errors[] = 'O nome não pode estar vazio.';
        } else {
            $stmtUp = $db->prepare("
                UPDATE users 
                SET name = :name, phone = :phone, nif = :nif, address = :address, 
                    postal_code = :postal_code, city = :city 
                WHERE id = :id
            ");
            $stmtUp->execute([
                ':name'        => $name,
                ':phone'       => $phone ?: null,
                ':nif'         => $nif ?: null,
                ':address'     => $address ?: null,
                ':postal_code' => $postal_code ?: null,
                ':city'        => $city ?: null,
                ':id'          => $user['id']
            ]);

            // Atualizar sessão
            $_SESSION['user']['name']  = $name;
            $_SESSION['user']['phone'] = $phone;
            $_SESSION['user']['nif']   = $nif;

            set_flash('success', 'Dados de perfil atualizados com sucesso!');
            header('Location: ' . url('profile.php'));
            exit;
        }
    }

    // Alteração de Senha
    if ($action === 'change_password' && empty($errors)) {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass     = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPass, $user['password'])) {
            $errors[] = 'A palavra-passe atual está incorreta.';
        } elseif (strlen($newPass) < 6) {
            $errors[] = 'A nova palavra-passe deve ter no mínimo 6 caracteres.';
        } elseif ($newPass !== $confirmPass) {
            $errors[] = 'As novas palavras-passe não coincidem.';
        } else {
            $newHash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmtPass = $db->prepare("UPDATE users SET password = :p WHERE id = :id");
            $stmtPass->execute([':p' => $newHash, ':id' => $user['id']]);

            set_flash('success', 'Palavra-passe alterada com sucesso!');
            header('Location: ' . url('profile.php'));
            exit;
        }
    }
}

// Obter encomendas do utilizador
$stmtOrders = $db->prepare("
    SELECT o.*, COUNT(oi.id) AS total_items 
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.user_id = :uid OR o.customer_email = :email
    GROUP BY o.id
    ORDER BY o.id DESC
");
$stmtOrders->execute([':uid' => $user['id'], ':email' => $user['email']]);
$userOrders = $stmtOrders->fetchAll();

$pageTitle = "O Meu Perfil";
require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-gray-100 py-6 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900">
            A Minha Conta
        </h1>
        <p class="text-xs text-gray-500 mt-1">
            Gestão de dados pessoais, morada de envio e histórico de compras.
        </p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <?php if (!empty($errors)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-2xl p-4 text-red-800 text-xs">
            <?php foreach ($errors as $e): ?>
                <p><i class="fa-solid fa-circle-exclamation mr-1.5"></i> <?= e($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Coluna Esquerda: Dados Pessoais & Password -->
        <div class="lg:col-span-5 space-y-8">
            
            <!-- Formulário de Perfil -->
            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-gray-200 shadow-sm">
                <h2 class="text-lg font-bold text-gray-900 pb-3 border-b border-gray-100 mb-6 flex items-center">
                    <i class="fa-regular fa-user text-blue-600 mr-2"></i> Informação Pessoal & Envio
                </h2>

                <form action="<?= url('profile.php') ?>" method="POST" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nome Completo</label>
                        <input type="text" name="name" required value="<?= e($user['name']) ?>" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Email (Não Editável)</label>
                        <input type="email" disabled value="<?= e($user['email']) ?>" class="w-full bg-gray-100 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-500 cursor-not-allowed">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Telefone</label>
                            <input type="text" name="phone" value="<?= e($user['phone']) ?>" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">NIF</label>
                            <input type="text" name="nif" value="<?= e($user['nif']) ?>" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Morada Principal</label>
                        <input type="text" name="address" value="<?= e($user['address']) ?>" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Código Postal</label>
                            <input type="text" name="postal_code" value="<?= e($user['postal_code']) ?>" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Cidade</label>
                            <input type="text" name="city" value="<?= e($user['city']) ?>" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-sm">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl text-xs transition shadow-sm mt-2">
                        Guardar Alterações
                    </button>
                </form>
            </div>

            <!-- Formulário de Alteração de Password -->
            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-gray-200 shadow-sm">
                <h2 class="text-lg font-bold text-gray-900 pb-3 border-b border-gray-100 mb-6 flex items-center">
                    <i class="fa-solid fa-shield-halved text-gray-700 mr-2"></i> Segurança & Palavra-passe
                </h2>

                <form action="<?= url('profile.php') ?>" method="POST" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Palavra-passe Atual</label>
                        <input type="password" name="current_password" required class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nova Palavra-passe</label>
                        <input type="password" name="new_password" required class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Confirmar Nova Palavra-passe</label>
                        <input type="password" name="confirm_password" required class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm">
                    </div>

                    <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 text-white font-bold py-3 px-4 rounded-xl text-xs transition mt-2">
                        Atualizar Palavra-passe
                    </button>
                </form>
            </div>

        </div>

        <!-- Coluna Direita: Histórico de Encomendas -->
        <div id="orders" class="lg:col-span-7">
            <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center">
                        <i class="fa-solid fa-box-open text-blue-600 mr-2"></i> As Minhas Encomendas
                    </h2>
                    <span class="text-xs text-gray-500 font-semibold"><?= count($userOrders) ?> encomenda(s)</span>
                </div>

                <?php if (empty($userOrders)): ?>
                    <div class="p-12 text-center">
                        <i class="fa-solid fa-box-open text-4xl text-gray-300 mb-3 block"></i>
                        <h3 class="text-base font-bold text-gray-800 mb-1">Ainda não realizou encomendas</h3>
                        <p class="text-xs text-gray-500 mb-4">Quando encomendar peças no nosso catálogo, o estado do envio aparecerá aqui.</p>
                        <a href="<?= url('products.php') ?>" class="text-xs font-bold bg-blue-600 text-white px-4 py-2 rounded-xl inline-block hover:bg-blue-700 transition">
                            Explorar Catálogo
                        </a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider">
                                <tr>
                                    <th class="p-4 pl-6">Referência</th>
                                    <th class="p-4">Data</th>
                                    <th class="p-4">Total</th>
                                    <th class="p-4">Estado</th>
                                    <th class="p-4 pr-6 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-700">
                                <?php foreach ($userOrders as $ord): 
                                    $statusInfo = get_order_status_info($ord['status']);
                                ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="p-4 pl-6 font-mono font-bold text-gray-900">
                                            <?= e($ord['order_number']) ?>
                                        </td>
                                        <td class="p-4 text-gray-500">
                                            <?= format_date($ord['created_at']) ?>
                                        </td>
                                        <td class="p-4 font-bold text-gray-900">
                                            <?= format_price((float)$ord['total']) ?>
                                        </td>
                                        <td class="p-4">
                                            <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $statusInfo['class'] ?>">
                                                <?= $statusInfo['label'] ?>
                                            </span>
                                        </td>
                                        <td class="p-4 pr-6 text-right">
                                            <a href="<?= url('order-view.php?id=' . $ord['id']) ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-bold">
                                                Ver Detalhes <i class="fa-solid fa-chevron-right ml-1 text-[10px]"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

