<!-- Packages (data loaded from the database in data.php; managed at /admin/packages_manage.php) -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/packages.css">

<section class="pk-section" id="packages">
  <div class="container">
    <p class="eyebrow">What we offer</p>
    <h2>Photography Packages</h2>

    <?php if ($loadError): ?>
      <p class="pk-empty">Packages are temporarily unavailable. Please try again shortly.</p>
    <?php elseif (!$packages): ?>
      <p class="pk-empty">No packages are available right now. Please check back soon.</p>
    <?php else: ?>
    <div class="pk-grid">
      <?php foreach ($packages as $p):
        $preview = array_slice($p['inclusions'], 0, 4);
        $more    = count($p['inclusions']) - count($preview);
        $img     = banner_url($p['banner_photo']);
      ?>
      <article class="pk-card <?= $p['is_popular'] ? 'is-popular' : '' ?>">
        <div class="pk-media" <?= $img ? 'style="background-image:url(\'' . e($img) . '\')"' : '' ?>>
          <?php if ($p['is_popular']): ?><span class="pk-badge">Most Popular</span><?php endif; ?>
        </div>

        <div class="pk-body">
          <h3 class="pk-name"><?= e($p['name']) ?></h3>
          <p class="pk-desc"><?= e($p['description']) ?></p>
          <p class="pk-price"><?= peso($p['price']) ?></p>
          <?php if ($p['duration']): ?><p class="pk-meta"><?= e($p['duration']) ?></p><?php endif; ?>

          <ul class="pk-list">
            <?php foreach ($preview as $inc): ?><li><?= e($inc) ?></li><?php endforeach; ?>
          </ul>
          <?php if ($more > 0): ?><p class="pk-more">+ <?= $more ?> more inclusion<?= $more > 1 ? 's' : '' ?></p><?php endif; ?>

          <div class="pk-actions">
            <button type="button" class="pk-btn pk-btn-ghost" data-pk-open="pk-dlg-<?= (int)$p['id'] ?>">View Details</button>
            <a class="pk-btn pk-btn-solid" href="<?= e(book_url((int)$p['id'])) ?>">Select Package</a>
          </div>
        </div>
      </article>

      <!-- Package details -->
      <dialog class="pk-dialog" id="pk-dlg-<?= (int)$p['id'] ?>" aria-label="<?= e($p['name']) ?> details">
        <div class="pk-dlg-head">
          <div>
            <h3 class="pk-name"><?= e($p['name']) ?></h3>
            <p class="pk-desc"><?= e($p['description']) ?></p>
          </div>
          <button type="button" class="pk-x" data-pk-close aria-label="Close">&times;</button>
        </div>
        <p class="pk-price"><?= peso($p['price']) ?> <?php if ($p['duration']): ?><span class="pk-meta-inline">· <?= e($p['duration']) ?></span><?php endif; ?></p>
        <ul class="pk-list pk-list-full">
          <?php foreach ($p['inclusions'] as $inc): ?><li><?= e($inc) ?></li><?php endforeach; ?>
        </ul>
        <a class="pk-btn pk-btn-solid pk-btn-block" href="<?= e(book_url((int)$p['id'])) ?>">Select Package</a>
      </dialog>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<script>
document.querySelectorAll('[data-pk-open]').forEach(b =>
  b.addEventListener('click', () => document.getElementById(b.dataset.pkOpen).showModal()));
document.querySelectorAll('.pk-dialog').forEach(d => {
  d.addEventListener('click', e => { if (e.target === d) d.close(); });          // click backdrop
  d.querySelector('[data-pk-close]').addEventListener('click', () => d.close());
});
</script>
