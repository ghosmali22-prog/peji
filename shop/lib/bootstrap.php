<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('DATA_DIR', ROOT . '/data');
define('UPLOAD_DIR', ROOT . '/uploads');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0775, true);
    }
    $pdo = new PDO('sqlite:' . DATA_DIR . '/shop.sqlite', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $installed = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'settings'")->fetch();
    if (!$installed) {
        require_once __DIR__ . '/install.php';
        install_schema($pdo);
        seed_demo($pdo);
    }
    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = db()->query('SELECT key, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $cache[$key] ?? $default;
}

function set_setting(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value');
    $stmt->execute([$key, $value]);
}

function money(float $amount): string
{
    return setting('currency', '$') . number_format($amount, 2);
}

function price_of(array $product): float
{
    $sale = $product['sale_price'];
    if ($sale !== null && $sale !== '' && (float) $sale < (float) $product['price']) {
        return (float) $sale;
    }
    return (float) $product['price'];
}

function on_sale(array $product): bool
{
    return price_of($product) < (float) $product['price'];
}

function sizes_of(array $product): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string) $product['sizes'])), 'strlen'));
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    if (!hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Your session expired. Go back, reload the page and try again.');
    }
}

function flash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function take_flash(): ?string
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function is_admin(): bool
{
    return !empty($_SESSION['admin']);
}

function require_admin(): void
{
    if (!is_admin()) {
        redirect('index.php');
    }
}

function categories(): array
{
    return db()->query('SELECT * FROM categories ORDER BY sort, name')->fetchAll();
}

function slugify(string $text): string
{
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
    return $slug !== '' ? $slug : 'category-' . bin2hex(random_bytes(2));
}
