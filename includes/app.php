<?php
declare(strict_types=1);

session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

const CATEGORIES = [
    'aneis' => 'Anéis',
    'brincos' => 'Brincos',
    'pulseiras' => 'Pulseiras',
    'chokers' => 'Colares e chokers',
];

// Hash fixo usado no login apenas para equalizar o tempo de resposta
// quando o e-mail informado não existe, evitando enumeração de contas.
const DUMMY_PASSWORD_HASH = '$2y$12$CSl80ZCYo4tRL7rc9OXWhOh/RHsxCp0niWPJwg3lZBxMDybJN3gXu';

function categoryLabel(string $category): string
{
    return CATEGORIES[$category] ?? ucfirst($category);
}

function db(): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $dataDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($dataDirectory) && !mkdir($dataDirectory, 0775, true) && !is_dir($dataDirectory)) {
        throw new RuntimeException('Não foi possível criar o diretório do banco de dados.');
    }

    $connection = new PDO('sqlite:' . $dataDirectory . DIRECTORY_SEPARATOR . 'loja.sqlite');
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $connection->exec('PRAGMA foreign_keys = ON');
    $connection->exec('PRAGMA journal_mode = WAL');
    initializeDatabase($connection);

    return $connection;
}

function initializeDatabase(PDO $connection): void
{
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL COLLATE NOCASE UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS products (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            category TEXT NOT NULL,
            price_cents INTEGER NOT NULL CHECK (price_cents >= 0),
            image TEXT NOT NULL,
            description TEXT NOT NULL,
            stock INTEGER NOT NULL DEFAULT 20 CHECK (stock >= 0)
        );
        CREATE TABLE IF NOT EXISTS cart_items (
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            product_id TEXT NOT NULL REFERENCES products(id),
            quantity INTEGER NOT NULL CHECK (quantity BETWEEN 1 AND 20),
            PRIMARY KEY (user_id, product_id)
        );
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL REFERENCES users(id),
            total_cents INTEGER NOT NULL,
            fulfillment TEXT NOT NULL CHECK (fulfillment IN (\'retirada\', \'entrega\')),
            address TEXT,
            status TEXT NOT NULL DEFAULT \'ficticio\',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
            product_id TEXT NOT NULL,
            product_name TEXT NOT NULL,
            unit_price_cents INTEGER NOT NULL,
            quantity INTEGER NOT NULL
        );'
    );

    $products = [
        ['anel-fio-esfera', 'Anel de Fio com Esfera', 'aneis', 1500, 'img/anel-fio-esfera.jpeg', 'Anel de aro delicado com esfera central lisa e acabamento elegante. A peça ajustável combina leveza e sofisticação para usar sozinha ou em composições.'],
        ['anel-caracol-ajustavel', 'Anel Caracol Ajustável', 'aneis', 1290, 'img/anel-caracol-ajustavel.jpeg', 'Anel ajustável com desenho em espiral, uma peça versátil e marcante para completar produções do dia a dia.'],
        ['argola-tubo-torcido', 'Brinco de Argola de Tubo Torcido', 'brincos', 7000, 'img/brinco-argola-tubo-torcido.jpeg', 'Argola de tubo torcido com brilho delicado e design contemporâneo. Um clássico fácil de combinar.'],
        ['argola-tubo-bold', 'Argola Tubo Bold', 'brincos', 1590, 'img/brinco-argola-tubo-bold.jpeg', 'Argola de presença com linhas arredondadas e visual moderno, ideal para destacar qualquer produção.'],
        ['choker-lisa-abaulada', 'Choker Lisa Abaulada', 'chokers', 2800, 'img/choker-lisa-abaulada.jpeg', 'Choker de linhas limpas e acabamento abaulado. Uma peça versátil para usar sozinha ou em um mix de colares.'],
        ['gargantilha-elo-oval', 'Gargantilha de Elo Oval', 'chokers', 7999, 'img/gargantilha-elo-oval.jpeg', 'Gargantilha com elos ovais e visual elegante, feita para dar destaque a composições especiais.'],
        ['bracelete-tubo-eliptico', 'Bracelete Tubo Elíptico Articulado', 'pulseiras', 2300, 'img/bracelete-tubo-eliptico-articulado.jpeg', 'Bracelete articulado de tubo elíptico com design limpo e acabamento elegante para acompanhar diferentes ocasiões.'],
        ['bracelete-tubo-encaixe', 'Bracelete de Tubo de Encaixe', 'pulseiras', 2900, 'img/bracelete-tubo-encaixe.jpeg', 'Bracelete de encaixe com visual contemporâneo e estrutura confortável. Uma peça versátil para usar todos os dias.'],
    ];

    $insert = $connection->prepare(
        'INSERT OR IGNORE INTO products (id, name, category, price_cents, image, description)
         VALUES (:id, :name, :category, :price, :image, :description)'
    );
    foreach ($products as $product) {
        $insert->execute([
            ':id' => $product[0],
            ':name' => $product[1],
            ':category' => $product[2],
            ':price' => $product[3],
            ':image' => $product[4],
            ':description' => $product[5],
        ]);
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function postString(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}

function requestMethod(): string
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    return is_string($method) ? strtoupper($method) : 'GET';
}

function money(int $cents): string
{
    return 'R$ ' . number_format($cents / 100, 2, ',', '.');
}

function formatDateTime(string $sqliteTimestamp): string
{
    // CURRENT_TIMESTAMP do SQLite é gravado em UTC; converte para o horário de São Paulo.
    $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $sqliteTimestamp, new DateTimeZone('UTC'));
    if ($date === false) {
        return $sqliteTimestamp;
    }
    return $date->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(string $returnTo = 'carrinho.php'): void
{
    if (currentUser() === null) {
        $_SESSION['flash'] = 'Entre na sua conta para continuar.';
        redirect('entrar.php?retorno=' . rawurlencode($returnTo));
    }
}

function flashMessage(): ?string
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_string($message) ? $message : null;
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrfToken(), $submitted)) {
        http_response_code(400);
        exit('A sessão do formulário expirou. Volte à página e tente novamente.');
    }
}

function safeReturnPath(mixed $path): string
{
    if (!is_string($path) || str_contains($path, "\r") || str_contains($path, "\n")) {
        return 'index.php';
    }

    $parts = parse_url($path);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['//'])) {
        return 'index.php';
    }

    $allowed = ['index.php', 'carrinho.php', 'catalogo.php', 'produto.php', 'historico.php'];
    $script = ltrim($parts['path'] ?? '', '/');
    if (!in_array($script, $allowed, true)) {
        return 'index.php';
    }

    return $script . (isset($parts['query']) ? '?' . $parts['query'] : '');
}

function productById(string $id): ?array
{
    $statement = db()->prepare('SELECT * FROM products WHERE id = :id');
    $statement->execute([':id' => $id]);
    $product = $statement->fetch();
    return $product === false ? null : $product;
}

function cartItems(int $userId): array
{
    $statement = db()->prepare(
        'SELECT products.*, cart_items.quantity
         FROM cart_items
         JOIN products ON products.id = cart_items.product_id
         WHERE cart_items.user_id = :user_id
         ORDER BY products.name'
    );
    $statement->execute([':user_id' => $userId]);
    return $statement->fetchAll();
}

function renderHeader(string $title): void
{
    $user = currentUser();
    $message = flashMessage();
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#e2b791">
        <title><?= e($title) ?> | Scherrer Joias</title>
        <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32.png">
        <link rel="stylesheet" href="css/loja.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    </head>
    <body>
    <header class="site-header">
        <a href="index.php" aria-label="Scherrer Joias - início"><img class="logo" src="img/logo.png" alt="Scherrer Joias"></a>
        <form class="search" action="catalogo.php" method="get">
            <label class="sr-only" for="busca">Pesquisar produtos</label>
            <input id="busca" type="search" name="busca" placeholder="Pesquisar produtos">
            <button type="submit">Buscar</button>
        </form>
        <nav class="account-nav" aria-label="Conta e carrinho">
            <?php if ($user !== null): ?>
                <span>Olá, <?= e((string) $user['name']) ?></span>
                <a href="carrinho.php">Carrinho</a>
                <a href="historico.php">Minhas compras</a>
                <form action="sair.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <button class="link-button" type="submit">Sair</button>
                </form>
            <?php else: ?>
                <a href="entrar.php">Entrar</a>
                <a href="cadastrar.php">Criar conta</a>
                <a href="carrinho.php">Carrinho</a>
            <?php endif; ?>
        </nav>
    </header>
    <nav class="category-nav" aria-label="Categorias">
        <a href="catalogo.php">Todas as joias</a>
        <a href="catalogo.php?categoria=aneis">Anéis</a>
        <a href="catalogo.php?categoria=brincos">Brincos</a>
        <a href="catalogo.php?categoria=pulseiras">Pulseiras</a>
        <a href="catalogo.php?categoria=chokers">Colares e chokers</a>
    </nav>
    <main class="page">
        <?php if ($message !== null): ?>
            <p class="notice" role="status"><?= e($message) ?></p>
        <?php endif; ?>
    <?php
}

function renderFooter(): void
{
    ?>
    </main>
    <footer class="site-footer">
        <div class="footer-badges">
            <section aria-labelledby="payment-title">
                <h2 id="payment-title">Formas de pagamento (ilustrativas)</h2>
                <div class="footer-badge-list">
                    <img src="img/visa.png" alt="Visa" width="60" height="40">
                    <img src="img/mastercard.svg" alt="Mastercard" width="60" height="40">
                    <img src="img/elo.png" alt="Elo" width="60" height="40">
                    <img src="img/pix.webp" alt="Pix" width="60" height="40">
                </div>
            </section>
            <section aria-labelledby="security-title">
                <h2 id="security-title">Selos e canais informativos</h2>
                <div class="footer-badge-list">
                    <img class="footer-security-badge" src="img/reclameaqui.jpg" alt="Reclame Aqui" width="60" height="60">
                    <img class="footer-security-badge" src="img/alphassl.jpg" alt="Alpha SSL" width="60" height="60">
                    <img class="footer-security-badge" src="img/google.jpg" alt="Google" width="60" height="60">
                </div>
            </section>
        </div>
        <p>Scherrer Joias · Loja demonstrativa para fins educativos.</p>
        <p>As compras deste site são fictícias e não geram cobrança.</p>
    </footer>
    </body>
    </html>
    <?php
}
