<?php
require_once __DIR__ . '/../../includes/helpers.php';
// Access is checked at the top of each admin page with require_login_page().
$authUser = $authUser ?? current_user();
$current = basename($_SERVER['SCRIPT_NAME']);
$flash   = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'Packages') ?> · Narrative Studios Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>
<nav class="adm-top">
  <strong>Narrative Studios</strong>
  <a href="packages_manage.php" class="<?= $current === 'packages_manage.php' ? 'on' : '' ?>">All Packages</a>
  <a href="packages_add.php" class="<?= $current === 'packages_add.php' ? 'on' : '' ?>">Add Package</a>
  <a href="<?= BASE_URL ?>/index.php#packages" target="_blank" style="margin-left:auto">View landing ↗</a>
  <?php if ($authUser): ?>
    <span style="opacity:.7;font-size:14px"><?= e($authUser['first_name'] . ' · ' . $authUser['role']) ?></span>
    <form method="post" action="<?= BASE_URL ?>/logout.php" style="margin:0"><button type="submit" style="background:none;border:0;color:inherit;font:inherit;cursor:pointer;text-decoration:underline">Log out</button></form>
  <?php endif; ?>
</nav>
<main class="adm-main">
<?php if ($flash): ?><div class="alert alert-<?= e($flash[1]) ?>"><?= e($flash[0]) ?></div><?php endif; ?>
