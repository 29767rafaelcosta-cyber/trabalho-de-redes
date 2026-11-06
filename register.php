<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . url('index.php'));
    exit;
}

$db = Database::getConnection();
$errors = [];

$formData = [
    'name'        => '',
    'email'       => '',
    'phone'       => '',
    'nif'         => '',
    'address'     => '',
    'postal_code' => '',
    'city'        => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Token de segurança inválido. Por favor recarregue a página.';
    }

    $formData['name']        = trim($_POST['name'] ?? '');
    $formData['email']       = trim($_POST['email'] ?? '');
    $formData['phone']       = trim($_POST['phone'] ?? '');
    $formData['nif']         = trim($_POST['nif'] ?? '');
    $formData['address']     = trim($_POST['address'] ?? '');
    $formData['postal_code'] = trim($_POST['postal_code'] ?? '');
    $formData['city']        = trim($_POST['city'] ?? '');
    $password                = $_POST['password'] ?? '';
    $passwordConfirm         = $_POST['password_confirm'] ?? '';

    // Validações
    if (empty($formData['name'])) $errors[] = 'O nome completo é obrigatório.';
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Insira um email válido.';
    if (strlen($password) < 6) $errors[] = 'A palavra-passe deve conter pelo menos 6 caracteres.';
    if ($password !== $passwordConfirm) $errors[] = 'As palavras-passe não coincidem.';

    // Verificar se o email já existe
    if (empty($errors)) {
        $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmtCheck->execute([':email' => $formData['email']]);
        if ($stmtCheck->fetch()) {
            $errors[] = 'Este endereço de email já se encontra registado.';
        }
    }

    // Inserir novo utilizador
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, role, phone, nif, address, postal_code, city, created_at)
            VALUES (:name, :email, :password, 'customer', :phone, :nif, :address, :postal_code, :city, NOW())
        ");
        
        $stmt->execute([
            ':name'        => $formData['name'],
            ':email'       => $formData['email'],
            ':password'    => $passwordHash,
            ':phone'       => $formData['phone'] ?: null,
            ':nif'         => $formData['nif'] ?: null,
            ':address'     => $formData['address'] ?: null,
            ':postal_code' => $formData['postal_code'] ?: null,
            ':city'        => $formData['city'] ?: null
        ]);

        $userId = (int)$db->lastInsertId();

        // Autenticar automaticamente
        login_user([
            'id'    => $userId,
            'name'  => $formData['name'],
            'email' => $formData['email'],
            'role'  => 'customer',
            'phone' => $formData['phone'],
            'nif'   => $formData['nif']
        ]);

        set_flash('success', 'Conta criada com sucesso! Bem-vindo à AutoPeças Pro.');
        header('Location: ' . url('profile.php'));
        exit;
    }
}

$pageTitle = "Criar Nova Conta";
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-xl mx-auto px-4 py-12">
    <div class="bg-white rounded-3xl border border-gray-200 p-8 sm:p-10 shadow-sm">
        
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl shadow-inner">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1 class="text-2xl font-black text-gray-900">Criar Conta de Cliente</h1>
            <p class="text-xs text-gray-500 mt-1">Registe-se para acompanhar encomendas e encomendar peças mais rapidamente.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-2xl p-4 text-red-800 text-xs">
                <div class="font-bold mb-1">Por favor corrija os seguintes erros:</div>
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= url('register.php') ?>" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nome Completo *</label>
                <input type="text" name="name" required value="<?= e($formData['name']) ?>" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Email *</label>
                    <input type="email" name="email" required value="<?= e($formData['email']) ?>" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Telefone</label>
                    <input type="text" name="phone" value="<?= e($formData['phone']) ?>" placeholder="+351 912 345 678" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Palavra-passe (mín. 6 car.) *</label>
                    <input type="password" name="password" required class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Confirmar Palavra-passe *</label>
                    <input type="password" name="password_confirm" required class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">NIF (Opcional p/ Faturas)</label>
                <input type="text" name="nif" value="<?= e($formData['nif']) ?>" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Morada de Entrega Principal</label>
                <input type="text" name="address" value="<?= e($formData['address']) ?>" placeholder="Rua, Número, Andar" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Código Postal</label>
                    <input type="text" name="postal_code" value="<?= e($formData['postal_code']) ?>" placeholder="Ex: 4000-001" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Cidade</label>
                    <input type="text" name="city" value="<?= e($formData['city']) ?>" placeholder="Ex: Porto" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 px-4 rounded-xl text-sm transition shadow-md mt-4">
                Criar a Minha Conta
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-gray-100 text-center text-xs text-gray-500">
            Já tem conta registada? 
            <a href="<?= url('login.php') ?>" class="text-blue-600 font-bold hover:underline">Iniciar sessão</a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

