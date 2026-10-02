<?php
declare(strict_types=1);

// Runs once, the first time the site is opened: creates the tables and fills the shop with demo products.

function install_schema(PDO $pdo): void
{
    $pdo->exec(<<<SQL
        CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            sort INTEGER NOT NULL DEFAULT 0
        );
        CREATE TABLE products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
            name TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT '',
            price REAL NOT NULL,
            sale_price REAL,
            sizes TEXT NOT NULL DEFAULT '',
            stock INTEGER NOT NULL DEFAULT 0,
            image TEXT NOT NULL DEFAULT '',
            featured INTEGER NOT NULL DEFAULT 0,
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT NOT NULL DEFAULT '',
            address TEXT NOT NULL,
            city TEXT NOT NULL,
            country TEXT NOT NULL,
            note TEXT NOT NULL DEFAULT '',
            items TEXT NOT NULL,
            total REAL NOT NULL,
            status TEXT NOT NULL DEFAULT 'new',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
        );
    SQL);
}

function seed_demo(PDO $pdo): void
{
    $settings = [
        'store_name' => 'Peji',
        'currency' => '$',
        'announcement' => 'Free shipping on orders over $80 · Easy 30-day returns',
        'campaign_active' => '1',
        'campaign_kicker' => 'Autumn drop 2026',
        'campaign_title' => 'Soft layers for loud days',
        'campaign_text' => 'Heavy cotton, easy cuts and colors that hold up. Up to 25% off the new season for one week.',
        'campaign_cta' => 'Shop the drop',
        'campaign_badge' => 'Up to 25% off',
    ];
    $stmt = $pdo->prepare('INSERT INTO settings (key, value) VALUES (?, ?)');
    foreach ($settings as $key => $value) {
        $stmt->execute([$key, $value]);
    }

    $cats = ['Tops', 'Outerwear', 'Bottoms', 'Dresses', 'Accessories'];
    $catIds = [];
    $stmt = $pdo->prepare('INSERT INTO categories (name, slug, sort) VALUES (?, ?, ?)');
    foreach ($cats as $i => $name) {
        $stmt->execute([$name, strtolower($name), $i]);
        $catIds[$name] = (int) $pdo->lastInsertId();
    }

    // name, category, price, sale, sizes, stock, featured, shape, garment color, tile color, description
    $products = [
        ['Cloud Tee', 'Tops', 32, null, 'XS,S,M,L,XL', 40, 1, 'tee', '#F7F7F2', '#3046FF',
            'Heavyweight 240 gsm organic cotton with a relaxed, boxy fit. Pre-washed, so it keeps its shape.'],
        ['Sunday Hoodie', 'Tops', 68, 54, 'S,M,L,XL', 18, 1, 'hoodie', '#FFD84D', '#10231C',
            'Brushed fleece inside, roomy hood, kangaroo pocket. The one you will reach for every weekend.'],
        ['Field Jacket', 'Outerwear', 128, null, 'S,M,L', 9, 1, 'jacket', '#6B7F3A', '#FF9E7A',
            'Water-resistant cotton canvas, four pockets and a corduroy collar. Cut to layer over knits.'],
        ['Crew Knit', 'Tops', 74, null, 'S,M,L,XL', 22, 0, 'sweater', '#FF5A36', '#FFE3D9',
            'Merino blend crewneck with ribbed cuffs and hem. Warm without the bulk.'],
        ['Easy Trousers', 'Bottoms', 64, 48, '28,30,32,34,36', 25, 0, 'pants', '#2B2F3A', '#CFE3FF',
            'Elastic back waist, tapered leg and deep pockets. Smart enough for work, easy enough for travel.'],
        ['Slip Dress', 'Dresses', 88, null, 'XS,S,M,L', 12, 1, 'dress', '#3046FF', '#FFD84D',
            'Bias-cut satin with adjustable straps. Falls just below the knee.'],
        ['Corner Cap', 'Accessories', 26, null, 'One size', 60, 0, 'cap', '#10231C', '#BDEBD3',
            'Six-panel washed cotton cap with an adjustable brass buckle.'],
        ['Market Tote', 'Accessories', 22, null, 'One size', 0, 0, 'tote', '#F7F7F2', '#FF5A36',
            'Thick canvas tote with an inner pocket. Carries a full grocery run.'],
    ];

    if (!is_dir(UPLOAD_DIR . '/seed')) {
        mkdir(UPLOAD_DIR . '/seed', 0775, true);
    }
    $stmt = $pdo->prepare('INSERT INTO products (category_id, name, description, price, sale_price, sizes, stock, image, featured)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($products as [$name, $cat, $price, $sale, $sizes, $stock, $featured, $shape, $fill, $bg, $desc]) {
        $file = 'uploads/seed/' . slugify($name) . '.svg';
        file_put_contents(ROOT . '/' . $file, garment_svg($shape, $fill, $bg));
        $stmt->execute([$catIds[$cat], $name, $desc, $price, $sale, $sizes, $stock, $file, $featured]);
    }
}

// Flat garment illustrations used as demo product photos.
function garment_svg(string $shape, string $fill, string $bg): string
{
    $line = 'stroke="rgba(0,0,0,.22)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" fill="none"';
    $body = match ($shape) {
        'tee' => '<path d="M200 190 L252 162 Q300 198 348 162 L400 190 L475 262 L428 310 L398 282 L398 572 L202 572 L202 282 L172 310 L125 262 Z" fill="' . $fill . '"/>'
            . '<path d="M252 162 Q300 214 348 162" ' . $line . '/>',
        'sweater' => '<path d="M205 182 L256 164 Q300 192 344 164 L395 182 Q432 198 446 262 L472 530 L430 538 L402 300 L400 572 L200 572 L198 300 L170 538 L128 530 L154 262 Q168 198 205 182 Z" fill="' . $fill . '"/>'
            . '<path d="M256 164 Q300 206 344 164 M204 548 L396 548 M136 512 L168 518 M464 512 L432 518" ' . $line . '/>',
        'hoodie' => '<path d="M232 196 Q232 108 300 106 Q368 108 368 196 Z" fill="' . $fill . '"/>'
            . '<path d="M205 186 L250 168 Q300 220 350 168 L395 186 Q432 200 446 262 L472 530 L430 538 L402 300 L400 572 L200 572 L198 300 L170 538 L128 530 L154 262 Q168 200 205 186 Z" fill="' . $fill . '"/>'
            . '<path d="M250 168 Q300 226 350 168 M286 206 L282 268 M314 206 L318 268 M238 430 L362 430 L382 512 L218 512 Z" ' . $line . '/>',
        'jacket' => '<path d="M205 182 L262 160 L300 212 L338 160 L395 182 Q432 198 446 262 L472 530 L430 538 L402 300 L400 578 L200 578 L198 300 L170 538 L128 530 L154 262 Q168 198 205 182 Z" fill="' . $fill . '"/>'
            . '<path d="M262 160 L238 236 L300 212 L362 236 L338 160 M300 212 L300 578 M226 330 L280 330 L280 380 L226 380 Z M320 330 L374 330 L374 380 L320 380 Z" ' . $line . '/>',
        'pants' => '<path d="M212 168 L388 168 L410 590 L326 590 L300 304 L274 590 L190 590 Z" fill="' . $fill . '"/>'
            . '<path d="M212 200 L388 200 M300 200 L300 304 M232 200 Q244 240 280 244 M368 200 Q356 240 320 244" ' . $line . '/>',
        'dress' => '<path d="M258 168 L342 168 L350 270 Q428 420 452 600 L148 600 Q172 420 250 270 Z" fill="' . $fill . '"/>'
            . '<path d="M262 168 L256 110 M338 168 L344 110 M250 270 Q300 290 350 270" ' . $line . '/>',
        'cap' => '<path d="M170 370 Q170 220 300 220 Q430 220 430 370 Z" fill="' . $fill . '"/>'
            . '<path d="M170 370 Q300 352 430 370 Q500 380 520 412 Q420 418 300 396 Q220 386 170 370 Z" fill="' . $fill . '"/>'
            . '<circle cx="300" cy="222" r="10" fill="' . $fill . '"/>'
            . '<path d="M300 222 L300 362 M236 236 Q214 300 222 364 M364 236 Q386 300 378 364" ' . $line . '/>',
        'tote' => '<path d="M240 260 Q240 140 300 140 Q360 140 360 260" stroke="' . $fill . '" stroke-width="18" fill="none"/>'
            . '<path d="M180 250 L420 250 L440 580 L160 580 Z" fill="' . $fill . '"/>'
            . '<path d="M240 250 L240 300 M360 250 L360 300" ' . $line . '/>',
        default => '',
    };
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 750" width="600" height="750">'
        . '<rect width="600" height="750" fill="' . $bg . '"/>'
        . '<circle cx="470" cy="150" r="70" fill="rgba(255,255,255,.14)"/>'
        . '<ellipse cx="300" cy="640" rx="190" ry="22" fill="rgba(0,0,0,.14)"/>'
        . $body . '</svg>';
}
