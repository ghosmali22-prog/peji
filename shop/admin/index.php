<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/_layout.php';

$hash = setting('admin_password');
$error = '';

// First visit: the owner picks the admin password.
if ($hash === '') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $pass = (string) ($_POST['password'] ?? '');
        if (strlen($pass) < 8) {
            $error = 'Use at least 8 characters.';
        } elseif ($pass !== ($_POST['confirm'] ?? '')) {
            $error = 'The two passwords do not match.';
        } else {
            set_setting('admin_password', password_hash($pass, PASSWORD_DEFAULT));
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            flash('Admin password saved. Welcome to your store.');
            redirect('index.php');
        }
    }
    admin_head('Set up');
    ?>
<div class="login">
  <form class="panel" method="post">
    <span class="kicker">First-time setup</span>
    <h1>Create the admin password</h1>
    <p class="muted">You will use it to sign in to this panel. Keep it somewhere safe.</p>
    <?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <label>Password<input id="password" name="password" type="password" autocomplete="new-password" required minlength="8"></label>
    <label>Repeat password<input id="confirm" name="confirm" type="password" autocomplete="new-password" required minlength="8"></label>
    <button class="btn block" type="submit">Save and open the panel</button>
  </form>
</div>
</body></html>
<?php
    exit;
}

if (!is_admin()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        if (password_verify((string) ($_POST['password'] ?? ''), $hash)) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            redirect('index.php');
        }
        sleep(1); // slows down password guessing
        $error = 'That password is not correct.';
    }
    admin_head('Sign in');
    ?>
<div class="login">
  <form class="panel" method="post">
    <span class="kicker"><?= e(setting('store_name', 'Peji')) ?> admin</span>
    <h1>Sign in</h1>
    <?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <label>Password<input id="password" name="password" type="password" autocomplete="current-password" required autofocus></label>
    <button class="btn block" type="submit">Sign in</button>
  </form>
</div>
</body></html>
<?php
    exit;
}

$pdo = db();
$stats = [
    'New orders' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn(),
    'Revenue (paid + shipped)' => money((float) $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ('paid', 'shipped')")->fetchColumn()),
    'Products live' => (int) $pdo->query('SELECT COUNT(*) FROM products WHERE active = 1')->fetchColumn(),
    'Out of stock' => (int) $pdo->query('SELECT COUNT(*) FROM products WHERE active = 1 AND stock <= 0')->fetchColumn(),
];
$recent = $pdo->query('SELECT * FROM orders ORDER BY id DESC LIMIT 6')->fetchAll();
$low = $pdo->query('SELECT * FROM products WHERE active = 1 AND stock <= 5 ORDER BY stock LIMIT 6')->fetchAll();

admin_header('Dashboard', 'dashboard');
?>
<div class="admin-top">
  <h1>Dashboard</h1>
  <a class="btn" href="product-edit.php">+ Add product</a>
</div>
<div class="stats">
  <?php foreach ($stats as $label => $value): ?>
    <div class="stat"><b><?= e((string) $value) ?></b><span><?= e($label) ?></span></div>
  <?php endforeach; ?>
</div>
<section class="panel">
  <h2>Latest orders</h2>
  <?php if (!$recent): ?>
    <p class="muted">No orders yet. They will show up here as soon as a customer checks out.</p>
  <?php else: ?>
  <div class="table-wrap"><table>
    <tr><th>#</th><th>Customer</th><th>Date</th><th>Status</th><th class="num">Total</th></tr>
    <?php foreach ($recent as $o): ?>
      <tr>
        <td><a href="orders.php?id=<?= (int) $o['id'] ?>">#<?= (int) $o['id'] ?></a></td>
        <td><?= e($o['name']) ?></td>
        <td class="muted"><?= e(date('M j, H:i', strtotime($o['created_at'] . ' UTC'))) ?></td>
        <td><?= status_pill($o['status']) ?></td>
        <td class="num"><?= e(money((float) $o['total'])) ?></td>
      </tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</section>
<?php if ($low): ?>
<section class="panel">
  <h2>Running low</h2>
  <div class="table-wrap"><table>
    <tr><th></th><th>Product</th><th class="num">In stock</th><th></th></tr>
    <?php foreach ($low as $p): ?>
      <tr>
        <td><img class="thumb" src="../<?= e($p['image']) ?>" alt=""></td>
        <td><?= e($p['name']) ?></td>
        <td class="num"><?= (int) $p['stock'] ?></td>
        <td><a href="product-edit.php?id=<?= (int) $p['id'] ?>">Restock</a></td>
      </tr>
    <?php endforeach; ?>
  </table></div>
</section>
<?php endif; ?>
<?php admin_footer(); ?>
