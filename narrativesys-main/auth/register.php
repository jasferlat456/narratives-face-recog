<?php
// POST /auth/register.php
// Body (JSON or form): first_name, last_name, email, phone, password, confirm_password (optional), consent
require_once __DIR__ . '/helpers.php';

only_method('POST');
list($errors, $v) = validate_register(read_input());

if ($errors) {
    json_out(400, ['error' => 'Please fix the highlighted fields.', 'errors' => $errors]);
}
if (find_user_by_email($v['email'])) {
    json_out(409, ['error' => 'An account with this email already exists.', 'errors' => ['email' => 'An account with this email already exists.']]);
}

$hash = password_hash($v['password'], PASSWORD_DEFAULT); // bcrypt, with a random salt built in

try {
    // Role is always 'customer' here. Staff and admin accounts are created with create_admin.php.
    $user = create_user($v['first_name'], $v['last_name'], $v['email'], $v['phone'], $hash, 'customer', $v['face_descriptor']);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') { // email was taken a moment ago
        json_out(409, ['error' => 'An account with this email already exists.', 'errors' => ['email' => 'An account with this email already exists.']]);
    }
    error_log($e->getMessage());
    json_out(500, ['error' => 'Something went wrong. Please try again.']);
}

json_out(201, ['message' => 'Account created.', 'user' => public_user($user)]);
