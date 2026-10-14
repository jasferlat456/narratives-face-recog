<?php
// POST /auth/login.php
// Body (JSON or form): email, password, next (optional page to return to)
// On success the browser receives a session cookie and later requests are logged in automatically.
require_once __DIR__ . '/helpers.php';

only_method('POST');
$in = read_input();

$email = strtolower(trim(isset($in['email']) && is_string($in['email']) ? $in['email'] : ''));
$password = isset($in['password']) && is_string($in['password']) ? $in['password'] : '';

$errors = [];
if (!is_valid_email($email)) $errors['email'] = 'Enter a valid email address.';
if ($password === '') $errors['password'] = 'Enter your password.';
if ($errors) {
    json_out(400, ['error' => 'Enter your email and password.', 'errors' => $errors]);
}

$user = find_user_by_email($email);

if ($user && (int) $user['locked_until'] > time()) {
    $minutes = (int) ceil(((int) $user['locked_until'] - time()) / 60);
    json_out(429, ['error' => 'Too many failed attempts. Try again in ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's') . '.']);
}

// Check a dummy hash when the email is unknown so the response time looks the same.
$dummy = '$2y$10$38OjdWvlenHCxQzKqV1CgODKr5ZAs7hgAxHz5wNOsrOdkFUSTLUKq';
$ok = password_verify($password, $user ? $user['password_hash'] : $dummy);

if (!$user || !$ok) {
    if ($user) {
        $left = record_failed_login($user['id']);
        if ($left <= 0) {
            json_out(429, ['error' => 'Too many failed attempts. This account is locked for ' . LOCK_MINUTES . ' minutes.']);
        }
    }
    json_out(401, ['error' => 'Email or password is incorrect.']);
}

// Face verification is mandatory for every account. Never fall back to password-only login.
if (empty($user['face_descriptor'])) {
    json_out(401, [
        'error' => 'This account has no registered face. Face registration is required before sign-in.',
        'errors' => ['face_descriptor' => 'This account has no saved face template. Register a face for this account first.']
    ]);
}

$submittedFace = isset($in['face_descriptor']) && is_array($in['face_descriptor']) ? $in['face_descriptor'] : null;
$savedFace = json_decode($user['face_descriptor'], true);
if (!$submittedFace || count($submittedFace) !== 128 || !is_array($savedFace) || count($savedFace) !== 128) {
    json_out(401, ['error' => 'Face verification is required for this account.', 'errors' => ['face_descriptor' => 'Please scan your face to continue.']]);
}
$distance = 0.0;
for ($i = 0; $i < 128; $i++) {
    if (!is_numeric($submittedFace[$i]) || !is_numeric($savedFace[$i]) || !is_finite((float) $submittedFace[$i]) || !is_finite((float) $savedFace[$i])) {
        json_out(401, ['error' => 'Face verification failed. Please try again.', 'errors' => ['face_descriptor' => 'Face scan data is invalid.']]);
    }
    $difference = (float) $submittedFace[$i] - (float) $savedFace[$i];
    $distance += $difference * $difference;
}
$distance = sqrt($distance);
if ($distance > 0.60) {
    json_out(401, ['error' => 'Face did not match this account. Please try again.', 'errors' => ['face_descriptor' => 'Face did not match this account.']]);
}

// Upgrade the stored hash if PHP's default hashing has become stronger.
if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
    $st = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $st->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
}

reset_login_failures($user['id']);
login_user($user);

json_out(200, [
    'message' => 'Logged in.',
    'user' => public_user($user),
    'redirect' => post_login_destination($user, isset($in['next']) ? $in['next'] : ''),
]);
