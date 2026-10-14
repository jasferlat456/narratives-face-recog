<?php
// Form-post logout used by the site header and admin nav. (auth/logout.php is the JSON version.)
require_once __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') logout_user();
header('Location: index.php');
exit;
