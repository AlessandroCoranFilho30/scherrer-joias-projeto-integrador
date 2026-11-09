<?php
require __DIR__ . '/includes/app.php';
requireLogin();

$userId = (int) currentUser()['id'];
if (requestMethod() === 'POST') {
    verifyCsrf();
    $productId = postString('produto_id');
    $action = postString('acao');
    if ($action === 'remover') {
        $statement = db()->prepare('DELETE FROM cart_items WHERE user_id = :user_id AND product_id = :product_id');
        $statement->execute([':user_id' => $userId, ':product_id' => $productId]);
    } elseif ($action === 'atualizar') {
        $quantity = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);
        if ($quantity === false || $quantity === null || $quantity < 1 || $quantity > 20) {
            $_SESSION['flash'] = 'A quantidade deve estar entre 1 e 20.';
            redirect('carrinho.php');
        }
        $product = productById($productId);
        if ($product === null) {
            $_SESSION['flash'] = 'Este produto não existe mais no catálogo.';
            redirect('carrinho.php');
        }
        if ($quantity > (int) $product['stock']) {
            $_SESSION['flash'] = 'Temos apenas ' . (int) $product['stock'] . ' unidade(s) de '
                . $product['name'] . ' em estoque.';
            redirect('carrinho.php');
        }
        $statement = db()->prepare('UPDATE cart_items SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id');
        $statement->execute([':quantity' => $quantity, ':user_id' => $userId, ':product_id' => $productId]);
    } else {
        http_response_code(400);
        exit('Ação inválida.');
    }
    $_SESSION['flash'] = 'Carrinho atualizado.';
    redirect('carrinho.php');
}

$items = cartItems($userId);
$total = array_sum(array_map(
    static fn (array $item): int => (int) $item['price_cents'] * (int) $item['quantity'],
    $items
));
renderHeader('Carrinho');
?>
<section class="section">
    <p class="eyebrow">Sua seleção</p>
    <h1>Carrinho de compras</h1>
    <?php if ($items === []): ?>
        <p class="empty-state">Seu carrinho está vazio.</p>
        <a class="button" href="catalogo.php">Explorar joias</a>
    <?php else: ?>
        <div class="cart-list">
            <?php foreach ($items as $item): ?>
                <article class="cart-item">
                    <img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>">
                    <div class="cart-item-info">
                        <h2><?= e($item['name']) ?></h2>
                        <p><?= money((int) $item['price_cents']) ?> cada</p>
                    </div>
                    <form method="post" class="quantity-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="produto_id" value="<?= e($item['id']) ?>">
                        <input type="hidden" name="acao" value="atualizar">
                        <label class="sr-only" for="q-<?= e($item['id']) ?>">Quantidade de <?= e($item['name']) ?></label>
                        <input id="q-<?= e($item['id']) ?>" type="number" name="quantidade" min="1" max="20" value="<?= (int) $item['quantity'] ?>">
                        <button class="button button-outline" type="submit">Atualizar</button>
                    </form>
                    <p class="price"><?= money((int) $item['price_cents'] * (int) $item['quantity']) ?></p>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="produto_id" value="<?= e($item['id']) ?>">
                        <input type="hidden" name="acao" value="remover">
                        <button class="link-button" type="submit">Remover</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="cart-total"><strong>Total</strong><strong><?= money($total) ?></strong></div>
        <p class="disclaimer">Compra demonstrativa. Nenhum pagamento será efetuado.</p>
        <a class="button" href="finalizar-compra.php">Continuar para compra fictícia</a>
    <?php endif; ?>
</section>
<?php renderFooter(); ?>
