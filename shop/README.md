# Peji shop

A small clothing store with an admin panel. Plain PHP and SQLite: no framework, no build step,
and no database to create. It runs on ordinary shared hosting.

## What's inside

- **Store**: home page with a campaign banner, category filter, product pages with sizes,
  a shopping bag, and checkout. Orders are saved without online payment; the owner follows up by email.
- **Admin** (`/admin`): add, edit, hide and delete products with photos, sale prices, sizes and stock;
  manage categories; view orders and change their status; edit the campaign banner,
  announcement bar, store name and currency; change the admin password.

## Requirements

PHP 8.1 or newer with the `pdo_sqlite` and `fileinfo` extensions (enabled on almost every host).

## Install on a host

1. Upload everything in this folder to the site root (for example `public_html/`).
2. Make sure the `data/` and `uploads/` folders are writable by PHP (permission 755 or 775).
3. Open the site. The database and demo products are created on the first visit.
4. Open `/admin` and choose the admin password.

The `.htaccess` files block direct access to `data/` and `lib/` and stop scripts from running in `uploads/`.
On an Nginx host, add the same rules to the server config.

## Run locally

```sh
php -S localhost:8000
```

Then open http://localhost:8000 and http://localhost:8000/admin/.

To start again from the demo data, delete `data/shop.sqlite` and the `uploads/` contents except `.htaccess`.
