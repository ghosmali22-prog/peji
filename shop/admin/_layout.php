<?php
declare(strict_types=1);

function admin_head(string $title): void
{
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> · <?= e(setting('store_name', 'Peji')) ?> admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,800&family=DM+Sans:wght@400;500;700&display=swap">
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php
}

function admin_header(string $title, string $active): void
{
    admin_head($title);
    $newOrders = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn();
    $links = [
        'dashboard' => ['index.php', 'Dashboard'],
        'products' => ['products.php', 'Products'],
        'categories' => ['categories.php', 'Categories'],
        'orders' => ['orders.php', 'Orders' . ($newOrders ? " ($newOrders)" : '')],
        'campaign' => ['settings.php', 'Campaign & settings'],
    ];
    ?>
<div class="admin">
  <nav class="admin-nav" aria-label="Admin">
    <span class="logo"><?= e(setting('store_name', 'Peji')) ?></span>
    <?php foreach ($links as $key => [$href, $label]): ?>
      <a href="<?= $href ?>" class="<?= $key === $active ? 'on' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <span class="sep"></span>
    <a href="../index.php" target="_blank" rel="noopener">View store ↗</a>
    <a href="logout.php">Log out</a>
  </nav>
  <main class="admin-main">
    <?php if ($msg = take_flash()): ?><div class="notice" role="status"><?= e($msg) ?></div><?php endif; ?>
<?php
}

function admin_footer(): void
{
    ?>
  </main>
</div>
</body>
</html>
<?php
}

function status_pill(string $status): string
{
    return '<span class="pill ' . e($status) . '">' . e(ucfirst($status)) . '</span>';
}

const ORDER_STATUSES = ['new', 'paid', 'shipped', 'cancelled'];
