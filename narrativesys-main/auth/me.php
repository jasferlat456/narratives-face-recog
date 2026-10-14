<?php
// GET /auth/me.php
// Returns the logged-in user, or 401 if nobody is logged in.
require_once __DIR__ . '/helpers.php';

only_method('GET');
$user = require_login();
json_out(200, ['user' => public_user($user)]);
