<?php
// Booking page placeholder. Login is required; guests are sent to login.php and come back here afterwards.
require __DIR__ . '/sections/function.php';
require __DIR__ . '/sections/data.php';

$packageId = (int)($_GET['package_id'] ?? 0);
$user = require_login_page('booking.php' . ($packageId ? '?package_id=' . $packageId : ''));
$selected = $packageId ? get_package($packageId) : null;

require __DIR__ . '/sections/header.php';
?>
<section class="container narrow" style="padding:80px 24px;max-width:720px">
  <p class="eyebrow">Book a session</p>
  <h2>Hi, <?= e($user['first_name']) ?>.</h2>
  <?php if ($selected): ?>
    <p class="lead" style="margin-top:16px">You picked <strong><?= e($selected['name']) ?></strong> (<?= peso($selected['price']) ?>). The date &amp; time picker goes here.</p>
  <?php else: ?>
    <p class="lead" style="margin-top:16px">Choose a package, then pick a date and time. The booking form goes here.</p>
  <?php endif; ?>
  <a class="btn btn-outline" href="index.php#packages">← Back to packages</a>
</section>
<?php require __DIR__ . '/sections/footer.php'; ?>
