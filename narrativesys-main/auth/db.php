<?php
// User queries. The PDO connection db() comes from the shared ../config/db.php.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../config/db.php';

function find_user_by_email($email)
{
    $st = db()->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([$email]);
    return $st->fetch() ?: null;
}

function find_user_by_id($id)
{
    $st = db()->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function create_user($first, $last, $email, $phone, $passwordHash, $role = 'customer', $faceDescriptor = null)
{
    $st = db()->prepare(
        'INSERT INTO users (first_name, last_name, email, phone, password_hash, role, consent_at, face_descriptor)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $st->execute([$first, $last, $email, $phone, $passwordHash, $role, date('Y-m-d H:i:s'), $faceDescriptor === null ? null : json_encode($faceDescriptor)]);
    return find_user_by_id((int) db()->lastInsertId());
}

// Count a wrong password. Returns attempts left (0 or less means the account is now locked).
function record_failed_login($userId)
{
    $user = find_user_by_id($userId);
    $attempts = (int) $user['failed_attempts'] + 1;
    if ($attempts >= MAX_FAILED_ATTEMPTS) {
        $st = db()->prepare('UPDATE users SET failed_attempts = 0, locked_until = ? WHERE id = ?');
        $st->execute([time() + LOCK_MINUTES * 60, $userId]);
    } else {
        $st = db()->prepare('UPDATE users SET failed_attempts = ? WHERE id = ?');
        $st->execute([$attempts, $userId]);
    }
    return MAX_FAILED_ATTEMPTS - $attempts;
}

function reset_login_failures($userId)
{
    $st = db()->prepare('UPDATE users SET failed_attempts = 0, locked_until = 0 WHERE id = ?');
    $st->execute([$userId]);
}
