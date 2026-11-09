<?php
require __DIR__ . '/includes/app.php';

if (!isAdmin()) {
    $_SESSION['flash'] = 'Área restrita à administração.';
    redirect('index.php');
}

function priceToCents(string $input): ?int
{
    if (str_contains($input, ',')) {
        // Formato brasileiro: pontos de milhar são removidos e a vírgula vira decimal.
        $normalized = str_replace(',', '.', str_replace('.', '', $input));
    } else {
        $normalized = $input;
    }
    if (preg_match('/^\d{1,7}(\.\d{1,2})?$/', $normalized) !== 1) {
        return null;
    }
    return (int) round((float) $normalized * 100);
}

function validProductImage(string $image): bool
{
    if ($image === '' || str_contains($image, '..') || !str_starts_with($image, 'img/')) {
        return false;
    }
    return is_file(__DIR__ . '/' . $image);
}

function slugifyProductId(string $name): string
{
    $accents = ['á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i',
        'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c'];
    $slug = strtolower(strtr($name, $accents));
    $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $slug), '-');
    return $slug === '' ? 'produto' : $slug;
}

function uniqueProductId(string $baseId): string
{
    if (productById($baseId) === null) {
        return $baseId;
    }
    for ($suffix = 2; $suffix < 100; $suffix++) {
        $candidate = $baseId . '-' . $suffix;
        if (productById($candidate) === null) {
            return $candidate;
        }
    }
    return $baseId . '-' . bin2hex(random_bytes(3));
}

$form = ['nome' => '', 'categoria' => 'aneis', 'preco' => '', 'estoque' => '20', 'imagem' => '', 'descricao' => ''];
$editingId = null;
$error = null;

if (requestMethod() === 'GET') {
    $requested = $_GET['editar'] ?? '';
    if (is_string($requested) && $requested !== '') {
        $product = productById($requested);
        if ($product !== null) {
            $editingId = $product['id'];
            $form = [
                'nome' => $product['name'],
                'categoria' => $product['category'],
                'preco' => number_format((int) $product['price_cents'] / 100, 2, ',', ''),
                'estoque' => (string) $product['stock'],
                'imagem' => $product['image'],
                'descricao' => $product['description'],
            ];
        }
    }
}

if (requestMethod() === 'POST') {
    verifyCsrf();
    $action = postString('acao');

    if ($action === 'remover') {
        $productId = postString('produto_id');
        $product = $productId !== '' ? productById($productId) : null;
        if ($product === null) {
            $_SESSION['flash'] = 'Produto não encontrado.';
        } else {
            $connection = db();
            $connection->beginTransaction();
            try {
                // Remove dos carrinhos antes do produto para respeitar a chave estrangeira.
                $deleteCart = $connection->prepare('DELETE FROM cart_items WHERE product_id = :id');
                $deleteCart->execute([':id' => $product['id']]);
                $deleteProduct = $connection->prepare('DELETE FROM products WHERE id = :id');
                $deleteProduct->execute([':id' => $product['id']]);
                $connection->commit();
            } catch (Throwable $exception) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }
                throw $exception;
            }
            $_SESSION['flash'] = 'Produto removido do catálogo.';
        }
        redirect('admin.php');
    }

    $name = trim(postString('nome'));
    $category = postString('categoria');
    $priceInput = postString('preco');
    $stock = filter_input(INPUT_POST, 'estoque', FILTER_VALIDATE_INT);
    $image = trim(postString('imagem'));
    $description = trim(postString('descricao'));
    $editingId = postString('editing_id');
    $editingId = $editingId !== '' ? $editingId : null;

    $form = ['nome' => $name, 'categoria' => $category, 'preco' => $priceInput,
        'estoque' => postString('estoque'), 'imagem' => $image, 'descricao' => $description];

    $priceCents = priceToCents($priceInput);
    if ($name === '' || mb_strlen($name) > 100) {
        $error = 'Informe um nome com até 100 caracteres.';
    } elseif (!isset(CATEGORIES[$category])) {
        $error = 'Selecione uma categoria válida.';
    } elseif ($priceCents === null) {
        $error = 'Informe um preço válido em reais, por exemplo 1290,90.';
    } elseif ($stock === false || $stock === null || $stock < 0 || $stock > 9999) {
        $error = 'Informe um estoque entre 0 e 9999.';
    } elseif (!validProductImage($image)) {
        $error = 'Informe o caminho de uma imagem existente dentro da pasta img/ (ex.: img/anel-fio-esfera.jpeg).';
    } elseif ($description === '' || mb_strlen($description) > 1000) {
        $error = 'Informe uma descrição com até 1000 caracteres.';
    } elseif ($action === 'atualizar') {
        $product = $editingId !== null ? productById($editingId) : null;
        if ($product === null) {
            $error = 'Produto não encontrado.';
        } else {
            $statement = db()->prepare(
                'UPDATE products
                 SET name = :name, category = :category, price_cents = :price,
                     stock = :stock, image = :image, description = :description
                 WHERE id = :id'
            );
            $statement->execute([
                ':name' => $name,
                ':category' => $category,
                ':price' => $priceCents,
                ':stock' => $stock,
                ':image' => $image,
                ':description' => $description,
                ':id' => $product['id'],
            ]);
            $_SESSION['flash'] = 'Produto atualizado.';
            redirect('admin.php');
        }
    } elseif ($action === 'criar') {
        $statement = db()->prepare(
            'INSERT INTO products (id, name, category, price_cents, image, description, stock)
             VALUES (:id, :name, :category, :price, :image, :description, :stock)'
        );
        $statement->execute([
            ':id' => uniqueProductId(slugifyProductId($name)),
            ':name' => $name,
            ':category' => $category,
            ':price' => $priceCents,
            ':image' => $image,
            ':description' => $description,
            ':stock' => $stock,
        ]);
        $_SESSION['flash'] = 'Produto cadastrado no catálogo.';
        redirect('admin.php');
    } else {
        http_response_code(400);
        exit('Ação inválida.');
    }
}

$products = db()->query('SELECT * FROM products ORDER BY category, name')->fetchAll();

renderHeader('Administração');
?>
<section class="section">
    <p class="eyebrow">Painel administrativo</p>
    <h1>Gerenciar produtos</h1>
    <p><a href="admin-mensagens.php">Mensagens de clientes</a> · <a href="catalogo.php">Ver catálogo</a></p>

    <section class="auth-card admin-card">
        <h2><?= $editingId !== null ? 'Editar produto' : 'Cadastrar novo produto' ?></h2>
        <?php if ($error !== null): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="acao" value="<?= $editingId !== null ? 'atualizar' : 'criar' ?>">
            <input type="hidden" name="editing_id" value="<?= e($editingId ?? '') ?>">
            <label for="nome">Nome</label>
            <input id="nome" name="nome" type="text" maxlength="100" value="<?= e($form['nome']) ?>" required>
            <label for="categoria">Categoria</label>
            <select id="categoria" name="categoria" required>
                <?php foreach (CATEGORIES as $slug => $label): ?>
                    <option value="<?= e($slug) ?>" <?= $form['categoria'] === $slug ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="preco">Preço em reais (ex.: 1290,90)</label>
            <input id="preco" name="preco" type="text" inputmode="decimal" value="<?= e($form['preco']) ?>" required>
            <label for="estoque">Estoque</label>
            <input id="estoque" name="estoque" type="number" min="0" max="9999" value="<?= e($form['estoque']) ?>" required>
            <label for="imagem">Imagem (caminho dentro de img/)</label>
            <input id="imagem" name="imagem" type="text" value="<?= e($form['imagem']) ?>" required>
            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" maxlength="1000" rows="4" required><?= e($form['descricao']) ?></textarea>
            <button class="button" type="submit"><?= $editingId !== null ? 'Salvar alterações' : 'Cadastrar produto' ?></button>
        </form>
        <?php if ($editingId !== null): ?>
            <p><a href="admin.php">Cancelar edição e cadastrar novo produto</a></p>
        <?php endif; ?>
    </section>

    <div class="admin-list">
        <?php foreach ($products as $item): ?>
            <article class="admin-item">
                <img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>">
                <div class="admin-item-info">
                    <strong><?= e($item['name']) ?></strong>
                    <p><?= e(categoryLabel($item['category'])) ?> · <?= money((int) $item['price_cents']) ?> ·
                        <?php if ((int) $item['stock'] === 0): ?>
                            <span class="badge-out">Esgotado</span>
                        <?php else: ?>
                            <?= (int) $item['stock'] ?> em estoque
                        <?php endif; ?>
                    </p>
                </div>
                <a class="button button-outline" href="admin.php?editar=<?= e($item['id']) ?>">Editar</a>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="produto_id" value="<?= e($item['id']) ?>">
                    <input type="hidden" name="acao" value="remover">
                    <button class="link-button" type="submit">Remover</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php renderFooter(); ?>
