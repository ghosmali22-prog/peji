<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$product = ['name' => '', 'category_id' => '', 'description' => '', 'price' => '', 'sale_price' => '',
    'sizes' => 'S,M,L,XL', 'stock' => '10', 'image' => '', 'featured' => 0, 'active' => 1];
if ($id) {
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch() ?: redirect('products.php');
}
$errors = [];

function save_upload(array $file, array &$errors): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'The photo did not upload. Try a smaller file (under 5 MB).';
        return null;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        $errors[] = 'The photo is larger than 5 MB. Resize it and try again.';
        return null;
    }
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($types[$mime]) || !getimagesize($file['tmp_name'])) {
        $errors[] = 'Upload a JPG, PNG, WebP or GIF photo.';
        return null;
    }
    $name = 'uploads/' . date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], ROOT . '/' . $name)) {
        $errors[] = 'The photo could not be saved. Check that the uploads folder is writable.';
        return null;
    }
    return $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'category_id' => ($_POST['category_id'] ?? '') === '' ? null : (int) $_POST['category_id'],
        'description' => trim((string) ($_POST['description'] ?? '')),
        'price' => trim((string) ($_POST['price'] ?? '')),
        'sale_price' => trim((string) ($_POST['sale_price'] ?? '')),
        'sizes' => implode(',', array_filter(array_map('trim', explode(',', (string) ($_POST['sizes'] ?? ''))), 'strlen')),
        'stock' => max(0, (int) ($_POST['stock'] ?? 0)),
        'featured' => isset($_POST['featured']) ? 1 : 0,
        'active' => isset($_POST['active']) ? 1 : 0,
    ];
    if ($data['name'] === '') {
        $errors[] = 'Give the product a name.';
    }
    if (!is_numeric($data['price']) || (float) $data['price'] <= 0) {
        $errors[] = 'Enter a price above zero, like 49 or 49.90.';
    }
    if ($data['sale_price'] !== '' && (!is_numeric($data['sale_price']) || (float) $data['sale_price'] >= (float) $data['price'])) {
        $errors[] = 'The sale price must be lower than the regular price. Leave it empty for no sale.';
    }
    $data['price'] = (float) $data['price'];
    $data['sale_price'] = $data['sale_price'] === '' ? null : (float) $data['sale_price'];

    $upload = save_upload($_FILES['image'] ?? [], $errors);
    $data['image'] = $upload ?? $product['image'];
    if ($data['image'] === '') {
        $errors[] = 'Add a product photo.';
    }

    if ($errors) {
        if ($upload) {
            unlink(ROOT . '/' . $upload);
            $data['image'] = $product['image'];
        }
        $product = array_merge($product, $data);
    } else {
        if ($id) {
            $old = $product['image'];
            db()->prepare('UPDATE products SET name = ?, category_id = ?, description = ?, price = ?, sale_price = ?,
                sizes = ?, stock = ?, featured = ?, active = ?, image = ? WHERE id = ?')
                ->execute([$data['name'], $data['category_id'], $data['description'], $data['price'], $data['sale_price'],
                    $data['sizes'], $data['stock'], $data['featured'], $data['active'], $data['image'], $id]);
            if ($upload && $old !== '' && !str_starts_with($old, 'uploads/seed/') && is_file(ROOT . '/' . $old)) {
                unlink(ROOT . '/' . $old);
            }
            flash("Saved changes to {$data['name']}.");
        } else {
            db()->prepare('INSERT INTO products (name, category_id, description, price, sale_price, sizes, stock, featured, active, image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$data['name'], $data['category_id'], $data['description'], $data['price'], $data['sale_price'],
                    $data['sizes'], $data['stock'], $data['featured'], $data['active'], $data['image']]);
            flash("Added {$data['name']} to the store.");
        }
        redirect('products.php');
    }
}

admin_header($id ? 'Edit product' : 'Add product', 'products');
?>
<div class="admin-top">
  <h1><?= $id ? 'Edit ' . e($product['name']) : 'Add product' ?></h1>
  <?php if ($id): ?><a class="btn ghost" href="../product.php?id=<?= $id ?>" target="_blank" rel="noopener">View in store ↗</a><?php endif; ?>
</div>
<?php if ($errors): ?>
  <div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
<form class="panel" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label class="wide">Name<input id="name" name="name" required value="<?= e($product['name']) ?>"></label>
    <label>Category
      <select id="category_id" name="category_id">
        <option value="">No category</option>
        <?php foreach (categories() as $cat): ?>
          <option value="<?= (int) $cat['id'] ?>" <?= (string) $cat['id'] === (string) $product['category_id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Stock <span class="hint">How many you can sell. At 0 it shows “Sold out”.</span>
      <input id="stock" name="stock" type="number" min="0" step="1" value="<?= e((string) $product['stock']) ?>">
    </label>
    <label>Price (<?= e(setting('currency', '$')) ?>)<input id="price" name="price" inputmode="decimal" required value="<?= e((string) $product['price']) ?>"></label>
    <label>Sale price (<?= e(setting('currency', '$')) ?>) <span class="hint">Optional. Leave empty for no sale.</span>
      <input id="sale_price" name="sale_price" inputmode="decimal" value="<?= e((string) ($product['sale_price'] ?? '')) ?>">
    </label>
    <label class="wide">Sizes <span class="hint">Separate with commas, e.g. S,M,L,XL or 28,30,32. Leave empty if there are no sizes.</span>
      <input id="sizes" name="sizes" value="<?= e($product['sizes']) ?>">
    </label>
    <label class="wide">Description<textarea id="description" name="description" rows="4"><?= e($product['description']) ?></textarea></label>
    <div class="wide actions">
      <?php if ($product['image']): ?><img class="preview" src="../<?= e($product['image']) ?>" alt="Current photo"><?php endif; ?>
      <label>Photo <span class="hint">JPG, PNG or WebP, up to 5 MB. Portrait (4:5) looks best.<?= $product['image'] ? ' Choose a file only to replace the current photo.' : '' ?></span>
        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
      </label>
    </div>
    <label class="check"><input id="active" type="checkbox" name="active" <?= $product['active'] ? 'checked' : '' ?>> Visible in the store</label>
    <label class="check"><input id="featured" type="checkbox" name="featured" <?= $product['featured'] ? 'checked' : '' ?>> Featured on the home page</label>
  </div>
  <div class="actions">
    <button class="btn" type="submit"><?= $id ? 'Save changes' : 'Add product' ?></button>
    <a class="btn ghost" href="products.php">Cancel</a>
  </div>
</form>
<?php admin_footer(); ?>
