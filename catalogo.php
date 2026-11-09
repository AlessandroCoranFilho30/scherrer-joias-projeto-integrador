<?php
require __DIR__ . '/includes/app.php';

$categoryLabels = [
    'aneis' => 'Anéis',
    'brincos' => 'Brincos',
    'pulseiras' => 'Pulseiras',
    'chokers' => 'Colares e chokers',
];
$category = $_GET['categoria'] ?? '';
$category = is_string($category) ? $category : '';
$searchInput = $_GET['busca'] ?? '';
$search = is_string($searchInput) ? trim($searchInput) : '';
$conditions = [];
$parameters = [];
if (is_string($category) && isset($categoryLabels[$category])) {
    $conditions[] = 'category = :category';
    $parameters[':category'] = $category;
}
if ($search !== '') {
    $conditions[] = '(name LIKE :search OR description LIKE :search)';
    $parameters[':search'] = '%' . $search . '%';
}
$query = 'SELECT * FROM products' . ($conditions ? ' WHERE ' . implode(' AND ', $conditions) : '') . ' ORDER BY name';
$statement = db()->prepare($query);
$statement->execute($parameters);
$products = $statement->fetchAll();
$title = isset($categoryLabels[$category]) ? $categoryLabels[$category] : 'Catálogo';

renderHeader($title);
?>
<section class="section">
    <div class="section-heading">
        <div><p class="eyebrow">Scherrer Joias</p><h1><?= e($title) ?></h1></div>
    </div>
    <div class="category-filters">
        <a href="catalogo.php">Todas</a>
        <?php foreach ($categoryLabels as $slug => $label): ?>
            <a href="catalogo.php?categoria=<?= e($slug) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
    <?php if ($search !== ''): ?><p>Resultados para “<?= e($search) ?>”</p><?php endif; ?>
    <?php if ($products === []): ?>
        <p class="empty-state">Não encontramos joias com esses filtros. Tente outra busca.</p>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <article class="product-card">
                    <a href="produto.php?id=<?= e($product['id']) ?>">
                        <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>">
                        <h2><?= e($product['name']) ?></h2>
                    </a>
                    <p class="price"><?= money((int) $product['price_cents']) ?></p>
                    <a class="button button-outline" href="produto.php?id=<?= e($product['id']) ?>">Ver produto</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php renderFooter(); ?>
