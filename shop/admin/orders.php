<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');
    if (in_array($status, ORDER_STATUSES, true)) {
        db()->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $id]);
        flash("Order #$id is now marked $status.");
    }
    redirect('orders.php?id=' . $id);
}

$id = (int) ($_GET['id'] ?? 0);
if ($id) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    $order = $stmt->fetch() ?: redirect('orders.php');
    $items = json_decode($order['items'], true) ?: [];

    admin_header("Order #$id", 'orders');
    ?>
<div class="admin-top">
  <h1>Order #<?= $id ?> <?= status_pill($order['status']) ?></h1>
  <a class="btn ghost" href="orders.php">← All orders</a>
</div>
<div class="form-grid">
  <section class="panel">
    <h2>Items</h2>
    <div class="table-wrap"><table>
      <tr><th>Product</th><th>Size</th><th class="num">Qty</th><th class="num">Price</th></tr>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= e($item['name']) ?></td>
          <td><?= e($item['size'] ?: '—') ?></td>
          <td class="num"><?= (int) $item['qty'] ?></td>
          <td class="num"><?= e(money($item['price'] * $item['qty'])) ?></td>
        </tr>
      <?php endforeach; ?>
      <tr><td colspan="3"><b>Total</b></td><td class="num"><b><?= e(money((float) $order['total'])) ?></b></td></tr>
    </table></div>
    <form method="post" class="actions">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <label>Change status
        <select id="status" name="status">
          <?php foreach (ORDER_STATUSES as $s): ?>
            <option value="<?= $s ?>" <?= $s === $order['status'] ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="btn" type="submit" style="align-self: flex-end">Update</button>
    </form>
  </section>
  <section class="panel">
    <h2>Customer</h2>
    <p><b><?= e($order['name']) ?></b><br>
      <a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a><br>
      <?= e($order['phone']) ?></p>
    <p><?= e($order['address']) ?><br><?= e($order['city']) ?>, <?= e($order['country']) ?></p>
    <?php if ($order['note'] !== ''): ?><p class="muted">Note: <?= e($order['note']) ?></p><?php endif; ?>
    <p class="muted small">Placed <?= e(date('M j, Y \a\t H:i', strtotime($order['created_at'] . ' UTC'))) ?></p>
  </section>
</div>
<?php
    admin_footer();
    exit;
}

$filter = (string) ($_GET['status'] ?? '');
if (in_array($filter, ORDER_STATUSES, true)) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE status = ? ORDER BY id DESC');
    $stmt->execute([$filter]);
} else {
    $filter = '';
    $stmt = db()->query('SELECT * FROM orders ORDER BY id DESC');
}
$orders = $stmt->fetchAll();

admin_header('Orders', 'orders');
?>
<div class="admin-top">
  <h1>Orders</h1>
  <nav class="actions">
    <a class="btn sm <?= $filter === '' ? '' : 'ghost' ?>" href="orders.php">All</a>
    <?php foreach (ORDER_STATUSES as $s): ?>
      <a class="btn sm <?= $filter === $s ? '' : 'ghost' ?>" href="orders.php?status=<?= $s ?>"><?= ucfirst($s) ?></a>
    <?php endforeach; ?>
  </nav>
</div>
<section class="panel">
  <?php if (!$orders): ?>
    <p class="muted">No orders <?= $filter ? "marked $filter" : 'yet' ?>.</p>
  <?php else: ?>
  <div class="table-wrap"><table>
    <tr><th>#</th><th>Customer</th><th>Country</th><th>Date</th><th>Status</th><th class="num">Total</th></tr>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><a href="orders.php?id=<?= (int) $o['id'] ?>">#<?= (int) $o['id'] ?></a></td>
        <td><?= e($o['name']) ?><br><span class="muted small"><?= e($o['email']) ?></span></td>
        <td><?= e($o['country']) ?></td>
        <td class="muted"><?= e(date('M j, H:i', strtotime($o['created_at'] . ' UTC'))) ?></td>
        <td><?= status_pill($o['status']) ?></td>
        <td class="num"><?= e(money((float) $o['total'])) ?></td>
      </tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</section>
<?php admin_footer(); ?>
