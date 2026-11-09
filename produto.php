<?php
require __DIR__ . '/includes/app.php';

$id = $_GET['id'] ?? '';
$product = is_string($id) ? productById($id) : null;
if ($product === null) {
    http_response_code(404);
    renderHeader('Produto não encontrado');
    echo '<section class="section"><h1>Produto não encontrado</h1><a href="catalogo.php">Voltar ao catálogo</a></section>';
    renderFooter();
    exit;
}

$stock = (int) $product['stock'];
$error = null;
if (requestMethod() === 'POST') {
    verifyCsrf();
    if (currentUser() === null) {
        $_SESSION['flash'] = 'Entre na sua conta para adicionar produtos ao carrinho.';
        redirect('entrar.php?retorno=' . rawurlencode('produto.php?id=' . $product['id']));
    }

    $quantity = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);
    if ($quantity === false || $quantity === null || $quantity < 1 || $quantity > 20) {
        $error = 'Selecione uma quantidade entre 1 e 20.';
    } elseif ($quantity > $stock) {
        $error = $stock === 0
            ? 'Este produto está esgotado.'
            : 'Temos apenas ' . $stock . ' unidade(s) em estoque.';
    } else {
        $statement = db()->prepare(
            'INSERT INTO cart_items (user_id, product_id, quantity)
             VALUES (:user_id, :product_id, :quantity)
             ON CONFLICT(user_id, product_id) DO UPDATE SET quantity = MIN(20, quantity + excluded.quantity)'
        );
        $statement->execute([
            ':user_id' => currentUser()['id'],
            ':product_id' => $product['id'],
            ':quantity' => $quantity,
        ]);
        $_SESSION['flash'] = 'Produto adicionado ao carrinho.';
        redirect('carrinho.php');
    }
}

renderHeader($product['name']);
?>
<section class="product-detail">
    <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>">
    <div>
        <p class="eyebrow"><?= e(categoryLabel($product['category'])) ?></p>
        <h1><?= e($product['name']) ?></h1>
        <p class="price price-large"><?= money((int) $product['price_cents']) ?></p>
        <p><?= e($product['description']) ?></p>
        <?php if ($stock === 0): ?>
            <p class="stock-note badge-out">Produto esgotado no momento.</p>
        <?php elseif ($stock <= 5): ?>
            <p class="stock-note">Últimas unidades: <?= $stock ?> em estoque.</p>
        <?php else: ?>
            <p class="stock-note">Disponível: <?= $stock ?> unidades em estoque.</p>
        <?php endif; ?>
        <?php if ($error !== null): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <?php if ($stock > 0): ?>
            <form method="post" class="add-to-cart">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <label for="quantidade">Quantidade</label>
                <input id="quantidade" type="number" name="quantidade" min="1" max="<?= min(20, $stock) ?>" value="1" required>
                <button class="button" type="submit">Adicionar ao carrinho</button>
            </form>
        <?php endif; ?>
        <a href="catalogo.php?categoria=<?= e($product['category']) ?>">Voltar à coleção</a>
    </div>
</section>
<?php renderFooter(); ?>
