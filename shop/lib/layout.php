<?php
declare(strict_types=1);

function shop_header(string $title = ''): void
{
    $store = setting('store_name', 'Peji');
    $current = $_GET['c'] ?? '';
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ? "$title · $store" : $store) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,800&family=DM+Sans:wght@400;500;700&display=swap">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php if (setting('announcement') !== ''): ?>
<div class="marquee" aria-label="<?= e(setting('announcement')) ?>">
  <div class="marquee-track" aria-hidden="true">
    <?php for ($i = 0; $i < 6; $i++): ?><span><?= e(setting('announcement')) ?></span><?php endfor; ?>
  </div>
</div>
<?php endif; ?>
<header class="site-header">
  <a class="logo" href="index.php"><?= e($store) ?></a>
  <nav class="cats" aria-label="Categories">
    <a href="index.php#shop" class="<?= $current === '' ? 'on' : '' ?>">All</a>
    <?php foreach (categories() as $cat): ?>
      <a href="index.php?c=<?= e($cat['slug']) ?>#shop" class="<?= $current === $cat['slug'] ? 'on' : '' ?>"><?= e($cat['name']) ?></a>
    <?php endforeach; ?>
  </nav>
  <button class="cart-btn" type="button" data-open-cart>Bag <span class="count" data-cart-count>0</span></button>
</header>
<main>
<?php
}

function shop_footer(): void
{
    $store = setting('store_name', 'Peji');
    ?>
</main>
<footer class="site-footer">
  <div class="logo"><?= e($store) ?></div>
  <p>Made in small batches. Shipped worldwide.</p>
  <p class="muted">© <?= date('Y') ?> <?= e($store) ?></p>
</footer>

<div class="drawer-backdrop" data-close-cart hidden></div>
<aside class="drawer" id="cart" aria-label="Shopping bag" hidden>
  <div class="drawer-head">
    <h2>Your bag</h2>
    <button type="button" class="icon" data-close-cart aria-label="Close">×</button>
  </div>
  <div class="drawer-items" data-cart-items></div>
  <div class="drawer-foot">
    <div class="row"><span>Subtotal</span><b data-cart-total><?= e(money(0)) ?></b></div>
    <a class="btn block" href="checkout.php" data-checkout-link>Checkout</a>
  </div>
</aside>
<div class="toast" data-toast hidden></div>
<script>window.SHOP = { currency: <?= json_encode(setting('currency', '$')) ?> };</script>
<script src="assets/app.js"></script>
</body>
</html>
<?php
}

function product_card(array $p): void
{
    $sale = on_sale($p);
    $soldOut = (int) $p['stock'] <= 0;
    ?>
<a class="card" href="product.php?id=<?= (int) $p['id'] ?>">
  <div class="card-img">
    <img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
    <?php if ($soldOut): ?>
      <span class="sticker dark">Sold out</span>
    <?php elseif ($sale): ?>
      <span class="sticker">−<?= (int) round(100 - price_of($p) / (float) $p['price'] * 100) ?>%</span>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <div>
      <h3><?= e($p['name']) ?></h3>
      <span class="muted"><?= e($p['category'] ?? '') ?></span>
    </div>
    <div class="price">
      <?php if ($sale): ?><s><?= e(money((float) $p['price'])) ?></s><?php endif; ?>
      <b><?= e(money(price_of($p))) ?></b>
    </div>
  </div>
</a>
<?php
}
