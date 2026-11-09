<?php
require __DIR__ . '/includes/app.php';
requireLogin();

$userId = (int) currentUser()['id'];
$items = cartItems($userId);
if ($items === []) {
    $_SESSION['flash'] = 'Adicione ao menos um produto antes de finalizar a compra.';
    redirect('carrinho.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $fulfillment = postString('entrega');
    $address = trim(postString('endereco'));
    if (!in_array($fulfillment, ['retirada', 'entrega'], true)) {
        $error = 'Selecione retirada na loja ou entrega fictícia.';
    } elseif ($fulfillment === 'entrega' && ($address === '' || preg_match('/^.{1,250}$/us', $address) !== 1)) {
        $error = 'Informe um endereço válido com até 250 caracteres.';
    } else {
        $total = array_sum(array_map(
            static fn (array $item): int => (int) $item['price_cents'] * (int) $item['quantity'],
            $items
        ));
        $connection = db();
        $connection->beginTransaction();
        try {
            $statement = $connection->prepare(
                'INSERT INTO orders (user_id, total_cents, fulfillment, address)
                 VALUES (:user_id, :total, :fulfillment, :address)'
            );
            $statement->execute([
                ':user_id' => $userId,
                ':total' => $total,
                ':fulfillment' => $fulfillment,
                ':address' => $fulfillment === 'entrega' ? $address : null,
            ]);
            $orderId = (int) $connection->lastInsertId();
            $insertItem = $connection->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, unit_price_cents, quantity)
                 VALUES (:order_id, :product_id, :name, :price, :quantity)'
            );
            foreach ($items as $item) {
                $insertItem->execute([
                    ':order_id' => $orderId,
                    ':product_id' => $item['id'],
                    ':name' => $item['name'],
                    ':price' => $item['price_cents'],
                    ':quantity' => $item['quantity'],
                ]);
            }
            $delete = $connection->prepare('DELETE FROM cart_items WHERE user_id = :user_id');
            $delete->execute([':user_id' => $userId]);
            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $exception;
        }

        renderHeader('Compra fictícia concluída');
        ?>
        <section class="auth-card">
            <p class="eyebrow">Pedido #<?= $orderId ?></p>
            <h1>Compra fictícia concluída!</h1>
            <p>Seu pedido de <?= money($total) ?> foi registrado para <?= $fulfillment === 'retirada' ? 'retirada na loja' : 'entrega no endereço informado' ?>.</p>
            <p>Nenhuma cobrança foi feita. Obrigado por visitar a Scherrer Joias.</p>
            <p><a href="historico.php">Ver minhas compras</a></p>
            <a class="button" href="catalogo.php">Continuar explorando</a>
        </section>
        <?php
        renderFooter();
        exit;
    }
}

$total = array_sum(array_map(
    static fn (array $item): int => (int) $item['price_cents'] * (int) $item['quantity'],
    $items
));
renderHeader('Finalizar compra fictícia');
?>
<section class="auth-card checkout-card">
    <p class="eyebrow">Último passo</p>
    <h1>Finalizar compra fictícia</h1>
    <p>Total do pedido: <strong><?= money($total) ?></strong></p>
    <p class="disclaimer">Esta é uma simulação educativa. Não será solicitado nem processado pagamento.</p>
    <?php if ($error !== null): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label for="entrega">Como deseja receber?</label>
        <select id="entrega" name="entrega" required>
            <option value="retirada">Retirada na loja</option>
            <option value="entrega">Entrega fictícia</option>
        </select>
        <label for="endereco">Endereço (necessário para entrega)</label>
        <textarea id="endereco" name="endereco" maxlength="250" rows="3"></textarea>
        <button class="button" type="submit">Confirmar compra fictícia</button>
    </form>
    <a href="carrinho.php">Voltar ao carrinho</a>
</section>
<?php renderFooter(); ?>
