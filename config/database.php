<?php
/**
 * Conexão à Base de Dados com Suporte Duplo: MySQL e Fallback Automático para SQLite
 * Loja Online de Peças de Carros & Gestão de Notícias
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'mysql';

    private static string $host = 'localhost';
    private static string $dbname = 'autoparts_db';
    private static string $username = 'root';
    private static string $password = '';
    private static string $port = '3306';

    /**
     * Obter instância Singleton da conexão PDO
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            
            // 1. Tentar conectar ao MySQL
            try {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', self::$host, self::$port, self::$dbname);
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_TIMEOUT            => 2
                ];
                self::$instance = new PDO($dsn, self::$username, self::$password, $options);
                self::$driver = 'mysql';
                return self::$instance;
            } catch (Throwable $e) {
                // Se MySQL não estiver ativo (ex: utilizador não conseguiu ligar o XAMPP),
                // recorrer ao motor SQLite embutido para funcionamento autónomo imediato!
                self::$instance = self::getSqliteConnection();
                self::$driver = 'sqlite';
            }
        }

        return self::$instance;
    }

    /**
     * Conexão e Inicialização Automática de SQLite Embutido
     */
    private static function getSqliteConnection(): PDO {
        $dbFile = __DIR__ . '/../database/autoparts.sqlite';
        $needsInit = !file_exists($dbFile) || filesize($dbFile) === 0;

        $pdo = new PDO("sqlite:" . $dbFile, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        if ($needsInit) {
            self::initializeSqliteDatabase($pdo);
        }

        return $pdo;
    }

    /**
     * Criar tabelas e dados iniciais automaticamente no SQLite se o MySQL estiver desligado
     */
    private static function initializeSqliteDatabase(PDO $pdo): void {
        $adminHash = '$2y$12$LEhxyAi32DgLq0nexnNFiup9q9Pgef.7ubKhnQpnfhv238HtcbyxW';
        $clientHash = '$2y$12$fXVvO4UAmer0Rk76WJ/2u.N4o9tI6sqxFsxtcR.TLvnP9cV7/oB.2';

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                role TEXT DEFAULT 'customer',
                phone TEXT,
                nif TEXT,
                address TEXT,
                postal_code TEXT,
                city TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT,
                icon TEXT DEFAULT 'fa-cogs',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS brands (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                slug TEXT NOT NULL UNIQUE,
                country TEXT,
                logo_url TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER NOT NULL,
                brand_id INTEGER,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                oem_reference TEXT,
                manufacturer TEXT DEFAULT 'Genérico',
                description TEXT NOT NULL,
                specifications TEXT,
                compatibility TEXT,
                price REAL NOT NULL,
                discount_price REAL,
                stock INTEGER NOT NULL DEFAULT 0,
                image_url TEXT,
                is_featured INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_number TEXT NOT NULL UNIQUE,
                user_id INTEGER,
                customer_name TEXT NOT NULL,
                customer_email TEXT NOT NULL,
                customer_phone TEXT NOT NULL,
                customer_nif TEXT,
                shipping_address TEXT NOT NULL,
                shipping_postal_code TEXT NOT NULL,
                shipping_city TEXT NOT NULL,
                payment_method TEXT NOT NULL,
                payment_reference TEXT,
                payment_status TEXT DEFAULT 'pending',
                subtotal REAL NOT NULL,
                tax_amount REAL NOT NULL,
                shipping_cost REAL DEFAULT 0.00,
                total REAL NOT NULL,
                status TEXT DEFAULT 'pending',
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER,
                product_name TEXT NOT NULL,
                product_price REAL NOT NULL,
                quantity INTEGER NOT NULL DEFAULT 1,
                subtotal REAL NOT NULL
            );

            CREATE TABLE IF NOT EXISTS news (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                category TEXT DEFAULT 'Dicas de Manutenção',
                author_id INTEGER,
                excerpt TEXT NOT NULL,
                content TEXT NOT NULL,
                image_url TEXT,
                views INTEGER DEFAULT 0,
                is_published INTEGER DEFAULT 1,
                published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Inserir Utilizadores com bind para proteger hashes
        $stmtU = $pdo->prepare("
            INSERT OR IGNORE INTO users (id, name, email, password, role, phone, nif, address, postal_code, city) VALUES
            (1, 'Administrador do Sistema', 'admin@autoparts.pt', :p1, 'admin', '+351 912 345 678', '500123456', 'Avenida Central da Oficina, 100', '4000-001', 'Porto'),
            (2, 'João Silva (Cliente)', 'cliente@autoparts.pt', :p2, 'customer', '+351 923 456 789', '234567890', 'Rua dos Mecânicos, 42, 2º Dto', '1000-123', 'Lisboa')
        ");
        $stmtU->execute([':p1' => $adminHash, ':p2' => $clientHash]);

        // Inserir Categorias, Marcas, Produtos, Notícias e Encomendas
        $pdo->exec("
            INSERT OR IGNORE INTO categories (id, name, slug, description, icon) VALUES
            (1, 'Sistema de Travões', 'travoes', 'Discos, pastilhas, pinças e sensores de desgaste.', 'fa-circle-stop'),
            (2, 'Motor & Distribuição', 'motor-distribuicao', 'Kits de distribuição, bombas de água, velas e bobinas.', 'fa-cogs'),
            (3, 'Suspensão & Direção', 'suspensao-direcao', 'Amortecedores, molas e braços de suspensão.', 'fa-arrows-up-down'),
            (4, 'Filtros Automóveis', 'filtros', 'Filtros de óleo, ar, combustível e habitáculo.', 'fa-filter'),
            (5, 'Óleos & Fluidos', 'oleos-fluidos', 'Lubrificantes 100% sintéticos e fluidos de travão.', 'fa-oil-can'),
            (6, 'Eletricidade & Baterias', 'eletricidade-baterias', 'Baterias AGM/EFB e iluminação.', 'fa-bolt');

            INSERT OR IGNORE INTO brands (id, name, slug, country) VALUES
            (1, 'BMW', 'bmw', 'Alemanha'),
            (2, 'Mercedes-Benz', 'mercedes-benz', 'Alemanha'),
            (3, 'Audi', 'audi', 'Alemanha'),
            (4, 'Volkswagen', 'volkswagen', 'Alemanha'),
            (5, 'Renault', 'renault', 'França');

            INSERT OR IGNORE INTO products (id, category_id, brand_id, name, slug, oem_reference, manufacturer, description, specifications, compatibility, price, discount_price, stock, image_url, is_featured, is_active) VALUES
            (1, 1, 1, 'Discos de Travão Dianteiros Ventilados Brembo Max', 'discos-travao-brembo-max-bmw', '09.C399.13', 'Brembo', 'Par de discos ventilados de alto rendimento.', 'Diâmetro: 340mm\nEixo: Dianteiro', 'BMW Série 3 (F30) 320d (2012-2019)', 149.90, 129.90, 18, 'https://images.unsplash.com/photo-1600790142055-619df03207e6?w=600&auto=format&fit=crop&q=80', 1, 1),
            (2, 1, 4, 'Jogo de Pastilhas de Travão Dianteiras Ferodo Racing', 'pastilhas-travao-ferodo-vw-golf', '5Q0698151AH', 'Ferodo', 'Composto cerâmico de baixo ruído.', 'Eixo: Dianteiro', 'VW Golf VII 2.0 TDI / GTI', 68.50, 54.90, 25, 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=600&auto=format&fit=crop&q=80', 1, 1),
            (3, 2, 4, 'Kit de Correia de Distribuição + Bomba de Água Continental', 'kit-distribuicao-bomba-agua-continental-vag', 'CT1168WP1', 'Continental', 'Kit com correia em teflon e bomba.', 'Dentes: 145', 'VW Golf VII / Audi A4 2.0 TDI', 189.00, 165.00, 12, 'https://images.unsplash.com/photo-1517524206127-48bbd363f3d7?w=600&auto=format&fit=crop&q=80', 1, 1),
            (4, 5, 2, 'Óleo de Motor Castrol EDGE 5W-30 LL Titanium 5L', 'oleo-castrol-edge-5w30-ll-5l', '15669E', 'Castrol', 'Óleo 100% sintético para filtros DPF.', 'Capacidade: 5L\nNormas: ACEA C3', 'BMW LL-04, MB 229.51, VW 507.00', 59.90, 47.90, 45, 'https://images.unsplash.com/photo-1615906655593-ad0386982a0f?w=600&auto=format&fit=crop&q=80', 1, 1),
            (5, 3, 1, 'Par de Amortecedores Traseiros Bilstein B4 a Gás', 'amortecedores-traseiros-bilstein-b4-bmw', '19-135052', 'Bilstein', 'Amortecedores a gás pressurizado calibragem original.', 'Eixo: Traseiro', 'BMW Série 3 E90 / E91 (2005-2012)', 135.00, NULL, 8, 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=600&auto=format&fit=crop&q=80', 0, 1),
            (6, 4, 3, 'Kit 4 Filtros Bosch (Óleo, Ar, Combustível e Habitáculo)', 'kit-4-filtros-bosch-audi-a4', 'BOSCH-KIT4-VAG', 'Bosch', 'Pack de revisão completo Bosch para filtragem eficiente.', 'Inclui: 4 Filtros', 'Audi A4 B8 2.0 TDI (2008-2015)', 79.90, 69.90, 20, 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600&auto=format&fit=crop&q=80', 1, 1),
            (7, 6, 2, 'Bateria Automóvel Varta Silver Dynamic AGM 80Ah 800A', 'bateria-varta-silver-dynamic-agm-80ah', 'F21 / 580 901 080', 'Varta', 'Bateria AGM especialmente desenhada para Start-Stop.', 'Capacidade: 80 Ah', 'Mercedes-Benz Classe C / E', 195.00, 172.50, 9, 'https://images.unsplash.com/photo-1558441719-72b144bcad18?w=600&auto=format&fit=crop&q=80', 1, 1);

            INSERT OR IGNORE INTO news (id, title, slug, category, author_id, excerpt, content, image_url, views, is_published, published_at) VALUES
            (1, 'Como Saber Quando Mudar as Pastilhas e Discos de Travão: Sintomas de Desgaste', 'quando-mudar-pastilhas-discos-travao', 'Dicas de Manutenção', 1, 'Aprenda a detetar os sinais visuais e sonoros de desgaste nos travões antes que comprometam a sua segurança.', '<p>O sistema de travagem é essencial. Ruídos estridentes metálicos indicam que as pastilhas atingiram a espessura limite.</p>', 'https://images.unsplash.com/photo-1600790142055-619df03207e6?w=800&auto=format&fit=crop&q=80', 342, 1, '2026-10-15 10:30:00'),
            (2, 'Óleo 5W-30 vs 0W-20: Qual a Importância da Norma do Fabricante?', 'oleo-motor-5w30-vs-0w20-normas-fabricante', 'Guias Técnicos', 1, 'Descubra por que a viscosidade correta protege o turbo e o filtro DPF.', '<p>Os motores modernos exigem óleos Low-SAPS para não obstruir o filtro de partículas.</p>', 'https://images.unsplash.com/photo-1615906655593-ad0386982a0f?w=800&auto=format&fit=crop&q=80', 189, 1, '2026-10-22 14:15:00'),
            (3, 'Kit de Distribuição: Quando Trocar para Evitar a Quebra do Motor?', 'kit-distribuicao-quando-trocar-evitar-danos', 'Dicas de Manutenção', 1, 'A correia de distribuição é o coração da sincronização do motor. Saiba prazos recomendados.', '<p>A substituição da correia deve ocorrer a cada 5 anos ou 120.000km com troca de bomba de água.</p>', 'https://images.unsplash.com/photo-1517524206127-48bbd363f3d7?w=800&auto=format&fit=crop&q=80', 276, 1, '2026-10-29 09:00:00');

            INSERT OR IGNORE INTO orders (id, order_number, user_id, customer_name, customer_email, customer_phone, customer_nif, shipping_address, shipping_postal_code, shipping_city, payment_method, payment_reference, payment_status, subtotal, tax_amount, shipping_cost, total, status, created_at) VALUES
            (1, 'ORD-2026-7841', 2, 'João Silva (Cliente)', 'cliente@autoparts.pt', '+351 923 456 789', '234567890', 'Rua dos Mecânicos, 42, 2º Dto', '1000-123', 'Lisboa', 'mbway', '923456789 (MB WAY Confirmado)', 'paid', 184.80, 42.50, 0.00, 227.30, 'shipped', '2026-11-03 11:20:00'),
            (2, 'ORD-2026-8924', 2, 'João Silva (Cliente)', 'cliente@autoparts.pt', '+351 923 456 789', '234567890', 'Rua dos Mecânicos, 42, 2º Dto', '1000-123', 'Lisboa', 'multibanco', 'Entidade: 21245 | Ref: 849 192 481', 'pending', 69.90, 16.08, 4.90, 90.88, 'pending', '2026-11-05 14:10:00');

            INSERT OR IGNORE INTO order_items (id, order_id, product_id, product_name, product_price, quantity, subtotal) VALUES
            (1, 1, 1, 'Discos de Travão Dianteiros Ventilados Brembo Max', 129.90, 1, 129.90),
            (2, 1, 2, 'Jogo de Pastilhas de Travão Dianteiras Ferodo Racing', 54.90, 1, 54.90),
            (3, 2, 4, 'Óleo de Motor Castrol EDGE 5W-30 LL Titanium 5L', 47.90, 1, 47.90);
        ");
    }

    public static function getDriver(): string {
        return self::$driver;
    }
}
