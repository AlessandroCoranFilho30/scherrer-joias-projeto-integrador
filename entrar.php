<?php
require __DIR__ . '/includes/app.php';

if (currentUser() !== null) {
    redirect('index.php');
}

$returnTo = safeReturnPath($_GET['retorno'] ?? $_POST['retorno'] ?? null);
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = strtolower(trim(postString('email')));
    $password = postString('senha');
    $statement = db()->prepare('SELECT id, name, email, password_hash FROM users WHERE email = :email');
    $statement->execute([':email' => $email]);
    $user = $statement->fetch();

    if ($user === false || !password_verify($password, $user['password_hash'])) {
        $error = 'E-mail ou senha incorretos.';
    } else {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email']];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['flash'] = 'Login realizado com sucesso.';
        redirect($returnTo);
    }
}

renderHeader('Entrar');
?>
<section class="auth-card">
    <p class="eyebrow">Bem-vinda(o) de volta</p>
    <h1>Entrar na sua conta</h1>
    <?php if ($error !== null): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="retorno" value="<?= e($returnTo) ?>">
        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" autocomplete="email" required>
        <label for="senha">Senha</label>
        <input id="senha" name="senha" type="password" autocomplete="current-password" required>
        <button class="button" type="submit">Entrar</button>
    </form>
    <p>Não tem uma conta? <a href="cadastrar.php">Cadastre-se</a></p>
</section>
<?php renderFooter(); ?>
