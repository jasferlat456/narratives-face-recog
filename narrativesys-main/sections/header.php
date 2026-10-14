<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($studio['name_main'] . ' ' . $studio['name_accent']) ?> — Photography Studio</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>


<header class="site-header">
  <a href="index.php" class="logo">
    <?= e($studio['name_main']) ?> <span><?= e($studio['name_accent']) ?></span>
  </a>
  <nav class="nav">
    <?php foreach ($nav as $label => $href): ?>
      <a href="<?= e($href) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <?php if ($me = viewer()): ?>
      <span class="nav-user">Hi, <?= e($me['first_name']) ?></span>
      <?php if (in_array($me['role'], ['admin', 'staff'], true)): ?><a href="admin/packages_manage.php">Admin</a><?php endif; ?>
      <form method="post" action="logout.php" class="nav-logout"><button type="submit">Log out</button></form>
    <?php endif; ?>
    <a href="<?= e(book_url()) ?>" class="btn btn-dark btn-sm">Book Now</a>
  </nav>
</header>

<main>