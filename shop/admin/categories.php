<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));

    if ($action === 'delete') {
        db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        flash('Category deleted. Its products are kept without a category.');
    } elseif ($name === '') {
        flash('Type a name for the category.');
    } else {
        // Keep the web address (slug) unique.
        $base = slugify($name);
        $slug = $base;
        $check = db()->prepare('SELECT COUNT(*) FROM categories WHERE slug = ? AND id != ?');
        for ($n = 2; $check->execute([$slug, $id]) && $check->fetchColumn() > 0; $n++) {
            $slug = "$base-$n";
        }
        if ($action === 'rename') {
            db()->prepare('UPDATE categories SET name = ?, slug = ? WHERE id = ?')->execute([$name, $slug, $id]);
            flash("Renamed to $name.");
        } else {
            $sort = (int) db()->query('SELECT COALESCE(MAX(sort), 0) + 1 FROM categories')->fetchColumn();
            db()->prepare('INSERT INTO categories (name, slug, sort) VALUES (?, ?, ?)')->execute([$name, $slug, $sort]);
            flash("Added the $name category.");
        }
    }
    redirect('categories.php');
}

$cats = db()->query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS n
    FROM categories c ORDER BY sort, name')->fetchAll();

admin_header('Categories', 'categories');
?>
<div class="admin-top"><h1>Categories</h1></div>
<form class="panel" method="post">
  <h2>Add a category</h2>
  <?= csrf_field() ?>
  <div class="actions">
    <input id="new-category" name="name" placeholder="e.g. Knitwear" style="max-width: 320px">
    <button class="btn" name="action" value="add">Add</button>
  </div>
</form>
<section class="panel">
  <div class="table-wrap"><table>
    <tr><th>Name</th><th class="num">Products</th><th></th></tr>
    <?php foreach ($cats as $c): ?>
      <tr>
        <td>
          <form method="post" class="actions">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
            <input id="cat-<?= (int) $c['id'] ?>" name="name" value="<?= e($c['name']) ?>" aria-label="Category name" style="max-width: 240px">
            <button class="btn sm ghost" name="action" value="rename">Rename</button>
          </form>
        </td>
        <td class="num"><?= (int) $c['n'] ?></td>
        <td>
          <form method="post" onsubmit="return this.querySelector('[name=confirm]').checked || (this.querySelector('label').hidden = false, false)">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
            <label class="check hint" hidden><input type="checkbox" name="confirm"> Sure?</label>
            <button class="btn sm danger" name="action" value="delete">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table></div>
</section>
<?php admin_footer(); ?>
