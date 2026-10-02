<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle') {
        db()->prepare('UPDATE products SET active = 1 - active WHERE id = ?')->execute([$id]);
        flash('Visibility updated.');
    } elseif ($action === 'delete') {
        $stmt = db()->prepare('SELECT image FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $image = (string) $stmt->fetchColumn();
        db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        if (str_starts_with($image, 'uploads/') && !str_starts_with($image, 'uploads/seed/') && is_file(ROOT . '/' . $image)) {
            unlink(ROOT . '/' . $image);
        }
        flash('Product deleted.');
    }
    redirect('products.php');
}

$products = db()->query('SELECT p.*, c.name AS category FROM products p
    LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC')->fetchAll();

admin_header('Products', 'products');
?>
<div class="admin-top">
  <h1>Products</h1>
  <a class="btn" href="product-edit.php">+ Add product</a>
</div>
<section class="panel">
  <?php if (!$products): ?>
    <p class="muted">No products yet. Add your first one to open the store.</p>
  <?php else: ?>
  <div class="table-wrap"><table>
    <tr><th></th><th>Name</th><th>Category</th><th class="num">Price</th><th class="num">Stock</th><th>Status</th><th></th></tr>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><img class="thumb" src="../<?= e($p['image']) ?>" alt=""></td>
        <td><a href="product-edit.php?id=<?= (int) $p['id'] ?>"><b><?= e($p['name']) ?></b></a><?= $p['featured'] ? ' <span class="pill new">Featured</span>' : '' ?></td>
        <td class="muted"><?= e($p['category'] ?? '—') ?></td>
        <td class="num">
          <?php if (on_sale($p)): ?><s class="muted"><?= e(money((float) $p['price'])) ?></s> <?php endif; ?>
          <?= e(money(price_of($p))) ?>
        </td>
        <td class="num"><?= (int) $p['stock'] ?></td>
        <td><?= $p['active'] ? '<span class="pill shipped">Live</span>' : '<span class="pill off">Hidden</span>' ?></td>
        <td>
          <div class="actions">
            <a class="btn sm ghost" href="product-edit.php?id=<?= (int) $p['id'] ?>">Edit</a>
            <form method="post">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
              <button class="btn sm ghost" name="action" value="toggle"><?= $p['active'] ? 'Hide' : 'Show' ?></button>
            </form>
            <form method="post" onsubmit="return this.querySelector('[name=confirm]').checked || (this.querySelector('label').hidden = false, false)">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
              <label class="check hint" hidden><input type="checkbox" name="confirm"> Sure?</label>
              <button class="btn sm danger" name="action" value="delete">Delete</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</section>
<?php admin_footer(); ?>
