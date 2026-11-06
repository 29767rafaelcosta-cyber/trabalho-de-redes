<?php
/**
 * Funções Auxiliares e Utilitários Gerais
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Sanitizar texto para output HTML (proteção XSS)
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Formatar valor monetário em Euros
 */
function format_price(float $amount): string {
    return number_format($amount, 2, ',', '.') . ' ' . CURRENCY_SYMBOL;
}

/**
 * Formatar data no padrão português
 */
function format_date(string $datetime, bool $includeTime = false): string {
    $timestamp = strtotime($datetime);
    if (!$timestamp) return $datetime;
    
    if ($includeTime) {
        return date('d/m/Y \à\s H:i', $timestamp);
    }
    return date('d/m/Y', $timestamp);
}

/**
 * Gerar slug a partir de string
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

/**
 * Gerar resumo de texto truncado
 */
function get_excerpt(string $text, int $limit = 120): string {
    $cleanText = strip_tags($text);
    if (mb_strlen($cleanText) <= $limit) {
        return $cleanText;
    }
    return mb_substr($cleanText, 0, $limit) . '...';
}

/**
 * Rótulos e badges para o estado de encomendas
 */
function get_order_status_info(string $status): array {
    switch ($status) {
        case 'pending':
            return ['label' => 'Pendente', 'class' => 'bg-yellow-100 text-yellow-800 border-yellow-200'];
        case 'processing':
            return ['label' => 'Em Processamento', 'class' => 'bg-blue-100 text-blue-800 border-blue-200'];
        case 'shipped':
            return ['label' => 'Enviado', 'class' => 'bg-indigo-100 text-indigo-800 border-indigo-200'];
        case 'completed':
            return ['label' => 'Concluído', 'class' => 'bg-green-100 text-green-800 border-green-200'];
        case 'cancelled':
            return ['label' => 'Cancelado', 'class' => 'bg-red-100 text-red-800 border-red-200'];
        default:
            return ['label' => ucfirst($status), 'class' => 'bg-gray-100 text-gray-800 border-gray-200'];
    }
}

/**
 * Rótulo amigável para método de pagamento
 */
function get_payment_method_name(string $method): string {
    switch ($method) {
        case 'multibanco': return 'Multibanco (Entidade / Referência)';
        case 'mbway': return 'MB WAY';
        case 'card': return 'Cartão de Crédito / Débito';
        case 'transfer': return 'Transferência Bancária';
        default: return ucfirst($method);
    }
}

/**
 * Obter todas as categorias ativas
 */
function get_all_categories(): array {
    $db = Database::getConnection();
    $stmt = $db->query("
        SELECT c.*, COUNT(p.id) AS products_count 
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
        GROUP BY c.id
        ORDER BY c.name ASC
    ");
    return $stmt->fetchAll();
}

/**
 * Obter todas as marcas de veículos
 */
function get_all_brands(): array {
    $db = Database::getConnection();
    $stmt = $db->query("
        SELECT b.*, COUNT(p.id) AS products_count 
        FROM brands b
        LEFT JOIN products p ON p.brand_id = b.id AND p.is_active = 1
        GROUP BY b.id
        ORDER BY b.name ASC
    ");
    return $stmt->fetchAll();
}

/**
 * Obter produtos em destaque para a página inicial
 */
function get_featured_products(int $limit = 8): array {
    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT p.*, c.name AS category_name, b.name AS brand_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE p.is_active = 1 AND p.is_featured = 1
        ORDER BY p.id DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obter últimas notícias publicadas
 */
function get_recent_news(int $limit = 3): array {
    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT n.*, u.name AS author_name 
        FROM news n
        LEFT JOIN users u ON n.author_id = u.id
        WHERE n.is_published = 1
        ORDER BY n.published_at DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

