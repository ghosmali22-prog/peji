<?php
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/layout.php';

$slug = (string) ($_GET['c'] ?? '');
$sql = 'SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.active = 1';
$params = [];
if ($slug !== '') {
    $sql .= ' AND c.slug = ?';
    $params[] = $slug;
}
$stmt = db()->prepare($sql . ' ORDER BY p.featured DESC, p.id DESC');
$stmt->execute($params);
$products = $stmt->fetchAll();

$featured = db()->query('SELECT * FROM products WHERE active = 1 AND featured = 1 ORDER BY id LIMIT 3')->fetchAll();
$heading = 'Everything';
foreach (categories() as $cat) {
    if ($cat['slug'] === $slug) {
        $heading = $cat['name'];
    }
}

shop_header();
?>
<?php if (setting('campaign_active') === '1'): ?>
<section class="hero">
  <div class="hero-copy">
    <?php if (setting('campaign_kicker') !== ''): ?><span class="kicker"><?= e(setting('campaign_kicker')) ?></span><?php endif; ?>
    <h1><?= e(setting('campaign_title')) ?></h1>
    <p><?= e(setting('campaign_text')) ?></p>
    <a class="btn" href="#shop"><?= e(setting('campaign_cta', 'Shop now')) ?> →</a>
  </div>
  <div class="hero-art">
    <?php foreach ($featured as $i => $p): ?>
      <a class="tile t<?= $i ?>" href="product.php?id=<?= (int) $p['id'] ?>"><img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>"></a>
    <?php endforeach; ?>
    <?php if (setting('campaign_badge') !== ''): ?><span class="badge"><?= e(setting('campaign_badge')) ?></span><?php endif; ?>
  </div>
</section>
<?php endif; ?>

<section class="shop" id="shop">
  <div class="shop-head">
    <h2><?= e($heading) ?></h2>
    <span class="muted"><?= count($products) ?> <?= count($products) === 1 ? 'piece' : 'pieces' ?></span>
  </div>
  <?php if (!$products): ?>
    <p class="empty">Nothing here yet. New pieces are on the way.</p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($products as $p) { product_card($p); } ?>
    </div>
  <?php endif; ?>
</section>
<?php shop_footer(); ?>
