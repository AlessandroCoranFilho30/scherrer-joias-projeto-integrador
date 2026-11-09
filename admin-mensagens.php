<?php
require __DIR__ . '/includes/app.php';

if (!isAdmin()) {
    $_SESSION['flash'] = 'Área restrita à administração.';
    redirect('index.php');
}

if (requestMethod() === 'POST') {
    verifyCsrf();
    $clientId = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
    $body = trim(postString('mensagem'));
    if ($clientId === false || $clientId === null || $body === '' || mb_strlen($body) > 2000) {
        $_SESSION['flash'] = 'Não foi possível enviar a resposta.';
        redirect('admin-mensagens.php');
    }
    $statement = db()->prepare('SELECT 1 FROM users WHERE id = :id');
    $statement->execute([':id' => $clientId]);
    if ($statement->fetchColumn() === false) {
        $_SESSION['flash'] = 'Cliente não encontrado.';
        redirect('admin-mensagens.php');
    }
    $insert = db()->prepare(
        "INSERT INTO contact_messages (user_id, sender, body) VALUES (:user_id, 'atendente', :body)"
    );
    $insert->execute([':user_id' => $clientId, ':body' => $body]);
    $_SESSION['flash'] = 'Resposta enviada ao cliente.';
    redirect('admin-mensagens.php');
}

$statement = db()->prepare(
    'SELECT contact_messages.user_id, contact_messages.sender, contact_messages.body,
            contact_messages.created_at, users.name, users.email
     FROM contact_messages
     JOIN users ON users.id = contact_messages.user_id
     ORDER BY users.name, contact_messages.created_at, contact_messages.id'
);
$statement->execute();
$rows = $statement->fetchAll();
$threads = [];
foreach ($rows as $row) {
    $threads[(int) $row['user_id']]['client'] = ['name' => $row['name'], 'email' => $row['email']];
    $threads[(int) $row['user_id']]['messages'][] = [
        'sender' => $row['sender'],
        'body' => $row['body'],
        'created_at' => $row['created_at'],
    ];
}

renderHeader('Mensagens de clientes');
?>
<section class="section">
    <p class="eyebrow">Atendimento</p>
    <h1>Mensagens de clientes</h1>
    <p><a href="admin.php">Voltar à administração</a></p>
    <?php if ($threads === []): ?>
        <p class="empty-state">Nenhuma mensagem de cliente até agora.</p>
    <?php else: ?>
        <div class="order-list">
            <?php foreach ($threads as $clientId => $thread): ?>
                <article class="order-card">
                    <div class="section-heading">
                        <div>
                            <h2><?= e($thread['client']['name']) ?></h2>
                            <p><?= e($thread['client']['email']) ?></p>
                        </div>
                    </div>
                    <div class="message-thread">
                        <?php foreach ($thread['messages'] as $message): ?>
                            <article class="message<?= $message['sender'] === 'atendente' ? ' message-attendant' : '' ?>">
                                <header>
                                    <strong><?= $message['sender'] === 'atendente' ? 'Atendente' : 'Cliente' ?></strong>
                                    <span><?= e(formatDateTime($message['created_at'])) ?></span>
                                </header>
                                <p><?= e($message['body']) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <form method="post" class="reply-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="cliente_id" value="<?= (int) $clientId ?>">
                        <label class="sr-only" for="mensagem-<?= (int) $clientId ?>">Responder a <?= e($thread['client']['name']) ?></label>
                        <textarea id="mensagem-<?= (int) $clientId ?>" name="mensagem" maxlength="2000" rows="2"
                                  placeholder="Escreva a resposta ao cliente" required></textarea>
                        <button class="button" type="submit">Responder</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php renderFooter(); ?>
