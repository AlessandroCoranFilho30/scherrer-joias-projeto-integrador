<?php
require __DIR__ . '/includes/app.php';
requireLogin('contato.php');

$userId = (int) currentUser()['id'];
$error = null;
if (requestMethod() === 'POST') {
    verifyCsrf();
    $body = trim(postString('mensagem'));
    if ($body === '' || mb_strlen($body) > 2000) {
        $error = 'Escreva uma mensagem com até 2000 caracteres.';
    } else {
        $statement = db()->prepare(
            "INSERT INTO contact_messages (user_id, sender, body) VALUES (:user_id, 'cliente', :body)"
        );
        $statement->execute([':user_id' => $userId, ':body' => $body]);
        $_SESSION['flash'] = 'Mensagem enviada. Acompanhe a resposta por aqui.';
        redirect('contato.php');
    }
}

$statement = db()->prepare(
    'SELECT sender, body, created_at FROM contact_messages WHERE user_id = :user_id ORDER BY created_at, id'
);
$statement->execute([':user_id' => $userId]);
$messages = $statement->fetchAll();

renderHeader('Fale com a Scherrer');
?>
<section class="auth-card">
    <p class="eyebrow">Atendimento</p>
    <h1>Fale com atendente</h1>
    <p>Envie dúvidas sobre peças, pedidos ou retirada na loja. Nossa equipe responde por aqui.</p>
    <?php if ($error !== null): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label for="mensagem">Mensagem</label>
        <textarea id="mensagem" name="mensagem" maxlength="2000" rows="4" required></textarea>
        <button class="button" type="submit">Enviar mensagem</button>
    </form>
    <?php if ($messages !== []): ?>
        <div class="message-thread">
            <?php foreach ($messages as $message): ?>
                <article class="message<?= $message['sender'] === 'atendente' ? ' message-attendant' : '' ?>">
                    <header>
                        <strong><?= $message['sender'] === 'atendente' ? 'Atendente' : 'Você' ?></strong>
                        <span><?= e(formatDateTime($message['created_at'])) ?></span>
                    </header>
                    <p><?= e($message['body']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php renderFooter(); ?>
