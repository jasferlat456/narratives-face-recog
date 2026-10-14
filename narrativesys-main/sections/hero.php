<section class="hero">
    <div class="hero-text">
      <p class="eyebrow">Photography Studio</p>
      <h1>Every laugh,<br><em>a story</em><br>worth telling.</h1>
      <p class="lead">
        <?= e($studio['name_main'] . ' ' . $studio['name_accent']) ?> captures the moments that matter most.
        Book your session, track your shoot, and download your memories — all in one place.
      </p>

      <div class="actions">
        <a href="<?= e(book_url()) ?>" class="btn btn-dark">Book a Session</a>
        <?php if (($me = viewer()) && $me['role'] === 'admin'): ?>
          <a href="admin/packages_manage.php" class="btn btn-outline">Admin Portal →</a>
        <?php else: ?>
          <a href="#packages" class="btn btn-outline">View Packages</a>
        <?php endif; ?>
      </div>

      <ul class="stats">
        <?php foreach ($stats as $s): ?>
          <li>
            <strong><?= e($s['value']) ?></strong>
            <span><?= e($s['label']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="hero-visual">
      <div class="hero-photo">
        <img src="images/logo.png" alt="Photographer tossing a camera in an autumn forest">
      </div>
      <div class="availability">
        <p class="eyebrow">Next available</p>
          <p class="date"><?= e($nextDate) ?></p>
          <p class="slots"><?= (int)$slotsLeft ?> slot<?= $slotsLeft === 1 ? '' : 's' ?> remaining</p>

      </div>
    </div>
  </section>