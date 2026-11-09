<?php
require __DIR__ . '/includes/app.php';

if (currentUser() !== null) {
    redirect('index.php');
}

$error = null;
$name = '';
$email = '';
if (requestMethod() === 'POST') {
    verifyCsrf();
    $name = trim(postString('nome'));
    $email = strtolower(trim(postString('email')));
    $password = postString('senha');
    $confirmation = postString('confirmar_senha');

    if (preg_match('/^.{2,100}$/us', $name) !== 1) {
        $error = 'Informe um nome com pelo menos 2 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Informe um e-mail válido.';
    } elseif (strlen($password) < 8) {
        $error = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif (strlen($password) > 100) {
        $error = 'A senha deve ter no máximo 100 caracteres.';
    } elseif ($password !== $confirmation) {
        $error = 'As senhas não conferem.';
    } else {
        try {
            $statement = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password)');
            $statement->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int) db()->lastInsertId(), 'name' => $name, 'email' => $email];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['flash'] = 'Conta criada. Boas compras!';
            redirect('index.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
            $error = 'Este e-mail já está cadastrado. Entre na sua conta.';
        }
    }
}

renderHeader('Criar conta');
?>
<section class="auth-card">
    <p class="eyebrow">Faça parte</p>
    <h1>Criar conta</h1>
    <?php if ($error !== null): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label for="nome">Nome</label>
        <input id="nome" name="nome" type="text" autocomplete="name" maxlength="100" value="<?= e($name) ?>" required>
        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" autocomplete="email" maxlength="190" value="<?= e($email) ?>" required>
        <label for="senha">Senha (mínimo 8 caracteres)</label>
        <input id="senha" name="senha" type="password" autocomplete="new-password" minlength="8" required>
        <label for="confirmar_senha">Confirmar senha</label>
        <input id="confirmar_senha" name="confirmar_senha" type="password" autocomplete="new-password" minlength="8" required>
        <button class="button" type="submit">Criar conta</button>
    </form>
    <p>Já tem cadastro? <a href="entrar.php">Entrar</a></p>
</section>
<?php renderFooter(); ?>
