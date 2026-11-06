<?php
$pageTitle = "Gestão de Utilizadores";
require_once __DIR__ . '/../includes/admin_header.php';

// Alternar papel (Role: Admin <-> Customer)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_role') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Token de segurança inválido.');
    } else {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $newRole = trim($_POST['new_role'] ?? 'customer');

        if ($targetUserId === (int)$currentUser['id']) {
            set_flash('danger', 'Não pode alterar o nível de acesso da sua própria conta com sessão ativa.');
        } elseif (in_array($newRole, ['admin', 'customer'])) {
            $stmtRole = $db->prepare("UPDATE users SET role = :role WHERE id = :id");
            $stmtRole->execute([':role' => $newRole, ':id' => $targetUserId]);
            set_flash('success', 'Nível de acesso do utilizador atualizado com sucesso.');
        }
    }
    header('Location: ' . url('admin/users.php'));
    exit;
}

// Obter todos os utilizadores com contagem de encomendas
$stmt = $db->query("
    SELECT u.*, COUNT(o.id) AS orders_count 
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id
    GROUP BY u.id
    ORDER BY u.id ASC
");
$allUsers = $stmt->fetchAll();
?>

<div class="space-y-6">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Utilizadores Registados</h2>
            <p class="text-xs text-gray-500">Controlo de acessos, clientes registados e administradores do sistema.</p>
        </div>
        <span class="text-xs font-bold bg-white border border-gray-200 px-3 py-1.5 rounded-xl text-gray-700 shadow-sm">
            Total: <?= count($allUsers) ?> utilizadores
        </span>
    </div>

    <!-- Tabela de Utilizadores -->
    <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-admin">
                <thead>
                    <tr>
                        <th class="p-4 pl-6">ID</th>
                        <th class="p-4">Nome & Email</th>
                        <th class="p-4">Contacto</th>
                        <th class="p-4">Localidade</th>
                        <th class="p-4 text-center">Encomendas</th>
                        <th class="p-4 text-center">Papel / Acesso</th>
                        <th class="p-4">Data Registo</th>
                        <th class="p-4 pr-6 text-right">Alterar Acesso</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    <?php foreach ($allUsers as $u): ?>
                        <tr>
                            <td class="p-4 pl-6 font-mono text-gray-400">#<?= $u['id'] ?></td>
                            <td class="p-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-gray-100 text-gray-700 font-bold flex items-center justify-center text-xs">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <span class="font-bold text-gray-900 block"><?= e($u['name']) ?></span>
                                        <span class="text-gray-400 text-[11px]"><?= e($u['email']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <?= e($u['phone'] ?: 'N/A') ?>
                                <?php if (!empty($u['nif'])): ?>
                                    <span class="text-[10px] text-gray-400 font-mono block">NIF: <?= e($u['nif']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <?= e($u['city'] ?: 'N/A') ?>
                            </td>
                            <td class="p-4 text-center font-bold">
                                <span class="bg-gray-100 text-gray-800 px-2 py-0.5 rounded-full text-[10px]">
                                    <?= $u['orders_count'] ?> enc.
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="bg-red-100 text-red-700 font-bold px-2.5 py-1 rounded-full text-[10px] uppercase">
                                        Administrador
                                    </span>
                                <?php else: ?>
                                    <span class="bg-blue-100 text-blue-700 font-bold px-2.5 py-1 rounded-full text-[10px] uppercase">
                                        Cliente
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-gray-500">
                                <?= format_date($u['created_at']) ?>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <?php if ($u['id'] == $currentUser['id']): ?>
                                    <span class="text-gray-400 text-[11px] italic font-semibold">(Sessão Atual)</span>
                                <?php else: ?>
                                    <form action="<?= url('admin/users.php') ?>" method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_role">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="new_role" value="<?= $u['role'] === 'admin' ? 'customer' : 'admin' ?>">
                                        
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-2.5 py-1 rounded-lg font-bold text-[11px] transition confirm-action" data-confirm="Tem a certeza que deseja remover os privilégios de Administrador a este utilizador?">
                                                Mudar para Cliente
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="bg-red-50 hover:bg-red-600 hover:text-white text-red-600 px-2.5 py-1 rounded-lg font-bold text-[11px] transition confirm-action" data-confirm="Tem a certeza que deseja promover este utilizador a Administrador do Sistema?">
                                                Promover a Admin
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

