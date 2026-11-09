<?php
require __DIR__ . '/includes/app.php';
requireLogin('historico.php');

$statement = db()->prepare(
    'SELECT orders.id, orders.total_cents, orders.fulfillment, orders.address, orders.created_at,
            order_items.product_name, order_items.unit_price_cents, order_items.quantity
     FROM orders
     JOIN order_items ON order_items.order_id = orders.id
     WHERE orders.user_id = :user_id
     ORDER BY orders.created_at DESC, orders.id DESC, order_items.id'
);
$statement->execute([':user_id' => currentUser()['id']]);
$rows = $statement->fetchAll();
$orders = [];
foreach ($rows as $row) {
    $orderId = (int) $row['id'];
    if (!isset($orders[$orderId])) {
        $orders[$orderId] = [
            'total_cents' => (int) $row['total_cents'],
            'fulfillment' => $row['fulfillment'],
            'address' => $row['address'],
            'created_at' => $row['created_at'],
            'items' => [],
        ];
    }
    $orders[$orderId]['items'][] = $row;
}

renderHeader('Minhas compras');
?>
<section class="section">
    <p class="eyebrow">Seus pedidos</p>
    <h1>Minhas compras</h1>
    <?php if ($orders === []): ?>
        <p class="empty-state">Você ainda não concluiu nenhuma compra fictícia.</p>
        <a class="button" href="catalogo.php">Explorar joias</a>
    <?php else: ?>
        <div class="order-list">
            <?php foreach ($orders as $id => $order): ?>
                <article class="order-card">
                    <div class="section-heading">
                        <div>
                            <h2>Pedido #<?= $id ?></h2>
                            <p><?= e(formatDateTime($order['created_at'])) ?> · <?= $order['fulfillment'] === 'retirada' ? 'Retirada na loja' : 'Entrega fictícia' ?></p>
                        </div>
                        <strong><?= money($order['total_cents']) ?></strong>
                    </div>
                    <?php if ($order['fulfillment'] === 'entrega' && $order['address'] !== null): ?>
                        <p>Entrega no endereço: <?= e($order['address']) ?></p>
                    <?php endif; ?>
                    <ul>
                        <?php foreach ($order['items'] as $item): ?>
                            <li><?= e($item['product_name']) ?> × <?= (int) $item['quantity'] ?> — <?= money((int) $item['unit_price_cents'] * (int) $item['quantity']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="disclaimer">Pedido demonstrativo: nenhuma cobrança foi realizada.</p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php renderFooter(); ?>
