<?php
// Creates the first admin (or a staff account). Run from the command line only:
//   php create_admin.php admin@narrativestudio.ph "Admin@12345"
//   php create_admin.php staff@narrativestudio.ph "Staff@12345" staff
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this from the command line.');
}
require_once __DIR__ . '/helpers.php';

$email = strtolower(trim(isset($argv[1]) ? $argv[1] : ''));
$password = isset($argv[2]) ? $argv[2] : '';
$role = (isset($argv[3]) && $argv[3] === 'staff') ? 'staff' : 'admin';

if (!is_valid_email($email)) exit("Usage: php create_admin.php <email> <password> [staff]\nEnter a valid email.\n");
if ($problem = password_problem($password)) exit($problem . "\n");
if (find_user_by_email($email)) exit("That email already has an account.\n");

$user = create_user('Studio', ucfirst($role), $email, '09000000000', password_hash($password, PASSWORD_DEFAULT), $role);
echo "Created $role account #{$user['id']} for $email\n";
