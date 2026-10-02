<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();

$fields = [
    'store_name' => 'Store name',
    'currency' => 'Currency symbol',
    'announcement' => 'Announcement bar',
    'campaign_kicker' => 'Small label above the title',
    'campaign_title' => 'Campaign title',
    'campaign_text' => 'Campaign text',
    'campaign_cta' => 'Button text',
    'campaign_badge' => 'Round badge',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'password') {
        $new = (string) ($_POST['new_password'] ?? '');
        if (!password_verify((string) ($_POST['current_password'] ?? ''), setting('admin_password'))) {
            $errors[] = 'Your current password is not correct.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'The new password needs at least 8 characters.';
        } else {
            set_setting('admin_password', password_hash($new, PASSWORD_DEFAULT));
            flash('Password changed.');
            redirect('settings.php');
        }
    } else {
        foreach ($fields as $key => $_) {
            set_setting($key, trim((string) ($_POST[$key] ?? '')));
        }
        set_setting('campaign_active', isset($_POST['campaign_active']) ? '1' : '0');
        flash('Saved. Your changes are live in the store.');
        redirect('settings.php');
    }
}

admin_header('Campaign & settings', 'campaign');
?>
<div class="admin-top"><h1>Campaign &amp; settings</h1></div>
<?php if ($errors): ?>
  <div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>

<form class="panel" method="post">
  <h2>Home page campaign</h2>
  <p class="muted">The big banner at the top of the home page. It shows your featured products next to it. To put items on sale, set a sale price on each product.</p>
  <?= csrf_field() ?>
  <div class="form-grid">
    <label class="check wide"><input id="campaign_active" type="checkbox" name="campaign_active" <?= setting('campaign_active') === '1' ? 'checked' : '' ?>> Show the campaign banner</label>
    <?php foreach (['campaign_kicker', 'campaign_title', 'campaign_cta', 'campaign_badge'] as $key): ?>
      <label><?= e($fields[$key]) ?><input id="<?= $key ?>" name="<?= $key ?>" value="<?= e(setting($key)) ?>"></label>
    <?php endforeach; ?>
    <label class="wide"><?= e($fields['campaign_text']) ?><textarea id="campaign_text" name="campaign_text" rows="3"><?= e(setting('campaign_text')) ?></textarea></label>
  </div>

  <h2>Store</h2>
  <div class="form-grid">
    <label><?= e($fields['store_name']) ?><input id="store_name" name="store_name" required value="<?= e(setting('store_name')) ?>"></label>
    <label><?= e($fields['currency']) ?> <span class="hint">e.g. $, €, £</span><input id="currency" name="currency" required maxlength="4" value="<?= e(setting('currency')) ?>"></label>
    <label class="wide"><?= e($fields['announcement']) ?> <span class="hint">Scrolling line at the very top. Leave empty to hide it.</span>
      <input id="announcement" name="announcement" value="<?= e(setting('announcement')) ?>">
    </label>
  </div>
  <div class="actions"><button class="btn" type="submit">Save changes</button></div>
</form>

<form class="panel" method="post">
  <h2>Admin password</h2>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="password">
  <div class="form-grid">
    <label>Current password<input id="current_password" name="current_password" type="password" autocomplete="current-password" required></label>
    <label>New password<input id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="8" required></label>
  </div>
  <div class="actions"><button class="btn ghost" type="submit">Change password</button></div>
</form>
<?php admin_footer(); ?>
