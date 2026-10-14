<?php
// Shared helpers: JSON responses, input reading, validation, session login, and the
// functions other pages use to protect themselves (require_login, require_role).

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

const AUTH_SESSION_NAME = 'NSPHSESSID';

function start_auth_session()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(AUTH_SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }
}

function json_out($status, $data)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function only_method($method)
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        header('Allow: ' . $method);
        json_out(405, ['error' => 'Method not allowed. Use ' . $method . '.']);
    }
}

// Reads a JSON body or a normal form post.
function read_input()
{
    $type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (stripos($type, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            json_out(400, ['error' => 'Invalid JSON body.']);
        }
        return $data;
    }
    return $_POST;
}

// Never send password_hash or lock fields to the client.
function public_user($u)
{
    return [
        'id' => (int) $u['id'],
        'first_name' => $u['first_name'],
        'last_name' => $u['last_name'],
        'email' => $u['email'],
        'phone' => $u['phone'],
        'role' => $u['role'],
        'created_at' => $u['created_at'],
    ];
}

// ---------- Validation ----------

function password_problem($pw)
{
    if (!is_string($pw) || strlen($pw) < 8) return 'Password must be at least 8 characters.';
    if (strlen($pw) > 72) return 'Password must be 72 characters or fewer.';
    if (!preg_match('/[A-Z]/', $pw)) return 'Password needs an uppercase letter.';
    if (!preg_match('/[a-z]/', $pw)) return 'Password needs a lowercase letter.';
    if (!preg_match('/\d/', $pw)) return 'Password needs a number.';
    if (!preg_match('/[^A-Za-z0-9]/', $pw)) return 'Password needs a symbol.';
    return null;
}

function is_valid_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 190;
}

function is_valid_ph_phone($phone)
{
    return (bool) preg_match('/^(09|\+639)\d{9}$/', $phone);
}

function clean_text($value)
{
    return trim(preg_replace('/\s+/', ' ', is_string($value) ? $value : ''));
}

// Returns [errors, values]
function validate_register($in)
{
    $errors = [];
    $first = clean_text(isset($in['first_name']) ? $in['first_name'] : '');
    $last = clean_text(isset($in['last_name']) ? $in['last_name'] : '');
    $email = strtolower(trim(isset($in['email']) && is_string($in['email']) ? $in['email'] : ''));
    $phone = preg_replace('/[\s-]/', '', isset($in['phone']) && is_string($in['phone']) ? $in['phone'] : '');
    $password = isset($in['password']) ? $in['password'] : '';
    $consent = isset($in['consent']) && filter_var($in['consent'], FILTER_VALIDATE_BOOLEAN);
    $face = isset($in['face_descriptor']) && is_array($in['face_descriptor']) ? $in['face_descriptor'] : null;
    if (!$face || count($face) !== 128) {
        $errors['face_descriptor'] = 'Please capture your face before creating your account.';
        $face = null;
    } else {
        foreach ($face as $value) {
            if (!is_numeric($value) || !is_finite((float) $value) || (float) $value < -2 || (float) $value > 2) {
                $errors['face_descriptor'] = 'Face scan is invalid. Please capture your face again.';
                $face = null;
                break;
            }
        }
    }

    if (mb_strlen($first) < 2 || mb_strlen($first) > 60) $errors['first_name'] = 'First name is required.';
    if (mb_strlen($last) < 2 || mb_strlen($last) > 60) $errors['last_name'] = 'Last name is required.';
    if (!is_valid_email($email)) $errors['email'] = 'Enter a valid email address.';
    if (!is_valid_ph_phone($phone)) $errors['phone'] = 'Use 09XXXXXXXXX or +639XXXXXXXXX.';
    $pwError = password_problem($password);
    if ($pwError) $errors['password'] = $pwError;
    if (isset($in['confirm_password']) && $in['confirm_password'] !== $password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }
    if (!$consent) $errors['consent'] = 'You must agree to the data privacy terms (Data Privacy Act of 2012).';

    return [$errors, ['first_name' => $first, 'last_name' => $last, 'email' => $email, 'phone' => $phone, 'password' => $password, 'face_descriptor' => $face]];
}

// ---------- Login state (PHP sessions) ----------

function login_user($user)
{
    start_auth_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
}

function logout_user()
{
    start_auth_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// Returns the logged-in user (database row) or null.
function current_user()
{
    // Visitors with no session cookie stay cookie-free (no session is started for them).
    if (session_status() === PHP_SESSION_NONE && empty($_COOKIE[AUTH_SESSION_NAME])) return null;
    start_auth_session();
    if (empty($_SESSION['user_id'])) return null;
    $user = find_user_by_id($_SESSION['user_id']);
    if (!$user) {
        logout_user();
        return null;
    }
    return $user;
}

// For other developers: call at the top of any protected API file.
//   $user = require_login();                      // any logged-in user
//   $user = require_role(['admin']);              // admin only
//   $user = require_role(['admin', 'staff']);     // admin or staff
function require_login()
{
    $user = current_user();
    if (!$user) json_out(401, ['error' => 'Login required.']);
    return $user;
}

function require_role($roles)
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        json_out(403, ['error' => 'You do not have permission to do this.']);
    }
    return $user;
}


// ---------- Page guards (for normal PHP pages, not JSON endpoints) ----------

// Only allow redirecting to a relative path inside this site (blocks open redirects).
function safe_next($next)
{
    if (!is_string($next) || $next === '' || strlen($next) > 200) return '';
    if (!preg_match('~^[A-Za-z0-9_\-][A-Za-z0-9_\-/\.]*(\?[A-Za-z0-9_=&\-]*)?$~', $next)) return '';
    if (strpos($next, '..') !== false) return '';
    return $next;
}

// Where each role lands after signing in.
function home_for_role($role)
{
    return in_array($role, ['admin', 'staff'], true) ? 'admin/packages_manage.php' : 'booking.php';
}

// Where to send someone right after signing in. Admins/staff go to the admin portal,
// customers go back to what they were doing (or the booking page).
function post_login_destination($user, $next)
{
    $next = safe_next($next);
    $isAdminPath = strpos($next, 'admin/') === 0;
    if (in_array($user['role'], ['admin', 'staff'], true)) {
        return $isAdminPath ? $next : home_for_role($user['role']);
    }
    return ($next !== '' && !$isAdminPath) ? $next : home_for_role($user['role']);
}

// Put at the top of a page. Sends guests to the login page and remembers where they were going.
//   $user = require_login_page('booking.php?package_id=2');
//   $user = require_login_page('admin/packages_manage.php', ['admin']);
function require_login_page($next, $roles = null)
{
    $user = current_user();
    if (!$user) {
        header('Location: ' . BASE_URL . '/login.php?next=' . rawurlencode($next));
        exit;
    }
    if ($roles !== null && !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to view this page.');
    }
    return $user;
}
