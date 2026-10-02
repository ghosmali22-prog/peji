<?php
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/layout.php';

$stmt = db()->prepare('SELECT p.*, c.name AS category, c.slug AS category_slug FROM products p
    LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ? AND p.active = 1');
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$p = $stmt->fetch();

if (!$p) {
    http_response_code(404);
    shop_header('Not found');
    echo '<section class="shop"><h2>We could not find that piece</h2><p><a class="btn" href="index.php">Back to the shop</a></p></section>';
    shop_footer();
    exit;
}

$more = db()->prepare('SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.active = 1 AND p.id != ? ORDER BY (p.category_id = ?) DESC, p.featured DESC LIMIT 4');
$more->execute([$p['id'], $p['category_id']]);
$more = $more->fetchAll();

$sizes = sizes_of($p);
$soldOut = (int) $p['stock'] <= 0;

shop_header($p['name']);
?>
<section class="product">
  <div class="product-img"><img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>"></div>
  <div class="product-info">
    <?php if ($p['category']): ?>
      <a class="kicker" href="index.php?c=<?= e($p['category_slug']) ?>#shop"><?= e($p['category']) ?></a>
    <?php endif; ?>
    <h1><?= e($p['name']) ?></h1>
    <div class="price big">
      <?php if (on_sale($p)): ?><s><?= e(money((float) $p['price'])) ?></s><?php endif; ?>
      <b><?= e(money(price_of($p))) ?></b>
    </div>
    <p class="desc"><?= nl2br(e($p['description'])) ?></p>

    <form class="buy" data-add-to-cart
          data-id="<?= (int) $p['id'] ?>"
          data-name="<?= e($p['name']) ?>"
          data-price="<?= e((string) price_of($p)) ?>"
          data-image="<?= e($p['image']) ?>">
      <?php if ($sizes): ?>
        <fieldset class="sizes">
          <legend>Size</legend>
          <?php foreach ($sizes as $i => $size): ?>
            <label><input type="radio" name="size" value="<?= e($size) ?>" <?= $i === 0 ? 'checked' : '' ?>><span><?= e($size) ?></span></label>
          <?php endforeach; ?>
        </fieldset>
      <?php endif; ?>
      <?php if ($soldOut): ?>
        <button class="btn block" type="button" disabled>Sold out</button>
      <?php else: ?>
        <button class="btn block" type="submit">Add to bag</button>
        <?php if ((int) $p['stock'] <= 5): ?><p class="muted">Only <?= (int) $p['stock'] ?> left</p><?php endif; ?>
      <?php endif; ?>
    </form>
    <ul class="perks">
      <li>Ships worldwide</li>
      <li>30-day returns</li>
    </ul>
  </div>
</section>

<?php if ($more): ?>
<section class="shop">
  <div class="shop-head"><h2>You might also like</h2></div>
  <div class="grid">
    <?php foreach ($more as $m) { product_card($m); } ?>
  </div>
</section>
<?php endif; ?>
<?php shop_footer(); ?>
