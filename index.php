<?php
require __DIR__ . '/includes/app.php';

$featured = db()->query('SELECT * FROM products ORDER BY category, name')->fetchAll();
$categories = [
    'aneis' => 'Anéis',
    'brincos' => 'Brincos',
    'pulseiras' => 'Pulseiras',
    'chokers' => 'Colares e chokers',
];

renderHeader('Joias para todos os momentos');
?>
<section class="hero">
    <div>
        <p class="eyebrow">Scherrer Joias</p>
        <h1>Detalhes que fazem você brilhar</h1>
        <p>Descubra peças especiais para compor o seu estilo.</p>
        <a class="button" href="catalogo.php">Conheça a coleção</a>
    </div>
    <img src="img/banner.png" alt="Coleção Scherrer Joias">
</section>

<section class="editorial section" aria-label="Campanha Scherrer Joias">
    <div class="section-heading">
        <div><p class="eyebrow">Inspire-se</p><h2>Joias que contam histórias</h2></div>
    </div>
    <div class="editorial-grid">
        <img src="img/campanha-pulseira-minimalista.png" alt="Pulseira dourada delicada em composição editorial">
        <img src="img/campanha-argolas-douradas.png" alt="Argolas douradas em destaque">
        <img src="img/campanha-colar-dourado.png" alt="Colar dourado usado em composição elegante">
        <img src="img/campanha-mix-pulseiras.png" alt="Composição de pulseiras douradas">
        <img src="img/campanha-anel-organico.png" alt="Anel dourado de formas orgânicas">
        <img src="img/campanha-argola-corrente.png" alt="Argolas e corrente douradas em detalhe">
    </div>
</section>

<section class="section">
    <div class="section-heading">
        <div><p class="eyebrow">Encontre a sua peça</p><h2>Explore por categoria</h2></div>
    </div>
    <div class="category-cards">
        <?php foreach ($categories as $slug => $label): ?>
            <a class="category-card" href="catalogo.php?categoria=<?= e($slug) ?>"><?= e($label) ?><span>Ver coleção →</span></a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="section-heading">
        <div><p class="eyebrow">Seleção especial</p><h2>Nossas joias</h2></div>
        <a href="catalogo.php">Ver catálogo completo</a>
    </div>
    <div class="product-grid">
        <?php foreach ($featured as $product): ?>
            <article class="product-card">
                <a href="produto.php?id=<?= e($product['id']) ?>">
                    <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>">
                    <h3><?= e($product['name']) ?></h3>
                </a>
                <p class="price"><?= money((int) $product['price_cents']) ?></p>
                <a class="button button-outline" href="produto.php?id=<?= e($product['id']) ?>">Ver produto</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php renderFooter(); ?>
