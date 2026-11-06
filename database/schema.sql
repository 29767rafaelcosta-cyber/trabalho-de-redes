-- ==========================================================
-- BASE DE DADOS: autoparts_db
-- PROJETO: Loja Online de Peças de Carros & Gestão de Notícias
-- REQUISITOS: MySQL 5.7+ / MariaDB 10.3+ / PHP 7.4+ / PHP 8.x
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `autoparts_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `autoparts_db`;

-- Desativar verificação de chaves temporariamente para recriação limpa
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `news`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `brands`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------
-- 1. TABELA DE UTILIZADORES (users)
-- ----------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
  `phone` VARCHAR(30) NULL,
  `nif` VARCHAR(20) NULL,
  `address` VARCHAR(255) NULL,
  `postal_code` VARCHAR(20) NULL,
  `city` VARCHAR(100) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. TABELA DE CATEGORIAS DE PEÇAS (categories)
-- ----------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `icon` VARCHAR(50) DEFAULT 'fa-cogs',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. TABELA DE MARCAS DE VEÍCULOS (brands)
-- ----------------------------------------------------------
CREATE TABLE `brands` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `country` VARCHAR(50) NULL,
  `logo_url` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. TABELA DE PRODUTOS / PEÇAS (products)
-- ----------------------------------------------------------
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `brand_id` INT NULL,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL UNIQUE,
  `oem_reference` VARCHAR(100) NULL,
  `manufacturer` VARCHAR(100) NOT NULL DEFAULT 'Genérico',
  `description` TEXT NOT NULL,
  `specifications` TEXT NULL,
  `compatibility` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `discount_price` DECIMAL(10,2) NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `image_url` VARCHAR(255) NULL,
  `is_featured` BOOLEAN NOT NULL DEFAULT FALSE,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE SET NULL,
  INDEX `idx_products_category` (`category_id`),
  INDEX `idx_products_brand` (`brand_id`),
  INDEX `idx_products_slug` (`slug`),
  INDEX `idx_products_featured` (`is_featured`),
  INDEX `idx_products_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 5. TABELA DE ENCOMENDAS (orders)
-- ----------------------------------------------------------
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_email` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(30) NOT NULL,
  `customer_nif` VARCHAR(20) NULL,
  `shipping_address` VARCHAR(255) NOT NULL,
  `shipping_postal_code` VARCHAR(20) NOT NULL,
  `shipping_city` VARCHAR(100) NOT NULL,
  `payment_method` ENUM('multibanco', 'mbway', 'card', 'transfer') NOT NULL,
  `payment_reference` VARCHAR(150) NULL,
  `payment_status` ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
  `subtotal` DECIMAL(10,2) NOT NULL,
  `tax_amount` DECIMAL(10,2) NOT NULL,
  `shipping_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'processing', 'shipped', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_orders_user` (`user_id`),
  INDEX `idx_orders_status` (`status`),
  INDEX `idx_orders_number` (`order_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 6. TABELA DE ITENS DA ENCOMENDA (order_items)
-- ----------------------------------------------------------
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `product_price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL,
  INDEX `idx_order_items_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 7. TABELA DE NOTÍCIAS / ARTIGOS AUTOMÓVEIS (news)
-- ----------------------------------------------------------
CREATE TABLE `news` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `category` VARCHAR(100) NOT NULL DEFAULT 'Dicas de Manutenção',
  `author_id` INT NULL,
  `excerpt` TEXT NOT NULL,
  `content` MEDIUMTEXT NOT NULL,
  `image_url` VARCHAR(255) NULL,
  `views` INT NOT NULL DEFAULT 0,
  `is_published` BOOLEAN NOT NULL DEFAULT TRUE,
  `published_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_news_slug` (`slug`),
  INDEX `idx_news_published` (`is_published`),
  INDEX `idx_news_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- DADOS INICIAIS (SEEDS)
-- ==========================================================

-- 1. Utilizadores: Admin e Cliente
-- Senha do admin: admin123
-- Senha do cliente: cliente123
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `nif`, `address`, `postal_code`, `city`) VALUES
(1, 'Administrador do Sistema', 'admin@autoparts.pt', '$2y$12$LEhxyAi32DgLq0nexnNFiup9q9Pgef.7ubKhnQpnfhv238HtcbyxW', 'admin', '+351 912 345 678', '500123456', 'Avenida Central da Oficina, 100', '4000-001', 'Porto'),
(2, 'João Silva (Cliente)', 'cliente@autoparts.pt', '$2y$12$fXVvO4UAmer0Rk76WJ/2u.N4o9tI6sqxFsxtcR.TLvnP9cV7/oB.2', 'customer', '+351 923 456 789', '234567890', 'Rua dos Mecânicos, 42, 2º Dto', '1000-123', 'Lisboa');

-- 2. Categorias de Peças
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`) VALUES
(1, 'Sistema de Travões', 'travoes', 'Discos, pastilhas, pinças, tubos e sensores de desgaste de alta segurança.', 'fa-circle-stop'),
(2, 'Motor & Distribuição', 'motor-distribuicao', 'Kits de distribuição, bombas de água, velas, bobinas e componentes internos.', 'fa-cogs'),
(3, 'Suspensão & Direção', 'suspensao-direcao', 'Amortecedores, molas, braços de suspensão, rótulas e casquilhos reforçados.', 'fa-arrows-up-down'),
(4, 'Filtros Automóveis', 'filtros', 'Filtros de óleo, ar do motor, combustível e habitáculo/pólen.', 'fa-filter'),
(5, 'Óleos & Fluidos', 'oleos-fluidos', 'Lubrificantes 100% sintéticos, líquidos de refrigeração e fluido de travões.', 'fa-oil-can'),
(6, 'Eletricidade & Baterias', 'eletricidade-baterias', 'Baterias AGM/EFB, alternadores, motores de arranque e iluminação LED.', 'fa-bolt'),
(7, 'Embraiagem & Caixa', 'embraiagem-caixa', 'Kits de embraiagem bimassa, discos, rolamentos e óleo de transmissão.', 'fa-gears'),
(8, 'Escape & Emissões', 'escape-emissoes', 'Filtros de partículas (DPF), catalisadores, sondas lambda e panelas.', 'fa-smog');

-- 3. Marcas de Veículos
INSERT INTO `brands` (`id`, `name`, `slug`, `country`, `logo_url`) VALUES
(1, 'BMW', 'bmw', 'Alemanha', 'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=200&auto=format&fit=crop&q=80'),
(2, 'Mercedes-Benz', 'mercedes-benz', 'Alemanha', 'https://images.unsplash.com/photo-1617814076367-b759c7d7e738?w=200&auto=format&fit=crop&q=80'),
(3, 'Audi', 'audi', 'Alemanha', 'https://images.unsplash.com/photo-1603584173870-7f23fdae1b7a?w=200&auto=format&fit=crop&q=80'),
(4, 'Volkswagen', 'volkswagen', 'Alemanha', 'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?w=200&auto=format&fit=crop&q=80'),
(5, 'Renault', 'renault', 'França', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=200&auto=format&fit=crop&q=80'),
(6, 'Peugeot', 'peugeot', 'França', 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=200&auto=format&fit=crop&q=80'),
(7, 'Toyota', 'toyota', 'Japão', 'https://images.unsplash.com/photo-1629897048514-3dd7414fe72a?w=200&auto=format&fit=crop&q=80'),
(8, 'Seat', 'seat', 'Espanha', 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=200&auto=format&fit=crop&q=80');

-- 4. Peças / Produtos Automóveis
INSERT INTO `products` (`id`, `category_id`, `brand_id`, `name`, `slug`, `oem_reference`, `manufacturer`, `description`, `specifications`, `compatibility`, `price`, `discount_price`, `stock`, `image_url`, `is_featured`, `is_active`) VALUES
(1, 1, 1, 'Discos de Travão Dianteiros Ventilados Brembo Max', 'discos-travao-brembo-max-bmw', '09.C399.13', 'Brembo', 'Par de discos de travão ventilados de alto rendimento com ranhuras anti-vitrificação para dissipação térmica superior.', 'Diâmetro exterior: 340mm\nEspessura: 30mm\nTipo de disco: Ranhurado e Perfurado\nEixo: Dianteiro\nRevestimento anticorrosão UV', 'BMW Série 3 (F30, F31) 320d / 330d (2012-2019)\nBMW Série 4 (F32, F36) 420d / 430d (2013-2020)', 149.90, 129.90, 18, 'https://images.unsplash.com/photo-1600790142055-619df03207e6?w=600&auto=format&fit=crop&q=80', 1, 1),

(2, 1, 4, 'Jogo de Pastilhas de Travão Dianteiras Ferodo Racing', 'pastilhas-travao-ferodo-vw-golf', '5Q0698151AH', 'Ferodo', 'Composto cerâmico de baixo ruído com proteção contra emissão de pó preto e excelente mordida inicial a frio.', 'Eixo: Dianteiro\nSistema de travagem: Teves / ATE\nCom sensor de desgaste incluído: Sim\nHomologação: ECE-R90', 'Volkswagen Golf VII / VIII 2.0 TDI / GTI (2013-2024)\nSeat Leon 5F 2.0 TDI (2012-2020)\nAudi A3 8V / 8Y (2013-2024)', 68.50, 54.90, 25, 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=600&auto=format&fit=crop&q=80', 1, 1),

(3, 2, 4, 'Kit de Correia de Distribuição + Bomba de Água Continental', 'kit-distribuicao-bomba-agua-continental-vag', 'CT1168WP1', 'Continental Contitech', 'Kit completo com correia dentada reforçada em teflon, rolamento tensor, polia guia e bomba de água com vedante.', 'Número de dentes: 145\nLargura: 25mm\nInclui bomba de água: Sim\nIntervalo recomendado: 120.000 km', 'Volkswagen Golf VII 1.6 / 2.0 TDI\nAudi A4 B8 / B9 2.0 TDI\nSeat Ibiza / Leon 1.6 TDI', 189.00, 165.00, 12, 'https://images.unsplash.com/photo-1517524206127-48bbd363f3d7?w=600&auto=format&fit=crop&q=80', 1, 1),

(4, 5, 2, 'Óleo de Motor Castrol EDGE 5W-30 LL Titanium FST 5L', 'oleo-castrol-edge-5w30-ll-5l', '15669E', 'Castrol', 'Óleo 100% sintético premium formulado com tecnologia Fluid Titanium que resiste às pressões extremas do motor moderno.', 'Viscosidade: SAE 5W-30\nCapacidade: 5 Litros\nNormas: ACEA C3\nAprovações: MB 229.31/229.51, VW 504 00/507 00, BMW Longlife-04, Porsche C30', 'Compatível com motores a Gasóleo e Gasolina modernos com filtro de partículas (DPF / GPF).', 59.90, 47.90, 45, 'https://images.unsplash.com/photo-1615906655593-ad0386982a0f?w=600&auto=format&fit=crop&q=80', 1, 1),

(5, 3, 1, 'Par de Amortecedores Traseiros Bilstein B4 a Gás', 'amortecedores-traseiros-bilstein-b4-bmw', '19-135052', 'Bilstein', 'Amortecedores a gás pressurizado com calibragem original OEM para máximo conforto e estabilidade em curva.', 'Tipo: Pressão de gás bi-tubo\nFixação: Olhal superior e inferior\nEixo: Traseiro (Esq + Dir)\nTecnologia de amortecimento Bilstein', 'BMW Série 3 E90 / E91 / E92 (2005-2012)\nBMW Série 1 E81 / E87 (2004-2011)', 135.00, NULL, 8, 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=600&auto=format&fit=crop&q=80', 0, 1),

(6, 4, 3, 'Kit 4 Filtros Bosch (Óleo, Ar, Combustível e Habitáculo)', 'kit-4-filtros-bosch-audi-a4', 'BOSCH-KIT4-VAG', 'Bosch', 'Pack de revisão completo Bosch para filtragem eficiente de impurezas e ar puro no habitáculo com carvão ativo.', 'Inclui: 1x Filtro de Óleo, 1x Filtro de Ar do Motor, 1x Filtro de Combustível, 1x Filtro de Habitáculo com Carvão Ativo', 'Audi A4 B8 2.0 TDI 143cv / 177cv (2008-2015)\nAudi A5 8T 2.0 TDI (2009-2016)', 79.90, 69.90, 20, 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600&auto=format&fit=crop&q=80', 1, 1),

(7, 6, 2, 'Bateria Automóvel Varta Silver Dynamic AGM 80Ah 800A', 'bateria-varta-silver-dynamic-agm-80ah', 'F21 / 580 901 080', 'Varta', 'Bateria AGM de qualidade topo de gama especialmente desenhada para veículos com sistema Start-Stop avançado e travagem regenerativa.', 'Capacidade: 80 Ah\nCorrente de arranque a frio: 800 A (EN)\nTensão: 12V\nTecnologia: AGM selada sem manutenção\nDimensões: 315 x 175 x 190 mm', 'Mercedes-Benz Classe C / E / GLC\nBMW Série 3 / Série 5 com Start-Stop\nAudi A4 / A6 TDI', 195.00, 172.50, 9, 'https://images.unsplash.com/photo-1558441719-72b144bcad18?w=600&auto=format&fit=crop&q=80', 1, 1),

(8, 7, 5, 'Kit de Embraiagem com Volante Bimassa LuK RepSet DMF', 'kit-embraiagem-volante-bimassa-luk-renault', '600 0016 00', 'LuK / Schaeffler', 'Kit completo profissional com volante de motor bimassa, prato de embraiagem, disco e rolamento hidráulico de desengate.', 'Diâmetro do disco: 240mm\nNúmero de dentes: 21\nInclui volante bimassa: Sim\nInclui rolamento hidráulico: Sim', 'Renault Mégane III / Scénic III 1.5 dCi / 1.6 dCi (2008-2016)\nNissan Qashqai 1.5 dCi', 489.00, 429.00, 4, 'https://images.unsplash.com/photo-1563720223185-11003d516935?w=600&auto=format&fit=crop&q=80', 0, 1),

(9, 2, 7, 'Jogo de 4 Velas de Ignição Iridium Tough Denso', 'velas-ignicao-iridium-denso-toyota', 'VFXE28', 'Denso', 'Elétrodo ultrafino de irídio de 0.4mm com ponteira de platina para ignição potente e durabilidade superior até 100.000km.', 'Rosca: M14x1.25\nComprimento da rosca: 26.5mm\nAbertura de chave: 16mm\nMaterial elétrodo: Iridium & Platinum', 'Toyota Yaris 1.33 / 1.5 Hybrid (2011-2023)\nToyota Auris / Corolla 1.6 Valvematic / 1.8 Hybrid', 44.00, 38.00, 30, 'https://images.unsplash.com/photo-1542282088-72c9c27ed0cd?w=600&auto=format&fit=crop&q=80', 0, 1),

(10, 8, 6, 'Válvula EGR com Radiador de Recirculação Valeo', 'valvula-egr-radiador-valeo-peugeot-16hdi', '700444', 'Valeo', 'Conjunto elétrico de válvula EGR com radiador em inox para controlo rigoroso de emissões de óxidos de azoto (NOx).', 'Modo de operação: Elétrico\nInclui refrigerador / permutador de calor: Sim\nNorma de emissões: Euro 5 / Euro 6', 'Peugeot 208, 308, 3008 1.6 BlueHDi\nCitroën C3, C4 Picasso 1.6 HDi', 245.00, 219.00, 6, 'https://images.unsplash.com/photo-1508974239320-0a029497e820?w=600&auto=format&fit=crop&q=80', 0, 1),

(11, 1, 8, 'Kit de Pinças de Travão Dianteiras Recondicionadas TRW', 'pincas-travao-dianteiras-trw-seat-ibiza', 'BHN961E', 'TRW Automotive', 'Pinças flutuantes de pistão único de precisão, prontas a montar com guias lubrificadas e purgas protegidas.', 'Diâmetro do pistão: 54mm\nEspessura máx. do disco: 22mm\nLado de montagem: Eixo dianteiro Esq + Dir', 'Seat Ibiza IV / V 1.0 TSI / 1.4 TDI\nVolkswagen Polo 6R / 6C\nSkoda Fabia III', 119.00, NULL, 7, 'https://images.unsplash.com/photo-1511919884226-fd3cad34687c?w=600&auto=format&fit=crop&q=80', 0, 1),

(12, 3, 4, 'Jogo de 2 Braços de Suspensão com Rótulas Lemförder', 'bracos-suspensao-lemforder-vw-golf', '37805 01', 'Lemförder', 'Braços oscilantes triangulares em aço forjado de alta resistência com casquilhos vulcanizados pré-instalados.', 'Material: Chapa de aço estampada\nEixo: Dianteiro inferior\nInclui rótula de suspensão: Sim', 'Volkswagen Golf VII, Passat B8\nAudi A3 8V\nSeat Leon 5F', 112.50, 98.00, 14, 'https://images.unsplash.com/photo-1502877338535-766e1452684a?w=600&auto=format&fit=crop&q=80', 0, 1),

(13, 5, 1, 'Líquido de Refrigeração Concentrado Motul Inugel Expert 5L', 'liquido-refrigeracao-motul-inugel-5l', '102924', 'Motul', 'Anticongelante híbrido de tecnologia híbrida OAT com proteção anticorrosão e anticavitação até -37°C.', 'Volume: 5 Litros\nCor: Azul escuro\nBase: Monoetilenoglicol com inibidores orgânicos\nNorma: G11 / G12', 'Universal para motores a gasolina e diesel BMW, Mercedes, Grupo VAG e Asiáticos.', 28.90, 23.50, 35, 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=600&auto=format&fit=crop&q=80', 0, 1),

(14, 4, 1, 'Filtro de Ar de Alto Fluxo Lavável K&N Performance', 'filtro-ar-kn-performance-bmw-f30', '33-2997', 'K&N Engineering', 'Filtro de ar desportivo em gaze de algodão lavável e reutilizável com ganho comprovado de fluxo e proteção de admissão.', 'Tipo de filtro: Filtro plano desportivo\nLavável e reutilizável: Sim (Garantia 1 Milhão de Km)\nDimensões: 235 x 205 mm', 'BMW 316d, 318d, 320d, 420d (F30, F31, F32, F36)', 79.00, NULL, 11, 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600&auto=format&fit=crop&q=80', 1, 1);

-- 5. Notícias e Artigos de Manutenção Automóvel
INSERT INTO `news` (`id`, `title`, `slug`, `category`, `author_id`, `excerpt`, `content`, `image_url`, `views`, `is_published`, `published_at`) VALUES
(1, 'Como Saber Quando Mudar as Pastilhas e Discos de Travão: Sintomas de Desgaste', 'quando-mudar-pastilhas-discos-travao', 'Dicas de Manutenção', 1, 'Aprenda a detetar os sinais visuais e sonoros de desgaste nos travões do seu carro antes que comprometam a sua segurança na estrada.', '<p>O sistema de travagem é o elemento de segurança ativa mais crítico do seu veículo. Conhecer o estado real das pastilhas e dos discos previne avarias graves e acidentes.</p><h3>1. Ruídos metálicos e chiados ao travar</h3><p>Muitas pastilhas de travão modernas incluem uma lâmina avisadora em chapa metálica. Quando a pastilha atinge a espessura limite (cerca de 2mm a 3mm), este avisador entra em contacto direto com o disco, emitindo um chiar estridente para alertar o condutor.</p><h3>2. Vibrações no volante ou pedal de travão</h3><p>Se ao travar a velocidades superiores a 80 km/h sente o volante ou o pedal a trepidar, é provável que os discos de travão estejam empenados devido a choque térmico ou aperto excessivo das porles das rodas com pistola de impacto.</p><h3>3. Distância de travagem aumentada</h3><p>Se sente que precisa de exercer mais força no pedal do travão ou a resposta é esponjosa, proceda de imediato à verificação do nível e estado de degradação do óleo de travões (DOT4 / DOT5.1) bem como da espessura dos discos.</p><p><strong>Conselho de oficina:</strong> Substitua sempre as pastilhas aos pares por cada eixo (dianteiro ou traseiro) e verifique se as pinças deslizam livremente nos respetivos casquilhos lubrificados.</p>', 'https://images.unsplash.com/photo-1600790142055-619df03207e6?w=800&auto=format&fit=crop&q=80', 342, 1, '2026-10-15 10:30:00'),

(2, 'Óleo 5W-30 vs 0W-20: Qual a Importância da Norma do Fabricante?', 'oleo-motor-5w30-vs-0w20-normas-fabricante', 'Guias Técnicos', 1, 'Descubra por que a viscosidade correta e as homologações (ex: VW 507.00, BMW LL-04) protegem o turbo e o filtro de partículas (DPF).', '<p>Escolher o óleo certo para o motor não é apenas escolher a marca ou a viscosidade SAE no rótulo da embalagem. É crucial respeitar a especificação do construtor automóvel.</p><h3>O que significam os números?</h3><p>No caso de um óleo 5W-30, o "5W" (Winter) representa o comportamento e fluidez a baixas temperaturas no arranque a frio, enquanto o "30" representa a resistência da película de lubrificação à temperatura de funcionamento normal (100°C).</p><h3>A proteção do Filtro de Partículas (DPF / FAP)</h3><p>Os motores a diesel modernos com filtro de partículas exigem óleos com baixo teor de cinzas sulfatadas, fósforo e enxofre (designados óleos Low-SAPS ou Mid-SAPS, de norma ACEA C2/C3). O uso de um óleo inadequado pode obstruir prematuramente o filtro de partículas, originando despesas avultadas de reparação.</p><p>Consulte sempre o manual do proprietário da sua viatura ou o nosso localizador de peças para garantir a compatibilidade com a norma oficial (MB 229.51, VW 504.00/507.00, BMW Longlife-04).</p>', 'https://images.unsplash.com/photo-1615906655593-ad0386982a0f?w=800&auto=format&fit=crop&q=80', 189, 1, '2026-10-22 14:15:00'),

(3, 'Kit de Distribuição: Quando Trocar para Evitar a Quebra do Motor?', 'kit-distribuicao-quando-trocar-evitar-danos', 'Dicas de Manutenção', 1, 'A correia de distribuição é o coração da sincronização do motor. Saiba quais os prazos de quilometragem e tempo recomendados.', '<p>A rotura da correia de distribuição em andamento é um dos pesadelos mecânicos mais caros que um condutor pode enfrentar, provocando o choque dos pistões contra as válvulas do motor.</p><h3>Intervalos de mudança: Anos vs Quilómetros</h3><p>A substituição da correia não depende apenas dos quilómetros percorridos. A borracha e as fibras de reforço envelhecem e perdem elasticidade com o tempo, calor e vapores de óleo. De uma forma geral, os fabricantes recomendam a troca entre <strong>5 a 8 anos</strong> ou entre <strong>100.000 km e 180.000 km</strong>, consoante o que for atingido primeiro.</p><h3>Substitua sempre a bomba de água e tensores</h3><p>Ao realizar a intervenção, é essencial instalar o kit completo que inclui rolamento tensor, polias guia e bomba de água. Na maioria dos carros, a bomba de água é acionada pela própria correia dentada. Se a bomba gripar semanas após colocar a correia nova, a correia será destruída de igual forma.</p>', 'https://images.unsplash.com/photo-1517524206127-48bbd363f3d7?w=800&auto=format&fit=crop&q=80', 276, 1, '2026-10-29 09:00:00'),

(4, 'Tendências da Indústria Automóvel: Peças Remanufaturadas e Sustentabilidade', 'sustentabilidade-pecas-automoveis-remanufaturadas', 'Novidades do Setor', 1, 'Como os turbos, caixas de velocidades e alternadores recondicionados com garantia original reduzem a pegada ecológica.', '<p>A economia circular está a transformar a cadeia de fornecimento de peças sobresselentes no mercado europeu pós-venda.</p><h3>O que é uma peça remanufaturada oficial?</h3><p>Ao contrário de uma peça usada de sucata, uma peça remanufaturada (remanufactured) por marcas de topo como a Bosch, Valeo ou TRW é totalmente desmontada em fábrica, limpa quimicamente, tendo todos os seus componentes de desgaste substituídos por peças novas genuínas, sendo calibrada em bancada de ensaio com garantia equivalente a uma nova.</p><p>Esta prática reduz em até 80% o consumo de energia e matérias-primas face à produção de uma peça de raiz, oferecendo ao condutor poupanças entre 30% e 50% com total fiabilidade.</p>', 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=800&auto=format&fit=crop&q=80', 142, 1, '2026-11-02 16:40:00');

-- 6. Encomendas de Demonstração
INSERT INTO `orders` (`id`, `order_number`, `user_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_nif`, `shipping_address`, `shipping_postal_code`, `shipping_city`, `payment_method`, `payment_reference`, `payment_status`, `subtotal`, `tax_amount`, `shipping_cost`, `total`, `status`, `notes`, `created_at`) VALUES
(1, 'ORD-2026-7841', 2, 'João Silva (Cliente)', 'cliente@autoparts.pt', '+351 923 456 789', '234567890', 'Rua dos Mecânicos, 42, 2º Dto', '1000-123', 'Lisboa', 'mbway', '923456789 (MB WAY Confirmado)', 'paid', 184.80, 42.50, 0.00, 227.30, 'shipped', 'Entregar preferencialmente da parte da tarde.', '2026-11-03 11:20:00'),
(2, 'ORD-2026-8924', 2, 'João Silva (Cliente)', 'cliente@autoparts.pt', '+351 923 456 789', '234567890', 'Rua dos Mecânicos, 42, 2º Dto', '1000-123', 'Lisboa', 'multibanco', 'Entidade: 21245 | Ref: 849 192 481', 'pending', 69.90, 16.08, 4.90, 90.88, 'pending', 'Aguardando validação da referência Multibanco.', '2026-11-05 14:10:00');

-- 7. Itens das Encomendas de Demonstração
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_price`, `quantity`, `subtotal`) VALUES
(1, 1, 1, 'Discos de Travão Dianteiros Ventilados Brembo Max', 129.90, 1, 129.90),
(2, 1, 2, 'Jogo de Pastilhas de Travão Dianteiras Ferodo Racing', 54.90, 1, 54.90),
(3, 2, 6, 'Kit 4 Filtros Bosch (Óleo, Ar, Combustível e Habitáculo)', 69.90, 1, 69.90);

