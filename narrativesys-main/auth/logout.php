<?php
// POST /auth/logout.php
require_once __DIR__ . '/helpers.php';

only_method('POST');
logout_user();
json_out(200, ['message' => 'Logged out.']);
