# 🚗 AutoPeças Pro - Loja Online de Peças de Carros & Gestão de Notícias

> **Trabalho Académico / Projeto Full-Stack Sénior em PHP e MySQL**  
> Desenvolvido segundo as melhores práticas de arquitetura de software, segurança web e usabilidade.

---

## 📋 1. Visão Geral do Projeto

O **AutoPeças Pro** é uma solução web completa para comércio eletrónico especializado em componentes e peças sobresselentes para automóveis, complementado com um **módulo de notícias e dicas técnicas de manutenção**.

A aplicação foi concebida de raiz sem frameworks pesadas de backend para evidenciar domínio pleno de **PHP Nativo Moderno (PHP 7.4 / 8.x)**, **MySQL Relacional com PDO**, arquitetura modular limpa e conformidade com as diretrizes de segurança **OWASP** (prevenção contra SQL Injection, CSRF, XSS e Session Fixation).

---

## 📁 2. Estrutura Organizada do Projeto

```text
/
├── admin/                     # Painel de Administração / Backoffice (Acesso Restrito)
│   ├── brands.php             # CRUD de Marcas de Carros (BMW, Mercedes, VW, etc.)
│   ├── categories.php         # CRUD de Categorias de Peças
│   ├── index.php              # Dashboard com KPIs, Vendas, Gráficos e Alertas de Stock
│   ├── news.php               # Gestão de Notícias e Dicas de Manutenção
│   ├── news-form.php          # Formulário de Criação e Edição de Artigos
│   ├── orders.php             # Gestão de Encomendas e Filtros por Estado
│   ├── order-detail.php       # Detalhes da Encomenda, Faturação e Atualização de Estado
│   ├── products.php           # Gestão de Peças, Stock, Preços e Promoções
│   ├── product-form.php       # Formulário de Peças (Especificações, OEM, Compatibilidade)
│   └── users.php              # Gestão de Utilizadores e Níveis de Acesso (RBAC)
├── assets/                    # Ficheiros Estáticos de Frontend
│   ├── css/
│   │   └── style.css          # Estilos customizados, tema automóvel e animações
│   └── js/
│       └── app.js             # Validações, interações do carrinho, toasts e alertas
├── config/                    # Configurações do Sistema
│   ├── config.php             # Constantes, URLs dinâmicas, sessões, tokens CSRF e IVA
│   └── database.php           # Conexão PDO Singleton com tratamento de exceções
├── database/                  # Base de Dados Relacional
│   └── schema.sql             # Estrutura completa (DDL), chaves estrangeiras e dados iniciais (SEEDS)
├── includes/                  # Componentes e Módulos Reutilizáveis
│   ├── admin_footer.php       # Rodapé do Backoffice
│   ├── admin_header.php       # Cabeçalho e Barra Lateral de Navegação do Backoffice
│   ├── auth.php               # Funções de Autenticação, Proteção de Rotas e Controlo de Sessão
│   ├── footer.php             # Rodapé da Loja Pública com Contactos e Links
│   ├── functions.php          # Utilitários, Sanitização XSS, Formatação Moeda/Data
│   └── header.php             # Cabeçalho Principal com Barra de Pesquisa, Menu e Carrinho
├── uploads/                   # Pasta para ficheiros e imagens enviadas
├── cart.php                   # Carrinho de Compras (Adicionar, Alterar Qtd, Remover, Limpar)
├── checkout.php               # Finalização de Encomenda Segura com Simulação de Pagamentos
├── index.php                  # Página Inicial (Hero, Seletor de Peças, Destaques, Notícias)
├── login.php                  # Autenticação de Utilizadores (com atalhos rápidos de teste)
├── logout.php                 # Término de Sessão Seguro
├── news.php                   # Módulo Público de Notícias e Dicas Automóveis
├── news-detail.php            # Visualizador de Artigo Completo com Recomendações
├── order-success.php          # Página de Sucesso com Resumo e Referências de Pagamento
├── order-view.php             # Visualizador de Encomenda / Recibo Digital para Clientes
├── product-detail.php         # Ficha Técnica da Peça, OEM e Compatibilidade de Veículos
├── products.php               # Catálogo Geral com Filtros (Marca, Categoria, Preço, Stock)
├── profile.php                # Área de Cliente (Dados Pessoais e Histórico de Encomendas)
├── register.php               # Registo de Novos Clientes
├── setup.php                  # Assistente Automático de Instalação da Base de Dados
└── README.md                  # Documentação Completa do Projeto
```

---

## 🗄️ 3. Modelo Relacional da Base de Dados (`/database/schema.sql`)

A base de dados `autoparts_db` está normalizada (3ª Forma Normal) e implementa integridade referencial:

1. **`users`**: Contas de utilizadores com nível de acesso (`admin` vs `customer`), password cifrada com bcrypt, dados de contacto e faturação (NIF, morada).
2. **`categories`**: Categorias de peças (Travões, Motor & Distribuição, Suspensão, Óleos, Filtros, Baterias, etc.) com slug e ícones.
3. **`brands`**: Marcas de automóveis compatíveis (BMW, Mercedes-Benz, Audi, Volkswagen, Renault, Peugeot, Toyota, Seat).
4. **`products`**: Peças automóveis com chave estrangeira para `categories` e `brands`, referência OEM, fabricante, preço normal, preço de desconto, stock real, ficha técnica, compatibilidade e flag de destaque.
5. **`orders`**: Registo de encomendas com número de encomenda único (`ORD-2026-XXXX`), morada de entrega, método de pagamento, referências de liquidação, subtotal, IVA (23%), portes e estado (`pending`, `processing`, `shipped`, `completed`, `cancelled`).
6. **`order_items`**: Detalhe de cada produto encomendado com preço histórico gravado no momento da compra e quantidade.
7. **`news`**: Artigos do módulo de notícias com título, slug, categoria, autor (`author_id`), resumo, conteúdo formatado, imagem e contador de visualizações.

---

## 🔑 4. Credenciais Pré-configuradas (Trabalho Académico)

Para facilitar a avaliação, o ficheiro `database/schema.sql` já inclui duas contas prontas:

| Perfil | Email | Palavra-passe | Nível de Acesso |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin@autoparts.pt` | `admin123` | **Total (Backoffice + Loja)** |
| **Cliente Teste** | `cliente@autoparts.pt` | `cliente123` | **Área de Cliente / Loja** |

> 💡 *Na página `login.php`, existem botões de 1-clique para preenchimento automático destas contas.*

---

## 🚀 5. Instruções de Instalação e Execução

### Opção A: Utilizando XAMPP (Recomendado)

1. **Copiar a pasta do projeto** para a pasta `htdocs` do seu XAMPP:
   - Caminho: `C:\xampp\htdocs\trabalho de redes` (ou outro nome pretendido).
2. **Iniciar o Apache e o MySQL** no painel de controlo do XAMPP (*XAMPP Control Panel*).
3. **Criar a Base de Dados**:
   - **Método 1 (Super Rápido)**: Abra o browser em `http://localhost/trabalho%20de%20redes/setup.php` e clique no botão **"Instalar Base de Dados com 1-Clique"**. O script cria automaticamente a base de dados `autoparts_db` e importa todas as tabelas e dados de teste.
   - **Método 2 (phpMyAdmin)**: Abra `http://localhost/phpmyadmin/`, clique em *Importar* e selecione o ficheiro `database/schema.sql`.
4. Aceda à loja em: `http://localhost/trabalho%20de%20redes/`.

---

### Opção B: Utilizando o Servidor Embutido do PHP

1. Certifique-se de que o servidor MySQL está ativo na porta 3306.
2. Abra um terminal na pasta do projeto e inicie o servidor:
   ```bash
   php -S localhost:8000
   ```
3. Aceda a `http://localhost:8000/setup.php` para instalar a base de dados em 1 clique.
4. Navegue na loja em `http://localhost:8000/`.

---

## 🛡️ 6. Segurança e Melhores Práticas Implementadas

- **Proteção contra SQL Injection**: 100% das consultas utilizam `PDO` com *Prepared Statements* e parâmetros vinculados (`bindValue`/`execute`). O modo de emulação de *prepares* está desligado.
- **Proteção contra Cross-Site Scripting (XSS)**: Todos os outputs dinâmicos no frontend são sanitizados através da função auxiliar `e()` com `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- **Proteção contra Cross-Site Request Forgery (CSRF)**: Todos os formulários sensíveis (login, registo, carrinho, checkout, alterações de perfil e todas as ações administrativas de criação/edição/eliminação) geram e validam tokens criptograficamente seguros com `hash_equals()`.
- **Criptografia Segura de Palavras-passe**: Armazenamento com hashing unidirecional moderno através de `password_hash()` com algoritmo Bcrypt.
- **Prevenção de Session Fixation**: Chamada a `session_regenerate_id(true)` no momento do login e logout.
- **Controlo de Acesso Baseado em Papéis (RBAC)**: Proteção rigorosa no diretório `/admin/` via middleware `require_admin()` que barra qualquer utilizador sem papel de administrador.
- **Transações Atómicas (ACID)**: O processo de checkout utiliza `$db->beginTransaction()` e `$db->commit()` com rollback em caso de falha, garantindo que o registo da encomenda, os itens comprados e o decremento de stock nos produtos ocorrem de forma consistente.

---

## 📦 7. Funcionalidades Principais Desenvolvidas

### 🛍️ Loja Online de Peças
- **Barra de Pesquisa Global**: Procura em tempo real por nome, fabricante ou referência OEM.
- **Filtros Avançados**: Filtro dinâmico por Categoria, Marca de Automóvel, Intervalo de Preços (€), Disponibilidade de Stock e Promoções ativas.
- **Ficha Detalhada do Produto**: Imagens em alta resolução, especificações técnicas, compatibilidade com modelos de viaturas, estado de stock em tempo real e cálculo de poupança promocional.
- **Carrinho de Compras Modular**: Gestão de quantidades com recalibração de limites de stock, remoção individual, esvaziamento total, cálculo automático de IVA (23%) e portes de envio gratuitos acima de 50,00 €.
- **Checkout e Faturação**: Formulário com morada, validação de NIF e simulação de múltiplos métodos de pagamento (Multibanco, MB WAY, Cartão de Crédito e Transferência Bancária).
- **Recibo / Fatura Digital**: Resumo completo com botão para impressão física ou PDF.

### 📰 Módulo de Gestão de Notícias
- **Página Pública de Notícias**: Artigos organizados por categorias (*Dicas de Manutenção*, *Guias Técnicos*, *Novidades do Setor*).
- **Contador Automático de Visualizações**: Incremento automático a cada leitura de artigo.
- **Recomendação Cruzada**: Cada artigo sugere peças do catálogo relacionadas com a manutenção abordada.
- **Backoffice de Notícias**: Gestão completa (CRUD), editor de texto com formatação HTML e controlo de publicação (Publicado / Rascunho).

### ⚙️ Painel de Administração (Backoffice)
- **Dashboard com Métricas em Tempo Real**: Faturação total, número de encomendas, total de artigos e alerta de stock baixo (&le; 8 unidades).
- **Gestão de Peças**: Criação, edição, alteração de stock, preços normais e promocionais, eliminação e proteção de integridade com encomendas históricas.
- **Gestão de Categorias e Marcas**: Manutenção integral das categorias e marcas de automóveis da loja.
- **Gestão de Encomendas**: Consulta detalhada de morada, itens comprados e atualização de estado operacional (*Pendente*, *Em Processamento*, *Enviado*, *Concluído*, *Cancelado*).
- **Gestão de Utilizadores**: Listagem de utilizadores registados e promoção/despromoção de privilégios de Administrador.

