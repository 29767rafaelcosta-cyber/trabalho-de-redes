<?php
/**
 * Configuração Geral da Aplicação
 * Loja Online de Peças de Carros & Gestão de Notícias
 */

// Iniciar sessão de forma segura se ainda não tiver sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    $tempDir = sys_get_temp_dir();
    if (is_dir($tempDir) && is_writable($tempDir)) {
        @session_save_path($tempDir);
    }
    @session_start();
}

// Configurações Globais
define('APP_NAME', 'AutoPeças Pro');
define('APP_TAGLINE', 'A sua loja de peças e acessórios auto de confiança');
define('APP_VERSION', '1.0.0');

// Taxas e Valores de Envio (Portugal / UE)
define('IVA_RATE', 0.23); // IVA 23%
define('FREE_SHIPPING_MIN', 50.00); // Portes grátis para encomendas >= 50€
define('SHIPPING_FEE', 4.90); // Custo de envio normal

// Moeda
define('CURRENCY_SYMBOL', '€');

/**
 * Detetar o caminho base automaticamente para funcionar tanto em:
 * - Servidor embutido PHP: php -S localhost:8000
 * - Subpastas do XAMPP/WAMP: http://localhost/trabalho%20de%20redes/
 */
function get_base_url(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Obter o diretório do script raiz
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    
    // Se o script atual estiver dentro de /admin, recuar um nível
    if (str_ends_with($scriptDir, '/admin')) {
        $scriptDir = dirname($scriptDir);
    }
    
    $basePath = rtrim($scriptDir, '/');
    return $protocol . $host . $basePath;
}

define('BASE_URL', get_base_url());

/**
 * Gera URL absoluta ou relativa amigável para qualquer recurso
 */
function url(string $path = ''): string {
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
}

/**
 * Gestão de Tokens CSRF para Proteção de Formulários
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Sistema de Mensagens Flash (Alertas / Toasts)
 */
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flashes(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Helpers para o Carrinho de Compras
 */
function get_cart_count(): int {
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += (int)($item['quantity'] ?? 0);
    }
    return $count;
}

function get_cart_totals(): array {
    $subtotal = 0.0;
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $price = (float)($item['price'] ?? 0);
            $qty = (int)($item['quantity'] ?? 0);
            $subtotal += ($price * $qty);
        }
    }

    $shipping = ($subtotal >= FREE_SHIPPING_MIN || $subtotal == 0) ? 0.0 : SHIPPING_FEE;
    $tax = $subtotal * IVA_RATE;
    $total = $subtotal + $shipping;

    return [
        'subtotal' => round($subtotal, 2),
        'tax' => round($tax, 2),
        'shipping' => round($shipping, 2),
        'total' => round($total, 2)
    ];
}

