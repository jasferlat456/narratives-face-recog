<?php
/** Expects: $pkg (array), $errors (array), $submitLabel (string), $isEdit (bool) */
$inclusions = $pkg['inclusions'] ?: [''];
$prev = banner_url($pkg['banner_photo'] ?? null);
$err  = fn(string $k) => $errors[$k] ?? '';
$bad  = fn(string $k) => isset($errors[$k]) ? 'invalid' : '';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/packages.css">

<div class="layout">
<form method="post" enctype="multipart/form-data" class="card" id="pkgForm" data-edit="<?= $isEdit ? '1' : '0' ?>" novalidate>
  <?= csrf_field() ?>

  <!-- Banner -->
  <div class="field">
    <span class="lbl">Banner Photo <?= $isEdit ? '' : '<b class="req">*</b>' ?></span>
    <div class="banner-prev" id="bannerPrev" <?= $prev ? 'style="background-image:url(\'' . e($prev) . '\')"' : '' ?>></div>
    <label class="drop <?= $bad('banner') ?>" id="drop">
      <input type="file" name="banner" accept="image/jpeg,image/png,image/webp">
      <strong id="dropText">Drag &amp; drop an image, or click to browse</strong>
      <small>JPG, PNG or WEBP · max 4 MB · at least 600px wide<?= $isEdit ? ' · leave empty to keep current' : '' ?></small>
    </label>
    <p class="err" data-err="banner"><?= e($err('banner')) ?></p>
  </div>

  <div class="row">
    <div class="field">
      <label for="name">Package Name <b class="req">*</b></label>
      <input type="text" id="name" name="name" class="<?= $bad('name') ?>" value="<?= e($pkg['name']) ?>" placeholder="All-In" maxlength="120" autocomplete="off">
      <div class="meta"><p class="err" data-err="name"><?= e($err('name')) ?></p><span class="count" data-count="name">0/120</span></div>
    </div>
    <div class="field">
      <label for="description">Description <b class="req">*</b></label>
      <input type="text" id="description" name="description" class="<?= $bad('description') ?>" value="<?= e($pkg['description']) ?>" placeholder="Perfect for Groups" maxlength="255" autocomplete="off">
      <div class="meta"><p class="err" data-err="description"><?= e($err('description')) ?></p><span class="count" data-count="description">0/255</span></div>
    </div>
  </div>

  <div class="row3">
    <div class="field">
      <label for="price">Price (₱) <b class="req">*</b></label>
      <input type="number" id="price" name="price" class="<?= $bad('price') ?>" step="0.01" min="0" inputmode="decimal" value="<?= e($pkg['price']) ?>" placeholder="1750.00">
      <p class="err" data-err="price"><?= e($err('price')) ?></p>
    </div>
    <div class="field">
      <label for="duration">Duration</label>
      <input type="text" id="duration" name="duration" class="<?= $bad('duration') ?>" value="<?= e($pkg['duration']) ?>" placeholder="1 hour" maxlength="60" autocomplete="off">
      <p class="err" data-err="duration"><?= e($err('duration')) ?></p>
    </div>
    <div class="field">
      <label for="status">Availability</label>
      <select id="status" name="status">
        <option value="available"   <?= $pkg['status'] === 'available'   ? 'selected' : '' ?>>Available</option>
        <option value="unavailable" <?= $pkg['status'] === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
      </select>
    </div>
  </div>

  <label class="check-line"><input type="checkbox" name="is_popular" value="1" <?= $pkg['is_popular'] ? 'checked' : '' ?>> Mark as “Most Popular”</label>

  <!-- Inclusions -->
  <div class="field">
    <span class="lbl">Inclusions <b class="req">*</b> <small class="hint" id="inclCount"></small></span>
    <div id="inclList">
      <?php foreach ($inclusions as $inc): ?>
        <div class="incl-row">
          <input type="text" name="inclusions[]" value="<?= e($inc) ?>" placeholder="e.g. 2 pcs 4R Prints" maxlength="255" autocomplete="off">
          <button type="button" class="icon-btn" data-up title="Move up">↑</button>
          <button type="button" class="icon-btn" data-down title="Move down">↓</button>
          <button type="button" class="icon-btn danger" data-remove title="Remove">✕</button>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-ghost btn-sm" id="addIncl">+ Add inclusion</button>
    <span class="hint"> Press Enter to add the next one.</span>
    <p class="err" data-err="inclusions"><?= e($err('inclusions')) ?></p>
  </div>

  <div class="form-actions">
    <button class="btn" type="submit" id="submitBtn"><?= e($submitLabel) ?></button>
    <a class="btn btn-ghost" href="packages_manage.php">Cancel</a>
  </div>
</form>

<!-- Live preview -->
<aside class="preview">
  <span class="lbl">Live preview</span>
  <div class="pk-section pk-preview">
    <article class="pk-card" id="pvCard">
      <div class="pk-media" id="pvMedia" <?= $prev ? 'style="background-image:url(\'' . e($prev) . '\')"' : '' ?>>
        <span class="pk-badge" id="pvBadge" hidden>Most Popular</span>
        <span class="pv-off" id="pvOff" hidden>Unavailable</span>
      </div>
      <div class="pk-body">
        <h3 class="pk-name" id="pvName">Package name</h3>
        <p class="pk-desc" id="pvDesc">Short description</p>
        <p class="pk-price" id="pvPrice">₱0</p>
        <p class="pk-meta" id="pvMeta">Duration</p>
        <ul class="pk-list" id="pvList"></ul>
        <p class="pk-more" id="pvMore" hidden></p>
      </div>
    </article>
  </div>
  <p class="hint" id="pvNote"></p>
</aside>
</div>

<script src="<?= BASE_URL ?>/assets/js/package_form.js"></script>
