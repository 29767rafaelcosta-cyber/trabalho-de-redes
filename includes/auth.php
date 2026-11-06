<?php
/**
 * Gestão de Autenticação, Sessões e Controlo de Acesso (RBAC)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Verifica se existe um utilizador com sessão iniciada
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Retorna os dados do utilizador autenticado
 */
function current_user(): ?array {
    return is_logged_in() ? $_SESSION['user'] : null;
}

/**
 * Verifica se o utilizador autenticado tem privilégios de administrador
 */
function is_admin(): bool {
    return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin');
}

/**
 * Força a autenticação para páginas protegidas (Área de Cliente)
 */
function require_login(?string $redirectBack = null): void {
    if (!is_logged_in()) {
        $target = $redirectBack ?? $_SERVER['REQUEST_URI'] ?? 'index.php';
        $_SESSION['redirect_after_login'] = $target;
        set_flash('warning', 'Por favor inicie sessão para aceder a esta página.');
        header('Location: ' . url('login.php'));
        exit;
    }
}

/**
 * Força privilégios de administrador para o Painel de Gestão (Backoffice)
 */
function require_admin(): void {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'admin/index.php';
        set_flash('danger', 'Acesso restrito. Inicie sessão como Administrador.');
        header('Location: ' . url('login.php'));
        exit;
    }

    if (!is_admin()) {
        set_flash('danger', 'Acesso negado: não tem permissões de administrador.');
        header('Location: ' . url('index.php'));
        exit;
    }
}

/**
 * Realiza autenticação com verificação segura de password_hash (bcrypt)
 */
function authenticate(string $email, string $password): ?array {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => trim($email)]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Remover a password do array antes de guardar em sessão
        unset($user['password']);
        return $user;
    }

    return null;
}

/**
 * Inicia sessão e regenera ID para prevenir Session Fixation
 */
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'    => (int)$user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
        'phone' => $user['phone'] ?? '',
        'nif'   => $user['nif'] ?? ''
    ];
}

/**
 * Termina sessão de forma segura e limpa dados
 */
function logout_user(): void {
    $_SESSION['user'] = null;
    unset($_SESSION['user']);
    // Opcional: manter carrinho ou limpar
    session_regenerate_id(true);
}

