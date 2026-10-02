<?php
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/layout.php';

$errors = [];
$form = ['name' => '', 'email' => '', 'phone' => '', 'address' => '', 'city' => '', 'country' => '', 'note' => ''];
$placed = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($form as $key => $_) {
        $form[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    foreach (['name' => 'your name', 'email' => 'your email', 'address' => 'a street address', 'city' => 'a city', 'country' => 'a country'] as $key => $label) {
        if ($form[$key] === '') {
            $errors[] = "Enter $label.";
        }
    }
    if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter an email address like name@example.com.';
    }

    // Prices and stock always come from the database, never from the browser.
    $cart = json_decode((string) ($_POST['cart'] ?? '[]'), true);
    $items = [];
    $total = 0.0;
    if (!is_array($cart) || !$cart) {
        $errors[] = 'Your bag is empty.';
    } else {
        $find = db()->prepare('SELECT * FROM products WHERE id = ? AND active = 1');
        $wanted = [];
        foreach ($cart as $line) {
            $id = (int) ($line['id'] ?? 0);
            $qty = max(1, min(20, (int) ($line['qty'] ?? 1)));
            $size = (string) ($line['size'] ?? '');
            $find->execute([$id]);
            $p = $find->fetch();
            if (!$p) {
                $errors[] = 'One item in your bag is no longer available. Remove it and try again.';
                continue;
            }
            if (sizes_of($p) && !in_array($size, sizes_of($p), true)) {
                $errors[] = "Choose a valid size for {$p['name']}.";
                continue;
            }
            $wanted[$id] = ($wanted[$id] ?? 0) + $qty;
            if ($wanted[$id] > (int) $p['stock']) {
                $errors[] = (int) $p['stock'] > 0
                    ? "Only {$p['stock']} of {$p['name']} left. Lower the quantity and try again."
                    : "{$p['name']} just sold out. Remove it from your bag to continue.";
                continue;
            }
            $price = price_of($p);
            $items[] = ['id' => $id, 'name' => $p['name'], 'size' => $size, 'qty' => $qty, 'price' => $price];
            $total += $price * $qty;
        }
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        $dec = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
        foreach ($wanted as $id => $qty) {
            $dec->execute([$qty, $id]);
        }
        $pdo->prepare('INSERT INTO orders (name, email, phone, address, city, country, note, items, total)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([
            $form['name'], $form['email'], $form['phone'], $form['address'], $form['city'], $form['country'], $form['note'],
            json_encode($items), round($total, 2),
        ]);
        $placed = (int) $pdo->lastInsertId();
        $pdo->commit();
    }
}

shop_header('Checkout');
?>
<?php if ($placed): ?>
<section class="done" data-clear-cart>
  <span class="kicker">Order #<?= $placed ?></span>
  <h1>Thank you, <?= e(strtok($form['name'], ' ')) ?>!</h1>
  <p>We received your order. We will email <b><?= e($form['email']) ?></b> with a payment link, then a tracking number once it ships.</p>
  <a class="btn" href="index.php">Keep shopping</a>
</section>
<?php else: ?>
<section class="checkout">
  <form method="post" class="checkout-form" data-checkout-form novalidate>
    <h1>Checkout</h1>
    <?php if ($errors): ?>
      <div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
    <?php endif; ?>
    <?= csrf_field() ?>
    <input type="hidden" name="cart" data-cart-input>
    <div class="fields">
      <label class="wide">Full name<input id="name" name="name" autocomplete="name" required value="<?= e($form['name']) ?>"></label>
      <label>Email<input id="email" name="email" type="email" autocomplete="email" required value="<?= e($form['email']) ?>"></label>
      <label>Phone <span class="muted">(optional)</span><input id="phone" name="phone" type="tel" autocomplete="tel" value="<?= e($form['phone']) ?>"></label>
      <label class="wide">Street address<input id="address" name="address" autocomplete="street-address" required value="<?= e($form['address']) ?>"></label>
      <label>City<input id="city" name="city" autocomplete="address-level2" required value="<?= e($form['city']) ?>"></label>
      <label>Country<input id="country" name="country" autocomplete="country-name" required value="<?= e($form['country']) ?>"></label>
      <label class="wide">Note <span class="muted">(optional)</span><textarea id="note" name="note" rows="3"><?= e($form['note']) ?></textarea></label>
    </div>
    <button class="btn block" type="submit">Place order</button>
    <p class="muted small">No payment is taken now. We confirm your order by email and send a secure payment link.</p>
  </form>
  <aside class="summary">
    <h2>Your bag</h2>
    <div data-summary-items></div>
    <div class="row total"><span>Total</span><b data-cart-total><?= e(money(0)) ?></b></div>
  </aside>
</section>
<?php endif; ?>
<?php shop_footer(); ?>
