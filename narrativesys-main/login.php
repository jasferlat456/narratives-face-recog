<?php
// Sign in / Register page. Shown when a visitor clicks "Book Now" (or any Select Package button).
require __DIR__ . '/sections/function.php';
require __DIR__ . '/sections/data.php';

$next = safe_next($_GET['next'] ?? '');

// Already signed in
if ($me = viewer()) {
    header('Location: ' . post_login_destination($me, $next));
    exit;
}

$mode = (($_GET['mode'] ?? '') === 'register') ? 'register' : 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in — <?= e($studio['name_main'] . ' ' . $studio['name_accent']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
<div class="auth">

  <!-- Photo side -->
  <aside class="auth-photo">
    <img src="images/logo.png" alt="Photographer tossing a camera in an autumn forest">
    <a href="index.php" class="auth-logo"><?= e($studio['name_main']) ?> <span><?= e($studio['name_accent']) ?></span></a>
    <p class="auth-quote">"Every session is a chapter<br>in your story."</p>
  </aside>

  <!-- Form side -->
  <main class="auth-panel">
    <div class="auth-box" id="authBox" data-mode="<?= e($mode) ?>" data-next="<?= e($next) ?>">

      <a href="index.php" class="auth-back">← Back to Home</a>

      <h1 id="authTitle"></h1>
      <p class="auth-sub" id="authSub"></p>

      <div class="auth-tabs" role="tablist">
        <button type="button" role="tab" data-tab="login">Sign In</button>
        <button type="button" role="tab" data-tab="register">Register</button>
      </div>

      <div class="auth-alert" id="authAlert" role="alert" hidden></div>

      <!-- Sign in -->
      <form id="loginForm" class="auth-form" novalidate>
        <div class="field">
          <label for="l_email">Email address</label>
          <input type="email" id="l_email" name="email" placeholder="mamamo@email.com" autocomplete="email">
          <p class="err" data-err="email"></p>
        </div>
        <div class="field">
          <label for="l_password">Password</label>
          <input type="password" id="l_password" name="password" placeholder="••••••••" autocomplete="current-password">
          <p class="err" data-err="password"></p>
        </div>
        <button type="button" class="face-open-btn" data-face-open="login">Use face recognition</button>
        <div class="face-box" data-face-box="login" hidden>
          <button type="button" class="face-close" data-face-close="login" aria-label="Close face recognition">×</button>
          <p class="face-heading">Verify your face <span>(required for every account)</span></p>
          <video class="face-video" data-face-video="login" autoplay muted playsinline hidden></video>
          <p class="face-status" data-face-status="login">Face verification is required. Scan the face registered to the email above.</p>
          <div class="face-actions">
            <button type="button" class="face-btn" data-face-start="login">Open camera</button>
            <button type="button" class="face-btn" data-face-capture="login" disabled>Capture face</button>
          </div>
          <p class="err" data-err="face_descriptor"></p>
          <input type="hidden" name="face_descriptor" data-face-value="login">
        </div>
        <button class="auth-btn" type="submit">Sign In</button>
        <p class="auth-switch">Don't have an account? <a href="#" data-tab="register">Register</a></p>
      </form>

      <!-- Register -->
      <form id="registerForm" class="auth-form" novalidate hidden>
        <div class="row2">
          <div class="field">
            <label for="r_first">First name</label>
            <input type="text" id="r_first" name="first_name" placeholder="Maria" autocomplete="given-name">
            <p class="err" data-err="first_name"></p>
          </div>
          <div class="field">
            <label for="r_last">Last name</label>
            <input type="text" id="r_last" name="last_name" placeholder="Santos" autocomplete="family-name">
            <p class="err" data-err="last_name"></p>
          </div>
        </div>
        <div class="field">
          <label for="r_email">Email address</label>
          <input type="email" id="r_email" name="email" placeholder="maria@email.com" autocomplete="email">
          <p class="err" data-err="email"></p>
        </div>
        <div class="field">
          <label for="r_phone">Mobile number</label>
          <input type="tel" id="r_phone" name="phone" placeholder="09XXXXXXXXX" autocomplete="tel">
          <p class="err" data-err="phone"></p>
        </div>
        <div class="field">
          <label for="r_password">Password</label>
          <input type="password" id="r_password" name="password" placeholder="••••••••" autocomplete="new-password">
          <p class="hint">8+ characters with upper &amp; lower case, a number and a symbol.</p>
          <p class="err" data-err="password"></p>
        </div>
        <div class="field">
          <label for="r_confirm">Confirm password</label>
          <input type="password" id="r_confirm" name="confirm_password" placeholder="••••••••" autocomplete="new-password">
          <p class="err" data-err="confirm_password"></p>
        </div>
        <button type="button" class="face-open-btn" data-face-open="register">Register your face with camera</button>
        <div class="face-box" data-face-box="register" hidden>
          <button type="button" class="face-close" data-face-close="register" aria-label="Close face registration">×</button>
          <p class="face-heading">Register your face</p>
          <video class="face-video" data-face-video="register" autoplay muted playsinline hidden></video>
          <p class="face-status" data-face-status="register">Open the camera, look directly at it, then capture your face.</p>
          <div class="face-actions">
            <button type="button" class="face-btn" data-face-start="register">Open camera</button>
            <button type="button" class="face-btn" data-face-capture="register" disabled>Capture face</button>
          </div>
          <p class="err" data-err="face_descriptor"></p>
          <input type="hidden" name="face_descriptor" data-face-value="register">
        </div>
        <div class="field">
          <label class="check">
            <input type="checkbox" name="consent" value="1">
            <span>I agree to the collection and use of my personal data, including my face descriptor for account verification, in line with the Data Privacy Act of 2012.</span>
          </label>
          <p class="err" data-err="consent"></p>
        </div>
        <button class="auth-btn" type="submit">Create Account</button>
        <p class="auth-switch">Already have an account? <a href="#" data-tab="login">Sign In</a></p>
      </form>

    </div>
  </main>
</div>
<script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script defer src="assets/js/auth.js"></script>
</body>
</html>
