<!-- Gallery strip -->
  <section class="gallery" id="about" aria-label="Recent work">
    <div class="gallery-track">
      <?php foreach ($gallery as [$src, $alt]): ?>
        <img src="<?= e($src) ?>" alt="<?= e($alt) ?>" loading="lazy">
      <?php endforeach; ?>
    </div>
  </section>