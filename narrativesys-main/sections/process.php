<!-- How it works -->
  <section class="process" id="how-it-works">
    <div class="container">
      <p class="eyebrow">The process</p>
      <h2>How It Works</h2>
      <ol class="steps">
        <?php foreach ($steps as $i => [$title, $text]): ?>
          <li>
            <span class="step-no"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
            <h3><?= e($title) ?></h3>
            <p><?= e($text) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>