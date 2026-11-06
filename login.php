<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Se já estiver logado, redirecionar
if (is_logged_in()) {
    header('Location: ' . url('index.php'));
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Token de segurança expirado. Por favor tente novamente.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = 'Preencha o email e a palavra-passe.';
        } else {
            $user = authenticate($email, $password);
            if ($user) {
                login_user($user);
                set_flash('success', 'Bem-vindo de volta, ' . $user['name'] . '!');

                $redirect = $_SESSION['redirect_after_login'] ?? ($user['role'] === 'admin' ? url('admin/index.php') : url('index.php'));
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
                exit;
            } else {
                $errors[] = 'Credenciais inválidas. Verifique o email e a palavra-passe.';
            }
        }
    }
}

$pageTitle = "Iniciar Sessão";
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white rounded-3xl border border-gray-200 p-8 shadow-sm">
        
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl shadow-inner">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h1 class="text-2xl font-black text-gray-900">Iniciar Sessão</h1>
            <p class="text-xs text-gray-500 mt-1">Aceda à sua conta de cliente ou ao painel de administração.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-2xl p-4 text-red-800 text-xs">
                <?php foreach ($errors as $err): ?>
                    <p class="flex items-center"><i class="fa-solid fa-circle-exclamation mr-1.5"></i> <?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Credenciais de Teste para o Avaliador Académico -->
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 mb-6 text-xs text-blue-900">
            <p class="font-bold mb-2 flex items-center">
                <i class="fa-solid fa-key text-blue-600 mr-1.5"></i> Contas Pré-configuradas (Trabalho Académico):
            </p>
            <div class="space-y-1.5 font-mono text-[11px]">
                <div class="flex justify-between items-center bg-white p-2 rounded-lg border border-blue-100">
                    <div>
                        <span class="font-bold text-red-700">Admin:</span> admin@autoparts.pt | admin123
                    </div>
                    <button type="button" onclick="fillDemo('admin@autoparts.pt', 'admin123')" class="bg-blue-600 text-white px-2 py-0.5 rounded text-[10px] font-sans font-bold hover:bg-blue-700">
                        Preencher
                    </button>
                </div>
                <div class="flex justify-between items-center bg-white p-2 rounded-lg border border-blue-100">
                    <div>
                        <span class="font-bold text-gray-700">Cliente:</span> cliente@autoparts.pt | cliente123
                    </div>
                    <button type="button" onclick="fillDemo('cliente@autoparts.pt', 'cliente123')" class="bg-gray-700 text-white px-2 py-0.5 rounded text-[10px] font-sans font-bold hover:bg-gray-800">
                        Preencher
                    </button>
                </div>
            </div>
        </div>

        <form action="<?= url('login.php') ?>" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Email</label>
                <div class="relative">
                    <input type="email" id="login-email" name="email" required value="<?= e($email) ?>" class="w-full bg-gray-50 border border-gray-300 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fa-regular fa-envelope absolute left-3.5 top-3.5 text-gray-400 text-sm"></i>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Palavra-passe</label>
                <div class="relative">
                    <input type="password" id="login-password" name="password" required class="w-full bg-gray-50 border border-gray-300 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fa-solid fa-lock absolute left-3.5 top-3.5 text-gray-400 text-sm"></i>
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 px-4 rounded-xl text-sm transition shadow-md">
                Entrar na Conta
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-gray-100 text-center text-xs text-gray-500">
            Ainda não tem conta de cliente? 
            <a href="<?= url('register.php') ?>" class="text-blue-600 font-bold hover:underline">Registe-se gratuitamente</a>
        </div>

    </div>
</div>

<script>
function fillDemo(email, pass) {
    document.getElementById('login-email').value = email;
    document.getElementById('login-password').value = pass;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

